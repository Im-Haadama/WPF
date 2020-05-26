<?php

abstract class TransView {
	const
		default = 0,
		from_last_zero = 1,
		not_paid = 2,
		read_last = 3,
		admin = 4;
}


class Fresh_Client_Views {
	private $edit_basket_allowed;

	public function __construct() {
		$this->edit_basket_allowed = true;
	}

	static function init_hooks()
	{
		// Add edit button on my-account/orders
		add_filter( 'woocommerce_my_account_my_orders_actions', array(__CLASS__, 'account_order_actions'), 10, 2 );
		add_action('init', array(__CLASS__, 'init'));
		add_action('woocommerce_account_edit-order_endpoint', array(__CLASS__, 'edit_order'));
		add_action('order_remove_from_basket', array(__CLASS__, 'remove_from_basket'));
		add_action('order_add_to_basket', array(__CLASS__, 'add_to_basket'));
		add_action('order_remove_item', array(__CLASS__, 'remove_item'));
//		add_filter( 'woocommerce_account_menu_items', array(__CLASS__, 'account_menu_items'), 10, 1 );
	}

	function getShortcodes()
	{
			//             code                           function                  capablity (not checked, for now).
		return ( array('finance_balance' => array(array( $this, 'show_balance'), null),
		               'finance_trans'   => array(array( $this, 'show_trans'), null)));
	}

	// Chava.
	static function remove_from_basket()
	{
//		print __FUNCTION__;
		$item_id = GetParam("item_id", true);
		$prod_id = GetParam("prod_id", true);

		$order_id = SqlQuerySingleScalar("select order_id from wp_woocommerce_order_items where order_item_id = $item_id");
		$Order = new Fresh_Order($order_id);
//		$current_remove = get_postmeta_field($order_id)
//		$Order->addProduct($prod_id, -1, false, $Order->getCustomerId(),null, "regular", 0);
		$Order->removeFromBasket($item_id, $prod_id);
		return false;
	}

	static function add_to_basket()
	{
		$item_id = GetParam("item_id", true);
		$prod_id = GetParam("new_prod_id", true);
//		print "pid=$prod_id";

		$order_id = SqlQuerySingleScalar("select order_id from wp_woocommerce_order_items where order_item_id = $item_id");
		$Order = new Fresh_Order($order_id);
		//               $product_id, $quantity, $replace = false, $client_id = - 1, $unit = null, $type = null, $price = null
		$Order->addToBasket($item_id, $prod_id);
//		$Order->addProduct($prod_id, 1, false, $Order->getCustomerId(),null, "regular", 0);
		return true;
	}

	static function remove_item()
	{
		$item_id = GetParam("item_id", true);
		$order_id = SqlQuerySingleScalar("select order_id from wp_woocommerce_order_items where order_item_id = $item_id");
		$Order = new Fresh_Order($order_id);
		$Order->DeleteLines(array($item_id));
		return true;
	}

	static function edit_basket($item_id, $prod_id)
	{
		$allowed_changes = 3;

		$P = new Fresh_Product($prod_id);
		$quantity = Fresh_Packing::get_order_itemmeta($item_id, '_qty');

		$removed = Fresh_Order::basketRemoved($item_id);
		$addon = Fresh_Order::basketAdded($item_id);
		$remove_allowed = (count($removed) < $allowed_changes);
		$more_to_add = (count($removed) - count($addon));
//		var_dump($removed); print "<br/>";
//		var_dump($addon); print "<br/>";

		$div_content = Core_Html::GuiHeader(1, "עריכת  " . $P->getName());
		$div_content .= "הסר באמצעות ה X פריט מהסל, כדי להוסיף אחד במקומו<br/>";
		$div_content .= "מספר השינויים האפשרי: " . Core_Html::GuiLabel("changes_allowed_$item_id", $allowed_changes);

		$basket_rows =self::expand_basket($item_id, $prod_id, $quantity, 0, $remove_allowed);
		$header = array("פריט", "כמות", "מחיר"); if ($remove_allowed) array_push($header, "הסר");
		array_unshift($basket_rows, $header);
		$div_content .= Core_Html::gui_table_args($basket_rows);
		for ($i = 0; $i < $more_to_add; $i++) {
//			$show = ($allowed_changes > $removed ? 'none' : 'block');
			$div_content .= Core_Html::GuiDiv( "add_to_basket_${item_id}_$i",
				"בחר מוצר להוסיף:" . Fresh_Product::gui_select_product( "new_prod_${item_id}_$i", null, array( "events" => "onchange=\"order_add_to_basket('" . Fresh::getPost() . "', $item_id, $prod_id, $i)\"" ) ));
//				array( "style" => "display: $show;" ) ); // none/block
		}

		return Core_Html::GuiDiv("basket_$item_id",
			Core_Html::GuiHeader(1, $div_content),
			array("style"=>"display: none;"));
	}

	static function expand_basket($item_id, $basket_id, $quantity_ordered, $level = 0, $remove_allowed = false) : array
	{
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;
		$client_type = "regular";
		$result2 = SqlQuery( $sql2 );
		$basket_lines = array();
		while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
			$prod_id  = $row2["product_id"];
			if (! $prod_id or in_array($prod_id, Fresh_Order::basketRemoved($item_id))) continue;
//			 print $prod_id . "<br/>";
			$P        = new Fresh_Product( $prod_id );
			$quantity = $row2["quantity"];
			$basket_or_prod = new Fresh_Basket($prod_id);
			if ( $basket_or_prod->is_basket( ) ) {
				foreach (self::expand_basket( $item_id, $prod_id, $quantity_ordered * $quantity, $level + 1 ) as $row)
					array_push($basket_lines, $row);
			} else {
				$line = array();
				$line[ "name" ] = $P->getName();
				$line[ "quantity" ]        = $quantity * $quantity_ordered;
				$line["price"] = Fresh_Pricing::get_price_by_type($prod_id);
				if ($remove_allowed) $line["action"] = Core_Html::GuiButton("remove_${item_id}_{$prod_id}", "X", array("action"=>"order_remove_from_basket('" . Fresh::getPost() . "', $item_id, $prod_id)"));
				array_push($basket_lines, $line);
			}
		}
		return $basket_lines;
	}
	// End Chava

	static function init()
	{
		add_rewrite_endpoint('edit-order', EP_PAGES);
		flush_rewrite_rules();
	}

	static function edit_order($order_id)
	{
		$result = Core_Html::GuiHeader(1, __("Editing order number") . " $order_id");

		$allowed_basket_edit = true;
		if ($allowed_basket_edit)
			$result .= "לחץ על שם הסל, כדי לבצע בו שינויים";
				//__("Press on basket name to edit it");

		$rows = SqlQueryArray( "select order_item_id, order_item_name from wp_woocommerce_order_items " .
		                       " where order_id = $order_id and order_item_type = 'line_item'");

		$table_rows = array(array("שם פריט", "כמות", "מחיר", 'סה"כ', "הערה", "הסר"));

		$basket_divs = "";
		foreach ($rows as $key => $row)
		{
			$item_id = $row[0];
			$name = $row[1];

			$qty = SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = '_qty'" );
			$line_total = SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = '_line_total'" );
			$prod_id = SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = '_product_id'" );
			$comment =  SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = 'product_comment'" );
//			$price = Fresh_Pricing::get_price_by_type($prod_id);

			$P = new Fresh_Product($prod_id);
			if ($P->is_basket()) {
				$name = Core_Html::gui_label("lab_$item_id", $name, array("events"=> "onclick=\"order_show_basket($item_id)\""));
				$basket_divs .= self::edit_basket($item_id, $prod_id);
			}

			$table_rows[$item_id] = array("name" => $name,
			                              "qty" => Core_Html::GuiInput("qty_$item_id", $qty, array("events" => "onchange=\"order_update_quantity($item_id)\"")),
			                              "price" => round($line_total/$qty, 2),
						   				  "total"=>$line_total,
				                          $comment,
				                          "remove" => Core_Html::GuiButton("rem_$item_id", "X", array("action" => "order_remove_line('" . Fresh::getPost() . "', $item_id, rem_$item_id)")),
			);

		}
		$result .= Core_Html::gui_table_args($table_rows);

		$result .= $basket_divs;

		print $result;
	}

	static function handle_operation($operation)
	{
		$args = self::Args();
		switch ($operation)
		{
			case "client_archive":
				$user_id = get_user_id(true);
				return self::show_trans($user_id, TransView::default, $args);


//			case "open_orders":
//				$user_id = get_user_id(true);
//				return self::open_orders($user_id);

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

	static function open_orders( $user_id )
	{
		if (strstr(GetUrl(), 'view-order'))
			return ""; // Already showing single order.
		$result = "";

		if ( $user_id ) {
			$sql = "select id from wp_posts where order_user(id) = " . $user_id . " and post_status in 
				('wc-processing', 'wc-on-hold', 'wc-pending')";

			$orders = SqlQueryArrayScalar( $sql );

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

	static function show_balance($customer_id = 0)
	{
		if (! $customer_id) $customer_id = get_user_id();
		if (! $customer_id) return null;
		$c = new Fresh_Client($customer_id);
		$b = $c->balance();
		if ($b) return "יתרה לתשלום: " . $b ."<br/>";
	}

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

		$args = [];
		$args["class"] = "widefat";
		$args["links"] = array();
		$args["links"]["transaction_ref"] = "/wp-content/plugins/fresh/delivery/get-delivery.php?id=%s";
			// Todo: Finish step 2: "/delivery?id=%s";
		$args["col_ids"] = array("chk", "id", "dat", "amo", "bal", "des", "del", "ord");
		if (! $admin) unset ($args["col_ids"][0]);
		$args["add_checkbox"] = ($view == TransView::not_paid); // Checkbox will be added only to unpaid rows
		$args["post_file"] = Fresh::getPost();
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
					$value =  Core_Html::GuiCheckbox("chk_" . $row_id, false, array("class" => "trans_checkbox", "events" => "onchange=update_sum()"));

			array_unshift($data1[$id], $value);
		}
		return Core_Gem::GemArray($data1, $args, "trans_table");
	}

	static function account_order_actions( $actions, $order )
	{
		$Order = new Fresh_Order($order->id);
		if (! in_array($Order->getStatus(), array('wc-on-hold', 'wc-processing', 'wc-pending') )) return $actions;
		$actions['name'] = array(
			'url'  => '/my-account/edit-order/'. $order->id,
			'name' => 'Edit',
		);
		return $actions;
	}
}
