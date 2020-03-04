<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/07/19
 * Time: 16:44
 */




if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );
require_once( FRESH_INCLUDES . "/org/gui.php" );
require_once( FRESH_INCLUDES . "/focus/focus_class.php" );

if (! get_user_id(true)) {
	return;
}

init(null);

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . '/core/PivotTable.php' );
require_once( FRESH_INCLUDES . '/core/gui/input_data.php' );

$this_url = "project_admin.php";
$entity_name = "חשבונית";
$table_name = "im_projects";

print header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js",
	"/vendor/sorttable.js") );

$year = GetParam( "year" );
if ( ! $year ) {
	$year = date( "Y" );
}
// $month = get_param("monty");

$page = "EXTRACT(YEAR FROM DATE) = " . $year . " and document_type = 4 and is_active=1";

$operation = GetParam( "operation", false, "show_projects" );

handle_project_operation($operation);

function handle_project_operation($operation)
{

	$args = [];
	$args["greeting"] = true;
	switch ( $operation ) {
		case "show_task":
			print handle_focus_operation("show_task", $args);
			break;
		case "show_add":
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
		case "show_projects":
			print HeaderText($args);
			$links = array("ID" => AddToUrl(array( "operation" => "show_project", "id" => "%s")));

			print Core_Html::gui_header( 1, "ניהול פרויקטים" );
			$sum = null;
			$args = array();
			$args["links"] = $links;
			$args["class"] = "sortable";
			$args["drill"] = true;
			$args["fields"] = array("id", "project_name", "project_contact", "project_priority", "is_active");

			$sql = "select * from im_projects order by project_priority desc";
			print GuiTableContent("im_projects", $sql, $args);

			break;

		case "show_project":
			$id = GetParam("id", true);
			$args = [];
			$args["page"] = GetParam("page", false, 1);
			print GemElement("im_projects", $id, $args);

			$args["project_id"] = $id;
			print Focus_Views::active_tasks($args);
			break;

		default:
			die( "$operation not handled" );
	}

	return;
}

return;
$row_id = GetParam( "row_id", false );

if ( $row_id ) {
	print Core_Html::gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = 1;
	$args["add_checkbox"] = true;
	$args["skip_id"]      = true;
	$args["transpose"] = true;

	print GuiRowContent( $table_name, $row_id, $args );
	print Core_Html::GuiButton( "btn_save", "data_save_entity('$table_name', " . $row_id . ')', "שמור" );

	return;
}

$part_id = GetParam( "part_id", false );

if ( $part_id ) {
	print Core_Html::gui_header( 2, get_supplier_name( $part_id ) );
	$page  .= " and part_id = " . $part_id;
	$links = array( "invoice_table.php?row_id=%s" );
	print GuiTableContent( "transactions", "select id, date as 'תאריך', amount as 'סכום', net_amount as 'סכום נקי', ref as 'סימוכין', pay_date as 'תאריך תשלום'
        from im_business_info where " . $page . " order by 2", true, true, $links );

	$date = date( 'Y-m-d', strtotime( "last day of previous month" ) );

	print Core_Html::GuiHyperlink( "הוסף", "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4" );

	return;
}

