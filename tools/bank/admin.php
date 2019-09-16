<?php

// $admin_scripts = array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" );

function handle_bank_operation($operation) {
	$account_id = 1;
	switch ( $operation ) {
		case "payments":
			$args = array();
			print gui_header(1, "Payments");
			$args["header_fields"] = array("Id", "Date", "Description", "Amount");
			$args["actions"] = array(array("Mark payment", "../business/business-post.php?operation=create_pay_bank&id=%s"));
			$page = get_param("page", false, 1);
			$rows_per_page = 20;
			$offset = ($page - 1) * $rows_per_page;

			print GuiTableContent( "im_banking", "select id, date, description, out_amount, reference from im_bank where account_id = " . $account_id .
			                                     " and receipt is null and out_amount > 0 " .
			                                     " order by date desc limit $rows_per_page offset $offset", $args);

			print gui_hyperlink("Older", add_to_url("page", $page + 1));

			return;
	}
}