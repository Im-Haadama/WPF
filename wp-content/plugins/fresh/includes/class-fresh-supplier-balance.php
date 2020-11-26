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
		$menu = Core_Admin_Menu::instance();

		$menu->AddMenu("הנהלת חשבונות", "הנהלת חשבונות", "edit_shop_orders", "accounting", array(__CLASS__, 'accounting'));
		AddAction("get_supplier_open_account", array(Fresh_Supplier_Balance::instance(), 'supplier_open_account'));
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'fresh_supplier_balance' => array( 'Fresh_Supplier_Balance::main',    'show_supplier_balance' ));          // Payments data entry
	}

	static function accounting() {
		$result = "";

		$selected_tab = GetParam("st_suppliers", false, "weekly");

		$tabs = array(array("weekly", "Weekly summary", "ws"),
			array("supplier_transactions", "Suppliers accounts", "st"),
		    array("supplier_invoices", "Supply invoices", "si"));


		// Put the tab names inside the array.
		if ($operation = GetParam("operation", false, null, true)) {
			$result .= apply_filters( $operation, $result, "", null, null );
			print $result;
			return;
		}

		switch ($selected_tab)
		{
			case "weekly":
				$tab_content = "";
				$week = GetParam("week", false, date( "Y-m-d", strtotime( "last sunday" )));
				if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
					$tab_content .= Core_Html::GuiHyperlink( "שבוע הבא", AddToUrl("week", date( 'Y-m-d', strtotime( $week . " +1 week" ) ) )) . " ";
				}

				$tab_content .= Core_Html::GuiHyperlink( "שבוע קודם", AddToUrl("week",  date( 'Y-m-d', strtotime( $week . " -1 week" ) ) ));
				$tab_content .= Finance_Accounting::weekly_report($week);
				$tabs[0][2] = $tab_content;
				break;

			case "supplier_transactions":
				$tab_content = self::SupplierTransactions();
				$tabs[1][2] = $tab_content;
//				if ($ms->getLocalSiteID() != 2) { // Makolet
//					ardray_push( $tabs, array( "", "",  ) );
//					array_push( $tabs, array(
//						"supplier_invoices",
//						"Suppliers invoices",
//						Finance_Invoices::Table( AddToUrl( "selected_tab", "supplier_invoices" ) )
//					) );
//				}
				break;

			case "supplier_invoices":
				$tabs[2][2] = Finance_Invoices::Table( AddToUrl( "selected_tab", "supplier_invoices" ));
				break;

			default:
				die ("foo: $selected_tab not handled");
		}

//		array_push($tabs, array("potato", "test", self::test_price()));
//		$tabs = apply_filters('wpf_accounts', $tabs);

		$args = array("st_suppliers" => $selected_tab);
		$args["tabs_load_all"] = false;
		$args["url"] = "/wp-admin/admin.php?page=accounting";

		$result .= Core_Html::gui_div("logging");
		$result .= Core_Html::GuiTabs( "suppliers", $tabs, $args );

		print  $result;
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
//		       " and balance > 0 "
       . " and document_type in (" . Finance_DocumentType::invoice . ", " . Finance_DocumentType::bank . ", " .
		       Finance_DocumentType::invoice_refund . ")\n"
//       . " and document_type in (" . Finance_DocumentType::invoice . ", " . Finance_DocumentType::bank . ")\n"
       . "group by part_id";

		if ( ! $include_zero ) $sql .= " having balance < 0";

		$sql_result = SqlQuery( $sql );

		$data_lines         = array();
		$data_lines_credits = array();

		while ( $row = SqlFetchRow( $sql_result ) ) {
			$supplier_total = $row[0];
//			print "st=$supplier_total" . abs($supplier_total);
			if (! (abs($supplier_total)> 10)) continue;
//			print "cocccc<br/>";
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
		       " AND document_type IN ( " . Finance_DocumentType::bank . "," . Finance_DocumentType::invoice ."," . Finance_DocumentType::invoice_refund . ") " .
		       " and is_active = 1" .
		       " ORDER BY date DESC ";

		$result .= Core_Html::GuiTableContent( "supplier_account", $sql, $args );

		// $result .=Core_Html::GuiHyperlink("Add invoice", add_to_url(array("operation" => "add_invoice", "supplier_id" => $supplier_id)));

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
		       . " where document_type in (" . Finance_DocumentType::invoice . ", " . Finance_DocumentType::bank .
		       ", " . Finance_DocumentType::invoice_refund .")\n"
//		       . " and part_id = 100007"
		       . " and is_active = 1 " // order by date asc";
		       . "group by 2\n"
		       . "having total < 0";

//		print $sql;

		$data   = "<table>";
		$result = SqlQuery( $sql );
//		$total = 0;
		while ( $row = SqlFetchRow( $result ) ) {
//			$total += $row[3];
//			array_push($row, $total);
			$data .= Core_Html::gui_row( $row );
		}
//		$data .= "total=$total<br/>";
		$data .= "</table>";
		print $data;
		return true;
	}
}

?>