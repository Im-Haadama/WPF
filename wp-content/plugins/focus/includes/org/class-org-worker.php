<?php


class Org_Worker extends Core_users
{
	private $teams;
	private $workers;

	/**
	 * Org_Worker constructor.
	 *
	 * @param $id
	 */

	public function __construct( $id ) {
		Core_Users::__construct($id);
		$teams = null;
		$workers = null;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	function IsGlobalCompanyWorker($company)
	{
		return sql_query_single_scalar("select count(*) from im_working where user_id = " . $this->id . " and project_id = 0 and company_id = $company");
	}

	function GetCompanies($is_manager = false){
		$sql = " select id from im_company where admin = " . $this->id;
		if (!$is_manager) $sql .= " union select company_id from im_working where user_id = " . $this->id;
		return sql_query_array_scalar($sql);
	}

	function getPersonalTeam()
	{
//		$teams =
//		foreach ($teams as $team)
//			if (strstr(, "Personal"))
	}

	function AllTeams()
	{
		if ($this->teams) return $this->teams;

		$this->teams = CommaArrayExplode(get_usermeta($this->id, 'teams'));
		return $this->teams;
	}

	function AllWorkers()
	{
		if ($this->workers) return $this->workers;

		$companies = self::GetCompanies(true);
		if ($companies) {
			$this->workers = array();
			foreach ($companies as $company_id){
				$company = new Org_Company($company_id);
				$this->workers = array_merge($this->workers, $company->getWorkers());
			}

			return $this->workers;
		}

		return null;
//		die("need to complete for teams");
//		$teams = self::AllTeams();
//		if (! $teams) return null;
//
//		$this->workers = array();
//
//		foreach ($teams as $team) {
//			$team = new Org_Team($team);
//			foreach($team->AllMembers() as $member) {
//				if ( ! in_array( $member, $this->workers ) ) array_push( $this->workers, $member );
//			}
//		}
//		return $this->workers;
	}

	function AllProjects($just_active = true)
	{
		$projects = CommaArrayExplode(get_usermeta($this->id, 'projects'));
		if (! $projects) $projects = array();
		$managed = sql_query_array_scalar("select id from im_projects where manager = " . $this->id );

		$result = array();
		if ($just_active)
			foreach(array_merge($projects, $managed) as $project_id)
			{
				$p = new Org_Project($project_id);
				if ($p->IsActive()) array_push($result, $project_id);
			}

		return $result;
	}

	function AllCompanies()
	{
		// return CommaArrayExplode(get_usermeta($this->id, 'teams'));
		unserialize(get_usermeta($this->id, 'companies'));
	}

	function AddCompany($company_id)
	{
		$current = unserialize(get_usermeta($this->id, 'companies'));
		if (! $current) $current = array();
		if (! in_array($company_id, $current)) {
			array_push($current, $company_id);
			update_usermeta($this->id, 'companies', serialize($current));
		}
	}

	function AddWorkingProject($user_id, $company_id, $project_id)
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