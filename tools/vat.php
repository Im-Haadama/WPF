<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:17
 */

$global_vat = 17;

//function get_vat_percent( $product_id ) {
//	global $global_vat;
//
//	$vat = $global_vat;
//
//	$terms = get_the_terms( $product_id, 'product_cat' );
//
//	if ( $terms ) {
//		foreach ( $terms as $term ) {
//			foreach ( array( "פרי", "פירות", "ירק", "עלים", "נבטים", "סלים" ) as $no_vat_cat ) {
//				if ( strstr( $term->name, $no_vat_cat ) ) {
//					$vat = 0;
//				}
//			}
//		}
//	} else {
//		if ( $product_id > 0 ) {
//			print "no terms for " . $product_id;
//		}
//	}
//
//	return $vat;
//}

function set_vat( $prod_ids, $vat_rate ) {
	$debug_string = "set_vat: " . implode( ", ", $prod_ids ) . " rate = " . $vat_rate;
//    my_log($debug_string, __FILE__);
	foreach ( $prod_ids as $prod ) {
//        my_log("set vat " . $prod, __FILE__);
		set_post_meta_field( $prod, "vat_percent", $vat_rate );
	}
}
