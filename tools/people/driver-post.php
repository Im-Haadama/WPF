<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 21:12
 */

require_once( "../r-shop_manager.php" );
require_once( "people.php" );
require_once( "../account/account.php" );

$operation = $_GET["operation"];
my_log( "Operation: " . $operation, __FILE__ );

switch ( $operation ) {
	case "add_item":
		$start     = $_GET["start"];
		$quantity  = $_GET["quantity"];
		$date      = $_GET["date"];
		$sender    = $_GET["sender"];
		$driver_id = get_user_id();
		if ( isset( $_GET["driver_id"] ) ) {
			$driver_id = $_GET["driver_id"];
		}
		driver_add( $driver_id, $date, $quantity, $sender );
		break;
}

function driver_add( $user_id, $date, $quantity, $sender ) {
	my_log( "driver_add", __FILE__ );
	driver_add_activity( $user_id, $date, $quantity, $sender );
	account_add_transaction( $user_id, $date, - 21.4 * ( $quantity ), 1, "שילוח" );
}

?>