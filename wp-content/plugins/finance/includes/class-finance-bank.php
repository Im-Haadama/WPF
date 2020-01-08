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

	public function __construct( $post_file ) {
		$this->post_file = $post_file;
		$this->version   = "1.0";
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

	static function handle_bank_operation($operation, $url = null) {
//		print __FILE__ . ':' .$operation . "<br/>";
		if (! im_user_can("show_bank"))
			return "no permissions";
		$account_id = 1;
		$ids = null;
		$post_file = self::getPost();

		// Todo: change to operation
		if (get_param("search", false, 0)){
			$ids=data_search("im_bank");
			Core_Html::gui_header(1, "Results");
			if (! $ids){
				print im_translate("Nothing found");
				return;
			}
		}

		switch ( $operation ) {
			case "bank_status":
				return self::bank_status();

			case "bank_receipts":
			case "receipts":
				$args = array();
				print Core_Html::gui_header( 1, "Receipts" );
				$args["header_fields"] = array( "Id", "Date", "Description", "Amount" );
				$args["actions"]       = array(
					array(
						"Receipt",
						"/org/business/business-post.php?operation=1&id=%s"
					),
					array(
						"Return",
						"/org/business/business-post.php?operation=mark_return_bank&id=%s"
					)

				);
				$query                 = "  account_id = " . $account_id . " and receipt is null and in_amount > 0 " .
				                         " and description not in (select description from im_bank_transaction_types) ";

				if ( $ids ) {
					$query .= " and id in (" . comma_implode( $ids ) . ")";
				}
				// " order by date desc limit $rows_per_page offset $offset";

				$args["fields"] = array( "id", "date", "description", "in_amount", "reference" );

				print bank_transactions( $query, $args );

				return;

			case "bank_payments":
			case "payments":
				$args = array();
				print Core_Html::gui_header( 1, "Payments" );
				$args["header_fields"] = array( "Id", "Date", "Description", "Amount" );
				$args["actions"]       = array(
					array(
						"Mark payment",
						"/org/business/business-post.php?operation=create_pay_bank&id=%s"
					)
				);
				$page                  = get_param( "page", false, 1 );
				$rows_per_page         = 20;
				$offset                = ( $page - 1 ) * $rows_per_page;
				$query                 = "  account_id = " . $account_id . " and receipt is null and out_amount > 0 " .
				                         " and description not in (select description from im_bank_transaction_types) ";

				if ( $ids ) {
					$query .= " and id in (" . comma_implode( $ids ) . ")";
				}
				// " order by date desc limit $rows_per_page offset $offset";

				$args["fields"] = array( "id", "date", "description", "out_amount", "reference" );
//			$sql = "select id, date, description, out_amount, reference from im_bank where account_id = " . $account_id .
//			       " and receipt is null and out_amount > 0 " .
//			       " and description not in (select description from im_bank_transaction_types) " .
//			       " order by date desc limit $rows_per_page offset $offset";

				//		print GuiTableContent( "im_banking", $sql, $args);

				//		print Core_Html::GuiHyperlink("Older", add_to_url("page", $page + 1));

				print bank_transactions( $query, $args );

				return;

			case "transaction_types":
				$args = array();
				// $args["selectors"] = array("part_id" => "gui_select_supplier");

				print Core_Html::GuiTableContent( "im_bank_transaction_types", null, $args );
				print Core_Html::GuiHyperlink( "add", add_to_url( "operation", "add_transaction_types" ) );

				return;

			case "add_transaction_types":
				$args              = array();
				$args["selectors"] = array( "part_id" => "gui_select_supplier" );
				$args["post_file"] = "/core/data/data-post.php";

				print Core_Gem::GemAddRow( "im_bank_transaction_types", "Transaction types", $args );

				return;

			case "search":
				$args           = array();
				$search_url     = "search_table('im_bank', '" . add_param_to_url( $url, "search", "1" ) . "')";
				$args["search"] = $search_url; //'/fresh/bank/bank-page.php?operation=do_search')";
				GemSearch( "im_bank", $args );

				return;

			case "do_search":
				$ids = data_search( "im_bank" );
				Core_Html::gui_header( 1, "Results" );
				if ( ! $ids ) {
					print im_translate( "Nothing found" );

					return;
				}
				print bank_transactions( "id in (" . comma_implode( $ids ) . ")" );
				return;

			case "show_bank":
				$args                  = self::Args();
				$args["selector"]      = "gui_select_bank_account";
				$args["order"] = "3 desc";
				$args["hide_cols"] = array("id" => 1, "account_id" => 1, "tmp_client_name" =>1, "customer_id"=>1);
				print Core_Gem::GemTable("im_bank", $args);

			case "bank_show_import":
			case "import":
				$args                  = array();
				$args["selector"]      = "gui_select_bank_account";
				$args["import_action"] = $post_file . '?operation=bank_import_from_file';

				$args["page"] = 1;
				$args["order"] = "3 desc";
				$args["post_file"] = self::getPost();
				print Core_Gem::GemTable("im_bank", $args);

				print Core_Gem::GemImport( "im_bank", $args );
				break;


			case "show_transactions":
				print bank_transactions( $ids ? "id in (" . comma_implode( $ids ) . ")" : null );
		}
	}

	static public function bank_wrapper()
	{
		$operation = get_param("operation", false, "bank_status");

		return self::handle_bank_operation($operation);
	}

	static public function bank_status()
	{
		$result = Core_Html::gui_header(2, "last bank load");
		$u = new Core_Users();

		$sql = "select id, name, bank_last_transaction(id)";
		if ($u->hasRole("cfo")) $sql .= ", round(bank_balance(id), 2) ";
		$sql .= " from im_bank_account";

		$args = [];
		$args["links"] = array("account_id" => add_to_url(array("operation" => "show_bank_load", "id" => "%s")),
			"id" => add_to_url("operation", "show_bank"));
//		$args["id_field"] = "count_id";
		$action_url = get_url(1); // plugin_dir_url(dirname(__FILE__)) . "post.php";
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

	static function Args()
	{
		return array("page" => get_param("page", false, -1),
		             "post_file" => self::getPost());
	}

function business_add_transaction(
	$part_id, $date, $amount, $delivery_fee, $ref, $project, $net_amount = 0,
	$document_type = FreshDocumentType::delivery,
	$document_file = null
) {
	// print $date . "<br/>";
	$sunday = sunday( $date );
	if ( ! $part_id ) {
		die ( "no supplier" );
	}

	$fields = "part_id, date, week, amount, delivery_fee, ref, project_id, net_amount, document_type ";
	$values = $part_id . ", \"" . $date . "\", " .
	          "\"" . $sunday->format( "Y-m-d" ) .
	          "\", " . ( $amount - $delivery_fee ) . ", " . $delivery_fee . ", '" . $ref . "', '" . $project . "', " .
	          $net_amount . ", " . $document_type;

	if ( $document_file ) {
		$fields .= ", invoice_file";
		$values .= ", " . quote_text( $document_file );
	}
	$sql = "INSERT INTO im_business_info (" . $fields . ") "
	       . "VALUES (" . $values . " )";

	my_log( $sql, __FILE__ );

	sql_query( $sql );

	return sql_insert_id();
}

function business_delete_transaction( $ref ) {
	$sql = "DELETE FROM im_business_info "
	       . " WHERE ref = " . $ref;

	my_log( $sql, __FILE__ );
	sql_query( $sql );
}

function business_update_transaction( $delivery_id, $total, $fee ) {
	$sql = "UPDATE im_business_info SET amount = " . $total . ", " .
	       " delivery_fee = " . $fee .
	       " WHERE ref = " . $delivery_id;

	my_log( $sql, __FILE__ );
	sql_query( $sql );
}

function business_logical_delete( $ids ) {
	$sql = "UPDATE im_business_info SET is_active = 0 WHERE id IN (" . $ids . ")";
	sql_query( $sql );
	my_log( $sql );
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
// 	var_dump($values);
	$account_idx = array_search( "account_id", $fields );
	$date_idx    = array_search( "date", $fields );
	$balance_idx = array_search( "balance", $fields );
	$account     = $values[ $account_idx ];
	$date        = $values[ $date_idx ];
	$balance     = $values[ $balance_idx ];
//	print "a=" . $account . " d=" . $date . " b=" . $balance;

	$sql = "SELECT count(*) FROM im_bank WHERE account_id = " . $account .
	       " AND date = " . quote_text( $date ) .
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

function gui_select_bank_account( $id, $value, $args ) {
	return Core_Html::GuiSelectTable($id, "im_bank_account", $args);
//	return Core_Html::gui_select_table( $id, "im_bank_account", $value,
//		GetArg( $args, "events", null ), null, "name" );
}
