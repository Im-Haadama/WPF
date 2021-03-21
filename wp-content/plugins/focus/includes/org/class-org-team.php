<?php


class Org_Team {
	private $id;
	private $company;
	private $name;

	/**
	 * Org_Team constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
		$this->name = SqlQuerySingleScalar( "select team_name from im_working_teams where id = " . $this->id);
		$this->company = 1; // SqlQuerySingleScalar( "select company_id from im_working_teams where id = " . $this->id);
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	function getName()
	{
		return $this->name;
	}

	/**
	 * @return int|null
	 */
	public function getCompany(): ?int {
		return $this->company;
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
	static function managed_teams($worker_id)
	{
		return SqlQueryArrayScalar( "select id from im_working_teams where manager = " . $worker_id);
	}

	function AllMembers()
	{
        $db_prefix = GetTablePrefix();
		$type1 = FlavorDbObjects::users;
		$type2 = FlavorDbObjects::team;

		$team_id = $this->id;
		return SqlQueryArrayScalar("Select id1 from ${db_prefix}links where id2=$team_id and type1=$type1 and type2=$type2");
	}

	function RemoveMember($members)
	{
		$db_prefix = GetTablePrefix();
		$type1 = FlavorDbObjects::users;
		$type2 = FlavorDbObjects::team;

		$team_id = $this->id;
		return SqlQuery("delete from ${db_prefix}links where id1 in (" . CommaImplode($members) . ") and id2=$team_id and type1=$type1 and type2 = $type2");
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
	function AddWorker($worker_id)
	{
		$db_prefix = GetTablePrefix();
		$team_id = $this->id;

		$type1 = FlavorDbObjects::users;
		$type2 = FlavorDbObjects::team;
		$sql = "insert into ${db_prefix}links (type1, type2, id1, id2) values($type1, $type2, $worker_id, $team_id)";
		return SqlQuery($sql);
	}

	function GetWorkers()
	{
		return null;
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

	function AddSender($worker_id)
	{
		$db_prefix = GetTablePrefix();

		$team_id = $this->getId();
		$type1 = FlavorDbObjects::team;
		$type2 = FlavorDbObjects::sender;
		return SqlQuery("insert into ${db_prefix}links (type1, type2, id1, id2) values($type1, $type2, $worker_id, $team_id)");
	}

	function RemoveSender($worker_id)
	{
		$db_prefix = GetTablePrefix();

		$team_id = $this->getId();
		$type1 = FlavorDbObjects::team;
		$type2 = FlavorDbObjects::sender;
		$sql = "delete from ${db_prefix}links where type1=$type1 and type2=$type2 and id1=$team_id and id2=$worker_id";
//		print $sql;
		return SqlQuery($sql);
	}


	function Senders($include_name = false)
	{
		$db_prefix = GetTablePrefix();

		$senders = array();

		// Each team member can send:
		$team_members = self::getWorkers();
		if ($team_members) foreach ($team_members as $worker_id)
		{
			$senders[] = $worker_id;
		}

		// Including the manager :)
		if (! in_array($manager = $this->getManager(), $senders)) {
			$senders[] = $manager;
		}

		// Add senders.
		$type1 = FlavorDbObjects::team;
		$type2 = FlavorDbObjects::sender;
		$id = $this->id;
		$sql = "select id2 from ${db_prefix}links where type1=$type1 and type2=$type2 and id1=$id";
		$ids = SqlQueryArrayScalar($sql);
		foreach ($ids as $id)
			$senders[] = $id;

		// Add the name if required.
		if ($include_name) foreach ($senders as $key => $id)
		{
			$user = new Core_Users($id);
			$senders[$key] = array($id, $user->getName());
		}
		return $senders;
	}
}