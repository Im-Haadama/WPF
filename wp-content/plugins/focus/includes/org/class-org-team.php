<?php


class Org_Team {
	static function team_get_name($team_id)
	{
		return sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id);
	}

	/**
	 * @param $worker_id
	 *
	 * @return array
	 * @throws Exception
	 */
	static function team_managed_teams($worker_id)
	{
		$result = sql_query_array_scalar("select id from im_working_teams where manager = " . $worker_id);
		return $result;
		// if (! $result) team_add($worker_id, "");
	}

	static function team_all_members($team_id)
	{
		// return sql_query_array_scalar("select id from im_working_teams where manager = " . $user_id);
		return sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'teams' and meta_value like '%:" . $team_id . ":%'");
	}

}