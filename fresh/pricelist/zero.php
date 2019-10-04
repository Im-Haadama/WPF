<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/10/18
 * Time: 19:44
 */
// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once( "pricelist.php" );

if ( ! isset( $_GET["ids"] ) ) {
	print "noting to done";
	exit( 1 );
}

$ids = explode( ",", $_GET["ids"] );
foreach ( $ids as $id ) {
	print "removing list " . get_supplier_name( $id ) . "<br/>";
	$p = new PriceList( $id );
	$p->RemoveLines( 1 );
}