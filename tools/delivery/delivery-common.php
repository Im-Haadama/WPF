<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/08/17
 * Time: 12:38
 */
// require_once( "../multi-site/imMulti-site.php" );
//require_once ("../supplies/supplies.php");

require_once( ROOT_DIR . "/tools/tasklist/Tasklist.php" );
require_once( ROOT_DIR . "/tools/supplies/supplies.php" );

class DeliveryFields {
	const
		/// User interface
		line_select = 0,
		/// Product info
		product_name = 1,
		product_id = 2,
		term = 3,
		// Order info
		order_q = 4, // Only display
		order_q_units = 5,
		delivery_q = 6,
		price = 7,
		order_line = 8,
		// Delivery info
		has_vat = 9,
		line_vat = 10,
		delivery_line = 11,
		// Refund info
		refund_q = 12,
		refund_line = 13,
		buy_price = 14,
		line_margin = 15,
		max_fields = 16;
}

$delivery_fields_names = array(
	"chk", // 0
	"nam", // 1
	"pid", // 2
	"ter", // 3
	"orq", // 4
	"oru", // 5
	"deq", // 6
	"prc", // 7
	"orl", // 8
	"hvt", // 9
	"lvt", // 10
	"del", // 11
	"req", // 12
	"ret",  // 13
	"buy", //14
	"mar", // 15
);

$header_fields = array(
	"בחר",
	"פריט",
	"ID",
	"קטגוריה",
	"כמות הוזמן",
	"יחידות הוזמנו",
	"כמות סופק",
	"מחיר",
	"סה\"כ להזמנה",
	"חייב מע\"מ",
	"מע\"מ",
	"סה\"כ",
	"כמות לזיכוי",
	"סה\"כ זיכוי",
	"מחיר עלות",
	"סה\"כ מרווח שורה"
);

class ImDocumentType {
	const order = 1,
		delivery = 2,
		refund = 3;
}

class ImDocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3;     // Load and edit

}

function print_fresh_category() {
	$list = "";

	$option = sql_query_single_scalar( "SELECT option_value FROM wp_options WHERE option_name = 'im_discount_categories'" );
	if ( ! $option ) {
		return;
	}

	$fresh_categ = explode( ",", $option );
	foreach ( $fresh_categ as $categ ) {
		$list .= $categ . ",";
		foreach ( get_term_children( $categ, "product_cat" ) as $child_term_id ) {
			$list .= $child_term_id . ", ";
		}
	}
	print rtrim( $list, ", " );
}

function print_deliveries( $query, $selectable = false ) {
	// print "q= " . $query . "<br/>";
	$data = "";
	$sql = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
	       . ' FROM `wp_posts` posts'
	       . ' WHERE ' . $query;

	$sql .= ' order by 1';

	$orders    = sql_query( $sql );
	$prev_user = - 1;
	while ( $order = sql_fetch_row( $orders ) ) {
		$order_id   = $order[0];
		$is_group   = $order[1];
		$order_user = $order[2];
		if ( ! $is_group ) {
			$data .= print_order( $order_id, $selectable );
			continue;
		} else {
			if ( $order_user != $prev_user ) {
				$data      .= print_order( $order_id, $selectable );
				$prev_user = $order_user;
			}
		}
	}

	return $data;
}

function print_order( $order_id, $selectable = false ) {
	$site_tools = ImMultiSite::LocalSiteTools();

	$fields = array();

	if ( $selectable ) {
		array_push( $fields, gui_checkbox( "chk" . $order_id, "deliveries", true ) );
	}

	array_push( $fields, ImMultiSite::LocalSiteName() );

	$client_id     = order_get_customer_id( $order_id );
	$ref           = "<a href=\"" . $site_tools . "/orders/get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a>";
	$address       = order_get_address( $order_id );
	$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
	                 get_meta_field( $order_id, '_shipping_last_name' );
	$shipping2     = get_meta_field( $order_id, '_shipping_address_2', true );

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

	array_push( $fields, order_get_mission_id( $order_id ) );

	array_push( $fields, ImMultiSite::LocalSiteID() );
	// array_push($fields, get_delivery_id($order_id));


	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	// get_field($order_id, '_shipping_city');

	return $line;
}

function delivery_table_line( $ref, $fields, $edit = false ) {
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

function delivery_table_header( $edit = false ) {
	$data = "";
	$data .= "<table><tr>";
	$data .= "<td><h3>אתר</h3></td>";
	$data .= "<td><h3>מספר </br>/הזמנה<br/>אספקה</h3></td>";
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
	return $data;
}

function print_task( $id ) {
	$fields = array();
	array_push( $fields, "משימות" );

	$ref = gui_hyperlink( $id, "../tasklist/c-get-tasklist.php?id=" . $id );

	array_push( $fields, $ref );

	$T = new Tasklist( $id );

	array_push( $fields, "" ); // client number
	array_push( $fields, $T->getLocationName() ); // name
	array_push( $fields, $T->getLocationAddress() ); // address
	array_push( $fields, $T->getTaskDescription() ); // address 2
	array_push( $fields, "" ); // phone
	array_push( $fields, "" ); // payment
	array_push( $fields, $T->getMissionId() ); // payment
	array_push( $fields, ImMultiSite::LocalSiteID() );

	$line = gui_row( $fields );

	print $line;

}

function print_supply( $id ) {
	if ( ! ( $id > 0 ) ) {
		throw new Exception( "bad id: " . $id );
	}
//	$site_tools = MultiSite::LocalSiteTools();

	$fields = array();
	array_push( $fields, "supplies" );

	$address = "";

	$supplier_id = supply_get_supplier_id( $id );
	$ref         = gui_hyperlink( $id, "../supplies/supply-get.php?id=" . $id );
	$address     = sql_query_single_scalar( "select address from im_suppliers where id = " . $supplier_id );
//	$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
//	                 get_meta_field( $order_id, '_shipping_last_name' );
//	$shipping2     = get_meta_field( $order_id, '_shipping_address_2', true );
//	$mission_id    = order_get_mission_id( $order_id );
//	$ref           = $order_id;
//
	array_push( $fields, $ref );
//
	array_push( $fields, $supplier_id );
//
	array_push( $fields, "<b>איסוף</b> " . get_supplier_name( $supplier_id ) );
//
	array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );
//
	array_push( $fields, "" );
//
	array_push( $fields, sql_query_single_scalar( "select supplier_contact_phone from im_suppliers where id = " . $supplier_id ) );
//	$payment_method = get_payment_method_name( $client_id );
//	if ( $payment_method <> "מזומן" and $payment_method <> "המחאה" ) {
//		$payment_method = "";
//	}
	array_push( $fields, "" );
//
	array_push( $fields, sql_query_single_scalar( "select mission_id from im_supplies where id = " . $id ) );
//
	array_push( $fields, imMultiSite::LocalSiteID() );
	// array_push($fields, get_delivery_id($order_id));


	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	// get_field($order_id, '_shipping_city');

	print $line;

}
