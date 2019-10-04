<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/06/17
 * Time: 16:32
 */
require_once( '../im_tools.php' );
require_once( '../catalog/catalog.php' );
print header_text();
$line = "";
// Catalog::UpdateProduct( 1599, $line );

$pl = new PriceList( 100030 );
$pl->Delete( 17279 );
//print $line;