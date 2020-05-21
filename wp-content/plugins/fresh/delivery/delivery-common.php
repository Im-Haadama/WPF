<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/08/17
 * Time: 12:38
 */
// require_once( "../multi-site/imMulti-site.php" );
//require_once ("../supplies/Supply.php");

require_once( ROOT_DIR . "/focus/Tasklist.php" );
require_once( ROOT_DIR . "/fresh/supplies/Supply.php" );


function print_fresh_category() {
	$list = "";

	$option = SqlQuerySingleScalar( "SELECT option_value FROM wp_options WHERE option_name = 'im_discount_categories'" );
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

	$fields = array();
	array_push( $fields, "supplies" );

	$address = "";

	$supplier_id = supply_get_supplier_id( $id );
	$ref         = gui_hyperlink( $id, "../supplies/supply-get.php?id=" . $id );
	$address     = SqlQuerySingleScalar( "select address from im_suppliers where id = " . $supplier_id );
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
	array_push( $fields, SqlQuerySingleScalar( "select supplier_contact_phone from im_suppliers where id = " . $supplier_id ) );

	array_push( $fields, "" );
//
	array_push( $fields, SqlQuerySingleScalar( "select mission_id from im_supplies where id = " . $id ) );
//
	array_push( $fields, imMultiSite::LocalSiteID() );
	// array_push($fields, get_delivery_id($order_id));


	$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

	// get_field($order_id, '_shipping_city');

	print $line;

}
