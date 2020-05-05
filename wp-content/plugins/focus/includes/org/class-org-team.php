<?php


class Org_Team {
	private $id;

	/**
	 * Org_Team constructor.
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

	function getName()
	{
		return SqlQuerySingleScalar( "select team_name from im_working_teams where id = " . $this->id);
	}

	static function getByName($name)
	{
		$id = SqlQuerySingleScalar( "select id from im_working_teams where team_name = " . QuoteText($name));
		if ($id)
			return new self($id);
		return null;
	}

	/**
	 * @param $worker_id
	 *
	 * @return array
	 * @throws Exception
	 */
	static function team_managed_teams($worker_id)
	{
		$result = SqlQueryArrayScalar( "select id from im_working_teams where manager = " . $worker_id);
		return $result;
	}

	function AllMembers()
	{
		// return sql_query_array_scalar("select id from im_working_teams where manager = " . $user_id);
		return SqlQueryArrayScalar( "select user_id from wp_usermeta where meta_key = 'teams' and meta_value like '%:" . $this->id . ":%'");
	}

	function RemoveMember($members)
	{
		$team_id = $this->id;
		if (is_array($members)){
			foreach ($members as $member)
				if (! self::RemoveMember($member)) return false;
			return true;
		}
		$member = $members;
		$current = get_usermeta($member, 'teams');
		$teams = CommaArrayExplode($current);

		$idx = array_search($team_id, $teams);
		if ($idx === false) {
			print "not member" . Core_Html::Br();
			return false;
		}

		unset ($teams[$idx]);

//	debug_var($teams);
		$new =  ':' . implode(':', $teams) . ':';
//	debug_var($new);
//	die(1);
		return update_usermeta($member, 'teams', $new);
	}

	function Delete($team_id)
	{
		// Check for permission;
		if (self::GetManager() != get_user_id() and ! im_user_can("edit_teams")) {
			print "no permission."; //  Manager is " . get_user_name($manager);
			// Todo: audit
			return false;
		}
		$members = self::AllMembers();
		foreach ($members as $member) team_remove_member($team_id, $member);
		SqlQuery( "delete from im_working_teams where id = " . $team_id);
		return true;
	}

	/**
	 * @param $user_id
	 * @param $team_name
	 *
	 * @param bool $manager_member
	 *
	 * @return int|string
	 */
	static function Create($user_id, $team_name, $manager_member = true)
	{
		SqlQuery( "insert into im_working_teams (team_name, manager) values (" . QuoteText($team_name) . ", $user_id)" );
		$team_id = SqlInsertId();
		$team = new Org_Team($team_id);
		// Team manager doesn't have to be part of it.
		if ($manager_member) $team->AddWorker($user_id);
		return $team_id;
	}

	/**
	 * @param $team_id
	 * @param $user_id
	 */
	function AddWorker($user_id)
	{
		$current = get_usermeta($user_id, 'teams');
		if (strstr($current ,":" . $this->id . ":")) return; // Already in.
		if (!$current or strlen($current) < 1) $current = ":";

		return update_usermeta($user_id, 'teams', $current . ":" . $this->id . ":");
	}

	/**
	 * @param $team_id
	 *
	 * @return string
	 */
	function GetManager()
	{
		return SqlQuerySingleScalar( "select manager from im_working_teams where id = " . $this->id);
	}

}