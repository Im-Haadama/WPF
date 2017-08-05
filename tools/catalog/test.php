<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/03/17
 * Time: 09:09
 */

require_once( 'catalog.php' );
print header_text();
// print Catalog::GetBuyPrice(32, 100005);

$line = "";
//Catalog::UpdateProduct(4425, $line);
//
//print $line;

// $alt = alternatives(297, true);
// var_dump($alt);
//
print_category_select( "aaa" );