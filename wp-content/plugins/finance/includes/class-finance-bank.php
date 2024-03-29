<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/03/18
 * Time: 17:11
 */

class Finance_Bank
{
	static private $_instance;
	private $post_file;
	private $version;
	private $user;
	private $multi_site;
	private $table_prefix;

	public function __construct( $post_file ) {
		$this->post_file = $post_file;
		$this->version   = "1.0";
		$this->user = new Core_Users();
		$this->multi_site = Core_Db_MultiSite::getInstance();
		$this->table_prefix = GetTablePrefix();

		self::$_instance = $this;

		// For rem
		self::init_remoting(Core_Hook_Handler::instance());
	}

	private function init_remoting($loader)
	{
		$loader->AddAction("bank_status", $this, 'bank_status');
//		AddAction("bank_show_import", array($this, "show_import"));
		$loader->AddAction("bank_create_invoice_receipt", $this, "bank_create_invoice_receipt");
		$loader->AddAction("bank_create_receipt", $this, "bank_create_receipt");
	}

	public static function instance() :Finance_Bank {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( WPF_Flavor::getPost() );
		}

		return self::$_instance;
	}

	static function getPost()
	{
		return self::instance()->post_file;
	}

	function init_hooks(Core_Hook_Handler  $loader) {
//		if (get_user_id() == 1) {
//			print debug_trace( 10 );
//			print '=====================================<br/>';
//
//		}
//		if (get_user_id() == 1) print debug_trace(10);
		if (! TableExists("bank_account")) {
			$db = new Finance_Database();
			$db->install($this->version);;
		}

		$loader->AddAction( "finance_bank_accounts", $this, "show_bank_accounts" );
		$loader->AddAction( "finance_bank_account", $this, "show_bank_account" );
		$loader->AddAction( "finance_bank_payments", $this, "show_bank_payments" );
		$loader->AddAction( "bank_show_create_invoice_receipt", $this, 'bank_show_create_invoice_receipt' );
		$loader->AddAction( "bank_show_create_receipt", $this, 'bank_show_create_receipt' );
		$loader->AddAction('create_bank_account', $this, 'create_bank_account');
		$loader->AddAction("bank_link_invoice", $this, 'bank_link_invoice');
		$loader->AddAction('bank_check_valid', $this, 'bank_check_valid', 10, 2);

		add_action('admin_menu', array($this, 'admin_menu'));

		$args = array("post_file" => Finance::getPostFile());
		Core_Gem::getInstance()->AddVirtualTable("bank", $args, $loader);

//		Flavor_Roles::addRole('business_admin', array('finance'));
	}

	function bank_show_create_invoice_receipt($params = null)
	{
		$id = GetParam( "id" );
		print $this->show_create_invoice_receipt($id);
	}

	function bank_show_create_receipt()
	{
		$id = GetParam( "id" );
		print $this->show_create_receipt($id);
	}

	/**
	 * @return Core_Users
	 */
	public function getUser(): Core_Users {
		return $this->user;
	}

	function create_bank_account()
	{
		$args = [];
		$args['post_file'] = $this->post_file;
		$result = Core_Gem::GemAddRow("bank_account", 'Create bank account', $args);

		print $result;
	}

	function show_bank_accounts_wrap()
	{
		print self::show_bank_accounts();
	}

	function show_bank_accounts()
	{
		if (! get_user_id() || ! current_user_can("show_bank")) {
			print "no permissions";
			die (1);
		}

		$post_file = Finance::getPostFile();

		$accounts = self::getBankAccounts(get_user_id());
		if (! $accounts) {
			return "No bank accounts found" . Core_Html::GuiButton("btn_create_bank", "Create", "bank_create_account('$post_file', result)") .
			       "<div id='result'></div>";
		}

		if (count($accounts) == 1)
			return $this->show_bank_account($accounts[0]);

		return "multiple bank accounts not implemented";
	}

	function show_bank_account($account_id)
	{
		$table_prefix = GetTablePrefix("bank");
		if (! current_user_can("show_bank", $account_id))
			return "no permissions";

		$operation = GetParam("operation", false, null, true);
		$args = [];
		if ($operation) {
			Core_Hook_Handler::instance()->DoAction($operation, $args);
			return;
//			return apply_filters( $operation, "" );
		}

		$args = [];
		$args["page"] = GetParam("page", false, null);
		$args["import_page"] = AddToUrl("account_id", $account_id);
			// Finance::getPostFile() . "?account_id=$account_id";
		$args["query"] = "account_id = $account_id";
		if ($filter = GetParam("filter", false, null)) {
			switch ($filter) {
				case "outcome":
					$args["query"] .= " and receipt is null and out_amount > 0 " .
					                  " and description not in (select description from 
					                  im_bank_transaction_types where cfo = 1)";
					break;
				case "income":
					$args["query"] .= " and in_amount > 0 " .
					                  " and description not in (select description from ${table_prefix}bank_transaction_types) ";
					break;
				case "income_to_receipt":
					$args["query"] .= " and receipt is null and in_amount > 0 " .
						" and description not in (select description from ${table_prefix}bank_transaction_types) ";
					break;
//					return self::show_bank_receipts($account_id);
			}
		}
		$args["account_id" ] = $account_id;

//		print "af1=" . $args["import_page"] . "<br/>";
		print Core_Html::GuiHeader(1, "דף חשבון") .
		      $this->transaction_filters() .
		      $this->bank_transactions($args);
		//.
//		      Core_Html::GuiHyperlink("Import", AddToUrl(array("operation" => "finance_show_bank_import",
//			      "account_id"=>$account_id)));
	}

	function transaction_filters()
	{
		$current_filter = GetParam("filter", false, "none");
		$result = "<div>";
		$income = Core_Html::GuiHyperlink("הכנסות", AddToUrl("filter", "income")) . " ";
		$income_to_receipt = Core_Html::GuiHyperlink("קבלה להכנסות", AddToUrl("filter", "income_to_receipt")) . " ";
		$outcome = Core_Html::GuiHyperlink("הוצאות", AddToUrl("filter", "outcome")) . " ";
		$clear_filters = Core_Html::GuiHyperlink("בטל סינון", AddToUrl("filter", null));

		switch($current_filter)
		{
			case "none":
				$result .= $income;
				$result .= $outcome;
				break;
			case "income":
				$result .= $income_to_receipt;
				$result .= $clear_filters;
				break;
			default:
				$result .= $clear_filters;
		}
		return $result . "</div>";
	}

//	function show_bank_import($account_id)
//	{
//		$args = self::Args();
//		// Using local action - to set account_id and check_dup. See above.
//		 $args["import_page"] = AddToUrl(array("operation"=>"finance_do_import"));
////		$args["import_page"] = AddParamToUrl(Finance::getPostFile() , array("operation" => "gem_do_import", "table" => "bank"));
//			// AddToUrl(array("operation" => "gem_do_import", "table"=>"bank"));//  . "&account_id=" . $account_id;
//		$result = Core_Gem::ShowImport("bank", $args);
//
//		print $result;
//	}

	function handle_bank_operation($operation, $url = null) {
		print "don't use";
		die( 1 ); // To check if needed.
		$table_prefix = GetTablePrefix();
		$multi_site   = Core_Db_MultiSite::getInstance();

		$u = new Core_Users();
		if ( ! $u->can( "show_bank" ) ) {
			return "no permissions";
		}
		$account_id = 1;
		$ids        = null;
		$post_file  = self::getPost();

		if ( GetParam( "search", false, 0 ) ) {
			$ids = data_search( "bank" );
			Core_Html::GuiHeader( 1, "Results" );
			if ( ! $ids ) {
				print ETranslate( "Nothing found" );

				return;
			}
		}
	}

	function bank_create_invoice_receipt() {
		$bank_amount = GetParam( "bank" );
		$date        = GetParam( "date" );
		$change      = GetParam( "change" );
		$ids         = GetParam( "ids", true );
		$site_id     = GetParam( "site_id" );
		$user_id     = GetParam( "user_id" );
		$bank_id     = GetParam( "bank_id" );

		return $this->create_multi_site_invoice_receipt( $bank_id, $bank_amount, $date, $change, $ids, $site_id, $user_id );
	}

	function bank_create_receipt()
	{
		$bank_amount = GetParam( "bank" );
		$date        = GetParam( "date" );
		$site_id     = GetParam( "site_id" );
		$user_id     = GetParam( "user_id" );
		$bank_id     = GetParam( "bank_id" );

		return $this->create_multi_site_receipt( $bank_id, $bank_amount, $date, $site_id, $user_id );
	}

	function mark_refund_bank() {
		$table_prefix = GetTablePrefix( "bank" );

		$bank_id     = GetParam( "bank_id", true );
		$supplier_id = GetParam( "supplier_id", true );
		$site_id     = GetParam( "site_id", true );
		$bank        = GetParam( "bank" );

		// 1) mark the bank transaction to invoice.
		$b    = Finance_Bank_Transaction::createFromDB( $bank_id );
		$date = $b->getDate();

		// 2) mark the invoices to transaction.
		$command = Finance::getPostFile() . "?operation=add_refund_bank&supplier_id=" . $supplier_id .
		           "&bank_id=" . $bank_id . "&date=" . $date .
		           "&amount=" . $bank;
		//			print $command;
		$multi_site = Core_Db_MultiSite::getInstance();

		print $multi_site->Run( $command, $site_id );

		print "מעדכן שורות";
		$sql = "update ${table_prefix}bank " .
		       " set receipt = \"refund\", " .
		       " site_id = " . $site_id .
		       " where id = " . $bank_id;

		return SqlQuery( $sql );
	}

	function mark_return_bank() {
		$id = GetParam( "id" );
		$b  = Finance_Bank_Transaction::createFromDB( $id );
		print Core_Html::GuiHeader( 1, "סמן החזר מהספק" );

		print Core_Html::GuiHeader( 2, "פרטי העברה" );
		print Core_Html::gui_table_args( array(
				array( "תאריך", gui_div( "pay_date", $b->getDate() ) ),
				array( "סכום", gui_div( "bank", $b->getInAmount() ) ),
				array( "מזהה", gui_div( "bank_id", $id ) )
			)
		);

		//			print Core_Html::GuiHeader(2, "חשבונית שהופקה");
		//			print GuiInput("invoice_id");
		//			print Core_Html::GuiButton("btn_invoice_exists", "invoice_exists()", "Exists invoice");

		$sums = array();
		print Core_Html::GuiHeader( 2, "בחר ספק" );
		print gui_select_open_supplier();
		print '<div id="logging"></div>';
		print '<div id="transactions"></div>';
		print gui_table( array(
			array(
				"תשלום",
				Core_Html::GuiButton( "btn_refund", "mark_refund_bank()", "סמן זיכוי" )
			),
			array( "עודף", " <div id=\"change\"></div>" )
		), "payment_table", true, true, $sums, "", "payment_table" );
	}

	function bank_transaction_types()
	{
		if (! $this->getUser()->can("cfo"))
			return "no permissions";
		$args = array();
		// $args["selectors"] = array("part_id" => "gui_select_supplier");

		print Core_Html::GuiTableContent( "bank_transaction_types", null, $args );
		print Core_Html::GuiHyperlink( "add", AddToUrl( "operation", "add_transaction_types" ) );
		return true;
	}

	function add_transaction_types()
	{
		if (! $this->getUser()->can("cfo"))
			return "no permissions";
		$args              = self::Args();
		$args["selectors"] = array( "part_id" => "Finance_Supplier::gui_select_supplier" );

		print Core_Gem::GemAddRow( "bank_transaction_types", "Transaction types", $args );
		return true;
	}

	function search() {
		$table_prefix = GetTablePrefix("bank");
		$url = GetUrl();
		$args           = array();
		$search_url     = "search_table('${table_prefix}bank', '" . AddParamToUrl( $url, "search", "1" ) . "')";
		$args["search"] = $search_url; //'/fresh/bank/bank-page.php?operation=do_search')";
		Core_Gem::GemSearch( "bank", $args );

		return true;
	}

	function do_search() {
		$ids = data_search( "bank" );
		Core_Html::GuiHeader( 1, "Results" );
		if ( ! $ids ) {
			print ETranslate( "Nothing found" );

			return;
		}

		print self::bank_transactions( array( "query" => "id in (" . CommaImplode( $ids ) . ")" ) );
	}

	function show_bank() {
		$args              = self::Args( $u );
		$args["selector"]  = __CLASS__ . "::gui_select_bank_account";
		$args["order"]     = "3 desc";
		$args["hide_cols"] = array_merge( $args["hide_cols"], array( "id"              => 1,
		                                                             "account_id"      => 1,
		                                                             "tmp_client_name" => 1,
		                                                             "customer_id"     => 1
		) );

		// $args["post_action"] = Finance::getPostFile()
		return true;
	}

//	function show_transactions() {
//		print self::bank_transactions( array( "query" => $ids ? "id in (" . CommaImplode( $ids ) . ")" : null ) );
//	}

	function bank_link_invoice()
	{
		$table_prefix = GetTablePrefix("bank_lines");
		$multi_site = Core_Db_MultiSite::getInstance();

		$bank_id     = GetParam( "bank_id", true );
		$supplier_id = GetParam( "supplier_id", true );
		$site_id     = GetParam( "site_id", true );
		$to_account = GetParam("to_account", false, false);
		$bank        = GetParam( "bank" );

		$b    = Finance_Bank_Transaction::createFromDB( $bank_id );
		$date = $b->getDate();

		$command = Finance::getPostFile() . "?operation=finance_add_payment&supplier_id=" . $supplier_id .
		           "&bank_id=" . $bank_id . "&date=" . $date .
		           "&amount=" . $bank;

		if ($to_account) {
			$sql = "update ${table_prefix}bank " .
			       " set receipt = 0, " .
			       " site_id = " . $site_id .
			       " where id = " . $bank_id;

			SqlQuery( $sql );

		} else {
			$ids = GetParamArray( "ids" );

			// 1) mark the bank transaction to invoice.
			foreach ( $ids as $id ) {
				$get_command     = Finance::getPostFile() . "?operation=finance_get_transaction_amount&id=" . $id;
				$amount      = doubleval( strip_tags( $multi_site->Run( $get_command, $site_id ) ) );
				$line_amount = min( $amount, $bank );

				$sql = "INSERT INTO ${table_prefix}bank_lines (line_id, amount, site_id, part_id, invoice)\n" .
				       "VALUES (" . $bank_id . ", " . $line_amount . ", " . $site_id . ", " . $supplier_id . ", " .
				       $id . ")";

				SqlQuery( $sql );
			}
			$command .= "&ids=" . implode( ",", $ids );

			print $multi_site->Run( $command, $site_id );

			$sql = "update ${table_prefix}bank " .
			       " set receipt = \"" . CommaImplode( $ids ) . "\", " .
			       " site_id = " . $site_id .
			       " where id = " . $bank_id;

			SqlQuery( $sql );
		}

	// 2) mark the invoices to transaction.
	//			print $command;

		print "מעדכן שורות";
	}

	static function gui_select_open_supplier( $id = "supplier" ) {
		$multi_site = Core_Db_MultiSite::getInstance();

		$url = self::getPost() . "?operation=get_supplier_open_account";
		$values  = Core_Html::html2array( $multi_site->GetAll( $url));

		if (! $values) 	return "nothing found " . $url;
		$open    = array();
		$list_id = 1;
		foreach ( $values as $value ) {
			$new                = array();
			$new["id"]          = $list_id ++;
			$new["site_id"]     = $value[0];
			$new["supplier_id"] = $value[1];
			$new["name"]        = $value[2];
			$new["balance"]     = $value[3];
//			print $new["id"] . " " . $new["supplier_id"] . " " . $new["name"] . "<br/>";
			array_push( $open, $new );
		}

		$datalist_id = "open_supplier";
		$result = Core_Html::GuiDatalist($datalist_id, $open, "id", "name");
		$result .= Core_Html::GuiInputDatalist("supplier", $datalist_id, 'onchange="supplier_selected()"');
		return $result;
		// return gui_select_datalist( $id, "im_suppliers", "open_supplier", "name", $open, 'onchange="supplier_selected()"', null, true );
	}

	function gui_select_client_open_account( $id = "open_account", $events = 'onchange="client_selected()"' ) {
		$output = "";
		$multi_site = Core_Db_MultiSite::getInstance();
		$url = Finance::getPostFile() . "?operation=get_client_open_account";
		$result = $multi_site->GetAll( $url );
		foreach ($multi_site->getHttpCodes() as $site_id => $code){
			if ($code != 200) {
				FinanceLog("Can't get result from " . $multi_site->getSiteName($site_id) . " error: $code");
				if (get_user_id()== 1) $output .= Core_Html::GuiHyperlink($multi_site->getSiteName($site_id), $multi_site->getSiteURL($site_id) . $url) . " ";
			}
		}
		$values  = Core_Html::html2array( $result );
		$open    = array();
		$list_id = 0;
		foreach ( $values as $value ) {
			$new              = array();
			$new["id"]        = $list_id ++;
			$new["site_id"]   = $value[0];
			$new["client_id"] = $value[1];
			$new["name"]      = $value[2];
			$new["balance"]   = $value[3];
			array_push( $open, $new );
		}
//		$events = ;
		$datalist_id = $id . "_datalist";
		$output .= Core_Html::GuiInputDatalist($id, $datalist_id, $events);
		$output .= Core_Html::GuiDatalist( $datalist_id, $open, "id","name", false);

		return $output;
	}


	static public function bank_wrapper()
	{
		print Core_Html::GuiHeader(1, "Bank");
		$operation = GetParam("operation", false, "bank_status", true);

		print apply_filters($operation, '');
	}

	function show_bank_payments($account_id = 1)
	{
		$account_id=  1;
		$table_prefix = $this->table_prefix;
		$result = "";
		$args = array();
		$result .= Core_Html::GuiHeader( 1, "Payments" );
		$args["header_fields"] = array( "id" => "Id", "date" => "Date", "description" => "Description", "out_amount" => "Amount" );
		$args["actions"]       = array(
			array(
				"Mark payment",
				GetUrl(1) . "?operation=bank_create_pay&id=%s"
			)
		);
		$page                  = GetParam( "page", false, 1 );
		$rows_per_page         = 20;
		$offset                = ( $page - 1 ) * $rows_per_page;
		$query                 = "  account_id = " . $account_id . " and receipt is null and out_amount > 0 " .
		                         " and description not in (select description from ${table_prefix}bank_transaction_types) ";

//		if ( $ids ) {
//			$query .= " and id in (" . CommaImplode( $ids ) . ")";
//		}
		// " order by date desc limit $rows_per_page offset $offset";

		$args["fields"] = array( "id", "date", "description", "out_amount", "reference", "client_name" );
//			$sql = "select id, date, description, out_amount, reference from im_bank where account_id = " . $account_id .
//			       " and receipt is null and out_amount > 0 " .
//			       " and description not in (select description from im_bank_transaction_types) " .
//			       " order by date desc limit $rows_per_page offset $offset";

		//		$result .= GuiTableContent( "im_banking", $sql, $args);

		//		$result .= Core_Html::GuiHyperlink("Older", add_to_url("page", $page + 1));
		$args["query"] = $query;

		$result .= self::bank_transactions( $args );

		return $result;

	}

	static function bank_transactions($args = null)
	{
		$table_prefix = GetTablePrefix();

		$result = "";

		$account_id = GetArg($args, "account_id", null);
		if (! $account_id) return "no account selected";

		$page = GetParam("page_number", false, 1);
		$args["page_number"] = $page;
		$args["rows_per_page"] = 16;
		$args["class"] = "widefat";
		$args["prepare_plug"] = __CLASS__ . "::prepare_row";
		$args["enable_import"] = true;

		$fields = GetArg($args, "fields", array("id", "date", "description", "out_amount", "in_amount", "balance", "receipt", "client_name"));

		$sql = "select " . CommaImplode($fields) . " from ${table_prefix}bank ";
		$sql .= " where " . $args["query"] . " and account_id = " . $account_id;
		$sql .= " order by date desc ";
		$args["sql"] = $sql;

		$result .= Core_Gem::GemTable("bank", $args);

		return $result;
	}

	static function prepare_row($row)
	{
		$id = $row['id'];
		if (($row['receipt'] == '') and ($row['in_amount'] > 0))
			$row['receipt'] = Core_Html::GuiHyperlink(__("Invoice-Receipt"), AddToUrl(array("operation" => "bank_show_create_invoice_receipt", "id" => $id))) . " ".
			                  Core_Html::GuiHyperlink(__("Receipt"), AddToUrl(array("operation" => "bank_show_create_receipt", "id" => $id)));

		if (($row['receipt'] == '') and ($row['out_amount'] > 0))
			$row['receipt'] = Core_Html::GuiHyperlink(__("Mark"), AddToUrl(array("operation" => "bank_create_pay", "id" => $id)));

		return $row;

	}

	function show_create_invoice_receipt($id)
	{
		$b = Finance_Bank_Transaction::createFromDB( $id );
		$result = ""; // "Creating invoice for bank transaction";
		$result .= Core_Html::GuiHeader( 1, "הפקת חשבונית קבלה להפקדה מבנק " );

		$result .= Core_Html::GuiHeader( 2, "פרטי העברה" );
		$result .= Core_Html::gui_table_args( array(
				array( "תאריך", Core_Html::gui_div( "pay_date", $b->getDate() ) ),
				array( "סכום", Core_Html::gui_div( "bank", $b->getInAmount() ) ),
				array( "מזהה", Core_Html::gui_div( "bank_id", $id ) ),
				array( "פרטי התנועה", Core_Html::gui_div( "bank_id", $b->getClientName() ) )
			)
		);

		$result .= Core_Html::GuiHeader(2, "חשבונית שהופקה");
		$result .= Core_Html::GuiInput("invoice_id");
		$result .= Core_Html::GuiButton("btn_invoice_exists", "Exists invoice", array("action" => "invoice_exists()"));

		$result .= Core_Html::GuiHeader( 2, "בחר לקוח" );
		$result .= self::gui_select_client_open_account();
		$result .= '<div id="logging"></div>';
		$result .= '<div id="transactions"></div>';
//		function gui_table(	$rows, $id = null, $header = true, $footer = true, &$acc_fields = null, $style = null, $class = null, $show_fields = null,
//			$links = null, $col_ids = null, $first_id = false, $actions = null

		$result .= Core_Html::gui_table_args(array(
			array("תשלום",	Core_Html::GuiButton( "btn_receipt", "הפק חשבונית מס קבלה", array("action" => "create_invoice_receipt_from_bank('".Finance::getPostFile()."')") )),
			array( "עודף", " <div id=\"change\"></div>" )));
		// ,"payment_table", "class" => "payment_table"));

//		$result .= gui_table( , "payment_table", true, true, $sums, "", "payment_table" );

		return $result;
	}

	function show_create_receipt($id)
	{
		$b = Finance_Bank_Transaction::createFromDB( $id );
		$result = "Creating receipt for bank transaction";
		$result .= Core_Html::GuiHeader( 1, "הפקת קבלה להפקדה מבנק " );

		$result .= Core_Html::GuiHeader( 2, "פרטי העברה" );
		$result .= Core_Html::gui_table_args( array(
				array( "תאריך", Core_Html::gui_div( "pay_date", $b->getDate() ) ),
				array( "סכום", Core_Html::gui_div( "bank", $b->getInAmount() ) ),
				array( "מזהה", Core_Html::gui_div( "bank_id", $id ) ),
				array( "פרטי התנועה", Core_Html::gui_div( "bank_id", $b->getClientName() ) )
			)
		);

//		$result .= Core_Html::GuiHeader(2, "חשבונית שהופקה");
//		$result .= Core_Html::GuiInput("invoice_id");
//		$result .= Core_Html::GuiButton("btn_invoice_exists", "Exists invoice", array("action" => "invoice_exists()"));

		$result .= Core_Html::GuiHeader( 2, "בחר לקוח" );
		$result .= self::gui_select_client_open_account($id = "open_account", null);
		$result .= '<div id="logging"></div>';
		$result .= '<div id="transactions"></div>';
//		function gui_table(	$rows, $id = null, $header = true, $footer = true, &$acc_fields = null, $style = null, $class = null, $show_fields = null,
//			$links = null, $col_ids = null, $first_id = false, $actions = null

		$result .= Core_Html::gui_table_args(array(
			array("תשלום",	Core_Html::GuiButton( "btn_receipt", "הפק קבלה", array("action" => "create_receipt_from_bank('".Finance::getPostFile()."')") )),
			array( "עודף", " <div id=\"change\"></div>" )));
		// ,"payment_table", "class" => "payment_table"));

//		$result .= gui_table( , "payment_table", true, true, $sums, "", "payment_table" );

		return $result;

	}

	public function bank_status()
	{
//		print debug_trace();
		$result = "";
		$table_prefix = GetTablePrefix();

		$account = GetParam("account_id", false, null);
		if ($account) {
			return self::show_bank_account($account);
		}
		$result = Core_Html::GuiHeader(2, "Bank accounts");
		$u = new Core_Users();

		$sql = "select id, name, bank_last_transaction(id)";
		if ($u->hasRole("cfo")) $sql .= ", round(bank_balance(id), 2) ";
		$sql .= " from ${table_prefix}bank_account";

		$args = [];
		$args["links"] = array("id" => AddToUrl(array( "operation" => "show_bank_load", "account_id" => "%s")));
//		$args["id_field"] = "count_id";
		$action_url = GetUrl(1);
		$args["actions"] =
			array(array( "load", $action_url . "?operation=bank_show_import&id=%s" ));

		$accounts = Core_Data::TableData($sql, $args);
		if (! $accounts) {
			$result .= "No transactions yet";
			return $result;
		}

		$result .= Core_Html::gui_table_args($accounts, "bank_accounts", $args);
		return $result;
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'finance_bank'      => array( 'Finance_Bank::bank',    null ));          // Payments data entry
	}

	function Args($user = null)
	{
		if (! $user) $user = self::getUser();
		$args = array("page" => GetParam("page", false, -1),
		             "post_file" => self::getPost());

		if (! $user->hasRole("cfo")) {
			$args["hide_cols"] = array("balance" => 1);

			// Todo: get from transaction_types
			$args["query"] = "1 ";
//			foreach (array("פרעון הלוואה", "מסלול מורחב", "הלואה") as $type)
//				$args["query"] .= " and description not like " . quote_text('%' . $type . '%');
//
//			print $args["query"];
		} else {
			$args["hide_cols"] = array();
		}
		return $args;
	}

	function create_multi_site_invoice_receipt( $bank_id, $bank_amount, $date, $change, $ids, $site_id, $user_id ) {
		FinanceLog(__FUNCTION__);
		// IDS sent as string.

		// $msg = $bank . " " . $date . " " . $change . " " . CommaImplode($ids) . " " . $site_id . " " . $user_id . "<br/>";
		$debug = false;

		$command = Finance::getPostFile() . "?operation=create_invoice_receipt&row_ids=" . $ids .
		           "&user_id=" . $user_id . "&bank=" . $bank_amount . "&date=" . $date .
		           "&change=" . $change;
		$result  = $this->multi_site->Run( $command, $site_id, true, $debug );

		if (($code = $this->multi_site->getHttpCode($site_id)) != 200) {
			FinanceLog("can't create");
			FinanceLog("getting $command, status: " . $this->multi_site->getHttpCode($site_id));
			print "failed: error code - " . $code;
			return false;
		}

		if ( strstr( $result, "כבר" ) ) {
			FinanceLog("already paid");
			die( "failed: already paid" );
		}
		if ( strlen( $result ) < 2 ) {
			FinanceLog("Bad response for $command: $result");
			die( "failed: bad response" );
		}
		if ( strlen( $result ) > 10 ) {
			FinanceLog("Bad response for $command: $result");
			die( "failed: " . $result );
		}

		FinanceLog(__FUNCTION__ . " $result");
		$receipt = intval( trim( $result ) );
		FinanceLog(__FUNCTION__ . " $receipt");

		if ( $receipt > 0 ) {
			$b = Finance_Bank_Transaction::createFromDB( $bank_id );
			$b->Update( $user_id, $receipt, $site_id );
			return $receipt;
		} else {
			return false;
		}
	}

	function create_multi_site_receipt( $bank_id, $bank_amount, $date, $site_id, $user_id ) {
		FinanceLog(__FUNCTION__);
		// IDS sent as string.

		// $msg = $bank . " " . $date . " " . $change . " " . CommaImplode($ids) . " " . $site_id . " " . $user_id . "<br/>";
		$debug = false;

		$command = Finance::getPostFile() . "?operation=create_receipt" .
		           "&user_id=" . $user_id . "&bank=" . $bank_amount . "&date=" . $date;
		$result  = $this->multi_site->Run( $command, $site_id, true, $debug );

		if ($this->multi_site->getHttpCode($site_id) != 200) {
			FinanceLog("can't create");
			FinanceLog("getting $command, status: " . $this->multi_site->getHttpCode($site_id));
			return false;
		}

		if ( strstr( $result, "כבר" ) ) {
			FinanceLog("already paid");
			die( "already paid" );
		}
		if ( strlen( $result ) < 2 ) {
			FinanceLog("Bad response for $command: $result");
			die( "bad response" );
		}
		if ( strlen( $result ) > 10 ) {
			FinanceLog("Bad response for $command: $result");
			die( $result );
		}

		FinanceLog(__FUNCTION__ . " $result");
		$receipt = intval( trim( $result ) );
		FinanceLog(__FUNCTION__ . " $receipt");

		if ( $receipt > 0 ) {
			$b = Finance_Bank_Transaction::createFromDB( $bank_id );
			$b->Update( $user_id, $receipt, $site_id );
			return $receipt;
		} else {
			return false;
		}
	}

	static function gui_select_bank_account( $id, $value, $args ) {
		return Core_Html::GuiSelectTable($id, "bank_account", $args);
	}

	static function getBankAccounts($user_id)
	{
		if (! TableExists("bank_account")) return null;
		return SqlQueryArrayScalar("select id from im_bank_account where owner = $user_id");

		// Later we'll add permissions
	}

	function business_logical_delete( $ids ) {
		$sql = "UPDATE im_business_info SET is_active = 0 WHERE id IN (" . $ids . ")";
		SqlQuery( $sql );
		MyLog( $sql );
	}


static function bank_check_valid( $fields, $values ) {
	$table_prefix = GetTablePrefix();
	$account_idx = array_search( "account_id", $fields );
	$date_idx    = array_search( "date", $fields );
	$balance_idx = array_search( "balance", $fields );
	$in_amount_idx = array_search("in_amount", $fields);
	$out_amount_idx = array_search("out_amount", $fields);
	$description_idx = array_search("description", $fields);

	$account     = $values[ $account_idx ];
	$date        = $values[ $date_idx ];
	$balance = str_replace(",", "", $values[$balance_idx]);
//	$balance     = (float) $balance;
//	print "b=$balance<br/>";
	$in_amount = $values[$in_amount_idx];
	$out_amount = $values[$out_amount_idx];
	$description = $values[$description_idx];
//	print round( $balance, 2 ) . "balance: $balance<br/>";

	// Check we've got info to add.
	if (! $account or ! $date or ! $balance and ! ($in_amount or $out_amount)) return false;

	// Check duplicate (from previous import).
	$sql = "SELECT count(*) FROM ${table_prefix}bank WHERE account_id = " . $account .
	       " AND date = " . QuoteText( $date );
	$sql .= " and round(in_amount, 2) = " . $in_amount;
	$sql .= " and round(out_amount, 2) = " . $out_amount;
	$sql .= " and description like '%" . $description . "%'";
//	$sql .= " AND round(balance, 2) = " . round( $balance, 2 );

	$dup = SqlQuerySingleScalar( $sql );

	return (! $dup);
}

function user_is_business_owner() {
	$user = wp_get_current_user();

	return in_array( 'business', (array) $user->roles );
}

	function admin_menu()
	{
		$menu = Core_Admin_Menu::instance();

		$menu->AddSubMenu('finance', 'edit_shop_orders',
			array('page_title' => 'Settings',
			      'menu_title' => 'Settings',
			      'menu_slug' => 'bank_settings',
			      'function' => array($this, 'bank_wrapper')));

	}
}


//print Core_Html::GuiHyperlink('Create Receipts', add_to_url("operation" , "receipts")); print " ";
//print Core_Html::GuiHyperlink('Mark payments', add_to_url("operation", "payments")); print " ";
//print Core_Html::GuiHyperlink('Import bank pages', add_to_url("operation" ,"import")); print " ";
//print Core_Html::GuiHyperlink('Edit transaction types', add_to_url("operation" ,"transaction_types")); print " ";
//print Core_Html::GuiHyperlink('Search transaction', add_to_url("operation" ,"search")); print " ";


//		print Core_Html::GuiHyperlink('Create Receipts', add_to_url("operation" , "receipts")); print " ";
//		print Core_Html::GuiHyperlink('Mark payments', add_to_url("operation", "payments")); print " ";
//		print Core_Html::GuiHyperlink('Import bank pages', add_to_url("operation" ,"import")); print " ";
//		print Core_Html::GuiHyperlink('Edit transaction types', add_to_url("operation" ,"transaction_types")); print " ";
//		print Core_Html::GuiHyperlink('Search transaction', add_to_url("operation" ,"search")); print " ";

function insert_leumi_conversion() {
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'date', 'תאריך')" );
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'description', 'תיאור')" );
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'reference', 'אסמכתא')" );
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'in_amount', 'בזכות')" );
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'out_amount', 'בחובה')" );
	SqlQuery( 'INSERT INTO im_conversion ( table_name, col, header) VALUES ( \'bank\', \'balance\', \'היתרה בש\"ח\'), \\\'' );
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'part_id', 'לקוח')" );
	SqlQuery( "INSERT INTO im_conversion ( table_name, col, header) VALUES ( 'bank', 'client_name', 'תאור מורחב')");
}
// Testing
// Mark payments
// 1) Open account
// https://fruity.co.il//wp-content/plugins/finance/post.php?operation=get_supplier_open_account&header=0&AUTH_USER=im-haadama&AUTH_PW=Wer95%25pl
// https://store.im-haadama.co.il/wp-content/plugins/finance/post.php?operation=get_open_invoices&supplier_id=100050&site_id=4
// https://fruity.co.il//wp-content/plugins/finance/post.php?operation=finance_get_open_site_invoices&supplier_id=100050&header=1&AUTH_USER=im-haadama&AUTH_PW=Wer95%25pl