<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 21:12
 */

require_once( "../tools_wp_login.php" );
require_once( "people.php" );
require_once( "../account/account.php" );
require_once( "people.php" );


$operation = $_GET["operation"];
$user_id   = $_GET["user_id"];

my_log( "Operation: " . $operation, __FILE__ );


switch ( $operation ) {
	case "add_time":
		$start      = $_GET["start"];
		$end        = $_GET["end"];
		$date       = $_GET["date"];
		$project    = $_GET["project"];
		$user_id    = $_GET["user_id"];
		$vol        = $_GET["vol"];
		$traveling  = $_GET["traveling"];
		$extra_text = $_GET["extra_text"];
		$extra      = $_GET["extra"];

		if ( isset( $_GET["user_id"] ) ) {
			$user_id = $_GET["user_id"];
		} else {
			$user_id = get_user_id();
		}
		// if ($user_id = 1) $user_id = 238;
		add_activity( $user_id, $date, $start, $end, $project, $vol, $traveling, $extra_text, $extra );
		break;

	case "display":
		$user_id = $_GET["user_id"];
		if ( $user_id == 0 ) {
			print print_transactions();
		} else {
			print print_transactions( $user_id );
		}
		break;

	case "display_all":
		print print_transactions();

		break;
}

?>