<?php


class Org_Worker {
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

	function IsGlobalCompanyWorker($company)
	{
		return sql_query_single_scalar("select count(*) from im_working where user_id = " . $this->id . " and project_id = 0 and company_id = $company");
	}

	function GetCompanies($is_manager = false){
		$sql = " select id from im_company where admin = " . $this->id;
		if (!$is_manager) $sql .= " union select company_id from im_working where user_id = " . $this->id;
		$result = sql_query_array_scalar($sql);

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
		sql_query( "insert into im_working_teams (team_name, manager) values (" . QuoteText($team_name) . ", $user_id)" );
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


}