<?php

ini_set( 'display_errors', 1 );

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}


require_once( FRESH_INCLUDES . '/core/gui/sql_table.php' );

$supplier_id = GetParam( "supplier_id", true );

print Core_Html::gui_header( 1, "מצב חשבון " . get_supplier_name( $supplier_id ), true );

$sql = "select id, amount, date\n" .
       "from im_business_info\n" .
       "where part_id = " . $supplier_id . "\n" .
       "order by 3";

$args = array("links" => array("id" => "/org/business/c-get-business_info.php?id=%s"));
print GuiTableContent( "table", $sql, $args );

