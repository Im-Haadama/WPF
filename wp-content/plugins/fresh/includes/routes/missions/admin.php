<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/07/19
 * Time: 10:41
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . '/fresh-public/r-shop_manager.php' );

require_once( FRESH_INCLUDES . '/fresh-public/im_tools_light.php' );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once( FRESH_INCLUDES . '/core/gui/input_data.php' );

$this_url = "admin.php";
$entity_name = "מסלול";
$entity_name_plural = "מסלולים";
$table_name = "im_missions";

print header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js",
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
			print Core_Html::GuiButton( "btn_add", "save_new('im_business_info')", "הוסף" );
			break;
		default:
			die( "$operation not handled" );
	}

	return;
}
$row_id = get_param( "row_id", false );

$part_id = get_param( "part_id", false );

if ( $part_id ) {
	print Core_Html::gui_header( 2, get_supplier_name( $part_id ) );
	$page  .= " and part_id = " . $part_id;
	$links = array( "invoice_table.php?row_id=%s" );
	print GuiTableContent( "transactions", "select id, date, amount, net_amount, ref, pay_date " .
        " from im_business_info where " . $page . " order by 2", true, true, $links );

	$date = date( 'Y-m-d', strtotime( "last day of previous month" ) );

	print Core_Html::GuiHyperlink( "הוסף", "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4" );

	return;
}

print Core_Html::gui_header( 1, "ניהול " . $entity_name_plural);
$sum = null;
