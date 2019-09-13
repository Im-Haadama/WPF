<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 19/10/15
 * Time: 22:25
 */

require_once( '../r-shop_manager.php' );
require_once( 'orders-common.php' );
?>

<html dir="rtl">
<header>
</header>

<?php

$total_margin;

function table_line( $prod_name, $prod_id, $prod_quantity, $supplier_name, $basket_count ) {
	global $total_margin;

	$prod_quantity_number = $prod_quantity;
	// Check in which baskets we have this product
	$sql           = 'SELECT basket_id FROM im_baskets WHERE product_id = ' . $prod_id;
	$result        = sql_query( $sql );
	$quantity      = $prod_quantity;
	$prod_name     = get_product_name( $prod_id );
	$supplier_name = get_postmeta_field( $prod_id, "supplier_name" );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$basket_id = $row[0];
		if ( is_numeric( $basket_count[ $basket_id ] ) ) {
			$quantity .= '+' . $basket_count[ $basket_id ];
		}
	}
	$line = "<td> " . $prod_name .
	        "</td><td><a href = \"get-orders-per-item.php?prod_id=" . $prod_id . "\">" . $prod_quantity . "</a></td>";

	$line .= "<td>" . $supplier_name . "</td>";

	$buy_price = get_postmeta_field( $prod_id, "buy_price" );
	if ( $buy_price == 0 ) // backward compatible: if no buy price, go with mapping to the price list
	{
		$buy_price = get_buy_price( $prod_id );
	}
	$line .= "<td>" . $buy_price . "</td>";

	$price = get_postmeta_field( $prod_id, '_regular_price' );
	$line  .= "<td>" . $price . "</td>";

	if ( $buy_price > 0 ) {
		if ( $price != 0 ) {
			$margin       = $prod_quantity_number * ( $price - $buy_price );
			$total_margin += $margin;

			$line .= "<td>" . $margin . "</td>";
		} else {
			$line .= "<td></td>";
		}
	} else {
		$line .= "<td></td><td></td>";
	}

	return $line;
}

function get_field( $order_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $order_id
	       . " AND meta_key = '" . $field_name . "'";
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysqli_fetch_row( $result );

//	print $row[0] + "<br>";
	return $row[0];
}

// Get basket components
$sql = 'select bk.product_id, wp.post_title'
       . ' from im_baskets bk, wp_posts wp'
       . ' where bk.product_id = wp.id';

$basket_products = array();
$basket_ids      = array();
$result          = sql_query( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	array_push( $basket_products, array( $row[0], $row[1], 0 ) );
	array_push( $basket_ids, $row[0] );
}

$basket_count = orders_item_count( 35 );

$data = "<table>";
$data .= "<tr><td><h3>מספר </br> הזמנה</h3></td>";

$sql = 'select woi.order_item_name, sum(woim.meta_value), woi.order_item_id'
       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim,'
       . '  wp_woocommerce_order_itemmeta woim1 '
       . '  where order_id in '
       . ' (SELECT id FROM `wp_posts`'
       . " WHERE `post_status` LIKE '%wc-processing%')"
       . " and woi.order_item_id = woim.order_item_id and woim.`meta_key` = '_qty'"
       . " and woi.order_item_id = woim1.order_item_id and woim1.`meta_key` = '_product_id'"
       . " group by woi.order_item_name order by 1 ";

$result = sql_query( $sql );

$fields = mysqli_num_fields( $result );

$data = "<table>";
$data .= "<tr>";
$data .= "<td>פריט</td>";
$data .= "<td>כמות</td>";
$data .= "<td>ספק</td>";
$data .= "<td>מחיר קניה</td>";
$data .= "<td>מחיר מכירה</td>";
$data .= "<td>סהכ</td>";

$data .= "</tr>";

$total        = 0;
$total_margin = 0;
$data_lines   = array();

while ( $row = mysqli_fetch_row( $result ) ) {
	// $line = '';
	$prod_name     = $row[0];
	$prod_quantity = $row[1];
	$order_item_id = $row[2];
	$prod_id       = get_prod_id( $order_item_id );
	$key           = array_search( $prod_id, $basket_ids );
	if ( is_numeric( $key ) ) {
		$basket_products[ $key ][2] ++;
	}
	$supplier_name = get_postmeta_field( $prod_id, "supplier_name" );
	$line          = delivery_table_line( $prod_name, $prod_id, $prod_quantity, $supplier_name, $basket_count );
	array_push( $data_lines, array( $supplier_name, $line ) );
}

// Now add basket products, not order directly.
for ( $i = 0; $i < count( $basket_products ); $i ++ ) {
	if ( $basket_products[ $i ][2] == 0 ) { // Product not ordered directly
		$prod_id       = $basket_products[ $i ][0];
		$prod_name     = $basket_products[ $i ][1];
		$supplier_name = get_postmeta_field( $prod_id, "supplier_name" );
		$line          = delivery_table_line( $prod_name, $prod_id, 0, $supplier_name, $basket_count );
		array_push( $data_lines, array( $supplier_name, $line ) );
	}
}
sort( $data_lines );

for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
	$line = $data_lines[ $i ][1];
	$data .= "<tr> " . trim( $line ) . "</tr>";
}

$data = str_replace( "\r", "", $data );

if ( $data == "" ) {
	$data = "\n(0) Records Found!\n";
}

print "<center><h1>
הוצאות, הכנסות</h1></center>";

$data .= "</table>";

print "$data";

print "total margin: " . $total_margin;

?>
</html>
