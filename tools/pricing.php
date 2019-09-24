<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:09
 */

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( STORE_DIR . '/tools/wp/Product.php' );

// TODO: Decide about 80% fruit factor
function calculate_price( $price, $supplier, $sale_price = '', $terms = null ) {
	$factor = sql_query_single_scalar( "SELECT factor FROM im_suppliers WHERE id = " . $supplier);

	// Check for sale
	if ( is_numeric( $sale_price ) and $sale_price < $price and $sale_price > 0 ) {
		$price = $sale_price;
	}

	if ( is_numeric( $factor ) ) {

		// Fruits factor
//		if ($terms) foreach ($terms as $term){
//			if ($term->id == 11 and $price > 10 and MultiSite::LocalSiteID() == 1){
//				$factor = $factor * 0.8;
//				print "fruit factor";
//			}
//		}

		return round( $price * ( 100 + $factor ) / 100, 1 );
	}

	return 0;
}


function get_price_by_type( $prod_id, $client_type = "", $quantity = 1, $variation_id = null )
{
	$debug = 0;

	if ( strlen( $client_type ) < 1 ) {
		$client_type = "regular";
	}
	// client type can be:
	// null - regular price.
	// string - type name
	// number - type id
	// print "CT=" . $client_type;
	$p    = new Product( $prod_id );
	$type = ( $p->isFresh() ) ? "rate" : "dry_rate";

	$sql = "SELECT min($type) FROM im_client_types WHERE type = '" . $client_type . "' AND (q_min <= " . $quantity . " OR q_min = 0)";
	//  print $sql . "<br/>";
	$rate = sql_query_single_scalar( $sql );

	$id = $variation_id ? $variation_id : $prod_id;

	// Nothing special. Return the price from the site.
	if ( is_null( $rate ) ) {
		return get_postmeta_field( $id, '_price' );
	}

//	 print "rate: " . $rate. "<br/>";

	$price = get_postmeta_field( $id, '_price' );

	if ($debug) print $prod_id ." " . $variation_id . "<br/>";

	$buy   = get_buy_price( $id );
	if ( $buy == 0 ) {
		return $price;
	}
	//print "price: " .$price;
	// if (! $p->isFresh()) $rate
	return min( $price, round( ( $buy * ( 100 + $rate ) ) / 100, 1 ) );

	// Non fresh
//	return get_postmeta_field( $prod_id, '_price' );
}
function get_price( $prod_id, $client_type = 0, $quantity = 1 ) {
	switch ( $client_type ) {
		case 0:
			if ( $quantity >= 8 ) {
				return round( get_buy_price( $prod_id ) * 1.4, 1 );
			}

			return get_postmeta_field( $prod_id, '_price' );
		case 1:
			return siton_price( $prod_id );
		case 2:
			return get_buy_price( $prod_id );
		case 5:
			return min( get_price( $prod_id ), round( get_buy_price( $prod_id ) * 1.3, 1 ) );
	}
}

function get_sale_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_sale_price' );
}

function get_regular_price( $prod_id ) {
	return get_postmeta_field( $prod_id, '_regular_price' );

}

function set_price( $prod_id, $price ) {
	$sql = "UPDATE wp_postmeta SET meta_value = " . $price . " WHERE meta_key = '_price' AND post_id = " . $prod_id;
	print $sql;
	sql_query( $sql );

	// return set_post_meta_field( $prod_id, '_sale_price', $sale_price );
}

// get_sale_price
