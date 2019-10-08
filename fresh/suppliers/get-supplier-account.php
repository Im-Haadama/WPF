<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/fresh/im_tools.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );

$supplier_id = get_param( "supplier_id", true );

print gui_header( 1, "מצב חשבון " . get_supplier_name( $supplier_id ), true );

$sql = "select id, amount, date\n" .
       "from im_business_info\n" .
       "where part_id = " . $supplier_id . "\n" .
       "order by 3";

$args = array("links" => array("id" => "/org/business/c-get-business_info.php?id=%s"));
print GuiTableContent( "table", $sql, $args );

