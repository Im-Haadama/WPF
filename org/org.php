<?php

function worker_get_projects($worker_id)
{
	$result = [];
	foreach (worker_get_companies($worker_id) as $company){
		if (worker_is_global_company($worker_id, $company)){
			foreach (sql_query_array_scalar("select id from im_projects where is_active = 1 and company = $company") as $project_id)
				$result [] = array("project_id" => $project_id, "project_name" => get_project_name($project_id));
		} else {
			foreach (sql_query_array_scalar("select project_id from im_working where user_id = $worker_id") as $project_id)
				if (project_is_active($project_id))
					$result [] = array("project_id" => $project_id, "project_name" => get_project_name($project_id));
		}
	}
	return $result; // sql_query_array_scalar("select project_id from im_working where user_id = " . $worker_id);
}

/**
 * @param $user_id
 *
 * @param bool $is_manager - just companies user is admin
 *
 * @return array
 * @throws Exception
 */
function worker_get_companies($user_id, $is_manager = false)
{
	$sql = " select id from im_company where admin = " . $user_id;
	if (!$is_manager) $sql .= " union select company_id from im_working where user_id = " . $user_id;
	$result = sql_query_array_scalar($sql);

	return $result;
}

function company_get_name($id)
{
	if (! ($id > 0)) die("invalid company_id: $id");
	return sql_query_single_scalar("select name from im_company where id = " . $id);
}

function worker_add_company($user_id, $company_id, $project_id)
{
	$current = worker_get_companies($user_id);
	if (in_array($user_id, $current)) return true; // already in.
	return sql_query("insert into im_working (company_id, is_active, user_id, project_id, rate) values ($company_id, 1, $user_id, $project_id, 0)");
}

/**
 * @param $user_id
 *
 * @return array
 */
function worker_get_teams($user_id)
{
	return comma_array_explode(sql_query_single_scalar("select meta_value from wp_usermeta where meta_key = 'team' and user_id = $user_id"));
}

/**
 * @param $uid
 *
 * @return string
 */
function is_volunteer($uid) {
	return sql_query_single_scalar( "SELECT volunteer FROM im_working WHERE user_id = " . $uid );
}


/** if the worker is global company worker, return array of companies
 *
 * @param $user_id
 *
 * @param $company
 *
 * @return string
 */
function worker_is_global_company($user_id, $company)
{
	return sql_query_single_scalar("select count(*) from im_working where user_id = " . $user_id . " and project_id = 0 and company_id = $company");
}

///////////////////////
/// Team functions. ///
///////////////////////


/**
 * @param $team_id
 *
 * @return array|string
 * @throws Exception
 */
function team_all_members($team_id)
{
	// return sql_query_array_scalar("select id from im_working_teams where manager = " . $user_id);
	return sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'teams' and meta_value like '%:" . $team_id . ":%'");
//	if (! $teams) return null;
//	$temp_result = array();
//	// Change to associative to have each member just once.
//	foreach ($teams as $team) {
//		$members = team_members($team);
//		foreach ($members as $member)
//			$temp_result[$member] = 1;
//	}
//	// Switch to simple array
//	$result = array();
//	foreach ($temp_result as $member => $x)
//		array_push($result, $member);
//	return $result;
}

/**
 * @param $user_id
 * @param $team_name
 *
 * @return int|string
 */
function team_add($user_id, $team_name)
{
	sql_query("insert into im_working_teams (team_name, manager) values (" . quote_text($team_name) . ", $user_id)" );
	$team_id =sql_insert_id();
	// Team manager doesn't have to be part of it.
	// team_add_worker($team_id, $user_id);
	return $team_id;
}

/**
 * @param $team_id
 * @param $user_id
 */
function team_add_worker($team_id, $user_id)
{
	$current = get_usermeta($user_id, 'teams');
	if (strstr($current ,":" . $team_id . ":")) return; // Already in.
	if (!$current or strlen($current) < 1) $current = ":";

	return update_usermeta($user_id, 'teams', $current . ":" . $team_id . ":");
}

/**
 * @param $team_id
 *
 * @return string
 */
function team_manager($team_id)
{
	return sql_query_single_scalar("select manager from im_working_teams where id = $team_id");
}

/**
 * @param $worker_id
 *
 * @return array
 * @throws Exception
 */
function team_managed_teams($worker_id)
{
	$result = sql_query_array_scalar("select id from im_working_teams where manager = " . $worker_id);
	return $result;
	// if (! $result) team_add($worker_id, "");
}

/**
 * @param $user_id
 *
 * @return array|null
 */
function team_all_teams($user_id)
{
	$m = get_usermeta($user_id, 'teams');
	// var_dump($m);
	return comma_array_explode($m);
}

/**
 * @param $team_id
 *
 * @return bool
 * @throws Exception
 */
function team_delete($team_id)
{
	// Check for permission;
	$manager = team_manager($team_id);
	if ($manager != get_user_id() and ! im_user_can("edit_teams")) {
		print "no permission."; //  Manager is " . get_user_name($manager);
		// Todo: audit
		return false;
	}
	$members = team_all_members($team_id);
//	print "members: "; var_dump($members); print "<br/>";
	foreach ($members as $member) team_remove_member($team_id, $member);
	sql_query("delete from im_working_teams where id = " . $team_id);
	return true;

}

/**
 * @param $team_id
 * @param $members - id or array of ids.
 *
 * @return bool
 */
function team_remove_member($team_id, $members)
{
	if (is_array($members)){
		foreach ($members as $member)
			if (! team_remove_member($team_id, $member)) return false;
		return true;
	}
	$member = $members;
	$current = get_usermeta($member, 'teams');
	$teams = comma_array_explode($current);

	$idx = array_search($team_id, $teams);
	if ($idx === false) {
		print "not member" . gui_br();
		return false;
	}

	unset ($teams[$idx]);

//	debug_var($teams);
	$new =  ':' . implode(':', $teams) . ':';
//	debug_var($new);
//	die(1);
	 return update_usermeta($member, 'teams', $new);
}

function company_invite_member($company_id, $email,  $name, $project_id)
{
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		print "Invalid email format";
		return false;
	}
	$user = get_user_by( "email", $email );
	$user_id = $user->ID;

	if (! $user_id) $user_id = add_im_user("", $name, $email);

	if ($user_id > 0) {
		worker_add_company($user_id, $company_id, $project_id);
		return true;
	}
	return false;

}

function project_company($project_id)
{
	return sql_query_single_scalar("select company from im_projects where id = " . $project_id);
}

function project_is_active($project_id)
{
	return sql_query_single_scalar("select is_active from im_projects where id = $project_id");
}