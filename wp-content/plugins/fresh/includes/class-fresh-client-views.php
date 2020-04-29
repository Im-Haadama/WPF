<?php

class eTransview {
	const
		default = 0,
		from_last_zero = 1,
		not_paid = 2,
		read_last = 3;
}


class Fresh_Client_Views {
	private $client_id;

	public function __construct($id) {
		$this->client_id = $id;;
	}

	static function handle_operation($operation)
	{
		$args = self::Args();
		switch ($operation)
		{
			case "client_archive":
				$user_id = get_user_id(true);
				return self::show_trans($user_id, eTransview::default, $args);

			case "client_balance":
				$user_id = get_user_id(true);
				return self::client_balance($user_id);

			case "open_orders":
				$user_id = get_user_id(true);
				return self::open_orders($user_id);

			case "show_delivery":
				die(1);
				$del_id = GetParam("id", true);
				$delivery = new Fresh_Delivery($del_id);
				print "del: " . $del_id . " me: " . get_user_id() . " del: " . $delivery->getUserId() . "<br/>";
				if (($delivery->getUserId() != get_user_id()) and
				    1) // here we need to check if the user is a manager with permissions.
					return "no permissions " . __FUNCTION__;
				return $delivery->CustomerView(); // (FreshDocumentType::delivery, Fresh_DocumentOperation::show);
		}
		return $operation . " not handled " . __FUNCTION__ . "<br/>";
	}

	static private function getPost()
	{
		return "/wp-content/plugins/fresh/post.php";
	}

	static function Args()
	{
		$args = [];
		$args["post_file"] = self::getPost();
		$args["page_number"] = GetParam("page_number");
		$query["query"] = GetParam("query");

		return $args;
	}

	static function client_balance($user_id)
	{
		$client = new Fresh_Client($user_id);
		return __("Balance") . ":" . $client->balance();
	}

	static function open_orders( $user_id ) {
		$result = "";

		if ( $user_id ) {
			$sql = "select id from wp_posts where order_user(id) = " . $user_id . " and post_status in 
				('wc-processing', 'wc-on-hold', 'wc-pending')";

			$orders = sql_query_array_scalar( $sql );

			if ( ! $orders ) {
				return __( "No pending orders" ) . "<br/>";
			}

			foreach ( $orders as $order ) {
				if ( get_post_meta( $order, '$result .=ed' ) ) {
					$result .= "הזמנה " . $order . " עברה לטיפול. צור קשר עם שירות הלקוחות" . "<br/>";
				} else {
					$result .= __("Order number") . $order . " " . __("before process") . ". " . __("Edit here:") . " ";
					$result .= Core_Html::GuiHyperlink( __("Order") . " " . $order, "/my-account/view-order/" . $order );
					$result .= ".<br/>";
				}
			}

			return $result;
		}
	}

	static function show_trans( $customer_id, $view = eTransview::default, $args =null )
	{
		// $from_last_zero = false, $checkbox = true, $top = 10000
		$query = GetArg($args, "param", null);

		// Show open deliveries
		$from_last_zero = false;
		$checkbox       = true;
		$top            = null;
		$not_paid       = false;
		switch ( $view ) {
			case eTransview::from_last_zero:
				$from_last_zero = true;
				break;
			case eTransview::not_paid:
				$not_paid = true;
				break;

			case eTransview::read_last:
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
			$sql .= " and transaction_method = 'משלוח' ";

		if ($query) $sql .= " and " . $query;

		$sql .= ' order by date desc ';

		if ( $top ) {
			$sql .= " limit " . $top;
		}

		$args["links"] = array();
		$args["links"]["transaction_ref"] = "/delivery?id=%s";
//		$args["links"]["order"] = "/delivery/order_id=%s";
		$args["col_ids"] = array("chk", "id", "dat", "amo", "bal", "des", "del", "ord");
//	$args["show_cols"] = array(); $args["show_cols"]['id'] = 0;
		$args["add_checkbox"] = false; // Checkbox will be added only to unpaid rows
		$first = true;

		$args["page"] = -1;// all rows
		$args["header_fields"] = array("transaction_amount" => "Transaction amount",
		                               "transaction_method" => "Operation",
		                               "transaction_ref" => "Reference number",
		                               "balance" => "Balance",
		                               "order_id" => "Order",
		                               "receipt" => "Receipt");

		$data1 = Core_Data::TableData($sql, $args);

		if (! $data1) {
			print ImTranslate("No orders");
			return;
		}

		foreach ($data1 as $id => $row)
		{
			$row_id = $row['id'];
			$value = "";
			if ($first) { $first = false; $value = "בחר";}
			else {
				if ($data1[$id]['transaction_method'] == "משלוח" and ! $data1[$id]['receipt']){ // Just unpaid deliveries
					$value =  Core_Html::GuiCheckbox("chk_" . $row_id, false, array("class" => "trans_checkbox", "events" => "onchange=update_sum()"));
				}
			}
			array_unshift($data1[$id], $value);
		}
		return Core_Gem::GemArray($data1, $args, "trans_table");
	}
}