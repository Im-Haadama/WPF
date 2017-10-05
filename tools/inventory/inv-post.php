<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/06/17
 * Time: 13:39
 */
require_once( '../tools_wp_login.php' );
require_once( '../gui/sql_table.php' );

$operation = $_GET["operation"];

switch ( $operation ) {
	case "show":
		show_inventory();
		break;

	case "in":
		show_in( $_GET["id"] );
		break;

	case "out":
		show_out( $_GET["id"] );
		break;

}

function show_in( $prod_id ) {
	print header_text();
	print gui_header( 1, "הספקות לפריט " . get_product_name( $prod_id ) );
	$inventory_in = sql_query_single_scalar( "SELECT inventory_in FROM im_info" );

	$sql = "select supply_id as אספקה, quantity as כמות, supplier_name as ספק " .
	       " from im_supplies_lines sl " .
	       " join im_suppliers sr " .
	       " join im_supplies s " .
	       " where supply_id > " . $inventory_in .
	       " and product_id = " . $prod_id .
	       " and s.supplier = sr.id" .
	       " and sl.supply_id = s.id" .
	       " and s.status <= 5 and sl.status = 1";
	// print $sql;

	// print $sql;
	print table_content( $sql, true, true, array( "../supplies/supply-get.php?id=%s" ) );
}

function show_out( $prod_id ) {
	print header_text();
	print gui_header( 1, "משלוחים לפריט " . get_product_name( $prod_id ) );
	$inventory_out = sql_query_single_scalar( "SELECT inventory_out FROM im_info" );

	$sql = "select delivery_id as משלוח, quantity as כמות, client_from_delivery(delivery_id) as לקוח" . //, supplier_name as לקוח " .
	       " from im_delivery_lines dl " .
	       // " join wp_users u " .
	       " join im_delivery d " .
	       " where delivery_id > " . $inventory_out .
	       " and prod_id = " . $prod_id .
	       // " and s.supplier = sr.id" .
	       " and dl.delivery_id = d.id";

	// print $sql;
	print table_content( $sql, true, true, array( "../delivery/get-delivery.php?id=%s" ) );
}

function show_inventory() {
	global $conn;

	$sql    = "SELECT product_id, q_in FROM i_in";
	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		print "no results " . $sql;
	}

	// $data = "<table>";
	$rows = [];

	array_push( $rows, array( "מוצר", "כמות נרכשה", "כמות סופקה", "מלאי" ) );
	while ( $row = mysqli_fetch_row( $result ) ) {
		$prod_id = $row[0];
		$q_in    = round( $row[1], 1 );
		$q_out   = get_out( $prod_id );
		$q       = round( $q_in - $q_out, 1 );
		if ( $q > 0 ) {
			$line = array(
				get_product_name( $prod_id ),
				gui_hyperlink( $q_in, "inv-post.php?operation=in&id=" . $prod_id ),
				gui_hyperlink( $q_out, "inv-post.php?operation=out&id=" . $prod_id ),
				$q_in - $q_out
			);
			array_push( $rows, $line );
		}
	}

	// $data .= "</table>";
	// $sql = "select product_name as מוצר, round(q, 0) as כמות from i_total where round(q) > 0";
	// print table_content($sql);
	print gui_table( $rows );

	// print $data;
}

function get_out( $prod_id ) {
	$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $prod_id;
	// print $sql;
	$r = sql_query_single_scalar( $sql );
	if ( ! is_numeric( $r ) ) {
		$r = 0;
	}

	return $r;
}