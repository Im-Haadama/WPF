<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 10:31
 */
if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( "pricelist-process.php" );
require_once( '../header_no_login.php' );

$results = array();

if ( isset( $_GET["full"] ) ) {
	pricelist_remote_site_process( 100030, $results, false );
} else {
	pricelist_remote_site_process( 100030, $results, true );
}