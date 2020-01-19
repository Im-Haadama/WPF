<?php


class Org_Project {
	private $id;
	private $manager;
	private $name;

	/**
	 * Org_Project constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
		$row = sql_query_single("select project_name, manager from im_projects where id=$id");
		$this->manager = $row[0];
		$this->name = $row[1];
	}

	/**
	 * @return mixed
	 */

	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	static function GetProjects($worker_id)
	{
		$worker = new Org_Worker($worker_id);
		$result = [];
		foreach ($worker->GetCompanies($worker_id) as $company){
			if ($worker->IsGlobalCompanyWorker($company)){
				foreach (sql_query_array_scalar("select id from im_projects where is_active = 1 and company = $company") as $project_id)
					$result [$project_id] = self::GetName($project_id);
			} else {
				foreach (sql_query_array_scalar("select project_id from im_working where is_active = 1 and user_id = $worker_id") as $project_id)
					if (self::IsActive($project_id))
						$result [$project_id] = self::GetName($project_id);
			}
		}
		return $result;
	}

/* @param $user_id
*
* @param bool $is_manager - just companies user is admin
*
* @return array
* @throws Exception
*/

	static function IsActive($project_id)
	{
		return sql_query_single_scalar("select is_active from im_projects where id = $project_id");
	}

	public function all_members()
	{
		$members = sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'projects' and meta_value like '%:" . $this->id . ":%'");
		$manager = $this->manager;
		if (!in_array($manager, $members))
			array_push($members, $manager);

		return $members;
	}
}