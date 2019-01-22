<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/09/16
 * Time: 20:27
 */

require_once( 'account.php' );

// set_default_display_name(190);
//set_post_meta_field(96,'_client_type', 'owner');
// print customer_type( 91 );
//for ( $i = 73; $i < 74; $i ++ ) {
//	print $i . "<br/>";
//	im_set_default_display_name( $i );
//}

$sql    = "SELECT id, transaction_method, transaction_ref FROM im_client_accounts " .
          "WHERE transaction_method LIKE 'אשראי %'";
$result = sql_query( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$id      = $row[0];
	$method  = $row[1];
	$ref     = $row[2];
	$dels    = substr( $method, 12 );
	$del_ids = explode( ",", $dels );

	foreach ( $del_ids as $del_id ) {
		if ( is_numeric( $del_id ) ) {
			$sql1 = "UPDATE im_delivery SET payment_receipt = " . $ref .
			        " WHERE id = " . $del_id;

			print $sql1 . "<br/>";
			sql_query( $sql1 );
			// print $del_id . " " . $ref . "<br/>";
		}
	}

}
