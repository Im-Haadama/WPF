<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 22:41
 */

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/multi-site.php' );

$debug = false;

if ( isset( $_GET["debug"] ) ) {
	$debug = true;
}

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
} else {
	$week = date( "Y-m-d", strtotime( "last sunday" ) );
}

if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
	print gui_hyperlink( "שבוע הבא", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
}

//print gui_hyperlink( "שבוע קודם", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

$output = MultiSite::GetAll( "delivery/get-driver.php?week=" . $week );

$dom = str_get_html( $output );

print header_text( false );

print "<style>";
print "@media print {";
print "h1 {page-break-before: always;}";
print "}";
print "</style>";

$data_lines = array();

$header     = null;
foreach ( $dom->find( 'tr' ) as $row ) {
	if ( ! $header ) {
		for ( $i = 0; $i < 7; $i ++ ) {
			$header .= $row->find( 'td', $i );
		}
		$header .= gui_cell( gui_header( 3, "מספר ארגזים, קירור" ) );
		$header .= gui_cell( gui_header( 3, "נמסר" ));
		continue;
	}
	// $key_fields = $row->find( 'td', 11 )->plaintext;
	$name       = $row->find( 'td', 3 )->plaintext;
	$zone_order = "0";
	$long       = "0";
	$lat        = "0";
	// sscanf( $key_fields, "%s %s %f %f", $day, $zone_order, $long, $lat );
	$zone_order = $row->find( 'td', 9 )->plaintext;
	$long       = $row->find( 'td', 10 )->plaintext;
	$lat        = $row->find( 'td', 11 )->plaintext;
	// print "day " . $day . " zone " . $zone_order . " long " . $long . " " . $lat . "<br/>";
	$coor = 100 * ( 40 - $long ) + $lat[1];

	// Key= ZZ LO LA
	$key = 10000 * $zone_order + $coor;

	// print "name = " . $name . " key= "  . $key . "<br/>";
	$mission_id = $row->find( 'td', 8 )->plaintext;
	$line_data  = "<tr>";
	for ( $i = 0; $i < 7; $i ++ ) {
		$line_data .= $row->find( 'td', $i );
	}
	$line_data .= gui_cell( "" ); // #box
	$line_data .= gui_cell( gui_checkbox( "", "" ) ); // #delivered
	if ( $debug ) {
		$line_data .= gui_cell( $key . " " . $long . " " . $lat );
	}

	$line_data .= "</tr>";
	if ( ! isset( $data_lines[ $mission_id ] ) ) {
		$data_lines[ $mission_id ] = array();
		/// print "new: " . $mission_id . "<br/>";
	}
	array_push( $data_lines[ $mission_id ], array( $key, $line_data ) );
}

foreach ( $data_lines as $mission_id => $data_line ) {
	print gui_header( 1, get_mission_name( $mission_id ) );
	// print "mission_id: " . var_dump($data_lines[$mission_id]) . "<br/>";
	print "<table>";
	$data = $header;

	$data .= gui_list( "באחריות הנהג להעמיס את הרכב ולסמן את מספר האריזות והאם יש קירור." );
	$data .= gui_list( "יש לוודא שכל המשלוחים הועמסו.");

	sort( $data_line );

	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
		$line = $data_line[ $i ][1];
		$data .= trim( $line );
	}

	print $data;

	print "</table>";
}

function mb_ord( $c ) {
	return ord( substr( $c, 1, 1 ) );
}