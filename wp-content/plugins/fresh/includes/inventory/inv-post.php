<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/06/17
 * Time: 13:39
 */
require_once( '../r-shop_manager.php' );
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . '/niver/gui/sql_table.php' );
require_once( "../catalog/bundles.php" );
require_once( "../orders/orders-common.php" );

$operation = get_param("operation", true);

switch ( $operation ) {
	case "add_waste":
		$prod_ids = $_GET["prod_ids"];
		add_waste( explode( ",", $prod_ids ) );
		return;

	case "in":
		show_in( $_GET["id"] );
		return;

	case "out":
		show_out( $_GET["id"] );
		return;

	case "save_inv":
		$data = get_param_array("data");
		if (save_inv($data)) print "done";
		return;
}

print HeaderText();

switch ($operation)
{
	case "show":
		$debug = false;
		if ( isset( $_GET["supplier_id"] ) ) {
			show_supplier_inventory( $_GET["supplier_id"] );

			return;
		}
		$not_available = get_param("not_available", false, false);
		show_fresh_inventory($not_available);
		break;
}

function show_not_available() {
	$iter = new ProductIterator();

	$iter->iteratePublished();

	$table = array();
	$table["header"] = array("product_name", "current inventory");

	while ( $prod_id = $iter->next() ) {
		$alter = alternatives($prod_id);
		$p = new Product($prod_id);
		// print "checking " . get_product_name($prod_id) . "<br/>";
		if (! count($alter)) {
			$row = array(get_product_name($prod_id), $p->getStock());
			$table[$prod_id] = $row;
		}
	}
	print gui_table_args($table);
}

function show_supplier_inventory( $supplier_id ) {
	$table = array( array( "", "מוצר", "מחיר עלות", "כמות במלאי" ) );

	print gui_header( 1, "מלאי לספק " . get_supplier_name( $supplier_id ) );
	$catalog = new Catalog();
	$display = "";

	$sql = 'SELECT product_name, price, date, pl.id, supplier_product_code, s.factor ' .
	       ' FROM im_supplier_price_list pl ' .
	       ' Join im_suppliers s '
	       . ' where supplier_id = ' . $supplier_id
	       . ' and s.id = pl.supplier_id '
	       . ' order by 1';

	$result = sql_query( $sql );
	while ( $row = sql_fetch_row( $result ) ) {
		$pl_id     = $row[3];
		$link_data = $catalog->GetProdID( $pl_id );
		if ( $link_data ) {
			$prod_id = $link_data[0];
			$line    = product_line( $prod_id, false, false, null, true, $supplier_id );
			array_push( $table, $line );
		}
	}

	$display .= gui_table_args( $table, "table_" . $supplier_id );

	$display .= gui_button( "btn_save_inv" . $supplier_id, "save_inv(" . $supplier_id . ")", "שמור מלאי" );

	print $display;
}

function show_fresh_inventory($not_available = false) {
	$wait = sql_query_single_scalar( "SELECT count(id) FROM wp_posts WHERE post_status IN ('wc-waiting', 'wc-on-hold')" );
	if ( $wait ) {
		print gui_header( 1, "יש הזמנות במצב המתנה!" );
		print "יש לטפל בהן לפני עדכון המלאי";
	}
	print gui_header( 1, "פריטים טריים במלאי" );
	if ($not_available) print gui_header(2, "not available at suppliers");
	$args = [];
	$args["not_available"] = $not_available;
	$args["fresh"] = true;
	$args["add_checkbox"] = true;
	show_inventory($args);
}

function save_inv( $data ) {
	for ( $i = 0; $i < count( $data ); $i += 2 ) {
		$id = $data[ $i ];
		$q  = $data[ $i + 1 ];

		my_log( "set inv " . $data[ $i ] . " " . $data [ $i + 1 ] );
		$p = new Product( $id );
		if (! $p->setStock( $q ))
			return false;
	}
	return true;
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
	$args = array("footer" => false, "links" => array("id" => "../supplies/supply-get.php?id=%s"));
	print GuiTableContent("table", $sql, $args);
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
	$args = array("links" => array("id" => "../delivery/get-delivery.php?id=%s"), "sums" => $sums);
	print GuiTableContent("table", $sql, $args);

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
			$args = array("links" => array("id" => "../delivery/get-delivery.php?id=%s"), "sums" => $sums);
			if ( is_numeric( $sums[1] ) ) {
				$total += $sums[1] * $B->GetQuantity();
			}
		}
		print "סך הכל סופק " . $total . "<br/>";
	}
}

function show_inventory($args) {

	$needed = array();
	// Order::CalculateNeeded( $needed ); // Not sure why
	$args["inventory"] = true;
	print ShowCategoryAll($args);
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

