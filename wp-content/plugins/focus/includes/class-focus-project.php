<?php


class Focus_Project {
	private $id;

	/**
	 * Focus_Project constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	static public function create_from_task($task_id)
	{
		$id = SqlQuerySingleScalar("select project_id from im_tasklist where id = $task_id");
//		print "tid=$task_id id=$id<br/>";
		if ($id) return new Focus_Project($id);
		return null;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

}