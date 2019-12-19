<?php

/**
function company_get_name($id)
{
	if (! ($id > 0)) die("invalid company_id: $id");
	return sql_query_single_scalar("select name from im_company where id = " . $id);
}

function worker_add_company($user_id, $company_id, $project_id)
{
	$current = Org_Project::worker_get_companies($user_id);
	if (in_array($user_id, $current)) return true; // already in.
	return sql_query("insert into im_working (company_id, is_active, user_id, project_id, rate) values ($company_id, 1, $user_id, $project_id, 0)");
}

/**
 * @param $user_id
 *
 * @return array
 */

/**
 * @param $uid
 *
 * @return string
 */
function is_volunteer($uid) {
	return sql_query_single_scalar( "SELECT volunteer FROM im_working WHERE user_id = " . $uid );
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

/**
 * @param $user_id
 * @param $team_name
 *
 * @param bool $manager_member
 *
 * @return int|string
 */
function team_add($user_id, $team_name, $manager_member = true)
{
	sql_query("insert into im_working_teams (team_name, manager) values (" . quote_text($team_name) . ", $user_id)" );
	$team_id =sql_insert_id();
	// Team manager doesn't have to be part of it.
	if ($manager_member) team_add_worker($team_id, $user_id);
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
 * @param $user_id
 *
 * @return array|null
 */

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

