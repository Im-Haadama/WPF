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
		self::init_remoting();
	}

	private function init_remoting()
	{
		AddAction("finance_add_payment", array($this, 'add_payment'));
		AddAction("bank_status", array($this, 'bank_status'));
		AddAction("bank_show_import", array($this, "show_import"));
	}

	public static function instance() :Finance_Bank {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "/wp-content/plugins/finance/post.php" );
		}

		return self::$_instance;
	}

	static function getPost()
	{
		return self::instance()->post_file;
	}

	function init_hooks() {
//		if (get_user_id() == 1) {
//			print debug_trace( 10 );
//			print '=====================================<br/>';
//
//		}
//		if (get_user_id() == 1) print debug_trace(10);
		AddAction( "finance_bank_accounts", array( $this, "show_bank_accounts" ) );
		AddAction( "finance_bank_account", array( $this, "show_bank_account" ) );
		AddAction( "finance_bank_payments", array( $this, "show_bank_payments" ) );
		AddAction( "finance_bank_receipts", array( $this, "show_bank_receipts" ) );
		AddAction( "finance_show_bank_import", array( $this, "show_bank_import" ) );
		AddAction( "finance_do_import", array( $this, 'do_bank_import' ) );
		AddAction( "bank_create_invoice", array( $this, 'bank_create_invoice' ) );
		AddAction( "bank_create_pay", array( $this, 'bank_payments' ) );
		AddAction("finance_get_transaction_amount", array($this, 'get_transaction_amount'));

		add_action('admin_menu', array($this, 'admin_menu'));

//		Flavor_Roles::addRole('business_admin', array('finance'));
	}

	function bank_create_invoice()
	{
		$id = GetParam( "id" );
		return $this->create_invoice($id);
	}
	/**
	 * @return Core_Users
	 */
	public function getUser(): Core_Users {
		return $this->user;
	}

	function do_bank_import()
	{
		if (! isset($_FILES["fileToUpload"])) {
			print "file name is missing. try again";
			return false;
		}
		$file_name = $_FILES["fileToUpload"]["tmp_name"];
		print "Trying to import $file_name<br/>";
		$fields               = array();
		$fields['account_id'] = GetParam( 'account_id' );
		if ( ! $fields['account_id'] ) {
			die( "not account given" );
		}
		$unmapped = [];
		try {
			$result = Core_Importer::Import( $file_name, "bank", $fields, 'Finance_Bank::bank_check_dup', $unmapped );
			if (count($unmapped)) {
				var_dump( $unmapped );
				return false;
			}
		} catch ( Exception $e ) {
			print $e->getMessage();

			return false;
		}
		print $result[0] . " rows imported<br/>";
		print $result[1] . " duplicate rows <br/>";
		print $result[2] . " failed rows <br/>";
		return true;
	}

	function show_bank_accounts_wrap()
	{
		print self::show_bank_accounts();
	}

	function show_bank_accounts()
	{
		if (! get_user_id() || ! current_user_can("show_bank"))
			return "no permissions";

		$accounts = self::getBankAccounts(get_user_id());
		if (! $accounts) return "No bank accounts found";

		if (count($accounts) == 1)
			return $this->show_bank_account($accounts[0]);

		return "multiple bank accounts not implemented";
	}

	function show_bank_account($account_id)
	{
		if (! current_user_can("show_bank", $account_id))
			return "no permissions";

		$operation = GetParam("operation", false, null, true);
		if ($operation)
			return apply_filters($operation, "");

		$args = [];
		$args["page"] = GetParam("page", false, null);
		$args["post_file"] = Finance::getPostFile();
		$args["query"] = "account_id = $account_id";

		print Core_Html::GuiHeader(1, "דף חשבון") .
		      self::bank_transactions($args) .
		      Core_Html::GuiHyperlink("Import", AddToUrl(array("operation" => "finance_show_bank_import",
			      "account_id"=>$account_id)));
	}

	function show_bank_import()
	{
		$args = self::Args();
		// Using local action - to set account_id and check_dup. See above.
		 $args["import_page"] = AddToUrl(array("operation"=>"finance_do_import"));
//		$args["import_page"] = AddParamToUrl(Finance::getPostFile() , array("operation" => "gem_do_import", "table" => "bank"));
			// AddToUrl(array("operation" => "gem_do_import", "table"=>"bank"));//  . "&account_id=" . $account_id;
		$result = Core_Gem::ShowImport("bank", $args);

		print $result;
	}

	function get_transaction_amount()
	{
		$sql = "SELECT amount FROM im_business_info \n" .
		       " WHERE id = " . GetParam( "id", true );
		print SqlQuerySingleScalar( $sql );
	}

	function add_payment()
	{
		$supplier_id = GetParam( "supplier_id", true );
		$bank_id     = GetParam( "bank_id", true );
		$ids         = GetParamArray( "ids" );
		$date        = GetParam( "date", true );
		$amount      = GetParam( "amount", true );
		$sql         = "INSERT INTO im_business_info (part_id, date, amount, ref, document_type)\n" .
		               "VALUES(" . $supplier_id . ", '" . $date . "' ," . $amount . ", " . $bank_id . ", " . FreshDocumentType::bank . ")";
		SqlQuery( $sql );

		$S = new Fresh_Supplier($supplier_id);
		$result = "התווסף תשלום בסך " . $amount . " לספק " . $S->getSupplierName() . "<br/>";

		$sql = "update im_business_info\n" .
		       "set pay_date = '" . $date . "'\n" .
		       "where id in (" . CommaImplode( $ids ) . ")";

		SqlQuery( $sql );
		$result .= "מסמכים מספר  " . CommaImplode( $ids ) . " סומנו כמשולמים<br/>";
		return $result;
	}

	function handle_bank_operation($operation, $url = null)
	{
		$table_prefix = GetTablePrefix();
		$multi_site = Core_Db_MultiSite::getInstance();

		$u = new Core_Users();
		if (! $u->can("show_bank"))
			return "no permissions";
		$account_id = 1;
		$ids = null;
		$post_file = self::getPost();

		// Todo: change to operation
		if (GetParam("search", false, 0)){
			$ids=data_search("bank");
			Core_Html::gui_header(1, "Results");
			if (! $ids){
				print ImTranslate("Nothing found");
				return;
			}
		}
		switch ( $operation ) {
			case "bank_create_receipt":
				$bank_amount = GetParam( "bank" );
				$date        = GetParam( "date" );
				$change      = GetParam( "change" );
				$ids         = GetParam( "ids", true );
				$site_id     = GetParam( "site_id" );
				$user_id     = GetParam( "user_id" );
				$bank_id     = GetParam( "bank_id" );

				return $this->create_multi_site_receipt( $bank_id, $bank_amount, $date, $change, $ids, $site_id, $user_id );
				break;

			case "bank_link_invoice":
				$bank_id      = GetParam( "bank_id", true );
				$supplier_id  = GetParam( "supplier_id", true );
				$site_id      = GetParam( "site_id", true );
				$ids          = GetParamArray( "ids" );
				$bank         = GetParam( "bank" );

				// 1) mark the bank transaction to invoice.
				foreach ( $ids as $id ) {
					$command = Finance::getPostFile() . "?operation=finance_get_transaction_amount&id=" . $id;
					$amount = doubleval(strip_tags($multi_site->Run($command , $site_id)));
					$line_amount = min ($amount, $bank);

					$sql    = "INSERT INTO ${table_prefix}bank_lines (line_id, amount, site_id, part_id, invoice)\n" .
					          "VALUES (" . $bank_id . ", " . $line_amount . ", " . $site_id . ", " . $supplier_id . ", " .
					          $id . ")";

					SqlQuery($sql);
				}
				$b    = Finance_Bank_Transaction::createFromDB( $bank_id );
				$date = $b->getDate();

				// 2) mark the invoices to transaction.
				$command = Finance::getPostFile() . "?operation=finance_add_payment&ids=" . implode( $ids, "," ) . "&supplier_id=" . $supplier_id .
				           "&bank_id=" . $bank_id . "&date=" . $date .
				           "&amount=" . $bank;
//			print $command;
				print $multi_site->Run( $command, $site_id );

				print "מעדכן שורות<br/>";
				$sql = "update ${table_prefix}bank " .
				       " set receipt = \"" . CommaImplode($ids) . "\", " .
				       " site_id = " . $site_id .
				       " where id = " . $bank_id;

				return SqlQuery($sql);

				break;

			case "mark_refund_bank":
				// TODO: NOT CHECKED
				$bank_id      = GetParam( "bank_id", true );
				$supplier_id  = GetParam( "supplier_id", true );
				$site_id      = GetParam( "site_id", true );
				$bank         = GetParam( "bank" );

				// 1) mark the bank transaction to invoice.
				$b    = Finance_Bank_Transaction::createFromDB( $bank_id );
				$date = $b->getDate();

				// 2) mark the invoices to transaction.
			// Todo: change hook
				$command = Finance::getPostFile() . "?operation=add_refund_bank&supplier_id=" . $supplier_id .
				           "&bank_id=" . $bank_id . "&date=" . $date .
				           "&amount=" . $bank;
//			print $command;
				print $multi_site->Run( $command, $site_id );

				print "מעדכן שורות<br/>";
				$sql = "update ${table_prefix}bank " .
				       " set receipt = \"refund\", " .
				       " site_id = " . $site_id .
				       " where id = " . $bank_id;

				SqlQuery($sql);

				break;
			case "create_invoice_bank":

				break;

			case "mark_return_bank":
				// Todo: Rewire
				require_once( FRESH_INCLUDES . '/org/business/BankTransaction.php' );
				require_once( FRESH_INCLUDES . '/fresh-public/account/gui.php' );
				print header_text( false, true, true,
					array(
						"business.js",
						"/core/gui/client_tools.js",
						"/fresh/account/account.js"
					) );
				$id = GetParam( "id" );
				$b = Finance_Bank_Transaction::createFromDB( $id );
				print Core_Html::gui_header( 1, "סמן החזר מהספק" );

				print Core_Html::gui_header( 2, "פרטי העברה" );
				print gui_table_args( array(
						array( "תאריך", gui_div( "pay_date", $b->getDate() ) ),
						array( "סכום", gui_div( "bank", $b->getInAmount() ) ),
						array( "מזהה", gui_div( "bank_id", $id ) )
					)
				);

//			print Core_Html::gui_header(2, "חשבונית שהופקה");
//			print GuiInput("invoice_id");
//			print Core_Html::GuiButton("btn_invoice_exists", "invoice_exists()", "Exists invoice");

				print Core_Html::gui_header( 2, "בחר ספק" );
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
				break;

			case "bank_transaction_types":
				if (! $this->getUser()->can("cfo"))
					return "no permissions";
				$args = array();
				// $args["selectors"] = array("part_id" => "gui_select_supplier");

				print Core_Html::GuiTableContent( "bank_transaction_types", null, $args );
				print Core_Html::GuiHyperlink( "add", AddToUrl( "operation", "add_transaction_types" ) );

				return;

			case "add_transaction_types":
				if (! $this->getUser()->can("cfo"))
					return "no permissions";
				$args              = self::Args();
				$args["selectors"] = array( "part_id" => "Fresh_Supplier::gui_select_supplier" );

				print Core_Gem::GemAddRow( "bank_transaction_types", "Transaction types", $args );

				return;

			case "search":
				$args           = array();
				$search_url     = "search_table('${table_prefix}bank', '" . AddParamToUrl( $url, "search", "1" ) . "')";
				$args["search"] = $search_url; //'/fresh/bank/bank-page.php?operation=do_search')";
				GemSearch( "bank", $args );

				return;

			case "do_search":
				$ids = data_search( "bank" );
				Core_Html::gui_header( 1, "Results" );
				if ( ! $ids ) {
					print ImTranslate( "Nothing found" );

					return;
				}

				print self::bank_transactions( array("query" => "id in (" . CommaImplode( $ids ) . ")" ));
				return;

			case "show_bank":
				$args                  = self::Args($u);
				$args["selector"]      = __CLASS__ . "::gui_select_bank_account";
				$args["order"] = "3 desc";
				$args["hide_cols"] = array_merge($args["hide_cols"], array("id" => 1, "account_id" => 1, "tmp_client_name" =>1, "customer_id"=>1));
				// $args["post_action"] = Finance::getPostFile()
				return true;

			case "show_transactions":
				print self::bank_transactions( array("query" => $ids ? "id in (" . CommaImplode( $ids ) . ")" : null) );
				break;
//			case 'bank_import_from_file':
		}
	}

	function show_import()
	{
		$args                  = array();
		$args["selector"]      = __CLASS__ . "::gui_select_bank_account";
		$args["import_action"] = Finance::getPostFile() . '?operation=bank_import_from_file';

		$args["page"] = 1;
		$args["order"] = "3 desc";
		$args["post_file"] = self::getPost();
		print Core_Gem::GemTable("bank", $args);

		print Core_Gem::ShowImport( "bank", $args );

	}

	function gui_select_open_supplier( $id = "supplier" ) {
		$multi_site = Core_Db_MultiSite::getInstance();

		$values  = Core_Html::html2array( $multi_site->GetAll( self::getPost() . "?operation=get_supplier_open_account" ) );

		if (! $values) 	return "nothing found";
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

	function gui_select_client_open_account( $id = "open_account" ) {
		$output = "";
		$multi_site = Core_Db_MultiSite::getInstance();
		$url = Finance::getPostFile() . "?operation=get_client_open_account";
		$result = $multi_site->GetAll( $url );
		foreach ($multi_site->getHttpCodes() as $side_id => $code){
			if ($code != 200) {
				$output .= "Can't get result from " . $multi_site->getSiteName($side_id) . " error: $code <br/>";
				if (get_user_id()== 1) $output .= $url . "<br/>";
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
		$events = 'onchange="client_selected()"';
		$datalist_id = $id . "_datalist";
		$output .= Core_Html::GuiInputDatalist($id, $datalist_id, $events);
		$output .= Core_Html::GuiDatalist( $datalist_id, $open, "id","name", false);

		return $output;
	}

	 public function bank_payments()
	{
		$id = GetParam( "id" );
		print Core_Html::gui_header( 1, "רישום העברה שבוצעה " );

		$b = Finance_Bank_Transaction::createFromDB( $id );
		print Core_Html::gui_header( 2, "פרטי העברה" );
		$free_amount = $b->getOutAmount( true );
		$client_name = $b->getClientName();
		print Core_Html::gui_table_args( array(
			array( "תאריך", Core_Html::gui_div( "pay_date", $b->getDate() ) ),
			array( "סכום", Core_Html::gui_div( "bank", $b->getOutAmount() ) ),
			array( "סכום לתיאום", Core_Html::gui_div( "bank", $free_amount ) ),
			array( "מזהה", Core_Html::gui_div( "bank_id", $id )),
			array( "Comment", $client_name	)
		));

		$lines = $b->getAttached();
		if ( $lines ) {
			print Core_Html::gui_header( 2, "שורות מתואמות" );

			print Core_Html::gui_table_args( $lines );
		}
		$sums = array();
		if ( $free_amount > 0 ) {
//				print "a=" . $amount . "<br/>";
			print Core_Html::gui_header( 2, "Select Supplier" );
			print self::gui_select_open_supplier();
		}
		print '<div id="logging"></div>';
		print '<div id="transactions"></div>';

		print Core_Html::gui_table_args( array(
			array("קשר",
				Core_Html::GuiButton( "btn_receipt", "link payment to invoice", array("action" => "link_invoice_bank()"))),
			array( "סה\"כ", " <div id=\"total\"></div>" )), "payment_table"); //, "payment_table", true, true, $sums, "", "payment_table" ));


	}
	static public function bank_wrapper()
	{
		print Core_Html::Gui_Header(1, "Bank");
		$operation = GetParam("operation", false, "bank_status", true);

		print apply_filters($operation, '');
	}

	function show_bank_payments($account_id = 1)
	{
		$account_id=  1;
		$table_prefix = $this->table_prefix;
		$result = "";
		$args = array();
		$result .= Core_Html::gui_header( 1, "Payments" );
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
	function show_bank_receipts($account_id, $ids = null)
	{
		$account_id = 1;
		$table_prefix = $this->table_prefix;

		$args = $this->Args();
		print Core_Html::gui_header( 1, "Receipts" );
		$args["header_fields"] = array( "id"=>"Id", "date" => "Date", "description" => "Description",
		                                "in_amount" => "Amount", "reference" => "Reference", "client_name" => "Details" );
		$args["actions"]       = array(
			array(
				"Receipt",
				AddToUrl(array( "operation" => "bank_create_invoice", "id" => "%s"))
			),
			array(
				"Return",
				self::getPost() . "?operation=bank_mark_return&id=%s"
			)

		);
		$query                 = " account_id = " . $account_id . " and receipt is null and in_amount > 0 " .
		                         " and description not in (select description from ${table_prefix}bank_transaction_types) ";

		if ( $ids ) {
			$query .= " and id in (" . CommaImplode( $ids ) . ")";
		}
		// " order by date desc limit $rows_per_page offset $offset";
		$args["query"] = $query;
		$args["fields"] = array( "id", "date", "description", "in_amount", "reference", "client_name" );

		print self::bank_transactions( $args );
	}

	static function bank_transactions($args = null)
	{
		$table_prefix = GetTablePrefix();

		$result = "";

		$account_id = 1;

		$page = GetParam("page_number", false, 1);
		$args["page_number"] = $page;
		$args["rows_per_page"] = 40;
		$args["class"] = "widefat";
		$args["prepare_plug"] = __CLASS__ . "::prepare_row";
//		$offset = ($page - 1) * $rows_per_page;

		$fields = GetArg($args, "fields", array("id", "date", "description", "out_amount", "in_amount", "balance", "receipt", "client_name"));

//		print "args=" . $args["query"] . "<br/>";
		$sql = "select " . CommaImplode($fields) . " from ${table_prefix}bank ";
		$sql .= " where " . $args["query"] . " and account_id = " . $account_id;
		$sql .= " order by date desc ";

		$result .= Core_Html::GuiTableContent("banking", $sql, $args);

		$result .= Core_Html::GuiHyperlink("Older", AddToUrl("page_number", $page + 1)) . " ";

		return $result;
	}

	static function prepare_row($row)
	{
		$id = $row['id'];
		if (($row['receipt'] == '') and ($row['in_amount'] > 0))
			$row['receipt'] = Core_Html::GuiHyperlink(__("Invoice"), AddToUrl(array("operation" => "bank_create_invoice", "id" => $id)));
//			$row['receipt'] = Core_Html::GuiButton("btn_bank_receipt", "קבלה", "bank_receipt('". Finance::getPostFile() . "', $id)");

		if (($row['receipt'] == '') and ($row['out_amount'] > 0))
			$row['receipt'] = Core_Html::GuiHyperlink(__("Mark"), AddToUrl(array("operation" => "bank_create_pay", "id" => $id)));

		return $row;

	}

	function create_invoice($id)
	{
		$b = Finance_Bank_Transaction::createFromDB( $id );
		$result = "Creating invoice for bank transaction";
		$result .= Core_Html::gui_header( 1, "הפקת חשבונית קבלה להפקדה מבנק " );

		$result .= Core_Html::gui_header( 2, "פרטי העברה" );
		$result .= Core_Html::gui_table_args( array(
				array( "תאריך", Core_Html::gui_div( "pay_date", $b->getDate() ) ),
				array( "סכום", Core_Html::gui_div( "bank", $b->getInAmount() ) ),
				array( "מזהה", Core_Html::gui_div( "bank_id", $id ) ),
				array( "פרטי התנועה", Core_Html::gui_div( "bank_id", $b->getClientName() ) )

			)
		);

		$result .= Core_Html::gui_header(2, "חשבונית שהופקה");
		$result .= Core_Html::GuiInput("invoice_id");
		$result .= Core_Html::GuiButton("btn_invoice_exists", "Exists invoice", array("action" => "invoice_exists()"));

		$result .= Core_Html::gui_header( 2, "בחר לקוח" );
		$result .= self::gui_select_client_open_account();
		$result .= '<div id="logging"></div>';
		$result .= '<div id="transactions"></div>';
//		function gui_table(	$rows, $id = null, $header = true, $footer = true, &$acc_fields = null, $style = null, $class = null, $show_fields = null,
//			$links = null, $col_ids = null, $first_id = false, $actions = null

		$result .= Core_Html::gui_table_args(array(
			array("תשלום",	Core_Html::GuiButton( "btn_receipt", "הפק חשבונית מס קבלה", array("action" => "create_receipt_from_bank('".Finance::getPostFile()."')") )),
			array( "עודף", " <div id=\"change\"></div>" )));
		// ,"payment_table", "class" => "payment_table"));

//		$result .= gui_table( , "payment_table", true, true, $sums, "", "payment_table" );

		return $result;

	}

	static public function bank_status()
	{
		$result = "";
		$table_prefix = GetTablePrefix();

		$account = GetParam("account", false, null);
		if ($account) {
			return self::show_bank_account($account);
		}
		$result = Core_Html::gui_header(2, "Bank accounts");
		$u = new Core_Users();

		$sql = "select id, name, bank_last_transaction(id)";
		if ($u->hasRole("cfo")) $sql .= ", round(bank_balance(id), 2) ";
		$sql .= " from ${table_prefix}bank_account";

		$args = [];
		$args["links"] = array("account_id" => AddToUrl(array( "operation" => "show_bank_load", "id" => "%s")),
			"id" => AddToUrl("account", "%d"));
//		$args["id_field"] = "count_id";
		$action_url = GetUrl(1); // plugin_dir_url(dirname(__FILE__)) . "post.php";
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
			// TOdo: get from transaction_types

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

	function create_multi_site_receipt( $bank_id, $bank_amount, $date, $change, $ids, $site_id, $user_id ) {
		// IDS sent as string.

		// $msg = $bank . " " . $date . " " . $change . " " . CommaImplode($ids) . " " . $site_id . " " . $user_id . "<br/>";
		$debug = false;

		$command = Finance::getPostFile() . "?operation=create_receipt&row_ids=" . $ids .
		           "&user_id=" . $user_id . "&bank=" . $bank_amount . "&date=" . $date .
		           "&change=" . $change;
		$result  = $this->multi_site->Run( $command, $site_id, true, $debug );

		if ($this->multi_site->getHttpCode($site_id) != 200) {
			print "can't create<br/>";
			 print "getting $command, status: " . $this->multi_site->getHttpCode($site_id) . "<br/>";
			return false;
		}

		if ( strstr( $result, "כבר" ) ) {
			die( "already paid" );
		}
		if ( strlen( $result ) < 2 ) {
			 print $command . "<br/>";
			die( "bad response" );
		}
		if ( strlen( $result ) > 10 ) {
			die( $result );
		}

		$receipt = intval( trim( $result ) );
		print "r=$receipt<br/>";

		if ( $receipt > 0 ) {
			// TODO: to parse $id from $result;
			$b = Finance_Bank_Transaction::createFromDB( $bank_id );
			$b->Update( $user_id, $receipt, $site_id );
			return $receipt;
		} else {
			print false;
		}
	}

	static function gui_select_bank_account( $id, $value, $args ) {
		return Core_Html::GuiSelectTable($id, "bank_account", $args);
	}

	static function getBankAccounts($user_id)
	{
		return SqlQueryArrayScalar("select id from im_bank_account where owner = $user_id");

		// Later we'll add permissions
	}

	function business_logical_delete( $ids ) {
		$sql = "UPDATE im_business_info SET is_active = 0 WHERE id IN (" . $ids . ")";
		SqlQuery( $sql );
		MyLog( $sql );
	}


static function bank_check_dup( $fields, $values ) {
	$table_prefix = GetTablePrefix();

	$account_idx = array_search( "account_id", $fields );
	$date_idx    = array_search( "date", $fields );
	$balance_idx = array_search( "balance", $fields );
	$account     = $values[ $account_idx ];
	$date        = $values[ $date_idx ];
	$balance     = $values[ $balance_idx ];
//	print "a=" . $account . " d=" . $date . " b=" . $balance;

	$sql = "SELECT count(*) FROM ${table_prefix}bank WHERE account_id = " . $account .
	       " AND date = " . QuoteText( $date ) .
	       " AND round(balance, 2) = " . round( $balance, 2 );
	// print sql_query($sql) . "<br/>";
	$c = SqlQuerySingleScalar( $sql );

//	print " c=" . $c . "<br/>";

	return $c;
}

function user_is_business_owner() {
	$user = wp_get_current_user();

	return in_array( 'business', (array) $user->roles );
}

	function admin_menu()
	{
		$menu = new Core_Admin_Menu();

		$menu->AddSubMenu('freight', 'edit_shop_orders',
			array('page_title' => 'Settings',
			      'menu_title' => 'Settings',
			      'menu_slug' => 'settings',
			      'function' => array($this, 'general_settings')));

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