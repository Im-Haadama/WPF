<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/11/16
 * Time: 06:20
 */
require_once( "../r-shop_manager.php" );
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

//sql_query( "set lc_time_names = 'he_IL'" );
//print sql_query_single_scalar( "select @@lc_time_names" );

print header_text( false );
print archive_get_supplier( 104, '2018-01-21' );

function archive_get_supplier( $prod_id, $week ) {
	$sql = "SELECT DISTINCT s.status, s.supplier
	FROM im_supplies s
	JOIN im_supplies_lines l
	WHERE l.supply_id = s.id
		AND first_day_of_week(date) = '" . $week . "'
		AND s.status = 5
		AND product_id = " . $prod_id;

//	print $sql;
//	print $sql; die(1);
	$result = sql_query( $sql );
	$s      = "";
	while ( $row = mysqli_fetch_row( $result ) ) {
		$s .= get_supplier_name( $row[0] ) . ", ";
	}
	$s = rtrim( $s, ", " );

	// var_dump($supps);

	return $s;
}