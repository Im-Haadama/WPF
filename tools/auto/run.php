<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/08/18
 * Time: 04:37
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

return; // Not used
require_once( TOOLS_DIR . "/options.php" );
require_once( TOOLS_DIR . "/delivery/missions.php" );
// require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/supplies/supplies.php" );
require_once( TOOLS_DIR . "/pricelist/pricelist-process.php" );

// DEBUG = 1. output on screen
$debug = 1;
//$gap_key = "run_gap";
//$run_gap = info_get( $gap_key );
//if ( $debug ) {
//	$run_gap = 5;
//} // debug mode
//$date_format = "h:m:s";
//
//if ( ! $run_gap ) {
//	// Set default
//	$run_gap = 600;
//	print "setting default run_gap to " . $run_gap . "<br/>";
//	info_update( "run_gap", $run_gap );
//}
//
ob_start();
$this_run_time = time();
print "run started " . date( $date_format ) . "\n";

// Check last run
$key      = "weekly_run";
$last_run = info_get( $key );

print "last_run: " . $last_run . "\n";
print "this_run_time: " . $this_run_time . "\n";
if ( $this_run_time - $last_run < $run_gap and $debug == 0 ) {
	print "no need to run\n";
	close_file( $debug );

	return;
}
// print $run . " " . $create_time . "<br/>";

auto_mail();

// TODO: Check permission
//if ( ImMultiSite::isMaster() ) {
//	duplicate_week();
//} else {
//	//require_once( TOOLS_DIR . "/delivery/sync-from-master.php" );
//}

update_remotes();

//if ( MultiSite::LocalSiteID() == 4 ) {
//	print "im haadama not proceesed<br/>";
//	// $results = "";
//	// print $results;
//}

auto_supply();

info_update( $key, $this_run_time );
close_file( $debug );

function close_file( $debug ) {

	global $date_format;
	print "run ended " . date( $date_format ) . "\n";

	$log = ob_get_clean();
//	print "log: " . $log . "<br/>";

	$file_name = ROOT_DIR . "/logs/run-" . date( 'd' ) . ".txt";
	// print "results saved to " . $file_name . "<br/>";
	$file = fopen( $file_name, "a" );
	fwrite( $file, $log );

	if ( $debug == 1 ) // debug
	{
		print nl2br( $log );
	}
}


function update_remotes() {
	// Update remote pricelist.
	$sql = "select id, site_id from im_suppliers where site_id is not null";

	$suppliers = sql_query( $sql );

	while ( $row = sql_fetch_row( $suppliers ) ) {
		$supplier_id = $row[0];
		pricelist_remote_site_process( $supplier_id, $results, false );

		// print $row[0] . " " . $row[1] . "<br/>";
	}
}