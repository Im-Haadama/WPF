<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/05/16
 * Time: 01:20
 */
if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}




require_once(FRESH_INCLUDES . '/wp-config.php');
require_once(FRESH_INCLUDES . '/im-config.php');

require_once( "orders-common.php" );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once( "Order.php" );

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( STORE_DIR . "/fresh/supplies/Supply.php" );

$filter_zero = GetParam("filter_zero", false, false);
$filter_stock = GetParam("filter_stock", false, false);
$supplier_id = GetParam("supplier_id");
$operation = GetParam("operation", true);

//$basket_quantities;
$basket_ordered = array();

switch ( $operation ) {
	case "create_single":
		create_supply_single();
		break;
//	case "show_required":
		break;
	default:
		print $operation . " not handled ";
		die ( 2 );
}

function create_supply_single() {
	$debug = false;
	print header_text( false, false, false );
	$needed_products = array();

	Order::CalculateNeeded( $needed_products );
	$supplies_data = array();

	foreach ( $needed_products as $prod_id => $quantity_array ) {
		$supplied_q = supply_quantity_ordered( $prod_id );
		if ( ! is_numeric( $supplied_q ) ) {
			$supplied_q = 0;
		}
		if ( $debug ) {
			print "prod: " . get_product_name( $prod_id ) . " supplied: " . $supplied_q . ". needed: " .
			      $quantity_array[0] . " " . $quantity_array[1];
		}
		if ( $quantity_array[0] <= $supplied_q ) {
			if ( $debug ) {
				print " already ordered<br/>";
			}
			continue;
		}

		$a = alternatives( $prod_id );
		if ( count( $a ) == 1 ) {
			// Check quantity in progress

			$supplier_id = $a[0]->getSupplierId();
			if ( is_numeric( $supplies_data[ $supplier_id ] ) ) {
				$supplies_data[ $supplier_id ] = array();
			}
			$supplies_data[ $supplier_id ][ $prod_id ] = $quantity_array;
			// Add to supply
			// print " single supplier " . get_supplier_name($supplier_id);
		}
		if ( $debug ) {
			print  "<br/>";
		}
	}

	foreach ( $supplies_data as $supplier_id => $supply_data ) {
		print get_supplier_name( $supplier_id ) . "<br/>";
		$supply_id = create_supply( $supplier_id );
		foreach ( $supplies_data[ $supplier_id ] as $prod_id => $datum ) {
			print get_product_name( $prod_id ) . "q=" . $datum[0] . "u=" . $datum[1] . "<br/>";
			$price = get_buy_price( $prod_id );
			supply_add_line( $supply_id, $prod_id, $datum[0], $price, $datum[1] );
		}
	}
}



function basket_ordered( $basket_id ) {
	global $basket_ordered;

	$val = $basket_ordered[ $basket_id ];
//    print "isset " . isset($basket_ordered[$basket_id]) . "<br/>";
	if ( isset( $basket_ordered[ $basket_id ] ) ) {
		return $val;
	}

	$sql = 'select sum(woim.meta_value)'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim,'
	       . '  wp_woocommerce_order_itemmeta woim1 '
	       . '  where woim1.meta_value = ' . $basket_id . ' and order_id in '
	       . ' (SELECT id FROM `wp_posts`'
	       . " WHERE `post_status` LIKE '%wc-processing%')"
	       . " and woi.order_item_id = woim.order_item_id and woim.`meta_key` = '_qty'"
	       . " and woi.order_item_id = woim1.order_item_id and woim1.`meta_key` = '_product_id'"
	       . " group by woi.order_item_name order by 1 ";
//     print $sql;
	$result = SqlQuery( $sql );

	if ( $row = mysqli_fetch_row( $result ) ) {
		$val = $row[0];
	} else {
		$val = 0;
	}

//    print "val =  " . $val . "<br/>";
	$basket_ordered[ $basket_id ] = $val;

//    print "isset " . isset($basket_ordered[$basket_id]) . "<br/>";

	return $val;
}


function table_line( $prod_id, $prod_quantity, $filter_zero, $history = false ) {
//    print "table_line<br/>";
	global $total_buy;
	global $total_sale;
	global $total_sale_supplier, $total_buy_supplier;

	$loop_count = 0;
	global $mt;
	$mt = microtime( true );
//    prof_flag("start table_line");

	// Check in which baskets we have this product
	$sql              = 'SELECT basket_id, quantity FROM im_baskets WHERE product_id = ' . $prod_id;
	$result           = SqlQuery( $sql );
	$quantity         = $prod_quantity;
	$numeric_quantity = $prod_quantity;
	$prod_name        = get_product_name( $prod_id );
	$supplier_name    = get_supplier( $prod_id );
	$supplier_id      = get_supplier_id( $supplier_name );

//    prof_flag("table_line export");

	while ( $row = mysqli_fetch_row( $result ) ) {
		$basket_id          = $row[0];
		$quantity_in_basket = $row[1];
//        prof_flag("b" . $basket_id);
		$basket_quantity = basket_ordered( $basket_id );
//        prof_flag("c" . $basket_id);

		// my_log("bid = " . $basket_id . "bq[bid] = " . $basket_quantity);
		if ( is_numeric( $basket_quantity ) ) {
			$quantity         .= '+';
			$quantity         .= $basket_quantity * $quantity_in_basket;
			$numeric_quantity += $basket_quantity * $quantity_in_basket;
		}
		// my_log("prod_id = " . $prod_id . " basket_id = " . $basket_id . " quan = " . $quantity . " bq = " . $basket_quantities[$basket_id]);
	}
//    prof_flag("mid 1 table_line");

	$supplied_q = supply_quantity_ordered( $prod_id );

	$line = "<tr><td><input id=\"chk" . $prod_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
	$line .= "<td> " . $prod_name .
	         "</td><td><a href = \"";

	$line .= "get-orders-per-item.php?prod_id=" . $prod_id;

	if ( $history ) {
		$line .= "&history";
	}

	$line .= "\">" . $quantity . "</a></td>";

	$qin  = q_in( $prod_id );
	$qout = q_out( $prod_id );

	$line .= "<td>" . $qin . "</td>";

	$line .= "<td>" . $qout . "</td>";

	$numeric_quantity = $numeric_quantity - $qin + $qout;

	$line .= "<td>" . $numeric_quantity . "</td>";

	$line .= "<td>" . $supplier_name . "</td>";

	// Add margin info
	$buy_price = get_buy_price( $prod_id );
	$line      .= "<td>" . $buy_price . "</td>";

	// TODO: sale price
	$price = get_price( $prod_id );
	$line  .= "<td>" . $price . "</td>";

	if ( $buy_price > 0 ) {
		if ( $price != 0 ) {
			$buy                                 = $numeric_quantity * $buy_price;
			$total_buy                           += $buy;
			$sale                                = $numeric_quantity * $price;
			$total_sale                          += $sale;
			$total_buy_supplier[ $supplier_id ]  += $buy;
			$total_sale_supplier[ $supplier_id ] += $sale;

			$line .= "<td>" . $numeric_quantity * ( $price - $buy_price ) . "</td>";
		} else {
			$line .= "<td></td>";
		}
	} else {
		$line .= "<td></td><td></td>";
	}

	$line .= "</tr>";

	// debug_time("end table_line");

	MyLog( __FILE__, "prod_id=" . $prod_id . ": ordered = " . $numeric_quantity . ", in supplies " . $supplied_q );
	// return $line;
	if ( $numeric_quantity > 0 || ! $filter_zero ) {
		return $line;
	}

//    print " done<br/>";
	return "";
}

function q_in( $prod_id ) {
	$sql = "SELECT q_in FROM i_in WHERE product_id = " . $prod_id;

//   print $sql;

	$q = SqlQuery( $sql );

	return round( $q, 1 );
}

function q_out( $prod_id ) {
	$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $prod_id;

	$q = SqlQuery( $sql );

	return round( $q, 1 );
}

//function delta_time($str, $zero = false)
//{
//    global $cycle_start;
//    if ($zero) {
//        $cycle_start = microtime(true);
//        return;
//    }
//    if (microtime(true) - $cycle_start > 0.1) print $str . " " . (microtime(true) - $cycle_start) . "<br/>";
//}

function prof_flag( $str ) {
	global $prof_timing, $prof_names;
	$prof_timing[] = microtime( true );
	$prof_names[]  = $str;
}

// Call this when you're done and want to see the results
function prof_print() {
	global $prof_timing, $prof_names;
	$size = count( $prof_timing );
	for ( $i = 0; $i < $size - 1; $i ++ ) {
		echo "<b>{$prof_names[$i]}</b><br>";
		echo sprintf( "&nbsp;&nbsp;&nbsp;%f<br>", $prof_timing[ $i + 1 ] - $prof_timing[ $i ] );
	}
	echo "<b>{$prof_names[$size-1]}</b><br>";
}
