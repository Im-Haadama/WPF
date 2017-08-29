<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/03/17
 * Time: 23:37
 */

require_once( '../im_tools.php' );

foreach ( array( 15 ) as $user ) {
	add_user_meta( $user, "legacy_user", 1 );
}