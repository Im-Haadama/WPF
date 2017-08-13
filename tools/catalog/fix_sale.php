<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/08/17
 * Time: 20:22
 */

require_once( 'catalog.php' );
require_once( "../gui/inputs.php" );

print header_text( true );

$sql = "SELECT post_id FROM wp_postmeta WHERE meta_key = '_sale_price' AND meta_value > 0 ORDER BY 1";

$result = sql_query( $sql );

$sale_table = array();

array_push( $sale_table, array( "מזהה", "שם מוצר", "מחיר מבצע", "מחיר רגיל", "פעולה" ) );

delete_transient( 'wc_products_onsale' );

while ( $row = mysqli_fetch_row( $result ) ) {
	$action = "";
	$prod_id = $row[0]; //print $prod_id . "<br/>";

	if ( get_sale_price( $prod_id ) < get_price( $prod_id ) ) {
		$action = "set price ";
		wc_delete_product_transients( $prod_id );
		set_price( $prod_id, get_sale_price( $prod_id ) );
//		$sql = "update wp_postmeta set meta_value = " . get_price($prod_id) . " where meta_key = '_price' and post_id = " .$prod_id;
//		// print $sql . "<br/>";
//		sql_query($sql);
	}

//	try {
//		$prod = new WC_Product( $prod_id );
//		if ( ! $prod ) {
//			continue;
//		}
//
//		if ( $prod->get_sale_price() < $prod->get_price() ) {
//			$action = "set price ";
//			$prod->set_price( $prod->get_sale_price() );
//			$prod->save();
//		}
//	} catch (Exception $e) {
//		$action = "מוצר לא תקין";
//		// echo 'Caught exception: ',  $e->getMessage() ."<br/>";
//	}

//	if (get_sale_price($prod_id) < get_price($prod_id)){
//		$action = "חידוש מבצע";
//		$sale_price = get_sale_price($prod_id);
//		// set_sale_price($prod_id, '');
//		set_sale_price($prod_id, $sale_price);
//	}

	array_push( $sale_table, array(
		$prod_id,
		get_product_name( $prod_id ),
		get_sale_price( $prod_id ),
		get_price( $prod_id ),
		$action
	) );
	// die (1);
}

print gui_table( $sale_table );