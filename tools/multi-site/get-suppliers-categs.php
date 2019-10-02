<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/07/17
 * Time: 15:55
 */

require_once( '../r-shop_manager.php' );
require_once( "../suppliers/gui.php" );

print "ספקים: ";
print_select_supplier( "remote_supplier", false );
print "<br/>קטגוריות: ";
gui_select_category( "remote_category_id" );
print "<br/>";
