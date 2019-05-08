<html>
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 22:41
 */
require_once( "../im_tools.php" );
// TODO: require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../maps/build-path.php' );
require_once( '../missions/Mission.php' );
require_once( '../orders/Order.php' );
require_once( "../supplies/Supply.php" );

$debug = get_param( "debug" );

$missing = get_param( "missing" );

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
}
//else {
//	$week = date( "Y-m-d", strtotime( "last sunday" ) );
//}

if ( isset( $week ) and date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
	print gui_hyperlink( "שבוע הבא", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
}

//print gui_hyperlink( "שבוע קודם", "get-driver-multi.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

print header_text( false, true, true );

?>
<style>
    @media print {
        h1 {
            page-break-before: always;
        }
    }
</style>
<script>
    function delivered(site, id, type) {
        var url = "delivery-post.php?site_id=" + site + "&type=" + type +
            "&id=" + id + "&operation=delivered";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                // alert (xmlhttp.response);
                if (xmlhttp.response == "delivered") {
                    var row = document.getElementById("chk_" + id).parentElement.parentElement;
                    var table = row.parentElement.parentElement;
                    table.deleteRow(row.rowIndex);
                } else {
                    alert(url + " failed: " + xmlhttp.response);
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

// Start collecting data
$data_lines = array();
$header     = null;
$missions   = get_param_array( "mission_ids" );

if ( ! $missions ) {
if ( isset( $week ) ) {
	$missions = sql_query_array_scalar( "SELECT id FROM im_missions WHERE date >= " . quote_text( $week ) .
	                                    " AND date < DATE_ADD(" . quote_text( $week ) . ", INTERVAL 1 WEEK)" );
} else {
	$missions = sql_query_array_scalar( "SELECT id FROM im_missions WHERE date = curdate()" );
//	var_dump($missions);
}
}

if ( ! count( $missions ) ) {
	print "אין משימות להיום!";
	die ( 1 );
}
//print gui_header( 1, "מדפיס משימות " );
//foreach ( $missions as $mission ) {
//	print $mission . " " . get_mission_name( $mission ) . " ";
//}
//print "<br/>";

$m        = new ImMultiSite();
$data_url = "delivery/get-driver.php?mission_ids=" . implode( ",", $missions );
$output   = $m->GetAll( $data_url, false, $debug );
if ( $debug ) {
	print "o= " . $output . "<br/>";
}
$dom    = im_str_get_html( $output );

if ( strlen( $output ) < 10 ) {
	print $output . "<br/>";
	die ( "אין מסלולים להצגה" . $data_url );
}

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
	try {
		$site = $row->find( 'td', 0 )->plaintext;
	} catch ( Exception $e ) {
		var_dump( $row );
		die ( 1 );
	}
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
	$type      = "orders";
	if ( $site == "supplies" ) {
		$type = "supplies";
	}

	if ( $site == "משימות" )
		$type = "tasklist";
	if ( ! is_numeric( $site_id ) ) {
		die ( $site_id . " not number" . $site_id . " order_id = " . $order_id . " name = " . $name . " <br/>" );
	}
	$line_data .= gui_cell( gui_checkbox( "chk_" . $order_id, "", "",
		'onchange="delivered(' . $site_id . "," . $order_id . ', \'' . $type . '\')"' ) ); // #delivered
	$line_data .= gui_cell( $site_id );

	$line_data .= "</tr>";
	if ( ! isset( $data_lines[ $mission_id ] ) ) {
		$data_lines[ $mission_id ] = array();
		/// print "new: " . $mission_id . "<br/>";
	}
	array_push( $data_lines[ $mission_id ], array( $addresses[ $order_id ], $line_data ) );
	// var_dump($line_data); print "<br/>";

}

foreach ( $data_lines as $mission_id => $data_line ) {
	$supplies_to_collect = array();
	$add_on_the_way      = "";

//    $mission_id = 152;
//    $data_line = $data_lines[152];1
//    if (1){
	if ( ! ( $mission_id > 0 ) ) {
		// print "mission 0 skipped<br/>";
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
		$dom        = im_str_get_html( $data_lines[ $mission_id ][ $i ][1] );
		$row        = $dom->find( 'tr' );
		$site       = get_text( $row[0], 0 );
		$site_id    = get_text( $row[0], 8 );
		$order_id   = get_text( $row[0], 1 );
		$customer   = get_text( $row[0], 2 );
		$pickup     = ImMultiSite::getPickupAddress( $site_id );
		if ( $site != "משימות" and $site != "supplies" and $pickup != $mission->getStartAddress() ) {

//			print "xxx: " . $order_id . "<Br/>";

//		    print "site: " . $site . "<br/>";
//		    print "add stop " . MultiSite::getPickupAddress($site_id) . "<br/>";
			add_stop_point( $pickup );
			add_line_per_station( $mission->getStartAddress(), $pickup, gui_row( array(
				$site,
				$order_id,
				"<b>איסוף </b>" . $customer,
				$pickup,
				"",
				"",
				"",
				"",
				""
			) ), $order_id );
		}
		if ( $site == "supplies" ) {
			array_push( $supplies_to_collect, array( $order_id, $site_id ) );
		}

		// print "stop point: " . $stop_point . "<br/>";

		add_stop_point( $stop_point );
//		array_push( $stop_points, $stop_point );
		add_line_per_station( $mission->getStartAddress(), $stop_point, $data_lines[ $mission_id ][ $i ][1], $order_id );
	}
//	foreach ($stop_points as $p) print $p . " ";
	if ( $debug )
		print_time( "start path ", true);
	// var_dump($mission);
	find_route_1( $mission->getStartAddress(), $stop_points, $path, true, $mission->getEndAddress() );
	$url = "https://www.google.com/maps/dir/" . $mission->getStartAddress();

	for ( $i = 0; $i < count( $path ); $i ++ ) {
		$url .= "/" . $path[ $i ];
	}
	$url .= "/" . $mission->getEndAddress();
	print gui_hyperlink( "Maps", $url );
	print "<br/>";

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
	$data .= gui_list( "אם יש ללקוח מוצרים קפואים או בקירור, יש לבדוק זמינות לקבלת המסלול (לעדכן את יעקב)." );
	$data .= gui_list( "יש לוודא שכל המשלוחים הועמסו.");
	$data .= gui_list( "בעת קבלת כסף או המחאה יש לשלוח מיידית הודעה ליעקב, עם הסכום ושם הלקוח.");
	$data .= gui_list( "במידה והלקוח לא פותח את הדלת, יש ליידע את הלקוח שהמשלוח בדלת (טלפון או הודעה)." );

	$prev           = $mission->getStartAddress();
	$total_distance = 0;
	$total_duration = 0;
	for ( $i = 0; $i < count( $path ); $i ++ ) {
		$first = true;
		foreach ( $lines_per_station[ $path[ $i ] ] as $line_array ) {
			$line     = $line_array[0];
			$order_id = $line_array[1];
			// print "oid=" . $order_id ."<br/>";
			$distance       = round( get_distance( $prev, $path[ $i ] ) / 1000, 1 );
			if ( $first ) {
				$total_distance += $distance;
				$duration       = round( get_distance_duration( $prev, $path[ $i ] ) / 60, 0 );
				$first          = false;
			} else {
				$duration = 0;
			}
			$total_duration += $duration + 5;
			$data           .= substr( $line, 0, strpos( $line, "</tr>" ) ) . gui_cell( $distance . "km" ) .
			                   gui_cell( $duration . "ד'" ) . gui_cell( date( "G:i", mktime( 6, $total_duration ) ) . "ד'" ) . "</td>";

			if ( $missing )
				try {
				$o    = new Order( $order_id );
				if ( $o->getDeliveryId() and strlen( $o->Missing() ) ) {
					$data .= gui_row( array(
						"חוסרים",
						$order_id,
						$o->CustomerName(),
						"נא לסמן מה הושלם:",
						$o->Missing(),
						"",
						"",
						"",
						"",
						"",
						"",
						""
					) );
				}
			} catch ( Exception $e ) {
				// probably from different site
			}

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

	if ( count( $supplies_to_collect ) ) {
		// var_dump($supplies_to_collect);
		foreach ( $supplies_to_collect as $_supply_id ) {
			$supply_id = $_supply_id[0];
			$site_id   = $_supply_id[1];
			// print "sid= " . $site_id . "<br/>";
			if ( $site_id != $m->getLocalSiteID() ) {
				print $m->Run( "supplies/supplies-post.php?operation=print&id=" . $supply_id, $site_id );
			} else {
				$s = new Supply( $supply_id );
				print gui_header( 1, "אספקה  " . $supply_id . " מספק " . $s->getSupplierName() );
				print $s->Html( true, 0 );
			}
		}
	}

//    foreach ($o)
}

function add_stop_point( $point ) {
	global $stop_points;

	if ( ! in_array( $point, $stop_points ) ) {
		array_push( $stop_points, $point );
	}
}

function add_line_per_station( $start_address, $stop_point, $line, $order_id ) {
	global $lines_per_station;

	if ( ! isset( $lines_per_station[ $stop_point ] ) ) {
		$lines_per_station[ $stop_point ] = array();
	}
	if ( get_distance( $start_address, $stop_point ) or ( $start_address == $stop_point ) ) {
		array_push( $lines_per_station[ $stop_point ], array( $line, $order_id) );
	} else {
		print "לא מזהה את הכתובת של הזמנה " . $line . "<br/>";
	}
}

function mb_ord( $c ) {
	return ord( substr( $c, 1, 1 ) );
}