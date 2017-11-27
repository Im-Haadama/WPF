<?php
require_once( '../r-shop_manager.php' );
// require_once( '../../wp-content/plugins/woocommerce-delivery-notes/woocommerce-delivery-notes.php' );
require_once( '../multi-site/multi-site.php' );
require_once( '../account/account.php' );
$header = $_GET["header"];
//if (isset($_GET["week"])) $week = $_GET["week"];
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
//else print_archive_deliveries($week);
//
//print_legacy();

die( 0 );

function print_deliveries( $edit = false ) {
//    print "start";
//    if (isset($_GET["week"])){
//        $week = $_GET["week"];
//        $sql = "select order_id from im_delivery where date >= '" .$week . "'";
//        print $sql;
//    } else {
	$sql = 'SELECT posts.id'
	       . ' FROM `wp_posts` posts'
	       . ' WHERE `post_status` in (\'wc-awaiting-shipment\', \'wc-processing\')'
	       . ' order by 1';
//    }

	$orders = sql_query_array_scalar( $sql );

	foreach ( $orders as $order ) {
		print_order( $order );
	}

}

function print_order( $order_id )
{
	$site_tools = MultiSite::LocalSiteTools();

	$fields = array();
	array_push( $fields, MultiSite::LocalSiteName() );

	$address = "";

	$client_id     = order_get_customer_id( $order_id );
	$ref           = "<a href=\"" . $site_tools . "/orders/get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a>";
	$address       = order_get_address( $order_id );
	$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
	                 get_meta_field( $order_id, '_shipping_last_name' );
	$shipping2     = get_meta_field( $order_id, '_shipping_address_2', true );
	$mission_id    = order_get_mission_id( $order_id );
	$ref           = $order_id;

	array_push( $fields, $ref );

	array_push( $fields, $client_id );

	array_push( $fields, $receiver_name );

	array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

	array_push( $fields, $shipping2 );

	array_push( $fields, get_user_meta( $client_id, 'billing_phone', true ) );
	$payment_method = get_payment_method_name( $client_id );
	if ( $payment_method <> "מזומן" and $payment_method <> "המחאה" ) {
		$payment_method = "";
	}
	array_push( $fields, $payment_method );

	array_push( $fields, order_get_mission_id( $order_id ));

	array_push( $fields, MultiSite::LocalSiteID() );
	// array_push($fields, get_delivery_id($order_id));


	$line = "<tr> " . table_line( 1, $fields ) . "</tr>";

			// get_field($order_id, '_shipping_city');

	print $line;
}

function sort_key( $zone_order, $long_lat ) {
	// $x = sprintf("%.02f", 40 - $long_lat[0]);
	// $y = sprintf("%.02f", $long_lat[1]);

//     $coor = 100 * (40 -$long_lat[0]) + $long_lat[1];

	$sort_index = $zone_order . " " . $long_lat[0] . " " . $long_lat[1];

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

function print_legacy() {
	global $conn;

	$site_tools = MultiSite::LocalSiteTools();

	$sql = "SELECT id, client_id, mission_id FROM im_delivery_legacy " .
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

			array_push( $fields, $client_id);
			$user_info = get_userdata( $client_id );

			// display customer name
			$name = $user_info->first_name . " " . $user_info->last_name;

			array_push( $fields, gui_hyperlink( $name, $site_tools . "../../wp-admin/user-edit.php?user_id=" . $client_id ) );

			$address = "";
			foreach ( array( 'shipping_address_1', 'shipping_city' ) as $field ) {
				$address .= get_user_meta( $client_id, $field )[0] . " ";
			}

			array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

			foreach ( array( 'shipping_address_2', 'billing_phone' ) as $field ) {
				$field_value = get_user_meta( $client_id, $field, true );
				// print $ref . " " . $field_value . "<br/>";
				array_push( $fields, $field_value );
			}

			array_push( $fields, "" ); // Billing method

			$postcode = get_user_meta( $client_id, 'shipping_postcode', true );
//            print $postcode . "<br/>";

			$zone_id = get_zone_from_postcode( $postcode );

			array_push( $fields, $row['mission_id'] );

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

			$sort_index = sort_key( $zone_order, $long_lat );
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


function table_line( $ref, $fields, $edit = false ) {
	//"onclick=\"close_orders()\""
	$row_text = "";
	if ( $edit ) {
		$row_text = gui_cell( gui_checkbox( "chk_" . $ref, "", "", null ) );
	}

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
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	$sql = 'SELECT driver FROM im_path_info WHERE id = "' . $row[0] . '"';
	// print $sql . "<br>";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	// print "done<br>";

	return $row[0];
}

function get_order_order( $order_id ) {
	print "get_order_order";
	$city = get_meta_field( $order_id, '_shipping_city' );

	$sql = 'SELECT city_order, path FROM im_paths WHERE city = "' . $city . '"';
	// print    $sql . "<br>";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );
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

function table_header( $edit = false ) {
	$data = "";
	$data .= "<table><tr>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר </br>הזמנה</h3></td>";
	$data .= "<td><h3>מספר </br>לקוח</h3></td>";
//	$data .= "<td><h3>שם המזמין</h3></td>";
	$data .= "<td><h3>שם המקבל</h3></td>";
	$data .= "<td><h3>כתובת</h3></td>";
	$data .= "<td><h3>כתובת-2</h3></td>";
	$data .= "<td><h3>טלפון</h3></td>";
	// $data .= "<td><h3></h3></td>";
	$data .= "<td><h3>מזומן/המחאה</h3></td>";
	$data .= "<td><h3>משימה</h3></td>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר משלוח</h3></td>";
	// $data .= "<td><h3>מיקום</h3></td>";
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
