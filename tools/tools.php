<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 21:42
 */
define( '__ROOT__', dirname( dirname( __FILE__ ) ) );
require_once( __ROOT__ . "/config.php" );
require_once( __ROOT__ . "/wp-load.php" );

$global_vat = 17;
$link       = mysql_connect( $servername, $username, $password );
mysql_set_charset( 'utf8', $link );
mysql_select_db( $dbname );

$conn = new mysqli( $servername, $username, $password, $dbname );
mysqli_set_charset( $conn, 'utf8' );

// Check connection
if ( $conn->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}

function send_mail( $subject, $to, $message ) {
//    print "start send";
//    print $subject ."<br/>";
//    print $to . "<br/>";
//    print $message . "<br/>";
	$headers   = array();
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "From: עם האדמה <info@im-haadama.co.il>";
	// $headers[] = "Cc: עם האדמה <info@im-haadama.co.il>";
	$headers[] = "Reply-To: Im Haadama <info@im-haadama.co.il>";
	$headers[] = "Subject: {$subject}";
	$headers[] = "X-Mailer: PHP/" . phpversion();
	$headers[] = "Content-type: text/html";

	$rc = mail( $to, $subject, $message, implode( "\r\n", $headers ) );
//    print "sent. RC = " . $rc . "<br/>";
//    print "to = " . $to. "<br/>";
//    print "subject = " . $subject. "<br/>";
	return $rc;
}

function get_vat_percent( $product_id ) {
	global $global_vat;

	$vat = $global_vat;

	$terms = get_the_terms( $product_id, 'product_cat' );

	foreach ( $terms as $term ) {
		foreach ( array( "פרי", "פירות", "ירק", "עלים", "נבטים", "סלים" ) as $no_vat_cat ) {
			if ( strstr( $term->name, $no_vat_cat ) ) {
				$vat = 0;
			}
		}
	}

	return $vat;
}

function print_category_select( $id, $select = false ) {
	$term             = get_term( sql_query_single_scalar( "SELECT suppliers_category FROM im_info" ), "product_cat" );
	$suppliers_father = urldecode( $term->slug );
	//print "father: " . $suppliers_father . "<br/>";
	print '<select id="' . $id . '">';
	$categ_lines = array();

	if ( $select ) {
		$line = '<option value="' . - 1 . '"data-category-id=' . - 1 . '>' . " בחר" . '</option>';
		// $line .= '<option value="' . $new_price . '" data-supplier = ' . $row1[1] . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' . get_supplier_name($row1[1]) . '</option>';
		array_push( $categ_lines, array( " בחר", $line ) );

	}

	$product_categories = get_terms( 'product_cat', array(
		'hide_empty' => false,
	) );
	foreach ( $product_categories as $cat ) {
//		print $cat->term_id . " " . "<br/>";
		$parents = explode( ",", get_category_parents( $cat->term_id, false, ',' ) );
		//	var_dump($parents); print "<br/>";
		if ( in_array( $suppliers_father, $parents ) ) {
			continue;
		}

		$line = '<option value="' . $cat->slug . '"data-category-id=' . $cat->term_id . '>' . $cat->name . '</option>';
		// $line .= '<option value="' . $new_price . '" data-supplier = ' . $row1[1] . ' data-supplier-price = ' . $supplier_price . '>' . $new_price . ' ' . get_supplier_name($row1[1]) . '</option>';
		array_push( $categ_lines, array( $cat->name, $line ) );
	}
	sort( $categ_lines );
	foreach ( $categ_lines as $line ) {
		print $line[1];
	}
	print '</select>';
}

// Logging
function my_log( $msg, $title = '' ) {
	$error_file = __ROOT__ . '/logs/php_error.log';
//    print $error_file;
	$date = date( 'd.m.Y h:i:s' );
	$msg  = print_r( $msg, true );
	$log  = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}

function uptime_log( $msg, $title = '' ) {
	$error_file = __ROOT__ . '/logs/uptime.log';
	$date       = date( 'd.m.Y h:i:s' );
	$msg        = print_r( $msg, true );
	$log        = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}


function calculate_price( $price, $supplier ) {
	global $conn;
	if ( ! is_numeric( $supplier ) ) {
		print "Mush sent number value for supplier. " . $supplier . " was sent";
		die( 2 );
	}
	$sql    = "SELECT factor FROM im_suppliers WHERE id = " . $supplier;
	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$row    = mysqli_fetch_assoc( $result );
	$factor = $row["factor"];

	if ( is_numeric( $factor ) ) {
		if ( $price > 10 ) {
			$factor = $factor * 0.8;
		}

		return round( $price * ( 100 + $factor ) / 100, 1 );
	}

	return 0;

//    switch ($supplier)
//    {
//        // Usual - 35%
//        // Collecting +10%
//        case 100001: // self
//            $new_price = round($price * 1.35, 1);
//            break;
//        case 100003: // Misc
//            $new_price = round($price * 1.4, 1);
//            break;
//        case 100004: // amir be yehuda
//            $new_price = round($price * 1.5,1);
//            break;
//        case 100006: // hamakolet
//            $new_price = round($price,1);
//            break;
//        case 100005: // yevulei bar
//            $new_price = round($price * 1.45,1);
//            break;
//        case 100008: // Samar
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100009: // RAMA
//            $new_price = round($price * 1.17 * 1.2, 1);
//            break;
//        case 100010: // hakselberg
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100016: // Sadot
//            $new_price = round($price * 1.35,1);
//            break;
//        case 100018: // Mahsan
//            $new_price = round($price * 1.3,1);
//            break;
//        case 100020: // Kesem
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100021: // Udi
//            $new_price = round($price * 1.6,1);
//            break;
//        case 100022: // Snir
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100023: // Yaara
//            $new_price = round($price * 1.5,1);
//            break;
//        case 100024: // Ohad
//            $new_price = round($price * 1.5,1);
//            break;
//        default:
//            $new_price = round($price,1);
//    }
//    return $new_price;
}

// Postmeta table
function get_postmeta_field( $post_id, $field_name ) {
	// print "get_postmeta: " . date("h:i:sa") . "<br/>";

	$link = $GLOBALS["glink"];

	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $post_id
	       . " AND meta_key = '" . $field_name . "'";

	// print $sql;
	if ( $export = mysql_query( $sql ) ) {
		$row = mysql_fetch_row( $export );

		// print "get_postmeta done: " . date("h:i:sa") . "<br/>";

		return $row[0];
	}

	return "";
}

function sql_query( $sql ) {
	global $conn;

	return mysqli_query( $conn, $sql );
}

function sql_query_array( $sql ) {
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	$rows = array();
	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $rows, $row );
	}

	return $rows;
}

// Good for sql that return just one value
function sql_query_single_scalar( $sql ) {
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	$arr = mysqli_fetch_row( $result );

	return $arr[0];
}

// Good for sql that returns array of one value
function sql_query_array_scalar( $sql ) {
	$arr    = array();
	$result = sql_query( $sql );
	if ( ! $result ) {
		sql_error( $sql );

		return "Error";
	}
	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $arr, $row[0] );
	}

	return $arr;
}

// Good fom sql that returns one record (an array is returned)
function sql_query_single( $sql ) {
	$result = sql_query( $sql );
	if ( $result ) {
		return mysqli_fetch_row( $result );
	}
	sql_error( $sql );

	return null;
}

function sql_query_single_assoc( $sql ) {
	$result = sql_query( $sql );
	if ( $result ) {
		return mysqli_fetch_assoc( $result );
	}
	sql_error( $sql );

	return null;
}

function get_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_price' );
}

function get_sale_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_sale_price' );
}

// get_sale_price
function get_buy_price( $prod_id ) {
	return get_postmeta_field( $prod_id, 'buy_price' );
}

function get_supplier_id( $supplier_name ) {
	$sql_i = 'SELECT id FROM im_suppliers WHERE supplier_name = \'' . $supplier_name . '\'';
	$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
	$row_i = mysql_fetch_row( $export_i );

	return $row_i[0];
}

function pricelist_get_price( $prod_id ) {
	// my_log("prod_id = " . $prod_id);
	if ( ! ( $prod_id > 0 ) ) {
		print "missing prod_id " . $prod_id . "<br/>";
		die ( 1 );
	}
	$supplier_id = get_supplier_id( get_postmeta_field( $prod_id, "supplier_name" ) );

	$sql_i = 'SELECT price FROM im_supplier_price_list WHERE supplier_id = \'' . $supplier_id . '\'' .
	         ' AND product_name IN (SELECT supplier_product_name FROM im_supplier_mapping WHERE product_id = ' . $prod_id . ')';

	$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
	$row_i = mysql_fetch_row( $export_i );

	// my_log("price = " . $row_i[0]);
	return $row_i[0];
}

function set_post_meta_field( $post_id, $field_name, $field_value ) {
	$link = $GLOBALS["glink"];
//    my_log("add_post_meta(" . $post_id . ", " . $field_name . ", " . $field_value . ");" , __FILE__);
	if ( ! add_post_meta( $post_id, $field_name, $field_value, true ) ) {
		update_post_meta( $post_id, $field_name, $field_value );
	}
	// my_log("Error: can't add meta. Post_id=" . $post_id . "Field_name=" . $field_name . "Field_value=" . $field_value, __FILE__);
}

// IM_Delivery table
function get_order_id( $delivery_id ) {
	if ( is_numeric( intval( $delivery_id ) ) ) {
		$sql_i = 'SELECT order_id FROM im_delivery WHERE id = ' . $delivery_id;
		$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
		$row_i = mysql_fetch_row( $export_i );

		return $row_i[0];
	} else {
		print "Must send a number to get_order_id!";
		print $delivery_id;

		return 0;
	}
}

function get_delivery_total( $delivery_id ) {
	if ( is_numeric( intval( $delivery_id ) ) ) {
		$sql_i = 'SELECT total FROM im_delivery WHERE id = ' . $delivery_id;
//        my_log(__METHOD__ . ": " . $sql_i, __FILE__);
		if ( $export_i = mysql_query( $sql_i ) ) {
			$row_i = mysql_fetch_row( $export_i );
		}

		return $row_i[0];
	} else {
		print "Must send a number to get_order_id!";
		print $delivery_id;

		return 0;
	}
}

function get_delivery_vat( $delivery_id ) {
	if ( is_numeric( intval( $delivery_id ) ) ) {
		$sql_i = 'SELECT vat FROM im_delivery WHERE id = ' . $delivery_id;
//        my_log(__METHOD__ . ": " . $sql_i, __FILE__);
		if ( $export_i = mysql_query( $sql_i ) ) {
			$row_i = mysql_fetch_row( $export_i );
		}

		return $row_i[0];
	} else {
		print "Must send a number to get_order_id!";
		print $delivery_id;

		return 0;
	}
}

function get_delivery_id( $order_id ) {
	if ( is_numeric( $order_id ) ) {
		$sql_i = 'SELECT id FROM im_delivery WHERE order_id = ' . $order_id;
		$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
		$row_i = mysql_fetch_row( $export_i );

		return $row_i[0];
	} else {
		print "Must send a number to get_delivery_id!";

		return 0;
	}
}

function get_supplier_name( $supplier_id ) {
	// my_log("sid=" . $supplier_id);
	if ( is_numeric( $supplier_id ) ) {
		$sql_i = 'SELECT supplier_name FROM im_suppliers WHERE id = ' . $supplier_id;
		$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
		$row_i = mysql_fetch_row( $export_i );

//        my_log("name=" . $row_i[0]);

		return $row_i[0];
	} else {
		print "Must send a number to get_supplier_name! " . $supplier_id;

		return 0;
	}
}

function get_supply_status_name( $supplier_id ) {
	if ( is_numeric( $supplier_id ) ) {
		$sql_i = 'SELECT status FROM im_supplies WHERE id = ' . $supplier_id;
		$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
		$row_i = mysql_fetch_row( $export_i );
		// Supply status: 1 = new, 3 = sent, 5 = close, 9 = delete
		$status_names = array( "חדש", "", "נשלח", "", "בוצע", "", "", "", "נמחק" );

		return $status_names[ $row_i[0] - 1 ];
	} else {
		return "לא ידוע";
	}
}

function get_supplier( $prod_id ) {
	return get_postmeta_field( $prod_id, "supplier_name" );
}

function get_customer_id_by_order_id( $order_id ) {
	return get_postmeta_field( $order_id, "_customer_user" );
}

function get_customer_by_order_id( $order_id ) {
	$first_name = get_postmeta_field( $order_id, '_billing_first_name' );
	$last_name  = get_postmeta_field( $order_id, '_billing_last_name' );
	if ( $first_name == "" and $last_name == "" ) {
		$first_name = get_postmeta_field( $order_id, '_shipping_first_name' );
		$last_name  = get_postmeta_field( $order_id, '_shipping_last_name' );
	}

	return $first_name . " " . $last_name;
}

function set_vat( $prod_ids, $vat_rate ) {
	$debug_string = "set_vat: " . implode( ", ", $prod_ids ) . " rate = " . $vat_rate;
//    my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $prod ) {
//        my_log("set vat " . $prod, __FILE__);
		set_post_meta_field( $prod, "vat_percent", $vat_rate );
	}
}

function set_supplier_name( $prod_ids, $supplier_name ) {
	// my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $prod ) {
//        my_log("set supplier " . $prod, __FILE__);
		set_post_meta_field( $prod, "supplier_name", $supplier_name );
	}
}

function get_prod_id( $order_item_id ) {
//    print "get_prod_id: " . date("h:i:sa");

	$sql2 = 'select woim.meta_value'
	        . ' from wp_woocommerce_order_itemmeta woim'
	        . ' where woim.order_item_id = ' . $order_item_id . ' and woim.`meta_key` = \'_product_id\''
	        . ' ';
	//print $sql2;

	$export2 = mysql_query( $sql2 ) or die ( "Sql error : " . mysql_error() );

//    $fields = mysql_num_fields($export2);

	$row2 = mysql_fetch_row( $export2 );

//    print " done<br/>";
	return $row2[0];
}

function get_product_id_by_name( $product_name ) {
	global $conn;
	$sql = "SELECT id FROM im_products WHERE post_title = '" . $product_name . "'";
	// $sql = "SELECT id FROM im_products where post_title like '" . $product_name . "%'";
//    print $sql;
	$result = mysqli_query( $conn, $sql );
	$row    = mysqli_fetch_assoc( $result );
//    var_dump($row);
//    print $row["ID"];
	return $row["ID"];
}

function get_product_name( $product_id ) {
	$sql = 'SELECT post_title FROM wp_posts WHERE id = ' . $product_id;
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$row = mysql_fetch_row( $export );

	return $row[0];
}

function get_product_parent( $prod_id ) {
	return sql_query_single_scalar( "SELECT post_parent FROM wp_posts WHERE id = " . $prod_id );
}

function client_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_price' );
}

function siton_price( $prod_id ) {
	$price = client_price( $prod_id );
	// my_log (__FILE__, "prod id = " . $prod_id . " price = " . $price);

//    $supplier = get_postmeta_field($prod_id, 'supplier_name');
	// my_log ("supplier = " . $supplier);
	$buy_price = get_buy_price( $prod_id );
	// my_log("buy price = " . $buy_price);
	$price = round( $buy_price * 1.15, 1 );

//    switch ($supplier)
//    {
//        case "עם האדמה":
//            $price = round($price / 1.4 * 1.1, 1);
//            break;
//        case "יבולי בר":
//        case "זינגר":
//        case "אמיר בן יהודה":
//        case "משק שש":
//        default:
//            $buy_price = get_buy_price($prod_id);
//            // my_log("buy price = " . $buy_price);
//            $price = round($buy_price * 1.1, 1);
//
//    }
	// print "siton: buy" . $buy_price . " " . get_product_name($prod_id) . " " . $price . "<br/>";
	return min( $price, get_price( $prod_id ) );

	return $price;
}

function is_admin_user() {
	$user    = new WP_User( wp_get_current_user() );
	$manager = false;
	if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role ) {
			if ( $role == 'administrator' or $role == 'shop_manager' ) {
				$manager = true;
			}
		}
	}

	return $manager;
}

function is_basket( $basket_id ) {
	// my_log(__METHOD__, __FILE__);
	$sql = 'SELECT count(product_id) FROM im_baskets WHERE basket_id = ' . $basket_id;
	/// print $sql;

	// my_log(__METHOD__, $sql);

	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

	return $row[0] > 0;
}

function is_bundle( $prod_id ) {
	// my_log(__METHOD__, __FILE__);
	$sql = 'SELECT count(bundle_prod_id) FROM im_bundles WHERE bundle_prod_id = ' . $prod_id;
	// my_log(__METHOD__, $sql);

	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

	return $row[0] > 0;
}

function is_order( $id ) {
//    my_log(__METHOD__, __FILE__);
	$sql = 'SELECT post_type FROM wp_posts WHERE id = ' . $id;
//    my_log(__METHOD__, $sql);

	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

	return ( $row[0] == 'shop_order' );
}

function sql_error( $sql ) {
	global $conn;
	$message = "Error: sql = `" . $sql . "`. Sql error : " . mysqli_error( $conn );
	my_log( $message );
	print $message . "<br/>";
}

function get_basket_date( $basket_id ) {
	$sql = 'SELECT max(date) FROM im_baskets WHERE basket_id = ' . $basket_id;

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$row = mysql_fetch_row( $export );

	return substr( $row[0], 0, 10 );
}

function get_basket_content( $basket_id ) {
	// t ;

	$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
	       ' ORDER BY 3';

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$basket_content = "";

	while ( $row = mysql_fetch_row( $export ) ) {
		$prod_id  = $row[0];
		$quantity = $row[1];

		if ( $quantity <> 1 ) {
			$basket_content .= $quantity . " ";
		}
		$basket_content .= get_product_name( $prod_id ) . ", ";
	}

	return chop( $basket_content, ", " ) . ".";
}

function get_basket_content_array( $basket_id ) {
	$result = array();

	$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
	       ' ORDER BY 3';

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$basket_content = "";

	while ( $row = mysql_fetch_row( $export ) ) {
		$prod_id            = $row[0];
		$quantity           = $row[1];
		$result[ $prod_id ] = $quantity;
	}

	return $result;
}


function debug_time( $message, $previous_time ) {
	$diff  = microtime( true ) - $previous_time;
	$sec   = intval( $diff );
	$micro = $diff - $sec;
	print "<p dir=\"ltr\"> " . $message . " " . $sec . " sec " . $micro . "</p>";

	return microtime( true );
}

function get_site_tools_url( $site_id ) {
	global $conn;

	$sql = "SELECT tools_url FROM im_multisite " .
	       " WHERE id = " . $site_id;

	// print $sql;

	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$row = mysqli_fetch_row( $result );

	return $row[0];
}


function print_select_supplier( $id, $source ) {
	print "<select id=\"" . $id . "\"";
	if ( $source ) {
		print "onclick=\"change_supplier();\"";
	}
	print ">";

	$sql1 = 'SELECT id, supplier_name, site_id FROM im_suppliers ORDER BY 2';

	// Get line options
	$export1 = mysql_query( $sql1 );
	while ( $row1 = mysql_fetch_row( $export1 ) ) {
		print "<option value = \"" . $row1[0] . "\" ";
		$site_id = $row1[2];
		if ( is_numeric( $site_id ) ) {
			print " data-site-id=\"" . $site_id . "\"";
			print " data-tools-url-id=\"" . get_site_tools_url( $site_id ) . "\"";
		}
		print "> " . $row1[1] . "</option>";
	}

	print "</select>";
}

function get_minimum_order() {
	global $woocommerce;
	global $conn;

	$value = 85;

	$country  = $woocommerce->customer->get_shipping_country();
	$state    = $woocommerce->customer->get_shipping_state();
	$postcode = $woocommerce->customer->get_shipping_postcode();
//    my_log("country " . $country);
//    my_log("state " . $state);
//    my_log("post code " . $postcode);
//    $package = WC()->cart->get_shipping_packages();
//    ob_start();
//    var_dump($package);
//    $result = ob_get_clean();
//    my_log ($result);
	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
//            'state'    => $state,
			'postcode' => $postcode,
		),
	) );
//    my_log ("zone_id = " . $zone1->get_id());

	$sql = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone1->get_id();
//    my_log($sql);
	$result = mysqli_query( $conn, $sql );
	$row    = mysqli_fetch_assoc( $result );
//    my_log($row["min_order"]);

	if ( is_numeric( $row["min_order"] ) ) {
		$value = $row["min_order"];
	}

	return $value;
}

function order_get_zone( $order_id ) {
//	print "order id = " . $order_id . "<br/>";
	my_log( __METHOD__ . " order_id " . $order_id );
	$country = get_postmeta_field( $order_id, '_shipping_country' );
	if ( strlen( $country ) < 2 ) {
		$country = "IL";
	}
	// print "country = " . $country . "<br/>";

	$postcode = get_postmeta_field( $order_id, '_shipping_postcode' );;
	my_log( "postcode = " . $postcode );
	// print "postcode = " . $postcode . "<br/>";

	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
//            'state'    => $state,
			'postcode' => $postcode,
		),
	) )->get_id();
	my_log( "zone: " . $zone1 );
//	print $zone1;

	if ( zone_get_name( $zone1 ) != 'N/A' ) {
		return $zone1;
	}

	$client_id = get_customer_id_by_order_id( $order_id );

	$client_shipping_zone = get_user_meta( $client_id, 'shipping_zone', true );

	if ( strlen( $client_shipping_zone ) > 1 ) {
		return $client_shipping_zone;
	}

	return 0;
}

function zone_get_name( $id ) {
	return sql_query_single_scalar( "SELECT zone_name FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $id );
}


function sunday( $date ) {
	$datetime = new DateTime( $date );
	$interval = new DateInterval( "P" . $datetime->format( "w" ) . "D" );
	$datetime->sub( $interval );

	return $datetime;
}

function get_week( $str_date ) {
	$s = sunday( $str_date );

	return $s->format( 'Y-m-j' );
	// var_dump($s);
	// $d = DateTime::createFromFormat("Y-m-j", $str_date);

	// sunday($d)->format("Y-m-j");
}

function handle_sql_error( $sql ) {
	global $conn;

	print $sql . "<br/>";
	print mysqli_error( $conn );
	die( 1 );
}

function get_meta_field( $post_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $post_id
	       . " AND meta_key = '" . $field_name . "'";
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );
	$row = mysql_fetch_row( $export );

//	print $row[0] + "<br>";
	return $row[0];
}

function get_last_order( $user_id ) {
	global $conn;

	// get last order id
	$sql = " SELECT max(meta.post_id) " .
	       " FROM `wp_posts` posts, wp_postmeta meta" .
	       " where meta.meta_key = '_customer_user'" .
	       " and meta.meta_value = " . $user_id .
	       " and meta.post_id = posts.ID";

	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$row      = mysqli_fetch_row( $result );
	$order_id = $row[0];

	return $order_id;
}

function get_logo_url() {
	global $logo_url;

	return $logo_url;
}

function header_text( $print_logo = true, $close_header = true ) {
	global $business_info;
	global $logo_url;

	$text = '<html dir="rtl">';
	$text .= '<head>';
	$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
	$text .= '<title>';
	$text .= $business_info;
	$text .= '</title>';
	$text .= '<p style="text-align:center;">';
	if ( $print_logo ) {
		$text .= '<img src=' . $logo_url . '>';
	}
	$text .= '</p>';
	if ( $close_header ) {
		$text .= '</head>';
	}

	return $text;
}

function multisite_map_get_remote( $prod_id, $remote_site_id ) {
	$sql = "SELECT local_prod_id FROM im_multisite_map WHERE remote_prod_id = " . $prod_id .
	       " AND remote_site_id = " . $remote_site_id;

	return sql_query_single_scalar( $sql );
}

function print_page_header( $display_logo ) {
	print '<html dir="rtl">
    <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>תוצרת טבעית - עם האדמה</title>';
	if ( $display_logo ) {
		print '<center><img src="http://store.im-haadama.co.il/wp-content/uploads/2014/11/cropped-imadama-logo-7x170.jpg"></center>';
	}
	print '</head>';
}

function get_user_name( $id ) {
//    var_dump(get_user_meta($id, 'first_name'));
	return get_user_meta( $id, 'first_name' )[0] . " " . get_user_meta( $id, 'last_name' )[0];

}

function get_product_variations( $prod_id ) {
	$vars = array();

	$args       = array(
		'post_type'   => 'product_variation',
		'post_status' => 'publish',
		'numberposts' => - 1,
		'orderby'     => 'menu_order',
		'order'       => 'asc',
		'post_parent' => $prod_id // $post->ID
	);
	$variations = get_posts( $args );

	foreach ( $variations as $v ) {
		array_push( $vars, $v->ID );
	}

	return $vars;
}

?>