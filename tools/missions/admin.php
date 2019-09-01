<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/07/19
 * Time: 10:41
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( '../r-shop_manager.php' );

require_once( ROOT_DIR . '/tools/im_tools_light.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once(ROOT_DIR . '/niver/gui/sql_table.php');

$this_url = "admin.php";
$entity_name = "מסלול";
$entity_name_plural = "מסלולים";
$table_name = "im_missions";

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js",
	"/vendor/sorttable.js") );

$operation = get_param( "operation", false );
if ( $operation ) {
	switch ( $operation ) {
		case "add":
			$args = array();
			foreach ( $_GET as $key => $data ) {
				if ( ! in_array( $key, array( "operation", "table_name" ) ) ) {
					if ( ! isset( $args["fields"] ) ) {
						$args["fields"] = array();
					}
				}
				$args["fields"][ $key ] = $data;
			}
			$args["edit"] = true;
			print NewRow( "im_business_info", $args, true );
			print gui_button( "btn_add", "save_new('im_business_info')", "הוסף" );
			break;
		default:
			die( "$operation not handled" );
	}

	return;
}
$row_id = get_param( "row_id", false );

if ( $row_id ) {
	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = 1;
	$args["skip_id"]      = true;
	$args["transpose"] = true;

	print GuiRowContent( $table_name, $row_id, $args );
	print gui_button( "btn_save", "save_entity('$table_name', " . $row_id . ')', "שמור" );

	return;
}

$part_id = get_param( "part_id", false );

if ( $part_id ) {
	print gui_header( 2, get_supplier_name( $part_id ) );
	$page  .= " and part_id = " . $part_id;
	$links = array( "invoice_table.php?row_id=%s" );
	print table_content( "transactions", "select id, date, amount, net_amount, ref, pay_date " .
        " from im_business_info where " . $page . " order by 2", true, true, $links );

	$date = date( 'Y-m-d', strtotime( "last day of previous month" ) );

	print gui_hyperlink( "הוסף", "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4" );

	return;
}

$links = array(); $links["id"] = $this_url . "?row_id=%s";

print gui_header( 1, "ניהול " . $entity_name_plural);
$sum = null;
$query = "where date > date_sub(curdate(), interval 10 day)";
$actions = array(
	array( "שכפל", "/tools/delivery/missions.php?operation=dup&id=%s" ),
	array( "מחק", "/tools/delivery/missions.php?operation=del&id=%s" )
);
$order        = "order by 2 ";

$args = array();
$args["links"] = $links;
// $args["first_id"] = true;
$args["actions"] = $actions;

print GuiTableContent($table_name, "select * from $table_name $query $order", $args);
