<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 12:15
 */

//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( "im_tools.php" );
require_once( "wp/Product.php" );
//print header_text( false, true, false );
//
//print valid_key( "2wX8xpY7S5rmbyTBBENVUdHbKRxlOqqH" );

// print order_get_shipping_fee(2230);

// require_once( 'r-shop_manager.php' );

//$args = array(
//    'posts_per_page' => -1,
//    'relation' => 'AND',
//    array(
//    'post_status' => 'draft', 'publish',
//    'post_type' => 'product',
//    'post_title' => 'מלפפון')
//);
//$query = new WP_Query( $args );
//
//while ( $query->have_posts() ) {
//    $query->the_post();
//    echo '<li>' . get_the_title() . '</li>';
//}

foreach ( array( 3184, 1061, 2021 ) as $prod_id ) {
	$p = new Product( ( $prod_id ) );
	if ( ! $p ) {
		continue;
	}
	print "prod: " . $p->getName();
	if ( $p->isFresh() ) {
		print "fresh";
	}
	print "<br>";

}
