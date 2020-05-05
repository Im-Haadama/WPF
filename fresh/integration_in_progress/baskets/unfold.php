<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/01/17
 * Time: 12:10
 */

//require_once( '../r-shop_manager.php' );
require_once( FRESH_INCLUDES . "/fresh/catalog/Basket.php" );
//"start";

foreach ( WC()->cart->cart_contents as $prod_in_cart ) {
	$prod_id = $prod_in_cart['product_id'];
	if ( is_basket( $prod_id ) ) {
		// Get it's unique ID within the Cart
		$prod_unique_id = WC()->cart->generate_cart_id( $prod_id );
		// Remove it from the cart by un-setting it
		unset( WC()->cart->cart_contents[ $prod_unique_id ] );
		add_basket_items( $prod_id );
	}
}

header( 'Location: ' . wc_get_cart_url() );


function add_basket_items( $basket_id ) {

	$result = SqlQuery( 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id);

	while ( $row = mysqli_fetch_row( $result ) ) {
		$prod_id = $row[0];
		$q       = $row[1];
		WC()->cart->add_to_cart( $prod_id, $q );
	}

}

//print "end";

