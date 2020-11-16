<?php

abstract class TransView {
	const
		default = 0,
		from_last_zero = 1,
		not_paid = 2,
		read_last = 3,
		admin = 4;
}




class Finance_Client_Views {
	static function show_trans( $customer_id = 0, $view = TransView::default, $args =null )
	{
		if (! $customer_id) $customer_id = get_user_id();
		// $from_last_zero = false, $checkbox = true, $top = 10000
		$query = GetArg($args, "param", null);

		// Show open deliveries
		$from_last_zero = false;
		$admin       = ($view == TransView::admin);

		$top            = null;
		$not_paid       = false;
		switch ( $view ) {
			case TransView::from_last_zero:
				$from_last_zero = true;
				break;
			case TransView::not_paid:
				$not_paid = true;
				break;

			case TransView::read_last:
				$top = 100;
				break;
		}
		$sql = 'select 
		id, 
		date,
		round(transaction_amount, 2) as transaction_amount,
		client_balance(client_id, date) as balance,
	    transaction_method,
	    transaction_ref, 
		order_from_delivery(transaction_ref) as order_id,
		delivery_receipt(transaction_ref) as receipt,
		id 
		from im_client_accounts 
		where client_id = ' . $customer_id;

		if ($not_paid)
			$sql .= " and transaction_method = 'משלוח'  and
          delivery_receipt(transaction_ref) is null and date > '2018-01-01'";

		if ($query) $sql .= " and " . $query;

		$sql .= ' order by date desc ';

		if ( $top ) $sql .= " limit " . $top;

		$args["class"] = "widefat";
		$args["links"] = array();
		$args["links"]["transaction_ref"] = Finance_Delivery::getLink('%d');

		// Todo: Finish step 2: "/delivery?id=%s";
		$args["col_ids"] = array("chk", "id", "dat", "amo", "bal", "des", "del", "ord");
		if (! $admin) unset ($args["col_ids"][0]);
		$args["add_checkbox"] = ($view == TransView::not_paid); // Checkbox will be added only to unpaid rows
		$args["post_file"] = Flavor::getPost();
		$first = true;

		$args["page_number"] = -1;// all rows
		$args["header_fields"] = array("transaction_amount" => "Transaction amount",
		                               "transaction_method" => "Operation",
		                               "transaction_ref" => "Reference number",
		                               "balance" => "Balance",
		                               "order_id" => "Order",
		                               "receipt" => "Receipt");


		$args["checkbox_class"] = "trans_checkbox";

		$data1 = Core_Data::TableData($sql, $args);

		if (! $data1) return ImTranslate("No orders");

		if ($admin) foreach ($data1 as $id => $row)
		{
			$row_id = $row['id'];
			$value = "";
			if ($first) { $first = false; $value = "בחר";}
			else if ($data1[$id]['transaction_method'] == "משלוח" and ! $data1[$id]['receipt']) // Just unpaid deliveries
				$value =  Core_Html::GuiCheckbox("chk_" . $row_id, false, array("checkbox_class" => "trans_checkbox", "events" => "onchange=update_sum()"));

			array_unshift($data1[$id], $value);
		}
		return Core_Gem::GemArray($data1, $args, "trans_table");
	}


}