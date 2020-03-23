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
		$this->table_prefix = get_table_prefix();
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "/wp-content/plugins/finance/post.php" );
		}

		return self::$_instance;
	}

	static function getPost()
	{
		return self::instance()->post_file;
	}

	public function accounts()
	{
		return sql_query_array();
	}

	/**
	 * @return Core_Users
	 */
	public function getUser(): Core_Users {
		return $this->user;
	}

	function handle_bank_operation($operation, $url = null)
	{
		$table_prefix = get_table_prefix();
		$multi_site = Core_Db_MultiSite::getInstance();

		//		print __FILE__ . ':' .$operation . "<br/>";
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
			case "bank_status":
				return self::bank_status();

			case "bank_create_invoice":
				$id = GetParam( "id" );
				return $this->create_invoice($id);

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

			case "bank_receipts":
			case "receipts":
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

				return;

			case "bank_create_pay":
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

					print gui_table_args( $lines );
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

				break;
			case "bank_link_invoice":
				$bank_id      = GetParam( "bank_id", true );
				$supplier_id  = GetParam( "supplier_id", true );
				$site_id      = GetParam( "site_id", true );
				$ids          = GetParamArray( "ids" );
				$bank         = GetParam( "bank" );

				// 1) mark the bank transaction to invoice.
				foreach ( $ids as $id ) {
					$command = "org/business/business-post.php?operation=get_amount&id=" . $id;
					$amount = doubleval(strip_tags($multi_site->Run($command , $site_id)));
					$line_amount = min ($amount, $bank);

					$sql    = "INSERT INTO ${table_prefix}bank_lines (line_id, amount, site_id, part_id, invoice)\n" .
					          "VALUES (" . $bank_id . ", " . $line_amount . ", " . $site_id . ", " . $supplier_id . ", " .
					          $id . ")";

					sql_query($sql);
				}
				$b    = Finance_Bank_Transaction::createFromDB( $bank_id );
				$date = $b->getDate();

				// 2) mark the invoices to transaction.
				$command = "org/business/business-post.php?operation=add_payment&ids=" . implode( $ids, "," ) . "&supplier_id=" . $supplier_id .
				           "&bank_id=" . $bank_id . "&date=" . $date .
				           "&amount=" . $bank;
//			print $command;
				print $multi_site->Run( $command, $site_id );

				print "מעדכן שורות<br/>";
				$sql = "update ${table_prefix}bank " .
				       " set receipt = \"" . CommaImplode($ids) . "\", " .
				       " site_id = " . $site_id .
				       " where id = " . $bank_id;

				sql_query($sql);

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
				$command = "org/business/business-post.php?operation=add_refund_bank&supplier_id=" . $supplier_id .
				           "&bank_id=" . $bank_id . "&date=" . $date .
				           "&amount=" . $bank;
//			print $command;
				print $multi_site->Run( $command, $site_id );

				print "מעדכן שורות<br/>";
				$sql = "update ${table_prefix}bank " .
				       " set receipt = \"refund\", " .
				       " site_id = " . $site_id .
				       " where id = " . $bank_id;

				sql_query($sql);

				break;
			case "create_invoice_bank":

				break;

			case "mark_return_bank":
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

			case "bank_payments":
			case "payments":
				$args = array();
				print Core_Html::gui_header( 1, "Payments" );
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

				if ( $ids ) {
					$query .= " and id in (" . CommaImplode( $ids ) . ")";
				}
				// " order by date desc limit $rows_per_page offset $offset";

				$args["fields"] = array( "id", "date", "description", "out_amount", "reference", "client_name" );
//			$sql = "select id, date, description, out_amount, reference from im_bank where account_id = " . $account_id .
//			       " and receipt is null and out_amount > 0 " .
//			       " and description not in (select description from im_bank_transaction_types) " .
//			       " order by date desc limit $rows_per_page offset $offset";

				//		print GuiTableContent( "im_banking", $sql, $args);

				//		print Core_Html::GuiHyperlink("Older", add_to_url("page", $page + 1));
				$args["query"] = $query;

				print self::bank_transactions( $args );

				return;

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
				print Core_Gem::GemTable("bank", $args);

			case "bank_show_import":
			case "import":
				$args                  = array();
				$args["selector"]      = __CLASS__ . "::gui_select_bank_account";
				$args["import_action"] = $post_file . '?operation=bank_import_from_file';

				$args["page"] = 1;
				$args["order"] = "3 desc";
				$args["post_file"] = self::getPost();
				print Core_Gem::GemTable("bank", $args);

				print Core_Gem::GemImport( "bank", $args );
				break;

			case "show_transactions":
				print self::bank_transactions( array("query" => $ids ? "id in (" . CommaImplode( $ids ) . ")" : null) );
				break;
			case 'bank_import_from_file':
				$file_name = $_FILES["fileToUpload"]["tmp_name"];
				print "Trying to import $file_name<br/>";
				$I                    = new Core_Importer();
				$fields               = null;
				$fields               = array();
				$fields['account_id'] = GetParam( 'selection' );
				if ( ! $fields['account_id'] ) {
					die( "not account given" );
				}
				try {
					$result = $I->Import( $file_name, "bank", $fields, 'Finance_Bank::bank_check_dup' );
				} catch ( Exception $e ) {
					print $e->getMessage();

					return;
				}
				print $result[0] . " rows imported<br/>";
				print $result[1] . " duplicate rows <br/>";
				print $result[2] . " failed rows <br/>";
				return true;

		}
	}

	function gui_select_open_supplier( $id = "supplier" ) {
		$multi_site = Core_Db_MultiSite::getInstance();

		$values  = Core_Html::html2array( $multi_site->GetAll( "org/business/business-post.php?operation=get_supplier_open_account" ) );

		if (! $values) 	return "nothing found";
		$open    = array();
		$list_id = 0;
		foreach ( $values as $value ) {
			$new                = array();
			$new["id"]          = $list_id ++;
			$new["site_id"]     = $value[0];
			$new["supplier_id"] = $value[1];
			$new["name"]        = $value[2];
			$new["balance"]     = $value[3];
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
		$url = "org/business/business-post.php?operation=get_client_open_account";
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

	static public function bank_wrapper()
	{
		$operation = GetParam("operation", false, "bank_status");
		$instance = self::instance();

		return $instance->handle_bank_operation($operation);
	}

	static function bank_transactions($args = null)
	{
		$table_prefix = get_table_prefix();

		$result = "";

		$account_id = 1;

		$page = GetParam("page", false, 1);
		$rows_per_page = 20;
		$offset = ($page - 1) * $rows_per_page;

		$fields = GetArg($args, "fields", array("id", "date", "description", "out_amount", "in_amount", "balance", "receipt"));

//		print "args=" . $args["query"] . "<br/>";
		$sql = "select " . CommaImplode($fields) . " from ${table_prefix}bank ";
		$sql .= " where " . $args["query"] . " and account_id = " . $account_id;
		$sql .= " order by date desc limit $rows_per_page offset $offset ";

		$result .= Core_Html::GuiTableContent("banking", $sql, $args);

		$result .= Core_Html::GuiHyperlink("Older", AddToUrl("page", $page + 1));

		return $result;
	}

	function create_invoice($id)
	{
		$b = Finance_Bank_Transaction::createFromDB( $id );
		$result = "";
		$result .= Core_Html::gui_header( 1, "הפקת חשבונית קבלה להפקדה מבנק " );

		$result .= Core_Html::gui_header( 2, "פרטי העברה" );
		$result .= Core_Html::gui_table_args( array(
				array( "תאריך", Core_Html::gui_div( "pay_date", $b->getDate() ) ),
				array( "סכום", Core_Html::gui_div( "bank", $b->getInAmount() ) ),
				array( "מזהה", Core_Html::gui_div( "bank_id", $id ) )
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
			array("תשלום",	Core_Html::GuiButton( "btn_receipt", "הפק חשבונית מס קבלה", array("action" => "create_receipt_from_bank()") )),
			array( "עודף", " <div id=\"change\"></div>" )));
		// ,"payment_table", "class" => "payment_table"));

//		$result .= gui_table( , "payment_table", true, true, $sums, "", "payment_table" );

		return $result;

	}

	static public function bank_status()
	{
		$table_prefix = get_table_prefix();

		$result = Core_Html::gui_header(2, "last bank load");
		$u = new Core_Users();

		$sql = "select id, name, bank_last_transaction(id)";
		if ($u->hasRole("cfo")) $sql .= ", round(bank_balance(id), 2) ";
		$sql .= " from ${table_prefix}bank_account";

		$args = [];
		$args["links"] = array("account_id" => AddToUrl(array( "operation" => "show_bank_load", "id" => "%s")),
			"id" => AddToUrl("operation", "show_bank"));
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

//var request = "account-post.php?operation=create_receipt" +
//              "&cash=" + cash +
//              "&credit=" + credit +
//              "&bank=" + bank +
//              "&check=" + check +
//              "&date=" + date +
//              "&change=" + change.innerHTML +
//              "&ids=" + del_ids.join() +
//              "&user_id=" + <?php print $customer_id; <!--;-->

		$command = "/fresh/multi-site/multi-get.php?operation=create_receipt&row_ids=" . $ids .
		           "&user_id=" . $user_id . "&bank=" . $bank_amount . "&date=" . $date .
		           "&change=" . $change;
//	print "ZZZZ" . $command;
		$result  = $this->multi_site->Run( $command, $site_id, true, $debug );

		if ($this->multi_site->getHttpCode($site_id) != 200) {
			print "can't create<br/>";
			if (developer()) print "getting $command, status: " . $this->multi_site->getHttpCode($site_id) . "<br/>";
			return false;
		}

		if ( strstr( $result, "כבר" ) ) {
			die( "already paid" );
		}
		if ( strlen( $result ) < 2 ) {
			if (developer()) print $command . "<br/>";
			die( "bad response" );
		}
		if ( strlen( $result ) > 10 ) {
			die( $result );
		}
		// print "r=" . $result . "<br/>";

		$receipt = intval( trim( $result ) );

		// print "re=" . $receipt . '<br/>';

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

function business_update_transaction( $delivery_id, $total, $fee ) {
	$sql = "UPDATE im_business_info SET amount = " . $total . ", " .
	       " delivery_fee = " . $fee .
	       " WHERE ref = " . $delivery_id;

	MyLog( $sql, __FILE__ );
	sql_query( $sql );
}

function business_logical_delete( $ids ) {
	$sql = "UPDATE im_business_info SET is_active = 0 WHERE id IN (" . $ids . ")";
	sql_query( $sql );
	MyLog( $sql );
}

function business_open_ship( $part_id ) {
	$sql = "select id, date, amount, net_amount, ref " .
	       " from im_business_info " .
	       " where part_id = " . $part_id .
	       " and invoice is null " .
	       " and document_type = " . FreshDocumentType::ship;

	// print $sql;

	$data = GuiTableContent( "table", $sql );

	// $rows = sql_query_array($sql );

	return $data; // gui_table($rows);
}

static function bank_check_dup( $fields, $values ) {
	$table_prefix = get_table_prefix();

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
	$c = sql_query_single_scalar( $sql );

//	print " c=" . $c . "<br/>";

	return $c;
}

function user_is_business_owner() {
	$user = wp_get_current_user();

	return in_array( 'business', (array) $user->roles );
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
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'date', 'תאריך')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'description', 'תיאור')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'reference', 'אסמכתא')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'in_amount', 'בזכות')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'out_amount', 'בחובה')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'balance', 'היתרה בש\"ח')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'part_id', 'לקוח')" );
	sql_query( "INSERT INTO nv_conversion ( table_name, col, header) VALUES ( 'bank', 'client_name', 'תאור מורחב')");
}
