<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/01/19
 * Time: 09:19
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/niver/data/sql.php' );
require_once( ROOT_DIR . '/niver/gui/window.php' );
require_once( ROOT_DIR . '/tools/missions/Mission.php' );

class Tasklist {
	private $id;
	private $location_name;
	private $location_address;
	private $task_description;
	private $mission_id;
	private $repeat_freq;
	private $repeat_freq_numbers;
	private $priority;

	public function __construct( $_id ) {
		$this->id = $_id;
		$row      = sql_query_single( "SELECT location_name, location_address, task_description, mission_id," .
		                              " task_template, priority " .
		                              " FROM im_tasklist " .
		                              " WHERE id = " . $this->id );

		$this->location_name    = $row[0];
		$this->location_address = $row[1];
		$this->task_description = $row[2];
		$this->mission_id       = $row[3];
		$this->priority         = $row[5];

		if ( $row[4] ) {
			$row = sql_query_single( "SELECT repeat_freq, repeat_freq_numbers " .
			                         " from im_task_templates where id = " . $row[4] );

			$this->repeat_freq         = $row[0];
			$this->repeat_freq_numbers = $row[1];

		}
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
	public function getLocationName() {
		return $this->location_name;
	}

	/**
	 * @return mixed
	 */
	public function getLocationAddress() {
		return $this->location_address;
	}

	/**
	 * @return mixed
	 */
	public function getTaskDescription() {
		return $this->task_description;
	}

	/**
	 * @return mixed
	 */
	public function getMissionId() {
		return $this->mission_id;
	}

	/**
	 * @return mixed
	 */
	public function getRepeatFreq() {
		return $this->repeat_freq;
	}

	/**
	 * @return mixed
	 */
	public function getRepeatFreqNumbers() {
		return $this->repeat_freq_numbers;
	}

	/**
	 * @return mixed
	 */
	public function getPriority() {
		return $this->priority;
	}


	public function Ended() {
		$sql = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::done .
		       " WHERE id = " . $this->id;
		// print $sql;
		sql_query( $sql );
	}

	public function Postpone() {
		$sql = "UPDATE im_tasklist set date = NOW() + INTERVAL 1 DAY\n" .
		       " where id = " . $this->id;
		sql_query( $sql );
	}
}

// require_once( ROOT_DIR . "/tools/people/people.php" );
require_once( ROOT_DIR . "/niver/data/im_simple_html_dom.php" );
require_once( ROOT_DIR . "/tools/options.php" );

class eTasklist {
	const
		waiting = 0,
		started = 1,
		done = 2,
		canceled = 3;
}

function task_started($task_id) {
	if ( ! sql_query_single_scalar( "select started from im_tasklist where id = " . $task_id ) ) {
		$sql = "UPDATE im_tasklist SET started = now(), status = " . eTasklist::started .
		       " WHERE id = " . $task_id;
		sql_query( $sql );
	}
}

function task_ended($task_id) {
		$sql = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::done .
		       " WHERE id = " . $task_id;
		sql_query( $sql );
		print "done";
}

function task_cancelled($task_id) {
	$sql = "UPDATE im_tasklist SET status = " . eTasklist::canceled .", status = " . eTasklist::done .
	       " WHERE id = " . $task_id;
	sql_query( $sql );
}


function task_template($task_id)
{
	return sql_query_single_scalar("select task_template from im_tasklist where id = " . $task_id);
}

function task_query($task_id)
{
	$task_template = task_template($task_id);

	if (! $task_template) return null;

	return sql_query_single_scalar("select condition_query from im_task_templates where id = " . $task_template);
}

function get_task_link( $template_id ) {
	if ( $template_id > 0 ) {
		return sql_query_single_scalar( "SELECT task_url FROM im_task_templates WHERE id = " . $template_id );
	}

	return "";
}

function task_url($task_id)
{
	$sql = "SELECT task_url FROM im_task_templates WHERE id = "
	       . " (SELECT task_template FROM im_tasklist WHERE id = " . $task_id . ")";
	return sql_query_single_scalar( $sql );

}
function get_task_status( $status ) {
	switch ( $status ) {
		case eTasklist::waiting:
			print "ממתין";
			break;
		case eTasklist::started:
			print "התחיל";
			break;
		case eTasklist::done:
			print "הסתיים";
			break;
		case eTasklist::canceled:
			print "בוטל";
			break;
	}
}

//

function check_condition() {
	if ( ! isset( $_GET["condition"] ) ) {
		print "must send condition";
		die ( 1 );
	}
	$condition = $_GET["condition"];
	switch ( $condition ) {
		case "daily":
			if ( ! isset( $_GET["id"] ) ) {
				print "daily must send id";
				die ( 2 );
			}
			$id          = $_GET["id"];
			$last_finish = sql_query_single_scalar( "SELECT max(datediff(curdate(), ended)) FROM im_tasklist WHERE task_template = " . $id );
			// print $last_finish;
			print $last_finish >= 1;
			break;
	}
}

function tasklist_set_defaults( &$values ) {
	$values["date"] = date( 'Y-m-d' );
}

function create_tasks_per_mission() {
	$mission_ids = sql_query_array_scalar( "SELECT id FROM im_missions WHERE date=CURDATE()" );
	$owner       = 1; // Todo: get it from the template

	foreach ( $mission_ids as $mission_id ) {
		print "handling mission " . $mission_id . "<br/>";
		$m = Mission::getMission( $mission_id );

		if ( $m->getTaskCount() ) {
			continue;
		}

		$path_code = $m->getPathCode();
		print "path_code = " . $path_code . "<br/>";

		if ($path_code === '') {
			print "empty path code for mission $mission_id. skipping<br/>";
			continue;
		}

		$template_ids = sql_query_array_scalar( "SELECT id FROM im_task_templates WHERE path_code = " . quote_text( $path_code ) );

		foreach ( $template_ids as $template_id ) {
			$sql    = "SELECT task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority " . "
		   		 FROM im_task_templates " .
			          " where id = " . $template_id;
			$result = sql_query( $sql );

			$row = mysqli_fetch_assoc( $result );
			// if ($row["id"] == 10) var_dump($row);
			$project_id = $row["project_id"];

			$priority = sql_query_single_scalar( "SELECT priority FROM im_task_templates WHERE id = " . $template_id );

			if ( ! $priority and ( $project_id > 0 ) ) {
				$priority = sql_query_single_scalar( "SELECT project_priority FROM im_projects WHERE id = " . $project_id );
			}

			if ( ! $priority ) {
				$priority = 0;
			}

			if ( ! $project_id > 0 ) {
				$project_id = 0;
			}

			$sql = "INSERT INTO im_tasklist " .
			       "(task_description, task_template, status, date, project_id, priority, owner) VALUES ( " .
			       "'" . $row["task_description"] . "', " . $template_id . ", " . eTasklist::waiting . ", now(), " . $project_id . ",  " .
			       $priority . "," . $owner . ")";

			// sql_query( $sql );
			 print $sql;
		}
	}
}
// if null = create for all freqs.
function create_tasks( $freqs = null, $verbose = false, $default_owner = 1 ) {
	if ( ! table_exists( "im_task_templates" ) ) {
		return;
	}
	do {
		if ( ! $freqs ) {
			$freqs = sql_query_array_scalar( "select DISTINCT repeat_freq from im_task_templates" );
			create_tasks_per_mission();
			break;
		}
		if ( ! is_array( $freqs ) ) {
			$freqs = array( $freqs );
			break;
		}
	} while ( 0 );

	$verbose_table = array( array( "template_id", "freq", "query", "active", "result", "priority", "new task" ));
	foreach ( $freqs as $freq ) {
//		print "freq=". $freq . "<br/>";
		//	print "v= " . $verbose . "<br/>";
		$info_key = "tasklist_create_run_" . $freq;
		// $verbose_data .= "Started $freq...<br/>";

		// TODO: check last run.
//		$last_run = info_get( $info_key );
//		if ( date( $freq ) == date( $last_run ) ) {
//			if ( $verbose ) {
//				print "run lately. Skipping " . $freq;
//			}
//
//			continue;
//		}

		$sql = "SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority, creator, owner " .
		       " FROM im_task_templates " .
		       " where repeat_freq = '" . $freq . "'";

		$result = sql_query( $sql );

		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$verbose_line = array();
			// if ($row["id"] == 10) var_dump($row);
			$id         = $row["id"];
			$project_id = $row["project_id"];
			if ( ! $project_id ) {
				$project_id = 0;
			}
			// 		print "project_id= " . $project_id . "<br/>";

			array_push( $verbose_line, $id );
			array_push( $verbose_line, check_frequency( $row["repeat_freq"], $row["repeat_freq_numbers"] ) );
			array_push( $verbose_line, check_query( $row["condition_query"] ) );
			array_push( $verbose_line, check_active( $id, $row["repeat_freq"] ) );
			$test_result = "";
			for ( $i = 1; $i < 4; $i ++ )
				$test_result .= substr( $verbose_line[ $i ], 0, 1);

			array_push( $verbose_line, $test_result );
//			 print $test_result . " " . (strpos("1" . $test_result, "0") != false) . "<br/>";
			if ( strpos(  $test_result, "0" ) !== false) {
//				print "xxxx";
				array_push( $verbose_line, "skipped" );
				array_push( $verbose_table, $verbose_line);
				continue;
			}

			$owner = sql_query_single_scalar("SELECT owner FROM im_task_templates WHERE id = " . $id );
			if (! $owner)
				$owner = $default_owner;

			$creator = sql_query_single_scalar("SELECT creator FROM im_task_templates WHERE id = " . $id );
			if (! $creator)
				$creator = $default_owner;

			$priority = sql_query_single_scalar( "SELECT priority FROM im_task_templates WHERE id = " . $id );
			if ( ! $priority ) {
				$priority = sql_query_single_scalar( "SELECT project_priority FROM im_projects WHERE id = " . $project_id );
			}

			if ( ! $priority ) {
				$priority = 0;
			}
			array_push( $verbose_line, $priority);

			$sql = "INSERT INTO im_tasklist " .
			       "(task_description, task_template, status, date, project_id, priority, owner, creator) VALUES ( " .
			       "'" . $row["task_description"] . "', " . $id . ", " . eTasklist::waiting . ", now(), " . $project_id . ",  " .
			       $priority . "," . $owner . "," . $creator . ")";

			my_log("create task " . $sql);

			sql_query( $sql );

			array_push( $verbose_line, sql_insert_id() );

			array_push( $verbose_table, $verbose_line);
			// print $sql;
		}
		info_update( $info_key, date( $freq ));
	}
	if ( $verbose )
		print gui_table_args( $verbose_table);
}


function check_frequency( $repeat_freq, $repeat_freq_numbers ) {

	// print "rf=$repeat_freq. rfn=$repeat_freq_numbers<br/>";
	if ( strlen( $repeat_freq ) == 0 ) {
		return "1 empty freq passed";
	}

	$result = $repeat_freq . ". now = " . date( $repeat_freq ) . " " . $repeat_freq_numbers;

	$repeat_freq = explode( " ", $repeat_freq )[0];
	$passed = 0;
	$result      .= "checking " . date( $repeat_freq ) . " " . $repeat_freq_numbers;
	if ( in_array( date( $repeat_freq ), explode( ",", $repeat_freq_numbers ) ) ) {
		$passed = 1;
	}

	return $passed . $result;
}

//
//
//		switch ( $query ) {
//			case "d": // once a day
//				$days_from_last_run = sql_query_single_scalar( "select MIN(DATEDIFF(CURDATE(), DATE(date)))  from im_tasklist
//											where task_template = " . $id );
//
//				if ( is_null( $days_from_last_run ) or ( $days_from_last_run > 0 ) ) {
//					if ( $verbose ) {
//						print "run";
//					}
//					$run = true;
//				} else if ( $verbose ) {
//					print $days_from_last_run . " days ago ";
//				}
//				break;
//			case "m": // once a month
//				$repeat_days = sql_query_single_scalar( "select repeat_days from im_task_templates" );
//
//				$trigger_time       = strtotime( $repeat_days . date( 'M Y' ) );
//				$days_from_last_run = sql_query_single_scalar( "select MIN(DATEDIFF(CURDATE(), DATE(date)))  from im_tasklist
//											where task_template = " . $id );
//				if ( $trigger_time > time() or $days_from_last_run > 27 ) {
//					$run = true;
//				}
//				break;
//
//			case "w": // a week
//				$repeat_days = sql_query_single_scalar( "select repeat_days from im_task_templates" );
//
//				$trigger_time       = strtotime( $repeat_days . date( 'M Y' ) );
//				$days_from_last_run = sql_query_single_scalar( "select MIN(DATEDIFF(CURDATE(), DATE(date)))  from im_tasklist
//											where task_template = " . $id );
//				if ( $trigger_time > time() or $days_from_last_run > 27 ) {
//					$run = true;
//				}
//				break;
//
//			default:
//				// $url = "http://store.im-haadama.co.il/tools/tasklist/" . $query . "&id=" . $row[0];
//
//				// print $url . "<br/>";
//		}


function check_query( $query ) {
	if ( strlen( $query ) == 0 ) {
		return "1 empty query passed<br/>";
	}
	if ( ! ( strlen( $query ) > 5 ) ) {
		return "0 short or bad query. " . $query . " failed";
	}

	return strip_tags( im_file_get_html( $query ));
}

function check_active( $id, $repeat_freq ) {
	// status < 2 - active.
	// Date(date) = curdate() - due today. (or should it be finish date?)

	$running_id = sql_query_single_scalar("select id from im_tasklist where task_template = " . $id .
	                                      " and (status < 2 or date(ended) = curdate()) limit 1");

	if ( $running_id ) {
		return "0 " . $running_id;
	}

	return "1 not active";
}

// Select relative optional preq for given task:
// 1) not finished.
// 2) in the same project.

function gui_select_task_related( $id, $value, $events, $task_id ) {
	$query = "";
	if ( $project_id = sql_query_single_scalar( "select project_id from im_tasklist where id = " . $task_id ) ) {
		$query = "status in (0, 1) \n" .
		         " and project_id = " . $project_id;
	}

	return gui_select_task( $id, $value, $events, $query );
}

function task_table( $task_ids ) {
	$rows = array( array( "מזהה", "עדיפות", "תיאור" ) );

	foreach ( $task_ids as $task_id ) {
		$t = new Tasklist( $task_id );

		$row = array(
			gui_hyperlink( $t->getId(), "c-get-tasklist.php?id=" . $t->getId() ),
			$t->getPriority(),
			$t->getTaskDescription()
		);

		array_push( $rows, $row );
	}


	return gui_table_args( $rows );
}