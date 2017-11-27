<?php
require_once( '../r-shop_manager.php' );
// require_once('../header.php');
?>
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/07/15
 * Time: 21:49
 */


$basket_id = $_GET["basket_id"];
$operation = $_GET["op"];

my_log( __FILE__, "operation = " . $operation );

switch ( $operation ) {
	case "save":
		my_log( "save basket " . $basket_id );
		save_basket( $basket_id );
		break;
	case "load":
		my_log( "load basket " . $basket_id );
		load_basket( $basket_id );
		break;
	case "empty":
		my_log( "empty basket" );
		empty_basket();
		break;
}

function empty_basket() {
	WC()->cart->empty_cart();
	header( 'Location: ' . wc_get_cart_url() );
}

function save_basket( $basket_id ) {
	$products_array = array();

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		// $_product = $values['data']; // apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
		$product_id = $cart_item['product_id'];
		$quantity   = $cart_item['quantity'];
		// my_log("id = " . $product_id . "quant = " . $quantity);
		// $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
		// $quantities = apply_filters('woocommerce_cart_item_product_i', $cart_item['product_id'], $cart_item, $cart_item_key);

		array_push( $products_array, array( $product_id, $quantity ) );
	}

	do_save_basket( $basket_id, $products_array );
}

function load_basket( $basket_id ) {
	my_log( __METHOD__ );
	WC()->cart->empty_cart( true );
	$sql    = "SELECT product_id, quantity FROM im_baskets WHERE basket_id=" . $basket_id;
	$result = sql_query( $sql );
	while ( $row = mysqli_fetch_row( $result ) ) {
		WC()->cart->add_to_cart( $row[0], $row[1] );
	}
	header( 'Location: ' . wc_get_cart_url() );
}

function do_save_basket( $basket_id, $products_array ) {
	$basket_text = "";
	$sql         = "DELETE FROM im_baskets WHERE basket_id = " . $basket_id;

	sql_query( $sql );

	// print "תוכן הסל הבסיסי נשמר מעגלת הקניות";

//    print "<table>";
//    print "<tr><td>קוד מוצר</td><td>שם</td></tr>";
	for ( $i = 0; $i < count( $products_array ); $i ++ ) {
		$product_id = $products_array[ $i ][0];
		$quantity   = $products_array[ $i ][1];

		//  print "<tr>";
		// print "<td>" . $product_id . "</td>";
//        print "<td>" . get_product_name($product_id);
//        $basket_text .= get_product_name($product_id) . ", ";
//        print "</tr>";

		$sql = 'INSERT INTO im_baskets (basket_id, date, product_id, quantity) VALUES (' . $basket_id . ", '" . date( 'Y/m/d' ) . "', " .
		       $product_id . ", " . $quantity . ')';

		sql_query( $sql );
	}
//    print "</table>";

//    print $basket_text;
	header( 'Location: ' . wc_get_cart_url() );
}

?>
