<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/02/19
 * Time: 20:39
 */

//if (! function_exists('gui_cell'))
//{
//	function gui_cell($cell)
//	{
//		$args = [];
//		return Core_Html::GuiCell($cell, $args);
//	}
//
//	function gui_checkbox($id)
//	{
//		return Core_Html::GuiCheckbox($id);
//	}
//}

class Fresh_Supplier_Balance {
	protected static $_instance = null;
	private $logger;

	public function __construct( ) {
		$this->logger = new Core_Logger(__CLASS__);
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'fresh_supplier_balance' => array( 'Fresh_Supplier_Balance::main',    'show_supplier_balance' ));          // Payments data entry
	}

	static function main($include_zero = false)
	{
		$result = "<table>";
		
		$sql = "SELECT supplier_balance(part_id, curdate()) as balance, part_id, supplier_displayname(part_id) \n"
       . "FROM `im_business_info`\n"
       . "where part_id > 10000\n"
       . " and is_active = 1\n"
       . " and document_type in (" . FreshDocumentType::invoice . ", " . FreshDocumentType::bank . ")\n"
       . " and document_type in (" . FreshDocumentType::invoice . ", " . FreshDocumentType::bank . ")\n"
       . "group by part_id";

		if ( ! $include_zero ) $sql .= " having balance < 0";

		$sql_result = SqlQuery( $sql );

		$data_lines         = array();
		$data_lines_credits = array();

		while ( $row = SqlFetchRow( $sql_result ) ) {
			$supplier_total = $row[0];
			$supplier_id    = $row[1];
			$supplier_name  = $row[2];
		
			$line = gui_cell( gui_checkbox( "chk_" . $supplier_id, "supplier_chk" ) );
			// $line .= "<td><a href = \"get-supplier-account.php?supplier_id=" . $supplier_id . "\">" . $supplier_name . "</a></td>";
			$line .= gui_cell( Core_Html::GuiHyperlink( $supplier_name,
				"get-supplier-balance.php?supplier_id=" . $supplier_id ) );
		
			$line .= "<td>" . $supplier_total . "</td>";
			array_push( $data_lines, $line );
	}

// sort( $data_lines );

		for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
			$line = $data_lines[ $i ];
			$result .= "<tr> " . trim( $line ) . "</tr>";
		}

		$result = str_replace( "\r", "", $result );

		print "<center><h1>יתרות לתשלום</h1></center>";


		$result .= "</table>";

		return $result;
	}
}

//
//require( '../r-shop_manager.php' );
//print header_text( false, true, is_rtl(), array("/core/data/data.js") );
//// require_once( "account.php" );
//
//print header_text(false, true, true, array("suppliers.js", "/core/gui/client_tools.js"));
//
//$operation = GetParam("operation", false, null);
//
//if ($operation) {
//	require_once(FRESH_INCLUDES .'/fresh/suppliers/suppliers.php');
//    print handle_supplier_operation($operation);
//	return;
//}
//
//
//$include_zero = isset( $_GET["zero"] );
//
//$supplier_id = GetParam( "supplier_id" );
//if ( $supplier_id ) {
//    get_supplier_balance( $supplier_id );
//	return;
//}
//
//
//$data = "<table>";
//$data .= "<tr>";
//$data .= gui_cell( "בחר" );
//
//$data .= "<td>לקוח</td>";
//$data .= "<td>יתרה לתשלום</td>";
//$data .= "</tr>";
//
//print ">הצג גם חשבונות מאופסים</a>";
//
//
//
//
//function get_supplier_balance( $supplier_id ) {
//    print Core_Html::gui_header(1, get_supplier_name($supplier_id));
//	$args = array();
//	$selectors = array();
//    $selectors["document_type"] = "gui_select_document_type";
//	$args["selectors"] = $selectors;
//
//    $selectors_events = array();
//    $selectors_events["document_type"] = 'onchange="update_document_type(%s)"';
//	$args["selectors_events"] = $selectors_events;
//
//	$args["links"] = array("id" => "/org/business/invoice_table.php?row_id=%s");
//
//
//    $sql = "SELECT id, date, amount, ref, pay_date, document_type, supplier_balance($supplier_id, date) as balance FROM im_business_info " .
//           " WHERE part_id = " . $supplier_id .
//           " AND document_type IN ( " . FreshDocumentType::bank . "," . FreshDocumentType::invoice . "," . FreshDocumentType::refund . ") " .
//           " and is_active = 1" .
//           " ORDER BY date DESC ";
//
//	print GuiTableContent( "supplier_account", $sql, $args );
//
//	// print Core_Html::GuiHyperlink("Add invoice", add_to_url(array("operation" => "add_invoice", "supplier_id" => $supplier_id)));
//
//	print Core_Html::gui_header(1, "Add transaction");
//	print im_translate("Meanwhile solution for returned goods and old transactions (older that bank account in the system");
//	$new_args = array("values" => array("part_id" => $supplier_id),
//                      "worker" => get_user_id(), // for gui_select_project
//                      "company" => worker_get_companies(get_user_id()),
//                      "selectors" => array("document_type" => "gui_select_document_type")); // , "project_id" => "gui_select_project"
//
//	print NewRow("im_business_info", $new_args);
//	print Core_Html::GuiButton("btn_add_row", "data_save_new('" . GetUrl() . "', 'im_business_info')", "הוסף");
//}
//
//?>