<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/17
 * Time: 18:26
 */

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( STORE_DIR . "/wp-config.php" );
require_once( STORE_DIR . "/wp-load.php" );

$user = wp_get_current_user();

$roles = $user->roles;

foreach ( $roles as $role ) {
	print $role . "<br/>";
}
