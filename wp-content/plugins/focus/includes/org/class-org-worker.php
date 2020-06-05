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
		return SqlQuerySingleScalar( "select count(*) from im_working where user_id = " . $this->id . " and project_id = 0 and company_id = $company");
	}

	function GetCompanies($is_manager = false){
		$sql = " select id from im_company where admin = " . $this->id;
		if (!$is_manager) $sql .= " union select company_id from im_working where user_id = " . $this->id;
		return SqlQueryArrayScalar($sql);
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

	function AllProjects($query = "is_active = 1", $field_list = "id")
	{
		$table_prefix = GetTablePrefix();

		// Managed projects
		$sql =  "select " . CommaImplode($field_list) . " from ${table_prefix}projects " .
		        " where manager = " . $this->id . ($query ? " and $query " : "");

		// or direct member.
		if ($direct_projects = get_usermeta($this->id, 'projects')){
			$sql .= " or id in (" . CommaImplode(CommaArrayExplode($direct_projects)) . ")";
		}

		if (is_array($field_list) and (count($field_list) > 1))
			return SqlQueryAssoc($sql);
		else
			return SqlQueryArrayScalar($sql);
//		if (! $projects) $projects = array();
//		$managed = sql_query_array_scalar("select $field_list from im_projects where manager = " . $this->id . ($query ? " and $query " : ""));
//
//		$result = array();
//		if ($just_active)
//			foreach(array_merge($projects, $managed) as $project_id)
//			{
//				$p = new Org_Project($project_id);
//				if ($p->IsActive()) array_push($result, $project_id);
//			}
//
//		return $result;
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
		$table_prefix = GetTablePrefix();

		$current = $this->GetCompanies();
		if (in_array($user_id, $current)) return true; // already in.
		return SqlQuery("insert into ${table_prefix}working (company_id, is_active, user_id, project_id, rate) values ($company_id, 1, $user_id, $project_id, 0)");
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
		return SqlQuerySingleScalar( "SELECT volunteer FROM im_working WHERE user_id = " . $uid );
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
		return SqlQuerySingleScalar( "select company from im_projects where id = " . $project_id);
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

	function myWorkQuery($status) // 1 - ready, 0 - not ready, 2 - both, 3- not finished
	{
		$teams         = self::AllTeams();
//		if ($status 3) $query .= " and (status < 2) ";
		switch ($status) {
			case 0: // Not active.
				$query = " (owner = " . $this->id . ( $teams ? " or team in (" . CommaImplode( $teams ) . ")" : "" ) . ")";
				$query .= " and !(" . Focus_Tasks::ActiveQuery() . ") and status < 2";
				break;
			case 1: // Active
				$query = " (owner = " . $this->id . ( $teams ? " or team in (" . CommaImplode( $teams ) . ")" : "" ) . ")";
				$query .= " and " . Focus_Tasks::ActiveQuery() . " and status < 2";
				break;
			case 2: // Done;
				$query = " (owner = " . $this->id . ")";
				$query .= " and status = 2";
				break;
			default:
				$query = " owner = " . $this->id;
		}
		return $query;
	}

	function tasksCount($status)
	{
		$prefix = GetTablePrefix();
		return SqlQuerySingleScalar( "select count(*) from ${prefix}tasklist where " . self::myWorkQuery($status));
	//	return Core_Html::GuiHyperlink($count, Focus_Tasks::get_link("tasks"));
	}

	function doneTask($period = "7 day")
	{
		$prefix = GetTablePrefix();
		return SqlQuerySingleScalar( "select count(*) from ${prefix}tasklist where owner = " . $this->id . " and " .
		                             " ended >= curdate() - INTERVAL $period ");
	}
}