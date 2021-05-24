<?php


class Freight_Legacy {
	private $legacy_user;
	/**
	 * Freight_Legacy constructor.
	 */
	public function __construct($legacy_user) {
		$this->legacy_user = $legacy_user;
	}

	function general_settings() {
		$result = "";
		$delivery_notes = "notes";
		$invoices       = "invoices";

		$selected_tab = GetParam("st_deliveries", false, 'deliveries_to_do');

		$tabs = array(array("deliveries_to_do", "Deliveries to do", "ws"),
			array("deliveries_done", "Deliveries done", "st"),
			array("delivery_notes", "Delivery Notes", "si"));

		switch ($selected_tab){
			case "deliveries_to_do":
				$tabs[0][2]=self::deliveries();
				break;
			case "deliveries_done":
				$tabs[1][2] = self::done_deliveries();
				break;
			case "delivery_notes":
				$tabs[2][2] =  self::delivery_notes();
				break;
			default:
				print $selected_tab . " not handled<br/>";
		}

		$args = [];
		$args["tabs_load_all"] = false;
		$args["st_deliveries"] = $selected_tab;

		$result .= Core_Html::GuiTabs( "deliveries", $tabs, $args );
		$result .= Core_Html::gui_div("logging");

		print  $result;
	}

	function init_hooks($loader)
	{
//		FreightLog(debug_trace(10));
		$loader->AddAction("create_ship", $this, 'create_ship_wrap');
		$loader->AddAction("create_subcontract_delivery_invoice", $this, "create_subcontract_delivery_invoice");
	}

	function create_ship_wrap()
	{
		$ids_ = $_GET["ids"];
		$ids  = explode( ',', $ids_ );

		// TODO: select customer id.
		return self::invoice_create_ship( $this->legacy_user, $ids );
	}

	function invoice_create_ship( $customer_id, $order_ids )
	{
		$debug = 0;
		if ($debug) MyLog(__FUNCTION__);
		$invoice = Finance_Business_Logic::Invoice4uConnect();

		if ( !$invoice or is_null( $invoice->token ) ) return false;

		$user = new Fresh_Client($customer_id);
		$client = $user->getInvoiceUser();
		if (! $client) {
			print "user not found<br/>";
			return 0;
		}
		$doc = new InvoiceDocument();

		$iEmail                = new InvoiceEmail();
		$iEmail->Mail          = $user->get_customer_email();
		$doc->AssociatedEmails = Array( $iEmail );
		//var_dump($client->ID);

		$doc->ClientID     = $client->ID;
		$doc->DocumentType = InvoiceDocumentType::InvoiceShip;

		// Set the subject
		$subject      = "משלוחים " . " " . CommaImplode( $order_ids );
		$doc->Subject = $subject;

		// Add the deliveries
		$doc->Items = Array();

		$total_lines = 0;
		$net_total   = 0;
		foreach ( $order_ids as $order_id ) {
			$o          = new Finance_Order( $order_id );
			$item       = new InvoiceItem();
			$item->Name = $o->GetComments();
			if ( strlen( $item->Name ) < 2 )
				$item->Name = "משלוח עבור " . $o->CustomerName() . " תאריך " . $o->OrderDate();

			// TODO: display prices.
			$item->Price           = round($o->getShippingFee(), 2);
			MyLog(__FUNCTION__ . "price=" . $item->Price);
			if (! $item->Price) $item->Price = 37.4;
			$net_total             += $item->Price;
			$item->Quantity        = 1;
			$item->TaxPercentage   = Israel_Shop::getVatPercent();
			$item->TotalWithoutTax = Israel_Shop::totalWithoutVat($item->Price);
			$item->Total           = round( $item->Price * $item->Quantity, 2 );
			array_push( $doc->Items, $item );
			$total_lines += $item->Total;

			if ($debug) MyLog("Completing $order_id");
			$o->setStatus( "wc-completed" );
		}

		// print "create<br/>";
		$doc_id = $invoice->CreateInvoiceDocument( $doc );
		Finance::add_transaction( $customer_id, date( 'Y-m-d' ), $total_lines, 0, $doc_id, 1, $net_total,
			Finance_DocumentType::ship );

		// var_dump($doc);
		return $doc_id;
	}

	function deliveries()
	{
		$table = self::print_deliveries( "`post_status` in ('wc-awaiting-shipment', 'wc-processing') " .
		                           " and post_excerpt like '%משלוח המכולת%'", true );

		if ( strlen( $table ) > 10 ) {
			$result = "";
			$result .=  Core_Html::GuiHeader( 1, "משלוחים לביצוע" );
// $sql = "select post_id from wp_posts where post_status = 'wc-
			$result .=  Flavor_Mission_Views::delivery_table_header();
			$result .=  $table;

			$result .=  "</table>";

			$result .= Core_Html::GuiButton("btn_done", "Delivered", "delivery_done('" . WPF_Flavor::getPost() . "')");

			return $result;
		} else {
			print Core_Html::GuiHeader( 1, "כל המשלוחים בוצעו" );
		}
	}

	function done_deliveries() {
		$result ="";
		$table = self::print_deliveries( "post_status = 'wc-awaiting-document'", true );

		if ( strlen( $table ) > 10 ) {
			$result .= Core_Html::GuiHeader( 1, "משלוחים שבוצעו" );
			$result .= Flavor_Mission_Views::delivery_table_header();
			$result .= $table;
			$result .= "</table>";
		}
		$result .= Core_Html::GuiButton( "btn_create_ship", "צור תעודת משלוח", array("action"=>"create_ship('" . Freight::getPost() . "')") );
		return $result;
	}
	
	function d() {

		print '<div id="logging">';

		printCore_Html::GuiHeader( 1, "הוספת משלוחים" );

		print "אנא בחר משלוחים לשבוע זה" . "<br/>";

//$sql = "SELECT DISTINCT user_id FROM wp_usermeta
//WHERE meta_key = 'legacy_user' AND meta_value=1";

		$sql = 'SELECT user_id FROM wp_usermeta WHERE meta_key like "%client_type"
AND (meta_value = "legacy" or meta_value = 1)';

		print $sql;
		$result = sql_query( $sql );

		print "<table>";
		while ( $row = mysqli_fetch_row( $result ) ) {
			print user_checkbox( $row[0] );
		}
		print "</table>";

		print gui_button( "btn_done", "done()", "בצע" );

		print gui_button( "btn_clear", "clear_legacy()", "נקה" );

		global $legacy_user;
	}

	function delivery_notes() {
		$result = "";
		if ($operation = GetParam("operation", null)) {
			Core_Hook_Handler::instance()->DoAction($operation, null);
			return;
		}
		$data = self::business_open_ship( $this->legacy_user );

// print $data . " " . strlen($data);
		if ( strlen( $data ) > 182 ) {
			$result .= Core_Html::GuiHeader( 1, "תעודות משלוח פתוחות" );

			$result .= $data;

			$result .= Core_Html::GuiButton( "id_legacy_invoice", "הפק חשבונית מס", array("action" => "create_subcontract_delivery_invoice('". Freight::getPost() . "')") );

		} else {
			$result .= Core_Html::GuiHeader( 1, "כל תעודות המשלוח הוכנסו לחשבוניות" );
		}
		return $result;
	}

	function invoices()
	{
		$sql = "select * from im_business_info where part_id = " . $this->legacy_user .
		       " and document_type = 4";

		$args  = array();
		$table = Core_Html::GuiTableContent( "open_invoices", $sql, $args );

		if ( $table ) {
			print Core_Html::GuiHeader( 1, "חשבוניות פתוחות" );
			print $table;
		}
	}

	function user_checkbox( $id ) {
		return Core_Gem::gui_row( array(
			Core_Html::GuiCheckbox("chk_" . $id, false,array ("class"=>"user_chk" )),
			get_user_name( $id )
		) );
	}

	function print_deliveries( $query, $selectable = false ) {
		// print "q= " . $query . "<br/>";
		$data = "";
//		$sql = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
		$sql = 'SELECT posts.id, order_user(posts.id), order_user(posts.id) '
		       . ' FROM `wp_posts` posts'
		       . ' WHERE ' . $query;

		$sql .= ' order by 1';

		$orders    = SqlQuery( $sql );
		$prev_user = - 1;
		while ( $order = SqlFetchRow( $orders ) ) {
			$order_id   = $order[0];
			$o          = new Finance_Order( $order_id );
			$is_group   = $order[1];
			$order_user = $order[2];
			if ( ! $is_group ) {
				$data .= $o->PrintHtml( $selectable );
				continue;
			} else {
				if ( $order_user != $prev_user ) {
					$data      .= $o->PrintHtml( $selectable );
					$prev_user = $order_user;
				}
			}
		}

		return $data;
	}

	function business_open_ship( $part_id )
	{
		$sql = "select id, date, amount, net_amount, ref " .
		       " from im_business_info " .
		       " where part_id = " . $part_id .
		       " and invoice is null " .
		       " and is_active = 1 " .
		       " and document_type = " . Finance_DocumentType::ship;


		$args = array("add_checkbox" => 1, "checkbox_class" => "delivery_note", "page_number"=>-1,
			"links" => array("id"=>AddToUrl(array("operation" => "invoice_show", "id"=>"%d"))));


		$data = Core_Html::GuiTableContent( "table", $sql, $args );

		// $rows = sql_query_array($sql );

		return $data; // gui_table($rows);
	}

	function create_subcontract_delivery_invoice(  ) {
		$ids = GetParam("ids");
		// $invoice_client_id
		global $invoice_user_sub;
		global $invoice_password_sub;
		global $legacy_user;

		$invoice = new Finance_Invoice4u(INVOICE_USER_SUB, INVOICE_PASSWORD_SUB);
		if (! $invoice) {
			die ("Not connected to invoice4u");
		}

//		if (!defined(INVOICE_CLINET)) die ("define ");
//
//		// print "invoice client id " . $invoice_client_id . "<br/>";
//
//		$client = $invoice->GetCustomerById( $this->legacy_user );
//
//		if ( ! ( $clien21
//t->ID ) > 0 ) {
//			print "Invoice client not found " . $invoice_client_id . "<br>";
//
//			// var_dump( $client );
//
//			return 0;
//		}
//		$email = $client->Email;
		// print "user mail: " . $email . "<br/>";
		$doc        = new InvoiceDocument();
		$doc->Items = Array();
		$iEmail                = new InvoiceEmail();
		$legacy_user = new Fresh_Client($this->legacy_user);
		$iEmail->Mail          = $legacy_user->get_customer_email();
		$doc->AssociatedEmails = Array( $iEmail );

		$client = $legacy_user->getInvoiceUser(true);

		$doc->ClientID     = $client->ID;

		$doc->DocumentType = InvoiceDocumentType::Invoice;

		$sql = "select id, date, amount, net_amount, ref " .
		       " from im_business_info " .
		       " where id in (" . CommaImplode($ids) . ")";

		$result = SqlQuery( $sql );

		$ship_ids     = array();
		$business_ids = array();
		$net_total    = 0;

		// Add the shipments
		while ( $row = SqlFetchRow( $result ) ) {
			$business_id = $row[0];
			array_push( $business_ids, $business_id );
			$ship_id = $row[4];
			array_push( $ship_ids, $ship_id );

			$item       = new InvoiceItem();
			$item->Name = "תעודת משלוח מספר " . $ship_id;

			$item->Price           = $row[2];
			$net_total             += $row[3];
			$item->Quantity        = 1;
			$item->TaxPercentage   = Israel_Shop::getVatPercent();
			$item->TotalWithoutTax = $row[3];
			$item->Total           = round( $item->Price * $item->Quantity, 2 );
			array_push( $doc->Items, $item );
		}

		$total_lines = 0;
		$net_total   = 0;

		// Set the subject
		$subject      = "חשבונית לתעודת משלוח " . " " . CommaImplode( $ship_ids );
		$doc->Subject = $subject;

		$doc_id = $invoice->CreateInvoiceDocument( $doc );

		if ( $doc_id ) {
			Finance::add_transaction( $this->legacy_user, date( 'Y-m-d' ), $total_lines, 0, $doc_id, 1, $net_total,
				Finance_DocumentType::invoice );

			SqlQuery ("UPDATE im_business_info SET invoice = " . $doc_id .
			           " WHERE id IN ( " . CommaImplode( $business_ids ) . " )" );
		}

		return $doc_id;
	}

}