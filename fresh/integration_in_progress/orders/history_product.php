<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/07/16
 * Time: 23:23
 */

require_once( 'get-total-orders-post.php' );
history_total_orders();

function history_total_orders() {
	require 'orders-common.php';
	require_once( '../supplies/Supply.php' );

	$basket_quantities = array();
	// $total_buy_supplier = array();
	// $total_sale_supplier = array();
	// $total_buy;
	// $total_sale;

	$sql = 'select woi.order_item_name, sum(woim.meta_value), woi.order_item_id'
	       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim,'
	       . '  wp_woocommerce_order_itemmeta woim1 '
	       . '  where order_id in '
	       . ' (SELECT id FROM `wp_posts`'
	       . " WHERE `post_status` LIKE '%wc-%')"
	       . " and woi.order_item_id = woim.order_item_id and woim.`meta_key` = '_qty'"
	       . " and woi.order_item_id = woim1.order_item_id and woim1.`meta_key` = '_product_id'"
	       . " group by woi.order_item_name order by 1 ";

	$result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$prod_name     = $row[0];
		$prod_quantity = $row[1];
		$order_item_id = $row[2];
		$prod_id       = get_prod_id( $order_item_id );

		// If item is basket, store the quantity.
		if ( is_basket( $prod_id ) ) {
			$basket_quantities[ $prod_id ] = $prod_quantity;
			MyLog( "basket id " . $prod_id . " quan= " . $basket_quantities[ $prod_id ] );
		}
	}

	$data = "<table>";
	$data .= "<tr>";
	$data .= "<td>בחר</td>";
	$data .= "<td>פריט</td>";
	$data .= "<td>כמות</td>";
	$data .= "<td>ספק</td>";
	$data .= "<td>מחיר קניה</td>";
	$data .= "<td>מחיר ללקוח</td>";
	$data .= "<td>סהכ מרווח</td>";
	$data .= "</tr>";

// Second pass. Output quantities
// -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
	$result     = sql_query( $sql );
	$data_lines = array();

	while ( $row = mysqli_fetch_row( $result ) ) {
		$prod_name     = $row[0];
		$prod_quantity = $row[1];
		$order_item_id = $row[2];
		$prod_id       = get_prod_id( $order_item_id );
		$supplier_name = get_supplier( $prod_id );

		$ordered_products[ $prod_id ] = $prod_quantity;
		// my_log("YYY: " . $basket_quantities[35] . ", " . $basket_quantities[2201]);
		$line = delivery_table_line( $prod_id, $prod_quantity, false, true );
		array_push( $data_lines, array( $supplier_name, $line ) );
	}

//// Now add basket products, not ordered directly.
//    $sql = 'select distinct product_id from im_baskets';
//
//    while ($row = mysql_fetch_row($export))
//    {
//        $prod_id = $row[0];
//        // Check if ordered directly
//        //
//        if (! is_numeric($ordered_products[$prod_id]))
//        {
//            $line = table_line($prod_id, 0, $filter_zero);
//            $supplier_name = get_supplier($prod_id);
//            array_push($data_lines, array($supplier_name, $line));
//        }
//    }

	sort( $data_lines );

	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
		$line = $data_lines[ $i ][1];
		$data .= trim( $line );
	}

	$data = str_replace( "\r", "", $data );

	if ( $data == "" ) {
		$data = "\n(0) Records Found!\n";
	}

	?>

	<?php
	$data .= "</table>";

	print "$data";
}
