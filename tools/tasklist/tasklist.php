<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "Tasklist.php" );

require_once( "tasklist-post.php" );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/r-shop_manager.php' );

$preset_basic_query = " and (date(date) <= CURRENT_DATE or isnull(date)) and (status < 2) " .
                      " and (not mission_id > 0) " . //  and task_active_time(id)
                      " and (isnull(preq) or task_status(preq) >= 2) ";

function preset_query( $preset ) {
	global $user_ID;

	global $preset_basic_query;

	//$preset_query       = array(
//	"",
//	$preset_basic_query,
//	$preset_basic_query . " and owner = 1",
//	$preset_basic_query . " and owner = 369 or creator = 369",
//	$preset_basic_query . " and owner = 369"
//);

	global $user_ID;
	if ( $preset > 100 ) {
		$q = $preset_basic_query . " and owner = " . ( $preset - 100 );
		if ( ! is_manager( $user_ID ) ) {
			$q .= "(owner = " . $user_ID . " or creator = " . $user_ID . ")";
		}

		return $q;
	}
	$set = array(
		null,
		$preset_basic_query,
		$preset_basic_query . " and owner = " . $user_ID,
		$preset_basic_query . " and (owner = " . $user_ID . " or creator = " . $user_ID . ")",
		" and mission_id > 0  and status = 1",
		"  "
	);

	return $set[ $preset ];
}

function tasklist_page_actions() {
	global $preset_basic_query;

	// add_link("פעילים", "c-get-all-tasklist.php?preset=1") . " ";
	add_link( "בטיפולי", "c-get-all-tasklist.php?preset=2" );
	add_link( "שלי", "c-get-all-tasklist.php?preset=3" );

	global $user_ID;
	// print "ia = " . is_manager() . "uid= " . $user_ID . "<br/>";
	if ( is_admin_user() ) {
		$sql = " SELECT DISTINCT owner FROM im_tasklist WHERE 1 " . $preset_basic_query;
//		print $sql;
		$workers = sql_query_array_scalar( $sql );
		foreach ( $workers as $worker ) {
			if ( $worker != $user_ID ) {
				add_link( get_customer_name( $worker ), "c-get-all-tasklist.php?preset=" . ( 100 + (int) $worker ) );
			}
		}
		add_link( "משימות נהיגה שלא בוצעו", "c-get-all-tasklist.php?preset=4" );

		add_link( "הכל", "c-get-all-tasklist.php?preset=5" );

		add_link( "חוזרות", "c-get-all-task_templates.php" );
	}
}

function add_link( $text, $link ) {
	print gui_button( "btn" . $text, "location.href='" . $link . "'", $text );
}

?>

