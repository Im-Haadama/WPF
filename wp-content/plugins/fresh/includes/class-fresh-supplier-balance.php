<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/02/19
 * Time: 20:39
 */

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

	public function init_hooks()
	{
		$menu = new Core_Admin_Menu();

		$menu->AddMenu("הנהלת חשבונות", "הנהלת חשבונות", "edit_shop_orders", "supplier_accounts", array(__CLASS__, 'supplier_accounts'));
		AddAction("get_supplier_open_account", array(Fresh_Supplier_Balance::instance(), 'supplier_open_account'));
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'fresh_supplier_balance' => array( 'Fresh_Supplier_Balance::main',    'show_supplier_balance' ));          // Payments data entry
	}

	static function supplier_accounts() {
		$result = "";

		$tabs = [];

		if ($operation = GetParam("operation", false, null)) {
			$result .= apply_filters( $operation, $result, "", null, null );
			print $result;
			return;
		}

		$ms = Core_Db_MultiSite::getInstance();

		if ($ms->getLocalSiteID() != 2) { // Makolet
			array_push( $tabs, array( "supplier_transactions", "Suppliers transactions", self::SupplierTransactions() ) );
			array_push( $tabs, array(
				"supplier_invoices",
				"Suppliers invoices",
				Finance_Invoices::Table( AddToUrl( "selected_tab", "supplier_invoices" ) )
			) );
		}
//		array_push($tabs, array("potato", "test", self::test_price()));
		$tabs = apply_filters('wpf_accounts', $tabs);
		array_push( $tabs, array( "test_invoice4u", "Test", self::test() ) );

		$args = [];
		$args["tabs_load_all"] = true;

		$result .= Core_Html::gui_div("logging");
		$result .= Core_Html::GuiTabs( $tabs, $args );

		print  $result;
	}


	static function test()
	{
	}

	static function test_invoice()
	{
		Finance::Invoice4uConnect();

		$u = new Fresh_Client(341);
		$result = $u->getName() . "<br/>";
		$iu = $u->getInvoiceUser();
//		var_dump($iu);
		if ($iu)
			$result .= $iu->ID;
		else
			$result .= "not found";
		return $result;
	}

	static function SupplierTransactions($include_zero = false)
	{
		$supplier_id = GetParam( "supplier_id", false, null );
		if ( $supplier_id ) return self::get_supplier_transactions($supplier_id);

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
		
			$line = Core_Html::gui_cell( gui_checkbox( "chk_" . $supplier_id, "supplier_chk" ) );
			// $line .= "<td><a href = \"get-supplier-account.php?supplier_id=" . $supplier_id . "\">" . $supplier_name . "</a></td>";
			$line .= Core_Html::gui_cell( Core_Html::GuiHyperlink( $supplier_name, AddToUrl("supplier_id", $supplier_id )));
		
			$line .= "<td>" . $supplier_total . "</td>";
			array_push( $data_lines, $line );
	}

// sort( $data_lines );

		for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
			$line = $data_lines[ $i ];
			$result .= "<tr> " . trim( $line ) . "</tr>";
		}

		$result = str_replace( "\r", "", $result );

		$result .= "<center><h1>יתרות לתשלום</h1></center>";

		$result .= "</table>";

		return $result;
	}

	static function get_supplier_transactions( $supplier_id ) {
		$result = "";
		$s = new Fresh_Supplier($supplier_id);
		$result .= Core_Html::GuiHeader(1, $s->getSupplierName());
		$args = array();
		$selectors = array();
		$selectors["document_type"] = "Finance_Invoices::gui_select_document_type";
		$args["selectors"] = $selectors;

		$selectors_events = array();
		$selectors_events["document_type"] = 'onchange="update_document_type(%s)"';
		$args["selectors_events"] = $selectors_events;

		$args["links"] = array("id" => AddToUrl(array("operation"=>"invoice_show", "id" => '%d')));


		$sql = "SELECT id, date, amount, ref, pay_date, document_type, supplier_balance($supplier_id, date) as balance FROM im_business_info " .
		       " WHERE part_id = " . $supplier_id .
		       " AND document_type IN ( " . FreshDocumentType::bank . "," . FreshDocumentType::invoice ."," . FreshDocumentType::refund . ") " .
		       " and is_active = 1" .
		       " ORDER BY date DESC ";

		$result .= Core_Html::GuiTableContent( "supplier_account", $sql, $args );

		// $result .= gui_hyperlink("Add invoice", add_to_url(array("operation" => "add_invoice", "supplier_id" => $supplier_id)));

		$result .= Core_Html::GuiHeader(2, "Add transaction");
		$result .= __("Meanwhile solution for returned goods and old transactions (older that bank account in the system");
		$new_args = array("values" => array("part_id" => $supplier_id),
		                  "worker" => get_user_id(), // for gui_select_project
//		                  "company" => worker_get_companies(get_user_id()),
		                  "selectors" => array("document_type" => "Finance_Invoices::gui_select_document_type")); // , "project_id" => "gui_select_project"

		$result .= Core_Html::NewRow("business_info", $new_args);
		$result .= Core_Html::GuiButton("btn_add_row", "הוסף", "data_save_new('" . Fresh::getPost() . "', 'business_info')");

		return $result;
	}

	function supplier_open_account()
	{
		$multi_site = Core_Db_MultiSite::getInstance();
		$sql = "select " . $multi_site->LocalSiteId() . ", part_id, supplier_displayname(part_id), round(sum(amount),2) as total\n"
		       . "from im_business_info\n"
		       . " where document_type in (" . FreshDocumentType::invoice . ", " . FreshDocumentType::bank . ")\n"
		       . "group by 2\n"
		       . "having total < 0";

		$data   = "<table>";
		$result = SqlQuery( $sql );
		while ( $row = SqlFetchRow( $result ) ) {
			$data .= Core_Html::gui_row( $row );
		}
		$data .= "</table>";
		print $data;
		return true;
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

?>