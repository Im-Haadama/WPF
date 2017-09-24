<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/09/17
 * Time: 06:18
 */

if ( ! defined( TOOLS_DIR ) ) {
	define( TOOLS_DIR, dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . '/im_tools.php' );
require_once( TOOLS_DIR . '/gui/sql_table.php' );

print header_text( false );

$sql = "SELECT product_name, price, quantity FROM im_delivery_lines " .
       " WHERE prod_id = 0 " .
       " AND product_name NOT IN ('הנחת כמוות','משלוח')" .
       " GROUP BY product_name " .
       " ORDER BY id DESC";

print table_content( $sql );