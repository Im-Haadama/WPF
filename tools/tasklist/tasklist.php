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
			break;
		case "cancel":
			$sql = "UPDATE im_tasklist SET ended = now(), status = " . eTasklist::canceled .
			       " WHERE id = " . $task_id;
			sql_query( $sql );
			redirect_back();
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

function create_tasks() {
	// TODO: check last run.
	// Todo: Run in the background
	$sql    = "SELECT id, task_description, task_url, project_id, repeat_days FROM im_task_templates";
	$result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		// Check if it is the right day.
		$days  = explode( ",", $row[4] );
		$today = date( "w" ) + 1;
		if ( ! in_array( $today, $days ) ) {
			continue;
		}

		// Check if task from this template active
		$count = sql_query_single_scalar( "SELECT count(*) FROM im_tasklist WHERE task_template = " . $row[0] .
		                                  " AND status < 2" );

		if ( $count < 1 ) {
			$query = sql_query_single_scalar( "SELECT condition_query FROM im_task_templates WHERE id = " . $row[0] );
			$c     = true;
			print "checking $query<br/>";
			if ( strlen( $query ) > 1 ) {
				$url = "http://store.im-haadama.co.il/tools/tasklist/" . $query . "&id=" . $row[0];
				// print $url . "<br/>";
				$c = file_get_html( $url );
//			print $c;
			}
			if ( $c ) {
				print "creating " . $row[1] . "<br/>";
				$sql = "INSERT INTO im_tasklist (task_description, task_template, status, date, project_id) VALUES ( " .
				       "'" . $row[1] . "', " . $row[0] . ", " . eTasklist::waiting . ", now(), " . $row[3] . ")";

				sql_query( $sql );
			}
		}
	}
}