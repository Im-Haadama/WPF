<?php
require_once( ROOT_DIR . '/niver/gui/gem.php' );
require_once( ROOT_DIR . '/niver/data/data.php' );

// $admin_scripts = array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/fresh/admin/admin.js" );

function handle_bank_operation($operation, $url = null) {
	$account_id = 1;
	$ids = null;

	switch ($operation)
	{
		default:
	}

	print HeaderText();
	// Todo: change to operation
	if (get_param("search", false, 0)){
		$ids=data_search("im_bank");
		gui_header(1, "Results");
		if (! $ids){
			print im_translate("Nothing found");
			return;
		}
	}

	switch ( $operation ) {
		case "receipts":
			$args = array();
			print gui_header( 1, "Receipts" );
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
		case "payments":
			$args = array();
			print gui_header( 1, "Payments" );
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

			//		print gui_hyperlink("Older", add_to_url("page", $page + 1));

			print bank_transactions( $query, $args );

			return;

		case "transaction_types":
			$args = array();
			// $args["selectors"] = array("part_id" => "gui_select_supplier");

			print GuiTableContent( "im_bank_transaction_types", null, $args );
			print gui_hyperlink( "add", add_to_url( "operation", "add_transaction_types" ) );

			return;

		case "add_transaction_types":
			$args              = array();
			$args["selectors"] = array( "part_id" => "gui_select_supplier" );
			$args["post_file"] = "/niver/data/data-post.php";

			print GemAddRow( "im_bank_transaction_types", "Transaction types", $args );

			return;

		case "search":
			$args           = array();
			$search_url     = "search_table('im_bank', '" . add_param_to_url( $url, "search", "1" ) . "')";
			$args["search"] = $search_url; //'/fresh/bank/bank-page.php?operation=do_search')";
			GemSearch( "im_bank", $args );

			return;

		case "do_search":
			$ids = data_search( "im_bank" );
			gui_header( 1, "Results" );
			if ( ! $ids ) {
				print im_translate( "Nothing found" );

				return;
			}
			print bank_transactions( "id in (" . comma_implode( $ids ) . ")" );

			return;

		case "import":
			$args                  = array();
			$args["selector"]      = "gui_select_bank_account";
			$args["import_action"] = '/org/bank/bank-page.php?operation=import_from_file';

			print GemImport( "im_bank", $args );
			print '<script> window.onload = change_import;</script>';
			break;

		case 'import_from_file':
			require_once( ROOT_DIR . "/niver/data/Importer.php" );
			$file_name = $_FILES["fileToUpload"]["tmp_name"];
			print "Trying to import $file_name<br/>";
			$I                    = new Importer();
			$fields               = null;
			$fields               = array();
			$fields['account_id'] = get_param( 'selection' );
			if ( ! $fields['account_id'] ) {
				die( "not account given" );
			}
			try {
				$result = $I->Import( $file_name, "im_bank", $fields, 'bank_check_dup' );
			} catch ( Exception $e ) {
				print $e->getMessage();

				return;
			}
			print $result[0] . " rows imported<br/>";
			print $result[1] . " duplicate rows <br/>";
			print $result[2] . " failed rows <br/>";
			break;

		case "show_transactions":
			print bank_transactions( $ids ? "id in (" . comma_implode( $ids ) . ")" : null );
	}
}

function bank_transactions($query = null, $args = null)
{
	$result = "";

	$account_id = 1;

	print gui_hyperlink('Create Receipts', add_to_url("operation" , "receipts")); print " ";
	print gui_hyperlink('Mark payments', add_to_url("operation", "payments")); print " ";
	print gui_hyperlink('Import bank pages', add_to_url("operation" ,"import")); print " ";
	print gui_hyperlink('Edit transaction types', add_to_url("operation" ,"transaction_types")); print " ";
	print gui_hyperlink('Search transaction', add_to_url("operation" ,"search")); print " ";

	$page = get_param("page", false, 1);
	$rows_per_page = 20;
// $args["debug"] = (get_user_id() == 1);
	$offset = ($page - 1) * $rows_per_page;
	
	$fields = GetArg($args, "fields", array("id", "date", "description", "out_amount", "in_amount", "balance", "receipt"));

	$sql = "select " . comma_implode($fields) . " from im_bank where account_id = " . $account_id;
	if ($query) $sql .= " and " . $query;
	$sql .= " order by date desc limit $rows_per_page offset $offset ";

	$result .= GuiTableContent("im_banking", $sql, $args);

	$result .= gui_hyperlink("Older", add_to_url("page", $page + 1));

	return $result;
}