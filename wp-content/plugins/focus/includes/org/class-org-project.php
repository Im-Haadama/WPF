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
		if (! ($id > 0)) {
			print debug_trace(3);
			die ("bad project id");
		}
		$this->id = $id;
		$row = SqlQuerySingle("select project_name, manager from im_projects where id=$id");
		$this->name = $row[0];
		$this->manager = $row[1];
//		print "manager=" . $this->manager . "<br/>name=".$this->name ."<br/>";
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

	function IsActive()
	{
		$sql = "select is_active from im_projects where id = " . $this->id;
		return SqlQuerySingleScalar($sql);
	}

	public function AllWorkers($include_name = false)
	{
		$type1 = FlavorDbObjects::project;
		$type2 = FlavorDbObjects::users;
		$db_prefix = GetTablePrefix();
		$project_id = $this->id;

		$members = SqlQueryArrayScalar( "select id2 from ${db_prefix}links where id1=$project_id and type1=$type1 and type2=$type2");
//		$manager = $this->manager;
//		if (!in_array($manager, $members))
//			array_push($members, $manager);

		if ($include_name){
			$table = array(array("", "id"=>"ID", "name"=>"name"));
			foreach ($members as $member_id){
				$u = new Core_Users($member_id);
				$table[$member_id] = array("id"=>$member_id, "name" => $u->getName());
			}
			return $table;
		}
		return $members;
	}

	function AddWorker($worker_id)
	{
		$db_prefix = GetTablePrefix();
		$project_id = $this->id;
		$type1 = FlavorDbObjects::project;
		$type2 = FlavorDbObjects::users;
		$sql = "insert into ${db_prefix}links (type1, type2, id1, id2) values($type1, $type2, $project_id, $worker_id)";
		return SqlQuery($sql);
	}

	function RemoveWorker($worker_id)
	{
		$db_prefix = GetTablePrefix();
		$type1 = FlavorDbObjects::project;
		$type2 = FlavorDbObjects::users;
		$project_id = $this->id;
		$sql = "delete from ${db_prefix}links  where type1=$type1 and type2=$type2 and id1=$project_id and id2=$worker_id";
		SqlQuery($sql);

	}

}