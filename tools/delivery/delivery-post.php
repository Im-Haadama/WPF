<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/05/17
 * Time: 14:19
 */
//require_once('catalog.php');
require_once( '../pricelist/pricelist.php' );
require_once( '../tools.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

$operation = $_GET["operation"];
// print $operation . "<br/>";

// print "opreation = " . $operation . "<br/>";

switch ( $operation ) {
	case "add_chef":
		print "adding user";
		$user    = $_GET["user"];
		$name    = urldecode( $_GET["name"] );
		$email   = $_GET["email"];
		$address = urldecode( $_GET["address"] );
		$city    = urldecode( $_GET["city"] );
		$phone   = $_GET["phone"];
		$zip     = $_GET["zip"];
		add_chef_user( $user, $name, $email, $address, $city, $phone, $zip );
		break;
	case "save_legacy":
		print "saving legacy deliveries<br/>";
		$ids_ = $_GET["ids"];
		$ids  = explode( ',', $ids_ );
//		var_dump($ids);
		save_legacy( $ids );
		break;
}

function add_chef_user( $user, $name, $email, $address, $city, $phone, $zip ) {
	$id = wp_create_user( $user, randomPassword(), $email );
	if ( ! is_numeric( $id ) ) {
		print "לא מצליח להגדיר יוזר";
		var_dump( $id );

		return;
	}
	$name_part = explode( " ", $name );
	update_user_meta( $id, 'first_name', $name_part[0] );
	update_user_meta( $id, 'shipping_first_name', $name_part[0] );
	unset( $name_part[0] );
	update_user_meta( $id, 'billing_address_1', $address );
	update_user_meta( $id, 'billing_city', $city );

	update_user_meta( $id, 'last_name', implode( " ", $name_part ) );
	update_user_meta( $id, 'shipping_last_name', implode( " ", $name_part ) );
	update_user_meta( $id, 'billing_phone', $phone );
	update_user_meta( $id, 'billing_postcode', $zip );

	update_user_meta( $id, 'shipping_address_1', $address );
	update_user_meta( $id, 'shipping_postcode', $zip );
	update_user_meta( $id, 'shipping_city', $city );
	update_user_meta( $id, 'legacy_user', 1 );
}

function randomPassword() {
	$alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass        = array(); //remember to declare $pass as an array
	$alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache
	for ( $i = 0; $i < 8; $i ++ ) {
		$n      = rand( 0, $alphaLength );
		$pass[] = $alphabet[ $n ];
	}

	return implode( $pass ); //turn the array into a string
}

function save_legacy( $ids ) {
	global $conn;
	$sql    = "UPDATE im_delivery_legacy SET status = 2 WHERE status = 1";
	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		print mysqli_error( $conn ) . " " . $sql;
		die ( 1 );
	}
	foreach ( $ids as $id ) {

		$sql = "INSERT INTO im_delivery_legacy (client_id, date, status) " .
		       " VALUES (" . $id . ", CURRENT_TIMESTAMP(), 1)";


		$result = mysqli_query( $conn, $sql );
		if ( ! $result ) {
			print mysqli_error( $conn ) . " " . $sql;
			die ( 1 );
		}
	}
}