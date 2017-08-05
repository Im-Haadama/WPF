<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 22:41
 */

require_once( '../tools.php' );
require_once( '../multi-site/multi-site.php' );

$output = MultiSite::GetAll( "delivery/get-driver.php" );

$dom = str_get_html( $output );

print header_text( false );


print "<table>";

$data_lines = array();
$header     = null;
foreach ( $dom->find( 'tr' ) as $row ) {
	if ( ! $header ) {
		$header = $row;
		continue;
	}
	$key_fields = $row->find( 'td', 11 )->plaintext;
	$name       = $row->find( 'td', 3 )->plaintext;
	$zone_order = "0";
	$long       = "0";
	$lat        = "0";
	sscanf( $key_fields, "%s %s %f %f", $day, $zone_order, $long, $lat );
	// print "day " . $day . " zone " . $zone_order . " long " . $long . " " . $lat . "<br/>";
	$coor = 100 * ( 40 - $long ) + $lat[1];


	// Key= D ZZ LO LA

	$key = 1000000 * ( mb_ord( $day ) - mb_ord( 'א' ) + 1 ) + 10000 * $zone_order + $coor;
	// print "name = " . $name . " key= "  . $key . "<br/>";
	array_push( $data_lines, array( $key, $row ) );
}

$data = $header;

sort( $data_lines );

for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
	$line = $data_lines[ $i ][1];
	$data .= trim( $line );
}

print $data;

print "</table>";

function mb_ord( $c ) {
	return ord( substr( $c, 1, 1 ) );
}