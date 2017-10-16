<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/11/16
 * Time: 06:20
 */
require_once( "../tools_wp_login.php" );
require_once( 'delivery.php' );

//$shipping = get_postmeta_field(6416, '_shipping_method');
//// var_dump($shipping);
//$zone = strtok(substr($shipping, strpos($shipping,"flat_rate") + 10), "\"");
//print "Zone: " . $zone. "<br/>";


// print get_user_meta(96, 'shipping_zone');
//print ord(substr('א', 1,1));
//print ord(substr('ב', 1,1));
//print header_text( false );
//$d = delivery::CreateFromOrder( 6862 );
//$d->print_delivery( true );

// print_fresh_category();
//print "a";
//$order = new WC_Order(2053 );
//$order->update_status( 'wc-awaiting-shipment' );
//
//print $order->get_status();


// print_fresh_category();

sql_query( "set lc_time_names = 'he_IL'" );
print sql_query_single_scalar( "select @@lc_time_names" );
