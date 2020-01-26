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
		$row = sql_query_single("select project_name, manager from im_projects where id=$id");
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

	public function AllWorkers()
	{
		$members = sql_query_array_scalar("select user_id from wp_usermeta where meta_key = 'projects' and meta_value like '%:" . $this->id . ":%'");
		$manager = $this->manager;
		if (!in_array($manager, $members))
			array_push($members, $manager);

		return $members;
	}

	function AddWorker($user_id)
	{
		$current = get_usermeta($user_id, 'projects');
		if (strstr($current ,":" . $this->id . ":")) return true; // Already in.
		if (!$current or strlen($current) < 1) $current = ":";

		return update_usermeta($user_id, 'projects', $current . ":" . $this->id . ":");
	}

}