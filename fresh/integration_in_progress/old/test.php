<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 12:15
 */



require_once( "catalog/gui.php" );
// print gui_select_product("xx");
//print gui_select_mission("mission");
//
//
//die (1);

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

//foreach ( array( 3184, 1061, 2021 ) as $prod_id ) {
//	$p = new Product( ( $prod_id ) );
//	if ( ! $p ) {
//		continue;
//	}
//	print "prod: " . $p->getName();
//	if ( $p->isFresh() ) {
//		print "fresh";
//	}
//	print "<br>";
//
//}


print  randomPassword();

function randomPassword() {
	$pass = "";
	$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789!@#$%^&*()";
	for ($i = 0; $i < 8; $i++) {
		$n = rand(0, strlen($alphabet)-1);
		$pass .= $alphabet[$n];
	}
	return $pass;
}