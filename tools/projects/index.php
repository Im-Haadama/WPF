<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */

require_once( '../im_tools.php' );

$user = new WP_User( $user_ID );

if ( $user_ID == 1 ) { // Agla
	// Display full menu
} else {
	// Display employee menu
}

?>