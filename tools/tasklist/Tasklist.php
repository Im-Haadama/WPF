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

class Tasklist {
	private $id;
	private $location_name;
	private $location_address;
	private $task_description;
	private $mission_id;
	private $repeat_freq;
	private $repeat_freq_numbers;

	public function __construct( $_id ) {
		$this->id = $_id;
		$row      = sql_query_single( "SELECT location_name, location_address, task_description, mission_id," .
		                              " task_template " .
		                              " FROM im_tasklist " .
		                              " WHERE id = " . $this->id );

		$this->location_name    = $row[0];
		$this->location_address = $row[1];
		$this->task_description = $row[2];
		$this->mission_id       = $row[3];

		if ( $row[4] ) {
			$row = sql_query_single( "SELECT repeat_freq, repeat_freq_numbers " .
			                         " from im_task_teplates where id = " . $row[4] );

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

	public function Ended() {
		$sql = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::done .
		       " WHERE id = " . $this->id;
		// print $sql;
		sql_query( $sql );
	}
}

require_once( ROOT_DIR . "/tools/people/people.php" );
require_once( ROOT_DIR . "/niver/data/im_simple_html_dom.php" );
require_once( ROOT_DIR . "/tools/options.php" );

class eTasklist {
	const
		waiting = 0,
		started = 1,
		done = 2,
		canceled = 3;
}

function get_task_link( $template_id ) {
	if ( $template_id > 0 ) {
		return sql_query_single_scalar( "SELECT task_url FROM im_task_templates WHERE id = " . $template_id );
	}

	return "";
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

function create_tasks( $freqs, $verbose = false ) {
	foreach ( $freqs as $freq ) {
		//	print "v= " . $verbose . "<br/>";
		$info_key = "tasklist_create_run_" . $freq;
		if ( $verbose ) {
			print "Started...<br/>";
		}

		// TODO: check last run.
		$last_run = info_get( $info_key );
		if ( date( $freq ) == date( $last_run ) ) {
			if ( $verbose ) {
				print "no need " . $freq . "<br/>";
			}

			return;
		}

		//	 Todo: Run in the background
		$sql    = "SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority " . "
		 FROM im_task_templates " .
		          " where repeat_freq = '" . $freq . "'";
		$result = sql_query( $sql );

		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$id         = $row["id"];
			$project_id = $row["project_id"];
			if ( ! $project_id ) {
				$project_id = 0;
			}
			// 		print "project_id= " . $project_id . "<br/>";

			if ( $verbose ) {
				print "<br/>checking " . $id . " ";
			}

			if ( ! check_frequency( $row["repeat_freq"], $row["repeat_freq_numbers"], $verbose ) ) {
				continue;
			}

			if ( ! check_query( $row["condition_query"], $verbose ) ) {
				continue;
			}

			// Check if task from this template active
			if ( check_active( $id, $row["repeat_freq"], $verbose ) ) {
				continue;
			}

			if ( $verbose ) {
				print "creating " . $id . "<br/>";
			}

			$priority = sql_query_single_scalar( "SELECT priority FROM im_task_templates WHERE id = " . $id );
			if ( ! $priority ) {
				$priority = sql_query_single_scalar( "SELECT project_priority FROM im_projects WHERE id = " . $project_id );
			}

			if ( ! $priority ) {
				$priority = 0;
			}

			$sql = "INSERT INTO im_tasklist (task_description, task_template, status, date, project_id, priority, repeat_freq) VALUES ( " .
			       "'" . $row["task_description"] . "', " . $id . ", " . eTasklist::waiting . ", now(), " . $project_id . ",  " .
			       $priority . ")";

			sql_query( $sql );
			print $sql;
		}
		info_update( $info_key, date( $freq ));
	}
}


function check_frequency( $repeat_freq, $repeat_freq_numbers, $verbose ) {
	if ( strlen( $repeat_freq ) == 0 ) {
		if ( $verbose ) {
			print "empty freq passed<br/>";
		}

		return true;
	}

	if ( $verbose ) {
		print "checking " . $repeat_freq . ". now = " . date( $repeat_freq ) . " " . $repeat_freq_numbers;
	}

	$passed = false;
//	print "d=" . date($repeat_freq_numbers) . "<br/>";
//	print "df=" . date($repeat_freq) . "<br/>";
	if ( in_array( date( $repeat_freq ), explode( ",", $repeat_freq_numbers ) ) ) {
		$passed = true;
	}

	if ( $verbose ) {
		print " " . ( $passed ? "yes" : "no" ) . " " . $repeat_freq_numbers . "<br/>";
	}

	// print "result=" . $passed . '<br/>';
	return $passed;
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


function check_query( $query, $verbose ) {
	if ( strlen( $query ) == 0 ) {
		if ( $verbose ) {
			print "empty query passed<br/>";
		}

		return true;
	}
	if ( ! ( strlen( $query ) > 5 ) ) {
		if ( $verbose ) {
			print "short or bad query. " . $query . " failed";
		}

		return false;
	}

	if ( $verbose ) {
		print "checking " . $query;
	}

	$c      = im_file_get_html( $query );
	$passed = ( $c === "1" );
	if ( $passed and $verbose ) {
		print "true";
	}
	print "<br/>";

	return $passed;
}

function check_active( $id, $repeat_freq, $verbose ) {
	$sql = "SELECT count(*) FROM im_tasklist WHERE task_template = " . $id .
	       " AND status < 2";

	if ( $verbose ) {
		print "checking " . $id . " " . $sql . ". ";
	}

	$count = sql_query_single_scalar( $sql );

	if ( $verbose ) {
		print $count . " active ";
	}

	if ( $count >= 1 ) {
		if ( $verbose ) {
			print "Not creating<br/>";
		}

		return true;
	}

	return false;
}

