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

	public function init_hooks($loader)
	{
		$loader->AddAction("get_supplier_open_account", Fresh_Supplier_Balance::instance());
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'fresh_supplier_balance' => array( 'Fresh_Supplier_Balance::main',    'show_supplier_balance' ));          // Payments data entry
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

	function get_supplier_open_account()
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