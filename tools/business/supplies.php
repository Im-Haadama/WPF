<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/02/18
 * Time: 18:19
 */

require_once( "../r-shop_manager.php" );
require_once( "../gui/inputs.php" );

$only_open = false;
if ( isset( $_GET["open"] ) ) {
	$only_open = true;
}
print header_text( false, true );
$sql = "SELECT id, date(date), supplier, status FROM im_supplies WHERE
	business_id IS NULL";

if ( $only_open ) {
	$sql .= " and status = 1";
} else {
	$sql .= " and status <> 9";
}

$result = sql_query( $sql );

$table = array();

array_push( $table, array( "מזהה", "תאריך", "ספק" ) );
while ( $row = mysqli_fetch_row( $result ) ) {
	$display_row = array( $row[0], $row[1], get_supplier_name( $row[2] ), get_supply_status( $row[3] ) );

	array_push( $table, $display_row );
}

print gui_table( $table );

