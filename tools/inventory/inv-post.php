<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/06/17
 * Time: 13:39
 */
require_once( '../r-shop_manager.php' );
require_once( ROOT_DIR . '/agla/gui/sql_table.php' );
require_once( "../catalog/bundles.php" );
require_once( "../orders/orders-common.php" );

$operation = $_GET["operation"];

switch ( $operation ) {
	case "show":
		$debug = false;
		if ( isset( $_GET["debug"] ) ) {
			$debug = true;
		}
		$wait = sql_query_single_scalar( "SELECT count(id) FROM wp_posts WHERE post_status IN ('wc-waiting', 'wc-on-hold')" );
		if ( $wait ) {
			print gui_header( 1, "יש הזמנות במצב המתנה!" );
		}
		print gui_header( 1, "פריטים טריים במלאי" );
		show_inventory( false, $debug );
		break;

	case "add_waste":
		$prod_ids = $_GET["prod_ids"];
		add_waste( explode( ",", $prod_ids ) );
		break;

	case "in":
		show_in( $_GET["id"] );
		break;

	case "out":
		show_out( $_GET["id"] );
		break;

	case "save_inv":
		print "start<br/>";
		$data = $_GET["data"];
		save_inv( explode( ",", $data ) );
}

function save_inv( $data ) {
	for ( $i = 0; $i < count( $data ); $i += 2 ) {
		$id = $data[ $i ];
		$q  = $data[ $i + 1 ];

		my_log( "set inv " . $data[ $i ] . " " . $data [ $i + 1 ] );
		$p = new Product( $id );
		$p->setStock( $q );
	}
}

function add_waste( $prod_ids ) {
	$user = sql_query_single_scalar( "SELECT user_id FROM wp_usermeta WHERE meta_key = 'nickname' AND meta_value = 'waste'" );
	if ( is_null( $user ) ) {
		$user = 1;
	}
	$quantities = array();
	foreach ( $prod_ids as $prod_id ) {
		array_push( $quantities, 0 );
	} // Non ordered
	//array_push($quantities, sql_query_single_scalar( "SELECT q FROM i_total WHERE prod_id = " . $prod_id ));

//	var_dump($prod_ids);
//	var_dump($quantities);
	$order_id = create_order( $user, 0, $prod_ids, $quantities, "פחת" );
	// print $order_id;

	$del_id = delivery::CreateDeliveryFromOrder( $order_id, 2 );
	print "del id: " . $del_id;
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
			$B    = Bundle::CreateFromDb( $b_id );
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

function show_inventory( $managed, $debug ) {
	global $conn;

//	$sql    = "SELECT product_id, q_in FROM i_in";
//	$result = mysqli_query( $conn, $sql );
//	if ( ! $result ) {
//		print "no results " . $sql;
//	}
	$needed = array();
	Order::CalculateNeeded( $needed );
	print show_category_all( false, false, true, true );
	// $data = "<table>";
//	$rows = [];

//	array_push( $rows, array( "מוצר", "כמות נרכשה", "כמות סופקה", "מלאי" ) );
//	while ( $row = mysqli_fetch_row( $result ) ) {
//		$prod_id = $row[0];
//		$p       = new Product( $prod_id );
//		if ( $p->getStockManaged() != $managed )
//			continue;
//		$q_in    = round( $row[1], 1 );
//		$q_out   = get_out( $prod_id );
//		$q       = round( $q_in - $q_out, 1 );
//		if ( $q > 0 or $debug ) {
//			$line = array(
//				gui_checkbox( "chk_" . $prod_id, "select"),
//				get_product_name( $prod_id ),
//				gui_hyperlink( $q_in, "inv-post.php?operation=in&id=" . $prod_id ),
//				gui_hyperlink( $q_out, "inv-post.php?operation=out&id=" . $prod_id ),
//				$q_in - $q_out
//			);
//			array_push( $rows, $line );
//		}
//	}
//
//	// $data .= "</table>";
//	// $sql = "select product_name as מוצר, round(q, 0) as כמות from i_total where round(q) > 0";
//	// print table_content($sql);
//	print gui_table( $rows );

	// print $data;
}

function do_get_out( $prod_id ) {
	$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $prod_id;

	// print $sql;
	return sql_query_single_scalar( $sql );

}
function get_out( $prod_id ) {
	if ( ! ( $prod_id > 0 ) ) {
		print "Bad usage get_out<br/>";

		return 0;
	}
	$r = do_get_out( $prod_id );
	$b = Bundle::CreateFromProd( $prod_id );
	if ( $b and $b->GetProdId() > 0 ) { // We have a bundle
		// var_dump($b);
////		print "bundle " . $b- . "<br/>";
//		$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $b->GetProdId();
//		// print $sql;
		// print $prod_id . " " . get_product_name($prod_id) . " " . $b->GetBundleProdId() . "<br/>";
		$r += $b->GetQuantity() * do_get_out( $b->GetBundleProdId() );
	}

	return $r;
}

