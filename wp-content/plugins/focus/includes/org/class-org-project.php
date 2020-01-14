<?php


class Org_Project {
	static function GetProjects($worker_id)
	{
		$result = [];
		foreach (Org_Worker::GetCompanies($worker_id) as $company){
//			print "company: " . $company;
			if (Org_Worker::IsGlobalCompanyWorker($worker_id, $company)){
				foreach (sql_query_array_scalar("select id from im_projects where is_active = 1 and company = $company") as $project_id)
					$result [$project_id] = self::GetName($project_id);
			} else {
//			debug_var(sql_query_single_scalar("select project_id from im_working where is_active = 1 and user_id = $worker_id"));
				foreach (sql_query_array_scalar("select project_id from im_working where is_active = 1 and user_id = $worker_id") as $project_id)
					if (self::IsActive($project_id))
						$result [$project_id] = self::GetName($project_id); // array("project_id" => $project_id, "project_name" => get_project_name($project_id));
			}
		}
		return $result; // sql_query_array_scalar("select project_id from im_working where user_id = " . $worker_id);
	}

/* @param $user_id
*
* @param bool $is_manager - just companies user is admin
*
* @return array
* @throws Exception
*/

	static function GetName($project_id)
	{
		if ($project_id)
			return sql_query_single_scalar("SELECT project_name FROM im_projects WHERE id = " . $project_id);
		return "No project selected";
	}

	static function IsActive($project_id)
	{
		return sql_query_single_scalar("select is_active from im_projects where id = $project_id");
	}
}