<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/17
 * Time: 05:55
 */

require_once( "../tools_wp_login.php" );
require_once( "terms.php" );
print header_text( false );
terms_add( 35, "product_cat", 4 );
