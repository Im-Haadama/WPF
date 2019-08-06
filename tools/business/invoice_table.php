<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/19
 * Time: 16:44
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

require_once( ROOT_DIR . '/niver/PivotTable.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/im_tools_light.php' );

print header_text(false, true, true, array("/niver/gui/client_tools.js", "/tools/admin/data.js"));

$year = get_param( "year" );
if ( ! $year ) {
	$year = date( "Y" );
}
// $month = get_param("monty");

$page ="EXTRACT(YEAR FROM DATE) = " . $year . " and document_type = 4 and is_active=1";

$operation = get_param("operation", false);
if ($operation){
	switch ($operation)
	{
		case "add":
			$args = array();
			foreach ($_GET as $key => $data)
			{
				if (! in_array($key, array("operation", "table_name")))
				{
					if (! isset($args["fields"]))
						$args["fields"] = array();
				}
				$args["fields"][$key] = $data;
			}
			$args["edit"] = true;
			print NewRow("im_business_info", $args, true);
			print gui_button("btn_add", "save_new('im_business_info')", "הוסף");
			break;
		default:
			die("$operation not handled");
	}
	return;
}
$row_id = get_param("row_id", false);

if ($row_id)
{
	print gui_header(1, "חשבונית מס " . $row_id);
	$args = array();
	$args["edit"] = 1;
	$args["add_checkbox"] = true;
	$args["skip_id"] = true;
	$args["selectors"] = array("part_id" => "gui_select_supplier");
	$args["transpose"] = true;
	print GuiRowContent("im_business_info", $row_id, $args);
	print gui_button("btn_save", 'save_entity(\'im_business_info\', ' . $row_id .')', "שמור");

	return;
}

$part_id = get_param("part_id", false);

if ($part_id) {
	print gui_header(2, get_supplier_name($part_id));
	$page .= " and part_id = " . $part_id;
	$links = array("id"=> "invoice_table.php?row_id=%s", "הספקה" => "/tools/supplies/supply-get.php?id=%s");
	$args["links"] = $links;

	$sql =  "select id, date as 'תאריך', amount as 'סכום', net_amount as 'סכום נקי', ref as 'סימוכין', pay_date as 'תאריך תשלום', supply_from_business(id) as הספקה
        from im_business_info where " . $page . " and is_active = 1 order by 2";

	print GuiTableContent("transactions", $sql, $args);

//	print table_content("transactions", $sql, true, true, $links);

	$date = date('Y-m-d', strtotime("last day of previous month"));

	print gui_hyperlink("הוסף" , "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4");
	return;
}

print gui_header (1, "ריכוז חשבוניות");

$t = new \Niver\PivotTable( "im_business_info", $page,
	"month_with_index(DATE)", "part_id", "net_amount" );

$trans            = array();
$trans["part_id"] = 'get_customer_name';
print gui_table_args( $t->Create(
	'c-get-business_info.php?document_type=4&part_id=%s&date=' . $year . '-' . '%02s-28',
	'invoice_table.php?part_id=%s',
	$trans ) );


print gui_hyperlink( "שנה קודמת", "pivot_test.php?year=" . ( $year - 1 ) );
