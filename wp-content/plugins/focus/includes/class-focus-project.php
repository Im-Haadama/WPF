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
		$id = sql_query_single_scalar("select project_id from im_tasklist where id = $task_id");
		if ($id) return new Focus_Tasklist($id);
		return null;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	static function show_edit_worker() {
		// Get worker info by id or worker_project link.
		$row_id    = GetParam( "row_id", false );
		$worker_id = GetParam( "worker_id", false );
		if ( ! $row_id and ! $worker_id ) {
			die ( "supply row or worker_id" );
		}
		if ( $row_id ) {
			$worker_id = sql_query_single_scalar( "select user_id from im_working where id = $row_id" );
		}
		$result               = Core_Html::gui_header( 1, "editing worker info" );
		$args                 = [];
		$args["edit"]         = false;
		$args["selectors"]    = array( "project_id" => "gui_select_project" );
		$args["query"]        = "user_id=" . $worker_id . " and is_active = 1";
		$args["add_checkbox"] = true;
		$args["links"]        = array( "id" => AddToUrl( "operation", "show_edit_worker_project" ) );

		$result .= GemTable( "im_working", $args );

		return $result;
	}

	static function init()
	{
		AddAction("show_edit_worker", 'show_edit_worker');
	}
}