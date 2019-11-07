<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/11/18
 * Tim7e: 20:00
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once (ROOT_DIR . '/niver/data/data.php');
require_once( ROOT_DIR . '/im-config.php' );
require_once( ROOT_DIR . "/init.php" );

class Mission {
	private $id, $start_address, $end_adress, $start_time, $end_time, $date;

	public function __construct( $id ) {
		$this->id = $id;
		$sql = "select name, hour(start_h), MINUTE(start_h), start_address, end_address, end_h, date from im_missions where id = " . $id;
		$result = sql_query_single($sql);
		if (! $result)
			throw new Exception("Can't find mission " . $id);

		$this->name = $result[0];
		$this->start_time = $result[1] . ":" . $result[2];
//		print "start time: " . $this->start_time . "<br/>";
		$this->start_address = $result[3];
		$this->end_address = $result[4];
		$this->end_time = $result[5];
		$this->date = $result[6];
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
	public function getEndAdress() {
		return $this->end_adress;
	}

	/**
	 * @return mixed
	 */
	public function getStartTime() {
		return $this->start_time;
	}

	public function getStart()
	{
		$s = $this->date . " " . $this->start_time;
		// print $s . "<br/>";
		return strtotime($s);
	}

	/**
	 * @param mixed $start_address
	 */
	public function setStartAddress( $start_address ): void {
		$this->start_address = $start_address;
		sql_query("update im_missions set start_address = '" . $start_address . "' where id = " . $this->id);
	}

	/**
	 * @param mixed $start_time
	 */
	public function setStartTime( $start_time ): void {
		$this->start_time = $start_time;
		$sql = "update im_missions set start_h = '" . $start_time . "' where id = " . $this->id;
		// print "$sql<br/>";
		sql_query($sql);
	}

	/**
	 * @return mixed
	 */
	public function getEndTime() {
		return $this->end_time;
	}

	static public function getMission( $id ) {
		if ( ! ( $id > 0 ) ) {
			die ( __METHOD__ . " id = " . $id );
		}
		$m     = new Mission($id);

		return $m;
	}

	public function getPathCode() {
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}

		return sql_query_single_scalar( "SELECT path_code FROM im_missions WHERE id = " . $this->id );
	}

	public function getStartAddress() {
		global $store_address;
		// print "<br/>";var_dump($this);print"<br/>";
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$start = sql_query_single_scalar( "SELECT start_address FROM im_missions WHERE id = " . $this->id );

		return $start ? $start : $store_address;
	}

	public function getEndAddress() {
		global $store_address;

		// print "<br/>";var_dump($this);print"<br/>";
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$end = sql_query_single_scalar( "SELECT end_address FROM im_missions WHERE id = " . $this->id );

		return $end ? $end : $store_address;
	}

	public function getTaskCount() {
		return (int) sql_query_single_scalar( "SELECT count(*) FROM im_tasklist WHERE mission_id = " . $this->id );
	}

	public function getMissionName()
	{
		return sql_query_single_scalar( "SELECT name FROM im_missions WHERE id = " . $this->id );
	}
}

function handle_mission_operation($operation) {
	$allowed_tables = array( "im_missions");

	$debug = 0;
	if ( $debug ) {
		print "operation: " . $operation . "<br/>";
	}

	// Without gui
	$handled = true;
	switch ($operation)
	{
		case "save_new":
			$id = data_save_new("im_missions");
			if ($id > 0) return "done.$id";
			break;
		default:
			$handled = false;
	}

	if ($handled) return;

	$args = [];
	$args["script_files"] = array("/niver/gui/client_tools.js", "/niver/data/data.js");
	print HeaderText($args);
	// With gui
	switch ( $operation ) {
		case "update":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			if (update_data($table_name))
				print "done";

			break;
		case "show_new":
			$args = [];
			$args["mandatory_fields"] = array("date", "start_address", "name");
			print GemAddRow("im_missions", "New mission", $args);
			return;
		case "show_action":
			print active_missions();
			break;
		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
}

$debug = get_param( "debug", false, false );

$operation   = get_param( "operation", false, null );
$entity_name = "mission";
$table_name  = "im_missions";

// print "X" . get_param("templates") != null . "X";



// Tasks I need to handle
//print gui_header( 1, "$entity_name" );
//$args["query"]       = " owner = " . get_user_id();
//$args["limit"]       = get_param( "limit", false, 10 );
//$args["active_only"] = get_param( "active_only", false, true );
//print active_missions( $args );
// if (get_user_id() != 1) return;


function active_missions()
{
	global $table_name;
	$query = " date > date_sub(curdate(), interval 10 day)";
	$actions = array(
		array( "שכפל", "/fresh/delivery/missions.php?operation=dup&id=%s" ),
		array( "מחק", "/fresh/delivery/missions.php?operation=del&id=%s" )
	);
	$order        = "order by 2 ";

	$args = array();
	$links = array(); $links["id"] = get_url(1) . "?row_id=%s";

	$args["links"] = $links;
// $args["first_id"] = true;
	$args["actions"] = $actions;
	$args["query"] = $query;

	// print GuiTableContent($table_name, "select * from $table_name $query $order", $args);
	print GemTable($table_name,$args);

}