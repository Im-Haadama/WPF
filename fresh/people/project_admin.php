<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/07/19
 * Time: 16:44
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

require_once( ROOT_DIR . '/niver/PivotTable.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/fresh/im_tools_light.php' );

$this_url = "project_admin.php";
$entity_name = "חשבונית";
$table_name = "im_projects";

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/fresh/admin/data.js",
	"/vendor/sorttable.js") );

$year = get_param( "year" );
if ( ! $year ) {
	$year = date( "Y" );
}
// $month = get_param("monty");

$page = "EXTRACT(YEAR FROM DATE) = " . $year . " and document_type = 4 and is_active=1";

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
	$args["add_checkbox"] = true;
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
	print table_content( "transactions", "select id, date as 'תאריך', amount as 'סכום', net_amount as 'סכום נקי', ref as 'סימוכין', pay_date as 'תאריך תשלום'
        from im_business_info where " . $page . " order by 2", true, true, $links );

	$date = date( 'Y-m-d', strtotime( "last day of previous month" ) );

	print gui_hyperlink( "הוסף", "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4" );

	return;
}

$links = array($this_url . "?row_id=%s");

print gui_header( 1, "ניהול פרויקטים" );
$sum = null;
$args = array();
$args["links"] = $links;
$args["class"] = "sortable";

$sql = "select * 
from im_projects";
print GuiTableContent($table_name, $sql, $args);
