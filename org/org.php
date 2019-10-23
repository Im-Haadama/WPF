<?php

/**
 * @param $user_id
 *
 * @return array
 * @throws Exception
 */
function worker_get_companies($user_id)
{
	$sql = "select company_id from im_working where user_id = " . $user_id .
	       " union select id from im_company where admin = " . $user_id;
//	print $sql;
	$result = sql_query_array_scalar($sql);

	return $result;
}

/**
 * @param $user_id
 *
 * @return string
 */
function worker_get_projects($user_id)
{
	return sql_query_single_scalar("select meta_value from wp_usermeta where meta_key = 'team' and user_id = $user_id");
// 	return sql_query_array_scalar("select project_id from im_working where user_id = " . $user_id);
}

/**
 * @param $uid
 *
 * @return string
 */
function is_volunteer( $uid ) {
	return sql_query_single_scalar( "SELECT volunteer FROM im_working WHERE user_id = " . $uid );
}


/** if the worker is global company worker, return array of companies
 * @param $user_id
 *
 * @return string
 */
function worker_is_global_company($user_id)
{
	return sql_query_single_scalar("select company_id from im_working where user_id = " . $user_id . " and project_id = 0");
}

/**
 * @param $team_id
 *
 * @return array|string
 */
function team_members($team_id)
{
	$sql = "select user_id from im_working where worker_teams(user_id) like '%:" . $team_id . ":%'";
	return sql_query_array_scalar($sql);
}

/**
 * @param $user_id
 *
 * @return array|string
 */
function team_all_members($user_id)
{
	// return sql_query_array_scalar("select id from im_working_teams where manager = " . $user_id);
	return sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'teams' and meta_value like '%:" . $user_id . ":%'");
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
	team_add_worker($team_id, $user_id);
	return $team_id;
}

function team_add_worker($team_id, $user_id)
{
	$current = get_usermeta($user_id, 'teams');
	if (strstr($current ,":" . $team_id . ":")) return; // Already in.
	if (!$current or strlen($current) < 1) $current = ":";

	update_usermeta($user_id, 'teams', $current . ":" . $team_id . ":");
}

function team_manager($team_id)
{
	return sql_query_single_scalar("select manager from im_working_teams where id = $team_id");
}

function team_all_teams($user_id)
{
	$t = [];
	$teams_string = get_usermeta($user_id, 'teams');
	if (! $teams_string) return null;
	$teams_string = str_replace("::", ":", $teams_string);
	$teams = array();
	while(strlen($teams_string) > 1) {
		$p = strpos($teams_string, ":", 1);
		$team = substr($teams_string, 1, $p - 1);
		// print "Team: $team<br/>";

		$t[] = $team;
//		print "p=$p<br/>";
		if ($team > 0) array_push($teams, $team);
		$teams_string = substr($teams_string, $p);
	}

	return $teams;
}