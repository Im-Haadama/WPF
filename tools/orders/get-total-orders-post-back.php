<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/05/16
 * Time: 01:20
 */

require_once( "orders-common.php" );
require_once( "../gui/inputs.php" );
require_once( __ROOT__ . "/tools/supplies/supplies.php" );

$filter_zero = $_GET["filter_zero"];
//$basket_quantities;
$basket_ordered = array();

get_total_orders( $filter_zero );

function get_total_orders( $filter_zero ) {
	global $conn;
	$basket_quantities = array();

	print "first: " . date( "h:i:sa" ) . "<br/>";

// First pass. Read the number of baskets.
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
// Get all ordered items (including baskets) from orders
	$sql = 'select woi.order_item_name as name, sum(woim.meta_value) as q, woi.order_item_id as oii'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim,'
	       . '  wp_woocommerce_order_itemmeta woim1 '
	       . '  where order_id in '
	       . ' (SELECT id FROM `wp_posts`'
	       . " WHERE id > 4390 and `post_status` LIKE '%wc-processing%')"
	       . " and woi.order_item_id = woim.order_item_id and woim.`meta_key` = '_qty'"
	       . " and woi.order_item_id = woim1.order_item_id and woim1.`meta_key` = '_product_id'"
	       . " group by woi.order_item_name order by 1 ";

	$result = mysqli_query( $conn, $sql );

	while ( $row = mysqli_fetch_assoc( $result ) ) {
		$prod_quantity = $row["q"];
		$order_item_id = $row["oii"];
		$prod_id       = get_prod_id( $order_item_id );

		// If item is basket, store the quantity.
		if ( is_basket( $prod_id ) ) {
			$basket_quantities[ $prod_id ] = $prod_quantity;
			my_log( "basket id " . $prod_id . " quan= " . $basket_quantities[ $prod_id ] );
		}
	}

	$data = "<table>";
	$data .= "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>פריט</td>";
	$data .= "<td>כמות נדרשת</td>";
	$data .= "<td>כמות אספקות</td>";
	$data .= "<td>כמות סופקה</td>";
	$data .= "<td>כמות להזמין</td>";
	$data .= "<td>ספק</td>";
	$data .= "<td>מחיר קניה</td>";
	$data .= "<td>מחיר ללקוח</td>";
	$data .= "<td>סהכ מרווח</td>";
	$data .= "</tr>";

	print "second: " . date( "h:i:sa" ) . "<br/>";

// Second pass. Output quantities
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
	$result = mysqli_query( $conn, $sql );
//    var_dump($conn); print "<br/>";
//    var_dump($result); print "<br/>";
	$data_lines = array();

	while ( $row = mysqli_fetch_assoc( $result ) ) {
//        prof_flag("loop start");

		// $prod_name = $row[0];
		$prod_quantity = $row["q"];
		$order_item_id = $row["oii"];

		$prod_id       = get_prod_id( $order_item_id );
		$supplier_name = get_supplier( $prod_id );
		// if ($prod_id == 1678) print "karamba";

		$ordered_products[ $prod_id ] = $prod_quantity;
		// my_log("YYY: " . $basket_quantities[35] . ", " . $basket_quantities[2201]);
		// print "loop4: " .  microtime() . "<br/>";
		$line = table_line( $prod_id, $prod_quantity, $filter_zero );
//        prof_flag("table_line end");

		//print "loop5: " .  microtime() . "<br/>";
		array_push( $data_lines, array( $supplier_name, $line ) );
	}

	print "baskets: " . date( "h:i:sa" ) . "<br/>";

	// Now add basket products, not ordered directly.
	$sql = 'SELECT DISTINCT product_id FROM im_baskets';
	$export = mysql_query( $sql ) or die ( $sql . " Sql error : " . mysql_error() );

	while ( $row = mysql_fetch_row( $export ) ) {
		$prod_id = $row[0];
		//if ($prod_id == 1678) print "karambasket";
		// Check if ordered directly
		//
		if ( ! is_numeric( $ordered_products[ $prod_id ] ) ) {
			$line          = table_line( $prod_id, 0, $filter_zero );
			$supplier_name = get_supplier( $prod_id );
			array_push( $data_lines, array( $supplier_name, $line ) );
		}
	}

	print "sort: " . date( "h:i:sa" ) . "<br/>";

	sort( $data_lines );

	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
		$line = $data_lines[ $i ][1];
		$data .= trim( $line );
	}

	$data = str_replace( "\r", "", $data );

	if ( $data == "" ) {
		$data = "\n(0) Records Found!\n";
	}
	global $total_buy;
	global $total_sale;
	$data .= gui_table( array( array( "", 'סה"כ', "", "", "", "", "", $total_buy, $total_sale ) ) );

	$data .= "</table>";

	print "print: " . date( "h:i:sa" ) . "<br/>";

	print "$data";

//    prof_print();
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
	$export = mysql_query( $sql ) or die ( $sql . " Sql error : " . mysql_error() );

	if ( $row = mysql_fetch_row( $export ) ) {
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
	$sql = 'SELECT basket_id, quantity FROM im_baskets WHERE product_id = ' . $prod_id;
	$export = mysql_query( $sql ) or die ( $sql . "Sql error : " . mysql_error() );
	$quantity         = $prod_quantity;
	$numeric_quantity = $prod_quantity;
	$prod_name        = get_product_name( $prod_id );
	$supplier_name    = get_supplier( $prod_id );
	$supplier_id      = get_supplier_id( $supplier_name );

//    prof_flag("table_line export");

	while ( $row = mysql_fetch_row( $export ) ) {
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

	my_log( __FILE__, "prod_id=" . $prod_id . ": ordered = " . $numeric_quantity . ", in supplies " . $supplied_q );
	// return $line;
	if ( $numeric_quantity > 0 || ! $filter_zero ) {
		return $line;
	}

//    print " done<br/>";
	return "";
}

function q_in( $prod_id ) {
	global $conn;
	$sql = "SELECT q_in FROM i_in WHERE product_id = " . $prod_id;

//   print $sql;

	$result = $conn->query( $sql );
	if ( ! $result ) {
		print $sql . " " . mysqli_error( $conn ) . "<br/>";
	}

	$row = $result->fetch_assoc();

	return round( $row["q_in"], 1 );
}

function q_out( $prod_id ) {
	global $conn;
	$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $prod_id;

//   print $sql;

	$result = $conn->query( $sql );
	if ( ! $result ) {
		print $sql . " " . mysqli_error( $conn ) . "<br/>";
	}

	$row = $result->fetch_assoc();

	return round( $row["q_out"], 1 );
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