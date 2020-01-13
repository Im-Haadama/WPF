<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/02/19
 * Time: 20:39
 */

require( '../r-shop_manager.php' );
print header_text( false, true, is_rtl(), array("/core/data/data.js") );
// require_once( "account.php" );

print header_text(false, true, true, array("suppliers.js", "/core/gui/client_tools.js"));

$operation = GetParam("operation", false, null);

if ($operation) {
	require_once(FRESH_INCLUDES .'/fresh/suppliers/suppliers.php');
    print handle_supplier_operation($operation);
	return;
}


$include_zero = isset( $_GET["zero"] );

$supplier_id = GetParam( "supplier_id" );
if ( $supplier_id ) {
    get_supplier_balance( $supplier_id );
	return;
}

$sql = "SELECT supplier_balance(part_id, curdate()) as balance, part_id, supplier_displayname(part_id) \n"
       . "FROM `im_business_info`\n"
       . "where part_id > 10000\n"
       . " and is_active = 1\n"
       . " and document_type in (" . FreshDocumentType::invoice . ", " . FreshDocumentType::bank . ")\n"
       . " and document_type in (" . FreshDocumentType::invoice . ", " . FreshDocumentType::bank . ")\n"
       . "group by part_id";

if ( ! $include_zero ) {
	$sql .= " having balance < 0";
}

//print $sql;

$result = sql_query( $sql );

$data = "<table>";
$data .= "<tr>";
$data .= gui_cell( "בחר" );

$data .= "<td>לקוח</td>";
$data .= "<td>יתרה לתשלום</td>";
$data .= "</tr>";

print ">הצג גם חשבונות מאופסים</a>";

$data_lines         = array();
$data_lines_credits = array();

while ( $row = sql_fetch_row( $result ) ) {
	// $line = '';
	$supplier_total = $row[0];
	$supplier_id    = $row[1];
	$supplier_name  = $row[2];

	// print $supplier_id . " " . $supplier_name . "<br/>";

	$line = gui_cell( gui_checkbox( "chk_" . $supplier_id, "supplier_chk" ) );
	// $line .= "<td><a href = \"get-supplier-account.php?supplier_id=" . $supplier_id . "\">" . $supplier_name . "</a></td>";
	$line .= gui_cell( Core_Html::GuiHyperlink( get_supplier_name( $supplier_id ),
		"get-supplier-balance.php?supplier_id=" . $supplier_id ) );

	$line .= "<td>" . $supplier_total . "</td>";
//	print $line;
//	$payment_method = get_payment_method( $customer_id );
//	$line           .= "<td>" . $row[4] . "</td>";
//	$line           .= "<td>" . get_payment_method_name( $customer_id ) . "</td>";
//	if ( $include_zero || $supplier_total > 0 ) {
//		print $line;
	//array_push( $data_lines, array( - $customer_total, $line ) );
	array_push( $data_lines, $line );
//	} else if ( $supplier_total < 0 ) {
//		//array_push( $data_lines, array( - $customer_total, $line ) );
//		array_push( $data_lines_credits, array( $customer_name, $line ) );
//	}

}

// sort( $data_lines );

for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
	$line = $data_lines[ $i ];
	$data .= "<tr> " . trim( $line ) . "</tr>";
}

$data = str_replace( "\r", "", $data );

print "<center><h1>יתרות לתשלום</h1></center>";


$data .= "</table>";

print "$data";

?>
<button id="btn_zero" onclick="zero()">אפס קרובים לאפס</button>
<button id="btn_remind" onclick="send_month_summary()">שלח תזכורת</button>
<div id="logging"></div>
</body>
</html>

<?php

function get_supplier_balance( $supplier_id ) {
    print Core_Html::gui_header(1, get_supplier_name($supplier_id));
	$args = array();
	$selectors = array();
    $selectors["document_type"] = "gui_select_document_type";
	$args["selectors"] = $selectors;

    $selectors_events = array();
    $selectors_events["document_type"] = 'onchange="update_document_type(%s)"';
	$args["selectors_events"] = $selectors_events;

	$args["links"] = array("id" => "/org/business/invoice_table.php?row_id=%s");


    $sql = "SELECT id, date, amount, ref, pay_date, document_type, supplier_balance($supplier_id, date) as balance FROM im_business_info " .
           " WHERE part_id = " . $supplier_id .
           " AND document_type IN ( " . FreshDocumentType::bank . "," . FreshDocumentType::invoice . "," . FreshDocumentType::refund . ") " .
           " and is_active = 1" .
           " ORDER BY date DESC ";

	print GuiTableContent( "supplier_account", $sql, $args );

	// print Core_Html::GuiHyperlink("Add invoice", add_to_url(array("operation" => "add_invoice", "supplier_id" => $supplier_id)));

	print Core_Html::gui_header(1, "Add transaction");
	print im_translate("Meanwhile solution for returned goods and old transactions (older that bank account in the system");
	$new_args = array("values" => array("part_id" => $supplier_id),
                      "worker" => get_user_id(), // for gui_select_project
                      "company" => worker_get_companies(get_user_id()),
                      "selectors" => array("document_type" => "gui_select_document_type")); // , "project_id" => "gui_select_project"

	print NewRow("im_business_info", $new_args);
	print Core_Html::GuiButton("btn_add_row", "data_save_new('" . GetUrl() . "', 'im_business_info')", "הוסף");
}

?>
