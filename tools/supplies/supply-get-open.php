<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/06/17
 * Time: 06:37
 */
require_once( "../im_tools.php" );
require_once( "supplies.php" );
$supply_id = $_GET["id"];

print header_text();
print "<center><h1>הספקה מספר ";
print $id . " - " . supply_get_supplier( $supply_id );
print  "</h1> </center>";

print_comment( $supply_id );
print_supply( $supply_id, false );
