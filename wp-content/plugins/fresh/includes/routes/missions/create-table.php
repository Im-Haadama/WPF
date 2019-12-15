<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/11/17
 * Time: 22:54
 */

require_once( "../r-shop_manager.php" );
require_once( "../sql.php" );

print header_text( false, true, false );

print "truncating...";
sql_query( "truncate table im_missions" );

print " done<br/>";

print "creating from wp_options...";
$sql = "SELECT option_value FROM wp_options WHERE option_name LIKE 'woocommerce_flat_rate_%_settings'";

$results = sql_query( $sql );

while ( $row = mysqli_fetch_row( $results ) ) {
//	print $row[0] . "<br/>";
	$options = unserialize( $row[0] );
	$title   = $options["title"];
	$name    = mb_substr( $title, 0, 7 );
	$h       = mb_strpos( $title, "-" );
	$start   = mb_substr( $title, 7, $h - 7 );
	$end     = mb_substr( $title, $h + 1 );
	if ( ! is_numeric( $start ) or ! is_numeric( $end ) ) {
		print $title . "ignored<br/>";
		continue;
	}
	// print "name: " . $name . " " . "h= " . $h . " start=" . $start . " end=" . $end . "<br/>";
	$sql = "insert into im_missions (name, start_h, end_h) values ('" .
	       mysqli_real_escape_string( $conn, $name ) . "', $start, $end)";
	sql_query( $sql );
}
print " done<br/>";
