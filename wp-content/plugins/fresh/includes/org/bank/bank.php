<?php
require_once( FRESH_INCLUDES . '/core/gui/gem.php' );
require_once( FRESH_INCLUDES . '/core/data/data.php' );

// $admin_scripts = array( "/core/gui/client_tools.js", "/core/data/data.js", "/fresh/admin/admin.js" );


function bank_transactions($query = null, $args = null)
{
	$result = "";

	$account_id = 1;

	print Core_Html::GuiHyperlink('Create Receipts', add_to_url("operation" , "receipts")); print " ";
	print Core_Html::GuiHyperlink('Mark payments', add_to_url("operation", "payments")); print " ";
	print Core_Html::GuiHyperlink('Import bank pages', add_to_url("operation" ,"import")); print " ";
	print Core_Html::GuiHyperlink('Edit transaction types', add_to_url("operation" ,"transaction_types")); print " ";
	print Core_Html::GuiHyperlink('Search transaction', add_to_url("operation" ,"search")); print " ";

	$page = get_param("page", false, 1);
	$rows_per_page = 20;
// $args["debug"] = (get_user_id() == 1);
	$offset = ($page - 1) * $rows_per_page;
	
	$fields = GetArg($args, "fields", array("id", "date", "description", "out_amount", "in_amount", "balance", "receipt"));

	$sql = "select " . comma_implode($fields) . " from im_bank where account_id = " . $account_id;
	if ($query) $sql .= " and " . $query;
	$sql .= " order by date desc limit $rows_per_page offset $offset ";

	$result .= GuiTableContent("im_banking", $sql, $args);

	$result .= Core_Html::GuiHyperlink("Older", add_to_url("page", $page + 1));

	return $result;
}