<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/08/18
 * Time: 04:27
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );

$key = "weekly_run";

print header_text( false );


// $sql = "select info_data from im_info where info_key = '" . $key . "'";

$last = info_get( $key );

if ( is_null( $last ) ) {
	print "אין ריצה קודמת";

	return;
}
print "ריצה אחרונה " . $last;

$log = readfile( "../logs/weekly.php" );

print $log;
