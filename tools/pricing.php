<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:09
 */

// TODO: Decide about 80% fruit factor
function calculate_price( $price, $supplier, $sale_price = '', $terms = null ) {
	global $conn;
	if ( ! is_numeric( $supplier ) ) {
		print "Invalid " . $supplier . " was sent";
		die( 2 );
	}
	$sql    = "SELECT factor FROM im_suppliers WHERE id = " . $supplier;
	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$row    = mysqli_fetch_assoc( $result );
	$factor = $row["factor"];

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

//    switch ($supplier)
//    {
//        // Usual - 35%
//        // Collecting +10%
//        case 100001: // self
//            $new_price = round($price * 1.35, 1);
//            break;
//        case 100003: // Misc
//            $new_price = round($price * 1.4, 1);
//            break;
//        case 100004: // amir be yehuda
//            $new_price = round($price * 1.5,1);
//            break;
//        case 100006: // hamakolet
//            $new_price = round($price,1);
//            break;
//        case 100005: // yevulei bar
//            $new_price = round($price * 1.45,1);
//            break;
//        case 100008: // Samar
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100009: // RAMA
//            $new_price = round($price * 1.17 * 1.2, 1);
//            break;
//        case 100010: // hakselberg
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100016: // Sadot
//            $new_price = round($price * 1.35,1);
//            break;
//        case 100018: // Mahsan
//            $new_price = round($price * 1.3,1);
//            break;
//        case 100020: // Kesem
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100021: // Udi
//            $new_price = round($price * 1.6,1);
//            break;
//        case 100022: // Snir
//            $new_price = round($price * 1.4,1);
//            break;
//        case 100023: // Yaara
//            $new_price = round($price * 1.5,1);
//            break;
//        case 100024: // Ohad
//            $new_price = round($price * 1.5,1);
//            break;
//        default:
//            $new_price = round($price,1);
//    }
//    return $new_price;
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
