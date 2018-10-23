<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/10/17
 * Time: 19:11
 */

require_once( "../im_tools.php" );
require_once( "../sql.php" );
require_once( "../people/people.php" );
require_once( "../multi-site/simple_html_dom.php" );
require_once( "../options.php" );

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

if ( isset( $_GET["operation"] ) ) {
	$task_id = $_GET["id"];
	switch ( $_GET["operation"] ) {
		case "start":
			$sql = "UPDATE im_tasklist SET started = now(), status = " . eTasklist::started .
			       " WHERE id = " . $task_id;
			sql_query( $sql );
			redirect_back();
			break;
		case "end":
			$sql = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::done .
			       " WHERE id = " . $task_id;
			sql_query( $sql );
			redirect_back();
			create_tasks();
			break;
		case "cancel":
			$sql = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::canceled .
			       " WHERE id = " . $task_id;
			sql_query( $sql );
			create_tasks();
			redirect_back();
			break;
		case "check":
			check_condition();
			break;
	}
}

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

function redirect_back() {
	if ( isset( $_SERVER["HTTP_REFERER"] ) ) {
		header( "Location: " . $_SERVER["HTTP_REFERER"] );
	}
}

function tasklist_set_defaults( &$values ) {
	$values["date"] = date( 'Y-m-d' );
}

function create_tasks( $verbose = false ) {
	// TODO: check last run.
	$last_run = info_get( "tasklist_run" );
	$delta    = info_get( "tasklist_delta" );
	if ( $last_run and ( time() - $last_run < $delta ) ) {
		print time() - $last_run;

		return;
	}
	print "+";

	// Todo: Run in the background
	$sql    = "SELECT id, task_description, task_url, project_id, repeat_days FROM im_task_templates";
	$result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		if ( $verbose ) {
			print "<br/>checking " . $row[1];
		}
		$id = $row[0];
		// Check if it is the right day.
		$days  = explode( ",", $row[4] );
		$today = date( "w" ) + 1;
		if ( ! in_array( $today, $days ) ) {
			if ( $verbose )
				print " not the day ";
			continue;
		}

		// Check if task from this template active
		$count = sql_query_single_scalar( "SELECT count(*) FROM im_tasklist WHERE task_template = " . $row[0] .
		                                  " AND status < 2" );

		if ( $count < 1 ) {
			$query = sql_query_single_scalar( "SELECT condition_query FROM im_task_templates WHERE id = " . $row[0] );
			$run   = false;
			if ( strlen( $query ) >= 1 ) {
				if ( $verbose ) {
					print "checking $query<br/>";
				}
				switch ( $query ) {
					case "d": // once a day
						$days_from_last_run = sql_query_single_scalar( "select MIN(DATEDIFF(CURDATE(), DATE(date)))  from im_tasklist
										where task_template = " . $id );

						if ( is_null( $days_from_last_run ) or ( $days_from_last_run > 0 ) ) {
							if ( $verbose ) {
								print "run";
							}
							$run = true;
						} else if ( $verbose ) {
							print $days_from_last_run . " days ago ";
						}
						break;
					case "m": // once a month
						$repeat_days = sql_query_single_scalar( "select repeat_days from im_task_templates" );

						$trigger_time       = strtotime( $repeat_days . date( 'M Y' ) );
						$days_from_last_run = sql_query_single_scalar( "select MIN(DATEDIFF(CURDATE(), DATE(date)))  from im_tasklist
										where task_template = " . $id );
						if ( $trigger_time > time() or $days_from_last_run > 27 ) {
							$run = true;
						}
						break;

					default:
						$url = "http://store.im-haadama.co.il/tools/tasklist/" . $query . "&id=" . $row[0];
						// print $url . "<br/>";
						$c = im_file_get_html( $url );
						if ( $c ) {
							$run = true;
						}
				}
			}
			if ( $run ) {
				if ( $verbose )
					print "creating " . $row[1] . "<br/>";
				$sql = "INSERT INTO im_tasklist (task_description, task_template, status, date, project_id) VALUES ( " .
				       "'" . $row[1] . "', " . $row[0] . ", " . eTasklist::waiting . ", now(), " . $row[3] . ")";

				sql_query( $sql );
			}
		}
	}
	info_update( "tasklist_run", time());
}