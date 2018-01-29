<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 10:31
 */
require_once( "pricelist-process.php" );
require_once( '../header_no_login.php' );
$results = array();

$inc = true;
if ( isset( $_GET["full"] ) ) {
	$inc = false;
}
pricelist_remote_site_process( 100001, $results, $inc );