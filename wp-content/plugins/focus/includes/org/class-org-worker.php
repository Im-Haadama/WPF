<?php


class Org_Worker extends Core_users
{
	private $id;

	/**
	 * Org_Worker constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	public function getName()
	{

	}

	function IsGlobalCompanyWorker($company)
	{
		return sql_query_single_scalar("select count(*) from im_working where user_id = " . $this->id . " and project_id = 0 and company_id = $company");
	}

	function GetCompanies($is_manager = false){
		$sql = " select id from im_company where admin = " . $this->id;
		if (!$is_manager) $sql .= " union select company_id from im_working where user_id = " . $this->id;
		$result = sql_query_array_scalar($sql);

//		print "companies: " . CommaImplode($result) . "<br/>";

		return $result;
	}

	function getPersonalTeam()
	{
//		$teams =
//		foreach ($teams as $team)
//			if (strstr(, "Personal"))
	}

	function AllTeams()
	{
		return CommaArrayExplode(get_usermeta($this->id, 'teams'));
	}

	function AllProjects()
	{
		$projects = CommaArrayExplode(get_usermeta($this->id, 'projects'));
		if (! $projects) $projects = array();
		$managed = sql_query_array_scalar("select id from im_projects where manager = " . $this->id);

		return array_merge($projects, $managed);
	}


	function worker_add_company($user_id, $company_id, $project_id)
	{
		$current = $this->GetCompanies();
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

	/**
	 * @param $team_id
	 * @param $members - id or array of ids.
	 *
	 * @return bool
	 */

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

//	function GetProjects()
//	{
//		return get_usermeta($this->id, 'teams'
//		$worker_id = $this->id;
//		$worker = new Org_Worker($worker_id);
//		$result = [];
//		$companies = $worker->GetCompanies($worker_id);
//		if (!$companies) {
//			print "Doesn't belong to any company";
//			print "user= $worker_id<Br/>";
//			return null;
//		}
////		print "companies: "; var_dump($co); print "<br/>";
//		foreach ($worker->GetCompanies($worker_id) as $company){
////			print "com=$company<br/>";
//			if ($worker->IsGlobalCompanyWorker($company)){
//				foreach (sql_query_array_scalar("select id from im_projects where is_active = 1 and company = $company") as $project_id)
//					$result [$project_id] = self::GetName($project_id);
//			}
//			$direct_projects = $worker->GetProjects();
////			var_dump($direct_projects);
//			foreach ($direct_projects as $p_id){
//				print "p_id=$p_id<br/>";
//				$pr = new Org_Project($p_id);
//				$result[$p_id] = $pr->getName();
//			}
//			else {
//				foreach (sql_query_array_scalar("select project_id from im_working where is_active = 1 and user_id = $worker_id") as $project_id)
//					if (self::IsActive($project_id))
//						$result [$project_id] = self::GetName($project_id);
//			}
//		}
//		return $result;
//	}

}