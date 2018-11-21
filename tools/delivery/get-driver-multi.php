<html>
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 22:41
 */

require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
require_once( '../multi-site/multi-site.php' );
require_once( '../maps/build-path.php' );
require_once( '../missions/Mission.php' );

$debug = false;
// $addresses = array();

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

$output = MultiSite::GetAll( "delivery/get-driver.php?week=" . $week, $debug );

$dom = im_str_get_html( $output );

print header_text( false );

?>
<style>
    @media print {
        h1 {
            page-break-before: always;
        }
    }
</style>
<script>
    function delivered(st, v, type) {
        var url = st + "/" + type + "/" + type + "-post.php?operation=delivered&ids=" + v;

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                if (xmlhttp.response == "delivered") {
                    var row = document.getElementById("chk_" + v).parentElement.parentElement;
                    var table = row.parentElement.parentElement;
                    table.deleteRow(row.rowIndex);
                } else {
                    alert("failed: " + xmlhttp.response);
                }
                // window.location = window.location;
            }
        }

        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    }
</script>
<?php

function get_text( $row, $index ) {
	$cell = $row->find( 'td', $index );
	if ( $cell ) {
		return $cell->plaintext;
	}

	return "";
}
$data_lines = array();

$header = null;
foreach ( $dom->find( 'tr' ) as $row ) {
	if ( ! $header ) {
		for ( $i = 0; $i < 7; $i ++ ) {
			if ( $i != 2 ) {
				$header .= $row->find( 'td', $i );
			}
		}
		$header .= gui_cell( gui_header( 3, "מספר ארגזים, קירור" ) );
		$header .= gui_cell( gui_header( 3, "נמסר" ));
		$header .= gui_cell( gui_header( 3, "ק\"מ ליעד" ) );
		$header .= gui_cell( gui_header( 3, "דקות" ) );
		$header .= gui_cell( gui_header( 3, "דקות מצטבר" ));
		continue;
	}
	// $key_fields = $row->find( 'td', 11 )->plaintext;
	$site                   = $row->find( 'td', 0 )->plaintext;
	if ( $site == 'אתר' ) {
		continue;
	}
	$order_id               = $row->find( 'td', 1 )->plaintext;
	$user_id                = $row->find( 'td', 2 )->plaintext;
	$name                   = $row->find( 'td', 3 )->plaintext;
	$addresses[ $order_id ] = $row->find( 'td', 4 )->plaintext;
	$site_id                = $row->find( 'td', 9 )->plaintext;
	$delivery_id            = get_text( $row, 10 );

	// print "name = " . $name . " key= "  . $key . "<br/>";
	$mission_id = $row->find( 'td', 8 )->plaintext;
	$line_data  = "<tr>";
	for ( $i = 0; $i < 7; $i ++ ) {
		if ( $i <> 2 )
			$line_data .= $row->find( 'td', $i );
	}
	$line_data .= gui_cell( "" ); // #box
	$type      = "order";
	if ( $site == "supplies" ) {
		$type = "supplies";
	}
	if ( ! is_numeric( $site_id ) ) {
		die ( $site_id . " not number" . $site_id . " order_id = " . $order_id . " name = " . $name . " <br/>" );
	}
	$line_data .= gui_cell( gui_checkbox( "chk_" . $order_id, "", "", 'onchange="delivered(\'' . MultiSite::SiteTools( $site_id ) . "'," . $order_id . ', \'' . $type . '\')"' ) ); // #delivered

	$line_data .= "</tr>";
	if ( ! isset( $data_lines[ $mission_id ] ) ) {
		$data_lines[ $mission_id ] = array();
		/// print "new: " . $mission_id . "<br/>";
	}
	array_push( $data_lines[ $mission_id ], array( $addresses[ $order_id ], $line_data ) );
	// var_dump($line_data); print "<br/>";
}

//var_dump($addresses);

foreach ( $data_lines as $mission_id => $data_line ) {
//    $mission_id = 152;
//    $data_line = $data_lines[152];1
//    if (1){
	if ( ! ( $mission_id > 0 ) ) {
		print "mission 0 skipped<br/>";
		continue;
	}
//        die ("no mission id");

	$mission = Mission::getMission( $mission_id);

	print gui_header( 1, get_mission_name( $mission_id ) . "($mission_id)" );

	if ( $debug ) {
		print_time( "start handle mission " . $mission_id, true );
	}

	// Collect the stop points
	$path              = array();
	$stop_points       = array();
	$lines_per_station = array();
	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
		$stop_point = $data_lines[ $mission_id ][ $i ][0];
		// print "stop point: " . $stop_point . "<br/>";
		array_push( $stop_points, $stop_point );
		if ( ! isset( $lines_per_station[ $stop_point ] ) ) {
			$lines_per_station[ $stop_point ] = array();
		}
		if ( get_distance( $mission->getStartAddress(), $stop_point ) ) {
				array_push( $lines_per_station[ $stop_point ], $data_lines[ $mission_id ][ $i ][1] );
			} else {
				print "לא מזהה את הכתובת של הזמנה " . $data_lines[ $mission_id ][ $i ] . "<br/>";
			}

	}
//	foreach ($stop_points as $p) print $p . " ";
	if ( $debug )
		print_time( "start path ", true);
	// var_dump($mission);
	find_route_1( $mission->getStartAddress(), $stop_points, $path, true, $mission->getEndAddress() );
	if ( $debug )
		print_time( "end path " . $mission_id, true);

//	var_dump($path);
	if ( $debug ) {
		print $path[0] . "<br/>";// . " " .get_distance(1, $path[0]) . "<br/>";
		for ( $i = 1; $i < count( $path ); $i ++ ) {
			// print $path[$i] . " " . $addresses[$path[$i]]. "<br/>";
			print $path[ $i ] . "<br/>"; // get_distance($path[$i], $path[$i-1]) . "<br/>";
		}
	}

	// print "mission_id: " . var_dump($data_lines[$mission_id]) . "<br/>";
	print "<table>";
	$data = $header;

	$data .= gui_list( "באחריות הנהג להעמיס את הרכב ולסמן את מספר האריזות והאם יש קירור." );
	$data .= gui_list( "יש לוודא שכל המשלוחים הועמסו.");
	$data .= gui_list( "בעת קבלת כסף או המחאה יש לשלוח מיידית הודעה ליעקב, עם הסכום ושם הלקוח.");

	$prev           = $mission->getStartAddress();
	$total_distance = 0;
	$total_duration = 0;
	for ( $i = 0; $i < count( $path ); $i ++ ) {
		foreach ( $lines_per_station[ $path[ $i ] ] as $line ) {
			$distance       = round( get_distance( $prev, $path[ $i ] ) / 1000, 1 );
			$total_distance += $distance;
			$duration       = round( get_distance_duration( $prev, $path[ $i ] ) / 60, 0 );
			$total_duration += $duration + 5;
			$data           .= substr( $line, 0, strpos( $line, "</tr>" ) ) . gui_cell( $distance . "km" ) .
			                   gui_cell( $duration . "ד'" ) . gui_cell( $total_duration . "ד'" ) . "</td>";
		}
		$prev = $path[ $i];
	}
	$total_distance += get_distance( $path[ count( $path ) - 1 ], $mission->getEndAddress() ) / 1000;

//	foreach ($path as $id => $stop_point){
//		print $id ."<br/>";
//	for ( $i = 0; $i < count( $data_lines[ $mission_id ] ); $i ++ ) {
//		$line = $data_line[ $i ][1];
//		$data .= trim( $line );
//	}

	print $data;

	print "</table>";

	print "סך הכל ק\"מ " . $total_distance . "<br/>";
	if ( $debug )
		print_time( "end handle mission " . $mission_id, true);

}

function mb_ord( $c ) {
	return ord( substr( $c, 1, 1 ) );
}