<?php


class Org_Worker {
	static function IsGlobalCompanyWorker($user_id, $company)
	{
		return sql_query_single_scalar("select count(*) from im_working where user_id = " . $user_id . " and project_id = 0 and company_id = $company");
	}

	static function GetTeams($user_id)
	{
		return comma_array_explode(sql_query_single_scalar(
			"select meta_value from wp_usermeta where meta_key = 'teams' and user_id = $user_id"));
	}

	static function GetCompanies($user_id, $is_manager = false){
		$sql = " select id from im_company where admin = " . $user_id;
		if (!$is_manager) $sql .= " union select company_id from im_working where user_id = " . $user_id;
		$result = sql_query_array_scalar($sql);

		return $result;

	}

	static function AllTeams($user_id)
	{
		$m = get_usermeta($user_id, 'teams');
		// var_dump($m);
		return comma_array_explode($m);
	}

}