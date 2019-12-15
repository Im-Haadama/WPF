<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/05/17
 * Time: 16:12
 */
require_once( '../r-shop_manager.php' );
require_once( '../header.php' );

print header_text();
//$prod_id = 1734;
//
//$prod = new WC_Product($prod_id);
//
//// $vars = $prod->get_available_variations();
//
//$args = array(
//	'post_type'     => 'product_variation',
//	'post_status'   => 'publish',
//	'numberposts'   => -1,
//	'orderby'       => 'menu_order',
//	'order'         => 'asc',
//	'post_parent'   => $prod_id // $post->ID
//);
//$variations = get_posts( $args );
//echo "<pre>"; print_r($variations); echo "</pre>";
//
//var_dump($vars);

//print_r(get_product_variations(1734));
//

$product_id = 1734;

$var       = get_product_variations( $product_id );
$price     = 4.2;
$buy_price = 2.5;
$supplier  = 100005;
foreach ( $var as $v ) {
	print_r( $v );
	print "<br/>";
	my_log( "updating variation " . $v . " to pricelist " . $pricelist_id . " price " . $price );
	$var = new WC_Product_Variation( $v );
	update_post_meta( $v, "supplier_name", get_supplier_name( $supplier ) );
	update_post_meta( $v, "_regular_price", $price );
	update_post_meta( $v, "_price", $price );
	update_post_meta( $v, "buy_price", $buy_price );
	// $var->save();
}
