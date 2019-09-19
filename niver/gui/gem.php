<?php

function GemAddRow($table_name, $text, $args){
	$result = "";
	//			$args["header_fields"] = array("Id", "Date", "Description", "Amount");
	//			$args["actions"] = array(array("Mark payment", "../business/business-post.php?operation=create_pay_bank&id=%s"));
	//			$page = get_param("page", false, 1);
	//			$rows_per_page = 20;
	//			$offset = ($page - 1) * $rows_per_page;

	//				, "select id, date, description, out_amount, reference from im_bank where account_id = " . $account_id .
	//			                                     " and receipt is null and out_amount > 0 " .
	//			                                     " order by date desc limit $rows_per_page offset $offset", $args);

	// print gui_hyperlink("Older", add_to_url("page", $page + 1));
	$result .= gui_header(1, "Add");
	$result .= NewRow($table_name, $args);
	$result .= gui_button("add_row", "save_new('$table_name')", "add");

	return $result;
}

// Data is updated upon change by the client;
function GemTable($table_name, $text, $args){
	$result = "";
	//			$args["header_fields"] = array("Id", "Date", "Description", "Amount");
	//			$args["actions"] = array(array("Mark payment", "../business/business-post.php?operation=create_pay_bank&id=%s"));
	//			$page = get_param("page", false, 1);
	//			$rows_per_page = 20;
	//			$offset = ($page - 1) * $rows_per_page;

	//				, "select id, date, description, out_amount, reference from im_bank where account_id = " . $account_id .
	//			                                     " and receipt is null and out_amount > 0 " .
	//			                                     " order by date desc limit $rows_per_page offset $offset", $args);

	// print gui_hyperlink("Older", add_to_url("page", $page + 1));
	$result .= gui_header(1, "Add");
	$args["events"] = 'onchange="update_table_field(\'/tools/admin/data-post.php\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';

	$result .= GuiTableContent($table_name, null, $args);
//	$result .= gui_button("add_row", "save_new('$table_name')", "Save");

	return $result;
}