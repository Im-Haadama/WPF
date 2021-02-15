<?php

class Fresh_Client_Views  extends Finance_Client_Views {
	private $edit_basket_allowed;
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}


	public function __construct() {
		$this->edit_basket_allowed = true;
	}

	function init_hooks($loader)
	{
		// Add edit button on my-account/orders
		add_filter( 'woocommerce_my_account_my_orders_actions', array(__CLASS__, 'account_order_actions'), 10, 2 );
		add_action('init', array(__CLASS__, 'init'));
		add_action('woocommerce_account_edit-order_endpoint', array(__CLASS__, 'edit_order'));
		add_action('order_remove_from_basket', array(__CLASS__, 'remove_from_basket'));
		add_action('order_add_to_basket', array(__CLASS__, 'add_to_basket'));
		add_action('order_remove_item', array(__CLASS__, 'remove_item'));
		AddAction('order_quantity_update', array(__CLASS__, 'order_quantity_update'));
//		add_filter( 'woocommerce_account_menu_items', array(__CLASS__, 'account_menu_items'), 10, 1 );
	}

	function getShortcodes()
	{
			//             code                           function                  capablity (not checked, for now).
		return ( array('finance_balance' => array(array( $this, 'show_balance'), null),
		               'finance_trans'   => array(array( $this, 'show_trans'), null),
			'fresh_siton' => array(array($this, 'fresh_siton'), null)));
	}

	function fresh_siton()
	{
		foreach ( array( 62, 18, 19, 66 ) as $categ ) {
			$term = get_term( $categ );

			print Core_Html::GuiHeader( 1, $term->name );
			Fresh_Catalog::show_catalog( 0, "", false, true, true, false, array( $term->term_id ), false );
		}
	}
	static function order_quantity_update()
	{
		return Fresh_Order::setQuantity(GetParam("ooid", true), GetParam("quantity"));
	}

	// Chava.
	static function remove_from_basket()
	{
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
		$quantity = Finance_Delivery::get_order_itemmeta($item_id, '_qty');

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

		$allowed_basket_edit = false;
		if ($allowed_basket_edit)
			$result .= "לחץ על שם הסל, כדי לבצע בו שינויים";
				//__("Press on basket name to edit it");
//			$result .=
//
//

		$o = new Fresh_Order($order_id);
		if ($o->getStatus() == 'wc-processing') {
			$result .= " ההזמנה כבר בטיפול. השינויים ירשמו ויבוצעו במידת האפשר<br/>";
			$table_rows = array(array("שם פריט", "כמות", "מחיר", 'סה"כ', "הערה"));
		} else {
			$table_rows = array(array("שם פריט", "כמות", "מחיר", 'סה"כ', "הערה", "הסר"));
		}

		$rows = SqlQueryArray( "select order_item_id, order_item_name from wp_woocommerce_order_items " .
		                       " where order_id = $order_id and order_item_type = 'line_item'");


		$basket_divs = "";
		foreach ($rows as $key => $row)	{
			$item_id = $row[0];
			$name = $row[1];

			$qty = SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = '_qty'" );
			$line_total = SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = '_line_total'" );
			$prod_id = SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = '_product_id'" );
			$comment =  SqlQuerySingleScalar( "select meta_value from wp_woocommerce_order_itemmeta where order_item_id = $item_id and meta_key = 'product_comment'" );

			$P = new Fresh_Product($prod_id);
			if ($P->is_basket()) {
				$name = Core_Html::gui_label("lab_$item_id", $name, array("events"=> "onclick=\"order_show_basket($item_id)\""));
				$basket_divs .= self::edit_basket($item_id, $prod_id);
			}

			$table_rows[$item_id] = array("name" => $name,
			                              "qty" => Core_Html::GuiInput("qty_$item_id", $qty, array("events" => "onchange=\"order_quantity_update('".Fresh::getPost() ."', $item_id)\"")),
			                              "price" => round($line_total/$qty, 2),
						   				  "total"=>$line_total,
				                          $comment);
			if ($o->getStatus() != 'wc-processing')
				$table_rows[$item_id] ["remove"]  = Core_Html::GuiButton("rem_$item_id", "X", array("action" => "order_remove_line('" . Fresh::getPost() . "', $item_id, rem_$item_id)"));

		}
		$result .= Core_Html::gui_table_args($table_rows);

		$result .= "<div>הוספת מוצר: " . Fresh_Product::gui_select_product("new_prd") .
			Core_Html::GuiButton("btn_add_prod", "הוסף", "order_add_product('".Fresh::getPost() ."', $order_id)\"") . "</div>";

		$result .= $basket_divs;

		print $result;
	}

	static function handle_operation($operation)
	{
		$args = self::Args();
		switch ($operation)
		{
			case "client_archive":
				$user_id = get_user_id();
				if ($user_id)
					return self::show_trans($user_id, TransView::default, $args);


//			case "open_orders":
//				$user_id = get_user_id(true);
//				return self::open_orders($user_id);

			case "show_delivery":
				die(1);
				$del_id = GetParam("id", true);
				$delivery = new Fresh_Delivery($del_id);
//				print "del: " . $del_id . " me: " . get_user_id() . " del: " . $delivery->getUserId() . "<br/>";
				if (($delivery->getUserId() != get_user_id()) and
				    1) // here we need to check if the user is a manager with permissions.
					return "no permissions " . __FUNCTION__;
				return $delivery->CustomerView(); // (Finance_DocumentType::delivery, Fresh_DocumentOperation::show);
		}
		return $operation . " not handled " . __FUNCTION__ . "<br/>";
	}

	static function Args()
	{
		$args = [];
		$args["post_file"] = Flavor::getPost();
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
					$wc_order = new WC_Order($order);
					$result .= "הזמנה " . Core_Html::GuiHyperlink(  $order, $wc_order->get_view_order_url() ) . " ניתנת לעריכה";
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
