<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/04/18
 * Time: 22:28
 */

require_once( "../im_tools.php" );
require_once( "../gui/sql_table.php" );
require_once( "../header.php" );
print header_text();

if ( isset( $_GET["date"] ) ) {
	$date = $_GET["date"];
}
if ( isset( $_GET["prod_id"] ) ) {
	$prod_id = $_GET["prod_id"];
}

$sql = "SELECT client_from_delivery(delivery_id), sum(quantity)
  FROM im_delivery_lines
WHERE prod_id = " . $prod_id . "
GROUP BY 1 ORDER BY 2 DESC";

print table_content( $sql );