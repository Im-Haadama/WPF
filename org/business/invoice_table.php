<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ) );
}
require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . "/init.php" );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/01/19
 * Time: 16:44
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/niver/PivotTable.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/fresh/im_tools_light.php' );
require_once( ROOT_DIR . '/niver/web.php' );
require_once(ROOT_DIR . '/niver/gui/input_data.php' );

print header_text(false, true, true, array("/niver/gui/client_tools.js", "/niver/data/data.js"));

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
		case "show_add_invoice":
			$args = array();
			set_args_value($args);
			$args["edit"] = true;
			// $args["]
			$args["selectors"] = array("part_id" => "gui_select_supplier", "document_type" => "gui_select_document_type");
			$args["fields"] = array("part_id", "date", "ref", "amount", "net_amount", "document_type");
			$args["mandatory_fields"] = array("part_id" => 1, "date" => 1, "ref" => 1, "amount" => 1, "document_type" => 1, "net_amount" => 1);
			$args["post_file"] = "/niver/data/data-post.php";
			print GemAddRow("im_business_info", "invoices", $args);
			// print NewRow("im_business_info", $args, true);
			// print gui_button("btn_add", "data_save_new('im_business_info')", "הוסף");
			break;
		case "null_date":
			$args = array();
			$args["selectors"] = array("part_id" => "gui_select_supplier", "document_type" => "gui_select_document_type");
			$args["edit"] = true;
			print GuiTableContent("invoices", "select * from im_business_info where document_type = ". ImDocumentType::invoice .
				" and date is null", $args );
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
	$args["skip_id"] = true;
	$args["selectors"] = array("part_id" => "gui_select_supplier", "document_type" => "gui_select_document_type");
	$args["transpose"] = true;
	$args["header_fields"] = array("Id", "Supplier", "Date", "Week", "Amount", "Reference", "Delivery fee", "Project", "Is active", "Pay date", "Document type", "Net amount", "Invoice file", "Invoice",
		"Occasional supplier");
	// print GuiRowContent("im_business_info", $row_id, $args);
	// print gui_button("btn_save", 'data_save_entity(\'im_business_info\', ' . $row_id .')', "שמור");
	print GemElement("im_business_info", $row_id, $args);

	return;
}

$part_id = get_param("part_id", false);

if ($part_id) {
	print gui_header(2, get_supplier_name($part_id));
	$page .= " and part_id = " . $part_id;
	$links = array("id"=> "invoice_table.php?row_id=%s", "הספקה" => "/fresh/supplies/supply-get.php?id=%s");
	$args["links"] = $links;

	$sql =  "select id, date, amount, net_amount, ref, pay_date, supply_from_business(id)
        from im_business_info where " . $page . " and is_active = 1 order by 2";

	$args ["sql"] = $sql;
	$args["page"] = get_param("page");
	print GemTable( "transactions", $args );

	$date = date('Y-m-d', strtotime("last day of previous month"));

	print gui_hyperlink("הוסף" , "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4");
	return;
}

print gui_header (1, "ריכוז חשבוניות");

print gui_hyperlink("Add invoice", add_param_to_url(get_url(), array("operation" => "show_add_invoice", "document_type" =>"4")));

try {
	$t = new \Niver\PivotTable( "im_business_info", $page,
		"date_format(date, '%m')", "part_id", "net_amount" ); // month_with_index(DATE)
} catch ( Exception $e ) {
	print $e->getMessage();
	die (2);
}

// var_dump($t);

$args = array("row_trans" => array ("part_id" => "get_customer_name"), "order" => "order by 1, 2");

try {
	$table = $t->Create(
		'/org/business/c-get-business_info.php?document_type=4&part_id=%s&date=' . $year . '-' . '%02s-28',
		'invoice_table.php?part_id=%s',
		$args );
} catch ( Exception $e ) {
	print $e->getMessage();
	die (3);
}

// var_dump($table);

try {
	print gui_table_args( $table, "invoices", $args );
} catch ( Exception $e ) {
	print $e->getMessage();
	die (4);
}

print gui_hyperlink( "שנה קודמת", "invoice_table.php?year=" . ( $year - 1 ) );
