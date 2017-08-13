<?php
require_once( '../tools.php' );
require_once( '../../wp-content/plugins/woocommerce-delivery-notes/woocommerce-delivery-notes.php' );
require_once( '../multi-site/multi-site.php' );
$header = $_GET["header"];
//$footer = $_GET["footer"];
//$header = ( MultiSite::LocalSiteID() == 1 );
// print "Start " . MultiSite::LocalSiteName();
// print $header;
if ( $header ) {
	print header_text( false );
	print '<style type="text/css" media="print">
    .page
    {
        -webkit-transform: rotate(-90deg);
        -moz-transform:rotate(-90deg);
        filter:progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
    }
</style>';
	print "<header style='text-align: center'><center><h1>מסלולים ליום " . date( 'd/m/Y' );
	print "</h1></header>";
	table_header();
}
$print = 1; //$_GET["print"];

print_deliveries();

print_legacy();

die( 0 );

function print_deliveries() {
	$sql = 'SELECT posts.id'
	       . ' FROM `wp_posts` posts'
	       . ' WHERE `post_status` in (\'wc-awaiting-shipment\', \'wc-processing\')'
	       . ' order by 1';

	// print $sql;
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$data_lines = array();
	$site_tools = MultiSite::LocalSiteTools();

	//print "loop start<br>";
	while ( $row = mysql_fetch_row( $export ) ) {
		$fields = array();
		foreach ( $row as $value ) {
			array_push( $fields, MultiSite::LocalSiteName() );

			$order_id = $value;
			// print $order_id . "<br/>";

			$client_id = get_customer_id_by_order_id( $order_id );

			$ref = "<a href=\"" . $site_tools . "/orders/get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a>";
			array_push( $fields, $ref );

			$name = get_meta_field( $order_id, '_billing_first_name' ) . " " .
			        get_meta_field( $order_id, '_billing_last_name' );

			array_push( $fields, gui_hyperlink( $name, $site_tools . "../../wp-admin/user-edit.php?user_id=" . $client_id ) );

			$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
			                 get_meta_field( $order_id, '_shipping_last_name' );

			array_push( $fields, $receiver_name );

			$address = "";
			foreach ( array( '_shipping_address_1', '_shipping_city' ) as $field ) {
				$address .= get_meta_field( $order_id, $field ) . " ";
			}

			array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

			foreach ( array( '_billing_phone', '_shipping_address_2' ) as $field ) {
				$field_value = get_meta_field( $order_id, $field );
				array_push( $fields, $field_value );
			}
			array_push( $fields, get_meta_field( $order_id, '_payment_method' ) );

			$long_lat = get_long_lat( $client_id, $address );

			// $sort_index = (40-$long_lat[0]) * 1000 + $long_lat[1];
			// get_postmeta_field($order_id, "_shipping_method")
			$zone_id = order_get_zone( $order_id );
			// print "zone id " . $zone_id . "<br/>";
			$zone_order = get_zone_order( $zone_id );
			// print "zone=" . $zone;
			// var_dump($shipping);

			// Day
			$optional_days = sql_query_single_scalar( "SELECT delivery_days FROM wp_woocommerce_shipping_zones WHERE zone_id =" . $zone_id );
			$today         = get_day_letter( date( 'w' ) );
			// print "today: " . $today . "<br/>";
			$choosed_day = $today;

			// Sooner the better.
			// Is in the optional days
			foreach ( explode( ",", $optional_days ) as $aday ) {
				if ( $aday >= $today ) {
					$choosed_day = $aday;
					break;
				}
			}
			array_push( $fields, $choosed_day );

			$zone = order_get_zone( $order_id );

			array_push( $fields, zone_get_name( $zone ) );
			$sort_index = sort_key( $choosed_day, $zone_order, $long_lat );

			array_push( $fields, $sort_index );

			$line = "<tr> " . table_line( $order_id, $fields ) . "</tr>";

			// get_field($order_id, '_shipping_city');
			array_push( $data_lines, array( $sort_index, $line, $order_id ) );
			// print "size = " . sizeof($data_lines) . "<br/>";
		}
	}
	sort( $data_lines );

	$data = "";
	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
		$line = $data_lines[ $i ][1];
		$data .= trim( $line );

		//	$print_url_id = $data_lines[$i][2] . "-" . $print_url_id;
	}
	print $data;

}

function get_day_letter( $day ) {
	switch ( $day ) {
		case 0:
			return 'א';
		case 1:
			return 'ב';
		case 2:
			return 'ג';
		case 3:
			return 'ד';
		case 4:
			return 'ה';
		case 5:
			return 'ו';
	}

	return "Error";
}

function sort_key( $choosed_day, $zone_order, $long_lat ) {
	// $x = sprintf("%.02f", 40 - $long_lat[0]);
	// $y = sprintf("%.02f", $long_lat[1]);

//     $coor = 100 * (40 -$long_lat[0]) + $long_lat[1];

	$sort_index = $choosed_day . " " . $zone_order . " " . $long_lat[0] . " " . $long_lat[1];

	return $sort_index;
}

// print "loop end<br>";


function get_zone_order( $zone_id ) {
	if ( ! is_numeric( $zone_id ) ) {
		print __METHOD__ . " got " . $zone_id . "<br/>";
		die( 1 );
	}
//    print "zone=" . $zone . "<br/>";
//	$shipping = ;
//	// print "shipping: " . $shipping[0] . "<br/>";
//	if (is_string($shipping)){
//		$zone = strtok(substr($shipping, strpos($shipping,"flat_rate") + 10), "\"");
//		// print "order zone=" . $zone . "<br/>";
//	}
//	// mot found shipping method from order.
//	// Take default from client
//	if (zone_get_name($zone) == 'N/A') $zone = get_user_meta($client_id, 'shipping_zone', true);
//	if (! is_numeric($zone)) {
//	    $zone = "00";
//		// print "default zone=" . $zone . "<br/>";
//		return $client_id;
//	}
//
	$sql = "SELECT zone_delivery_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone_id;

//	// print $sql . "<br/>";
	return sprintf( "%02d", sql_query_single_scalar( $sql ) );
}

//print "start legacy<br/>";

function print_legacy() {
	global $conn;

	$site_tools = MultiSite::LocalSiteTools();


	$sql = "SELECT id, client_id FROM im_delivery_legacy " .
	       " WHERE status = 1";

	$result     = mysqli_query( $conn, $sql );
	$data_lines = array();

	if ( $result ) {
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$fields = array();

			array_push( $fields, "המכולת" );

			array_push( $fields, "" ); // order number

			$client_id = $row['client_id'];
			// print "client_id = " .$client_id . "<br/>";
			$ref = $row['id'];

			$user_info = get_userdata( $client_id );

			// display customer name
			$name = $user_info->first_name . " " . $user_info->last_name;

			array_push( $fields, gui_hyperlink( $name, $site_tools . "../../wp-admin/user-edit.php?user_id=" . $client_id ) );

			$receiver_name = "";

			array_push( $fields, $receiver_name );

			$address = "";
			foreach ( array( 'shipping_address_1', 'shipping_city' ) as $field ) {
				$address .= get_user_meta( $client_id, $field )[0] . " ";
			}

			array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

			foreach ( array( 'billing_phone', 'shipping_address_2' ) as $field ) {
				$field_value = get_user_meta( $client_id, $field, true );
				// print $ref . " " . $field_value . "<br/>";
				array_push( $fields, $field_value );
			}

			array_push( $fields, "" ); // Billing method

			$postcode = get_user_meta( $client_id, 'shipping_postcode', true );
//            print $postcode . "<br/>";

			$zone_id = get_zone_from_postcode( $postcode );

			$optional_days = sql_query_single_scalar( "SELECT delivery_days FROM wp_woocommerce_shipping_zones WHERE zone_id =" . $zone_id );
			$today         = get_day_letter( date( 'w' ) );
			// print "today: " . $today . "<br/>";
			$choosed_day = $today;

			// Sooner the better.
			// Is in the optional days
			foreach ( explode( ",", $optional_days ) as $aday ) {
				if ( $aday >= $today ) {
					$choosed_day = $aday;
					break;
				}
			}
			array_push( $fields, $choosed_day );

			// $client_id = $ref;
			$long_lat = get_long_lat( $client_id, $address );
			// var_dump( $long_lat); print "<br/>";


			if ( ! $zone_id ) {
				$zone_id = intval( get_user_meta( $client_id, 'shipping_zone', true ) );
			}
			/// print $zone_id . "<br/>";

			array_push( $fields, zone_get_name( $zone_id ) );

			$zone_order = get_zone_order( $zone_id );

//	    $x = sprintf("%.02f", 40 - $long_lat[0]);
//	    $y = sprintf("%.02f", $long_lat[1]);

			$sort_index = sort_key( $choosed_day, $zone_order, $long_lat );
			// $sort_index = $day . "/" . $zone_order . ":" . $x . "-" . $long_lat[1];
			array_push( $fields, $sort_index );

			$line = "<tr> " . table_line( $ref, $fields ) . "</tr>";

			array_push( $data_lines, array( $sort_index, $line, $ref ) );
		}
	}
	sort( $data_lines );

	$data = "";
	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
		$line = $data_lines[ $i ][1];
		$data .= trim( $line );

		//	$print_url_id = $data_lines[$i][2] . "-" . $print_url_id;
	}
	print $data;
}


//print "done legacy<br/>";


function table_line( $ref, $fields ) {
	//"onclick=\"close_orders()\""
	$row_text = gui_cell( gui_checkbox( "chk_" . $ref, "", "", null ) );

	foreach ( $fields as $field ) // display customer name
	{
		$row_text .= gui_cell( $field );
	}

	return $row_text;
}

function get_payment_name( $method ) {
	switch ( $method ) {
		case "bacs":
			return "העברה";
		case "cheque":
			return "כ. אשראי";
		case "cod":
			return "מזומן";
	}

	return $method;
}

function get_delivery_driver( $order_id ) {
	// print "get_delivery_driver " . $order_id . "<br/>";
	$city = get_meta_field( $order_id, '_shipping_city' );

	$sql = 'SELECT path  FROM im_paths WHERE city = "' . $city . '"';
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );
	$row = mysql_fetch_row( $export );

	$sql = 'SELECT driver FROM im_path_info WHERE id = "' . $row[0] . '"';
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );
	$row = mysql_fetch_row( $export );

	// print "done<br>";

	return $row[0];
}

function get_order_order( $order_id ) {
	print "get_order_order";
	$city = get_meta_field( $order_id, '_shipping_city' );

	$sql = 'SELECT city_order, path FROM im_paths WHERE city = "' . $city . '"';
	// print    $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );
	$row = mysql_fetch_row( $export );
//	print $row[0] + "<br>";
	$city_order = $row[0];
	$path       = $row[1];
	print "done<br/>";

	return 100 * $path + $city_order;
}

function get_long_lat( $client_id, $address ) {
	$long_lat = get_user_meta( $client_id, "long_lat", true );
	// print "llt=" . $long_lat[0]. ":" . $long_lat[1] . "<br/>";

	if ( ! $long_lat or $long_lat[0] == "" ) {
		$long_lat = do_get_long_lat( $address );
		update_user_meta( $client_id, "long_lat", $long_lat );
	}

	return $long_lat;
}

function table_header() {
	$data = "";
	$data .= "<table>";
	$data .= "<tr><td>בחר</td></td><td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר </br>הזמנה</h3></td>";
	$data .= "<td><h3>שם המזמין</h3></td>";
	$data .= "<td><h3>שם המקבל</h3></td>";
	$data .= "<td><h3>כתובת</h3></td>";
	$data .= "<td><h3>טלפון</h3></td>";
	$data .= "<td><h3>כתובת-2</h3></td>";
	// $data .= "<td><h3></h3></td>";
	$data .= "<td><h3>אופן תשלום</h3></td>";
	$data .= "<td><h3>יום</h3></td>";
	$data .= "<td><h3>אזור</h3></td>";
	$data .= "<td><h3>מיקום</h3></td>";
	print $data;
}

function do_get_long_lat( $address ) {
	// print $address . "<br/>";
	$dashed_address = str_replace( " ", "-", $address );

	//print $dashed_address . "<br/>";
	$url = "http://maps.google.com/maps/api/geocode/json?address=" . $dashed_address . "&sensor=false&region=Israel";

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_PROXYPORT, 3128 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	$response = curl_exec( $ch );
	// print $response . " " . $dashed_address . "<br/>";
	curl_close( $ch );
	$response_a = json_decode( $response );
	$lat        = $response_a->results[0]->geometry->location->lat;
	$long       = $response_a->results[0]->geometry->location->lng;

	return array( $lat, $long );
}

?>
</html>
