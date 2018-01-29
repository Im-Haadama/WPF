<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/06/17
 * Time: 13:39
 */
require_once( '../r-shop_manager.php' );
require_once( '../gui/sql_table.php' );
require_once( "../catalog/bundles.php" );

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
	$inventory_in = sql_query_single_scalar( "SELECT info_data FROM im_info WHERE info_key = 'inventory_in'" );

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
	$sums = array(
		"סה\"כ",
		array( 0, 'sum_numbers' ),
		'',
	);
	print header_text();
	print gui_header( 1, "משלוחים לפריט " . get_product_name( $prod_id ) );
	$inventory_out = sql_query_single_scalar( "SELECT info_data FROM im_info WHERE info_key = 'inventory_out'" );

	$sql = "select delivery_id as משלוח, quantity as כמות, client_from_delivery(delivery_id) as לקוח" . //, supplier_name as לקוח " .
	       " from im_delivery_lines dl " .
	       // " join wp_users u " .
	       " join im_delivery d " .
	       " where delivery_id > " . $inventory_out .
	       " and prod_id = " . $prod_id .
	       // " and s.supplier = sr.id" .
	       " and dl.delivery_id = d.id";

	// print $sql;
	print table_content( $sql, true, true, array( "../delivery/get-delivery.php?id=%s" ), $sums );

	$sql    = "select id, quantity from im_bundles where prod_id = $prod_id";
	$result = sql_query( $sql );
	$total  = $sums[1][0];


	if ( $result ) {
		$sums = array(
			"סה\"כ",
			array( 0, 'sum_numbers' ),
			'',
		);

		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$b_id = $row["id"];
			$B    = Bundle::createFromDb( $b_id );
			print gui_header( 2, "במארזים " . get_product_name( $b_id ) );

			$sql = "select delivery_id as משלוח, quantity as כמות, client_from_delivery(delivery_id) as לקוח" . //, supplier_name as לקוח " .
			       " from im_delivery_lines dl " .
			       // " join wp_users u " .
			       " join im_delivery d " .
			       " where delivery_id > " . $inventory_out .
			       " and prod_id = " . $b_id .
			       // " and s.supplier = sr.id" .
			       " and dl.delivery_id = d.id";

			// print $sql;
			print table_content( $sql, true, true, array( "../delivery/get-delivery.php?id=%s" ), $sums );
			if ( is_numeric( $sums[1] ) ) {
				$total += $sums[1] * $B->GetQuantity();
			}
		}
		print "סך הכל סופק " . $total . "<br/>";
	}
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
	if ( ! ( $prod_id > 0 ) ) {
		print "Bad usage get_out<br/>";

		return 0;
	}
	$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $prod_id;
	// print $sql;
	$r = sql_query_single_scalar( $sql );
	if ( ! is_numeric( $r ) ) {
		$r = 0;
	}
	$b = Bundle::CreateFromProd( $prod_id );
	if ( $b ) { // We have a bundle
////		print "bundle " . $b- . "<br/>";
//		$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $b->GetProdId();
//		// print $sql;
		// print $prod_id . " " . get_product_name($prod_id) . " " . $b->GetBundleProdId() . "<br/>";
		$r += $b->GetQuantity() * get_out( $b->GetBundleProdId() );
	}

	return $r;
}

