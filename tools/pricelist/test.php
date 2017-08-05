<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/06/17
 * Time: 16:32
 */
require_once( '../catalog/catalog.php' );
print header_text();
$line = "";
Catalog::UpdateProduct( 1599, $line );

print $line;