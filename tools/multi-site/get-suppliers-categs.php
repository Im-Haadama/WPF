<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/07/17
 * Time: 15:55
 */

require_once( '../tools_wp_login.php' );
require_once( "../suppliers/gui.php" );

print "ספקים: ";
print_select_supplier( "remote_supplier", false );
print "<br/>קטגוריות: ";
print_category_select( "remote_category_id" );
print "<br/>";
