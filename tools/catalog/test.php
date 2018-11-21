<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/03/17
 * Time: 09:09
 */

require_once( "../im_tools.php" );
require_once( 'catalog.php' );

print header_text( false, true, false );

//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

$line = "";
Catalog::UpdateProduct( 1110, $line );
print $line;
