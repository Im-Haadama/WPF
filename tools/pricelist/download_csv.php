<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/03/17
 * Time: 07:44
 */
require_once( "../r-shop_manager.php" );

$supplier_id = $_GET["supplier_id"];
print $supplier_id;

if ( ! ( $supplier_id > 0 ) ) {
	print "Usage: download_csv.php?supplier_id=#";
	exit( 1 );
}

