<?php

class Finance_Delivery
{
	private $delivery_id;
	private $order;
	private $line_number = 0;
	private $delivery_fields_names;
	private $order_total = 0;
	private $order_due_vat = 0;
	private $order_vat_total = 0;
	private $delivery_total = 0;
	private $delivery_total_vat = 0;
	private $margin_total = 0;
	private $order_id = 0;

	/**
	 * @return int|mixed|string|null
	 */
	public function getId() {
		return $this->delivery_id;
	}

	static function init()
	{
	}

	static function init_hooks($loader)
	{
		// Gui
		$loader->AddAction( "delivery_show_create", __CLASS__,  "delivery_show_create" ); // gui: order->delivery
		$loader->AddAction( "delivery_show_edit", __CLASS__,  "delivery_show_edit" ); // gui: order->delivery

		// Actions
		$loader->AddAction("delivery_save", __CLASS__,  'save_wrap'); // POST: create delivery
		$loader->AddAction("delivery_edit", __CLASS__,  'edit_wrap'); // POST: update delivery
		$loader->AddAction("delivery_delete", __CLASS__,  "delete_wrap");
//		$loader->AddAction("delivery_delete_lines", __CLASS__,  "delete_lines_wrap");
		$loader->AddAction("delivery_get_price", __CLASS__,  'get_price');

		// Complete order
		$loader->AddAction('woocommerce_order_status_completed', __CLASS__,  'order_complete_wrap');

		// Show link to delivery note
		$loader->AddAction('woocommerce_order_actions_start', __CLASS__,  'show_delivery_link');
	}

	/**
	 * Finance_Delivery constructor.
	 *
	 * @param $delivery_id
	 * @param $order_id
	 */
	public function __construct( $order_id = 0, $delivery_id = 0 ) {
		// To create new delivery send just order_id.
		// To load existing delivery send delivery (order_id can be 0);
		if ($delivery_id) {
			$this->delivery_id = $delivery_id;
			$this->order_id = SqlQuerySingleScalar( "SELECT order_id FROM im_delivery WHERE id = " . $delivery_id );
		} else {
			if ($order_id) {
				$this->delivery_id = SqlQuerySingleScalar( "SELECT id FROM im_delivery WHERE order_id = " . $order_id );
				$this->order_id = $order_id;
			} else {
				throw new Exception("No order id and no delivery id");
			}
		}
//		print "DDD=" . $this->delivery_id . "; OOOO=" . $this->order_id . "<br/>";
		if ($this->order_id > 0)
			$this->order       = new Finance_Order($this->order_id);
	}

	public function getDeliveryTotal() : float
	{
		$db_prefix = GetTablePrefix("delivery_lines");

		if (! $this->delivery_total and $this->delivery_id) {
			$this->delivery_total = SqlQuerySingleScalar("select sum(line_price) from ${db_prefix}delivery_lines where delivery_id = $this->delivery_id");
		}
		return round($this->delivery_total, 2);
	}

	public function getOrder() : ?Finance_Order {
		if (! $this->order) {
			try {
				$this->order = new Finance_Order( $this->order_id );
			} catch (Exception $e) {
				return null;
			}
		}
		return $this->order;
	}

	static function deliveries() {
		$report = "";
		$operation = GetParam( "operation", false, null, true );
		if ($operation) {
			$report = apply_filters($operation,  null);
			if (is_string($report)) {
				print $report;
				return true;
			}
		}

		$delivery_id = GetParam("delivery_id", false, 0);
		$order_id = GetParam("order_id", false, 0);
		if ($delivery_id or $order_id)
		{
			$d = new Finance_Delivery($order_id, $delivery_id);
			$report .= $d->Show();
			if ($d->delivery_id) {
				if ( $receipt = $d->getReceipt() ) {
					$report .= "תעודה שולמה ($receipt) ולא ניתנת לעריכה או למחיקה";
				} else {
					// $report .= Core_Html::GuiHyperlink( "[Delete]", AddToUrl( "operation", "delivery_delete" ) ) . " ";
					$report .= Core_Html::GuiButton("btn_delete", "Delete", array("action"=> "delivery_delete('" . WPF_Flavor::getPost() . "')"));
					$report .= Core_Html::GuiHyperlink( "[Edit]", AddToUrl( "operation", "delivery_show_edit" ) ) . " ";
					$report .= Core_Html::GuiButton( "btn_send", "send delivery", array( "action" => "delivery_send_mail('" . WPF_Flavor::getPost() . "', $delivery_id)" ) );
				}
			} else {
				$report .= __("No delivery note for order"). " " .$order_id;
			}
		} else {
			$date_format = 'Y-m-j';
			$date        = GetParam( "week", false, first_day_of_week() );
			$report      .= Core_Html::GuiHeader( 1, __( "Deliveries of week" ) . " " . $date );
			$report      .= Core_Html::GuiHyperlink( "last week", AddParamToUrl( GetUrl(), "week", date( $date_format, strtotime( $date . " -1 week" ) ) ) ) . " ";
			$report      .= Core_Html::GuiHyperlink( "next week", AddParamToUrl( GetUrl(), "week", date( $date_format, strtotime( $date . " +1 week" ) ) ) );

			$report .= self::week_deliveries( $date );
			$report .= "<br/>";
		}

		print $report;
//		print "op=$operation";
		if ( $operation ) {
			return apply_filters( $operation, "" );
		}
	}

	public function OrderId() {
		if ( ! ( $this->order_id > 0 ) ) {
			$sql = "SELECT order_id FROM im_delivery WHERE id = " . $this->delivery_id;

			$this->order_id = SqlQuerySingleScalar( $sql );
		}

		return $this->order_id;
	}


	static function week_deliveries($date)
	{
		$report = "";
		$args   = [];
		// Links to prev/next week

		// Show selected week
		$args["sql"]       = "select ID, date, order_id, client_from_delivery(ID) as client from im_delivery where first_day_of_week(date) = " . QuoteText( $date );
		$args["id_field"]  = "ID";
		$args["post_file"] = WPF_Flavor::getPost();

		// $args["links"] = array("ID" => add_param_to_url(get_url(), "operation", "show_id", "row_id", "%s"));
		$args["links"] = array( "ID" => AddToUrl("delivery_id", "%s" ));
		$table = Core_Gem::GemTable( "delivery", $args );

		if ( strlen( $table ) < 100 ) {
			$report .= "No deliveries done this week<br/>";
		} else {
			$report .= $table;
		}

		return $report;
	}

	// Split from finance_order. Need to find the common
	// Action to show the delivery creation. Loads from order.
	static function delivery_show_create() {
		$order_id = GetParam( "order_id", true );
		$delivery        = new Finance_Delivery( $order_id );
		if ($delivery->getId()) {
			// Already have delivery
			return $delivery->Show();
		}
		$user_id = $delivery->getUserId();
		$vat = apply_filters("finance_vat", 0);

		return $delivery->ShowCreate(array("user_id" => $user_id, "vat" => $vat));
	}

	static function delivery_show_edit()
	{
		$order_id = GetParam( "order_id", false );
		$delivery_id = GetParam("delivery_id", false);
		if (! $order_id and ! $delivery_id) {
			return "no delivery id or order id";
		}
		$delivery        = new Finance_Delivery( $order_id, $delivery_id );

		return $delivery->Show(true);
	}

	// POST: update delivery
	static public function edit_wrap()
	{
		$send_email = GetParam("send_email", false);
		$id = self::do_create_delivery(true);
		if ($send_email)
			Finance_Actions::delivery_send_mail($id);
	}

//	static function CreateFromOrder( $order_id )
//	{
//		$id = Finance_Order::get_delivery_id( $order_id );
//
//		if ($id) {
//			$instance = new self( $id );
//
//			$instance->SetOrderId( $order_id );
//
//			return $instance;
//		}
////		print "delivery for $order_id not found";
//		return null;
//	}

	private function SetOrderID( $order_id ) {
		$this->order_id = $order_id;
	}

	static function get_order_itemmeta( $order_item_id, $meta_key ) {
		if ( is_array( $order_item_id ) ) {
			$sql = "SELECT sum(meta_value) FROM wp_woocommerce_order_itemmeta "
			       . ' WHERE order_item_id IN ( ' . CommaImplode( $order_item_id ) . ") "
			       . ' AND meta_key = \'' . EscapeString( $meta_key ) . '\'';

			return SqlQuerySingleScalar( $sql );
		}
		if ( is_numeric( $order_item_id ) ) {
			$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
			        . ' WHERE order_item_id = ' . $order_item_id
			        . ' AND meta_key = \'' . EscapeString( $meta_key ) . '\''
			        . ' ';

			return SqlQuerySingleScalar( $sql2 );
		}

		return - 1;
	}

	private function OrderQuery() {
		if ( is_array( $this->order_id ) ) {
			return "order_id in (" . CommaImplode( $this->order_id ) . ")";
		} else {
			return "order_id = " . $this->order_id;
		}
	}

	public function OrderInfoBox() {
		$order = $this->getOrder();
		if (! $order) {
			throw new Exception("No order");
		}
		return $this->getOrder()->infoBox();
	}

	private function getReceipt()
	{
		return $this->delivery_id and SqlQuerySingleScalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $this->delivery_id );
	}

	// Show - if delivery note created.
	function Show($edit = false)
	{
		$order = $this->getOrder();
		if (! $order) return "Order not found";
		$html = self::OrderInfoBox();
		$user_id = $this->getUserId();

		$events = 'onkeypress="quantity_changed()" onfocusout="quantity_changed()"';
		$args = array("fields"=>array("id", "product_name", "quantity_ordered", "quantity", "price", "line_price", "prod_id", "has_vat"),
		              "where" => " delivery_id = " . $this->delivery_id,
		              "skip_id"=>true, "header_fields" => array("product_name" => __("Product name", "e-fresh"),
		                                                        "quantity_ordered" => __("Quantity ordered"),
		                                                        "quantity" => __("Quantity supplied", "e-fresh"),
		                                                        "price"=>__("Price", "e-fresh"),
		                                                        "line_price" => __("Line total")),
			          "events"=>$events,
			          "order_by"=>" order by id ",
			          "edit"=>$edit,
		              "form_table" => "del_table",
		              "edit_cols" => array("quantity"=>1, "price"=>1),
		              "checkbox_class"=>"delivery_lines",
		              "hide_cols" => array("prod_id"=> 1),
		              "add_delete" => $edit
		);

		$args = apply_filters("delivery_args", $args);

		$vat = apply_filters("finance_vat", false);


		if ($this->delivery_id) {
//			print "cc=".$args["checkbox_class"];

			$html .= Core_Html::GuiTableContent( "delivery_lines", null, $args );
			$html .= Core_Html::GuiDiv( "total_div",
				__( "Total" ) . ": " .
				Core_html::gui_label( "total", $this->getDeliveryTotal() )
			);
			$post_file = WPF_Flavor::getPost() . "?delivery_id=" . $this->getId();
			$html .= Core_Html::GuiLabel( "order_id", $this->order_id, array( "hidden" => true ) );
			if ( $edit ) {
				$html .= Core_Html::GuiButton("btn_add_line", "Add Line", "delivery_add_line('" . WPF_Flavor::getPost() . "', $user_id, $vat, 1)");

				$html .= "<div>" . Core_html::GuiCheckbox("chk_send_email", true) . ETranslate("Send email") .
				         Core_Html::GuiButton( "btn_do", "Save", "delivery_save_or_edit('" . WPF_Flavor::getPost() . "', 'delivery_edit')" ) . "</div>";

			}
			$html .= '<datalist id="products"></datalist>';
		}
		return $html;
	}

	// Args:
	// Packing - before packing:
	//  -- No edit.
	//  -- Show comments.
	function ShowCreate($args)
	{
		$user_id = GetArg($args, "user_id", get_user_id());
		$vat = GetArg($args, "vat", 0);

		$html = $this->getOrder()->infoBox( false, "יצירת תעודת משלוח ל" );

		$table = array(
			"header" => array(
				"Product",
				"Comment",
				"Quantity ordered",
				"Quantity supplied",
				"Price",
				"Line Total",
				"Has Vat"
			)
		);

		$packing = GetArg($args, "packing", false);
//		var_dump($packing);

		if ($packing) {
			$args["edit"] = false;
		} else {
			unset_by_value($table['header'], 'Comment');
			$args["edit"] = true;
			$args["edit_cols"] = array("quantity" => 1, "price"=>1, "has_vat"=>1);
			$args["types"] = array("has_vat"=>"tiny");
		}
		$args["hide_cols"] = array("prod_id"=> 1);

		$items = SqlQueryArray("select order_item_id, order_item_type from wp_woocommerce_order_items where order_id = " . $this->order_id);
//		$sql = 'select distinct woim.meta_value, order_line_get_variation(woi.order_item_id) '
//		       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
//		       . ' where ' . $this->OrderQuery()
//		       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\' ' .
//		' order by woi.order_item_id';
//
//		print $sql ."<br/>";
//
//		$rows = SqlQueryArray( $sql );
		$price = 0;
		for ($i = 0; $i < count($items); $i++) {
			$item_id = $items[$i][0];
			$type = $items[$i][1];

			$prod_id = self::get_order_itemmeta($item_id, '_product_id');
			$var_id  = self::get_order_itemmeta($item_id, '_variation_id');
			$has_vat = apply_filters("finance_has_vat", 1, $prod_id);
			switch ($type) {
				case 'line_item':
//					$items_sql = 'select woim.order_item_id'
//					             . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
//					             . ' where ' . $this->OrderQuery()
//					             . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
//					             . ' and woim.meta_value = ' . $prod_id
//					             . ' and order_line_get_variation(woi.order_item_id) = ' . $var_id
//					             . ' order by 1';

					//			print $items_sql. "<br/>";

//					$order_item_ids          = SqlQueryArrayScalar( $items_sql ); // For group order. (not tested in this version).
//					$order_item_id           = $order_item_ids[0];
					$line_total              = self::get_order_itemmeta( $item_id, '_line_total' );
					$quantity                = self::get_order_itemmeta( $item_id, '_qty' );
					$name = SqlQuerySingleScalar( "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $item_id );
					if ($prod_id) {
						$p = new Fresh_Product($prod_id);
						$name = $p->getName(false, true);
					}
					$table[ $item_id ] = array(
						"product_name"     => $name,
						"comment"          => self::get_order_itemmeta( $item_id, 'product_comment' ),
						"quantity_ordered" => $quantity,
						"quantity"         => '',
						"price"            => round( $line_total / $quantity, 2 ),
						"line_price"       => 0,
						"prod_id" => $prod_id,
						"has_vat"=>$has_vat
					);
					break;
				case 'shipping':
					$price = self::get_order_itemmeta($item_id, 'cost');
					$table[$item_id] = array(
						"product_name" => "משלוח",
						'comment' => '',
						'quantity_ordered' => 1,
						"quantity" => 1,
						"price" => $price,
						"line_price"=> $price,
						"prod_id"=>0,
						"has_vat" => 1,
					);
					break;
				case 'coupon':
					$coupon_data = self::get_order_itemmeta($item_id, 'coupon_data');
					if (! $coupon_data) die("no coupon data");
					$coupon_data = unserialize($coupon_data);
					$table[$item_id] = array(
						"product_name" => ETranslate("Coupon") . " " . $coupon_data['code'],
						'comment' => '',
						'quantity_ordered' => null,
						"quantity" => null,
						"price" => null,
						"line_price"=> null,
						"prod_id"=>null,
						"has_vat" => null,
					);
					break;
				default:
					print "type $type not handled<br/>";
					die( 1 );
			}
			if (! $packing) unset($table[$item_id]['comment']);
		}
//		$args = array("edit" => true,
//			"edit_cols" => array("quantity" => 1,
//				"price" => 1),
		$args["size"] = array("quantity"=>2, "price"=>3);
		$args["events"] = 'onfocusout="calcDelivery()" onkeypress="moveNextRow(' . $this->line_number . ');calcDelivery()" ' .
				'onfocusout="leaveQuantityFocus(' . $this->line_number . ')"';

//		$args["class"] = "widefat";

//		foreach ($table as $key => $line)
//		{
//			$table[$key]['has_vat'] = Core_Html::GuiCheckbox('has_vat_$id', $table[$key]['has_vat']);
//		}
		$html .= Core_Html::gui_table_args($table, "del_table", $args);
		$html .= "<div>" . __("Total") . ": " .Core_html::gui_label("total", $price) . "</div>";

		// Delivery payment and total
//		$args = array("class"=>"widefat");
//		$html .= Core_Html::gui_table_args(array(
//			array(__("Delivery"), Core_Html::GuiInput("fee", $fee))
////			array(__("Total"), Core_html::gui_label("total", $fee))),
////		"summary",
//		$args);

		$html .= Core_Html::GuiLabel("order_id", $this->order_id, array("hidden"=>true));
		$html .= Core_Html::GuiButton("btn_add_line", "Add Line", "delivery_add_line('" . WPF_Flavor::getPost() . "', $user_id, $vat, 0)");
		$html .= Core_Html::GuiDiv(null, Core_Html::GuiButton("btn_do", "Create", "delivery_save_or_edit('" . WPF_Flavor::getPost() . "', 'delivery_save')")) .
		         '<datalist id="products"></datalist>';
		$html .= Core_Html::GuiDiv(null, Core_Html::GuiLabel(null, "Send to customer") . Core_Html::GuiCheckbox("chk_send_email", 1));

		return $html;
	}

	static private function do_create_delivery($edit = false)
	{
		FinanceLog(__FUNCTION__ . " e=$edit");
		$json_params = file_get_contents("php://input");
		$data = json_decode( $json_params );
		if (! $data) {
			print "no post data";
			return false;
		}
		$order_id = $data[0][0];
		if (! ($order_id > 0)) {
			$message = "Failed: bad order id $order_id";
			FinanceLog($message);
			print $message;
			return false;
		}
		$total = $data[0][1];
		$vat = $data[0][2];
		$fee = $data[0][3];

		$d = new Finance_Delivery($order_id);

		$lines = count($data) - 1;

		if ($edit) {
			if (! $d->DeleteLines()) return false;
		}
		$del_id = $d->CreateOrUpdateDelivery( $order_id, $total, $vat, $lines, $edit, $fee );

		FinanceLog("created id $del_id");

		for ($i = 1; $i < count($data); $i ++)
		{
			$prod_name = urldecode($data[$i][0]);
			$q = $data[$i][1]; 		if (! is_numeric($q)) $q = 0;
			$p = $data[$i][2]; if (! is_numeric($p)) $p = 10;
			$prod_id = $data[$i][4];
			$has_vat = $data[$i][6];
			if (! ($prod_id > 0)) {
				$prod = Finance_Product::getByName($prod_name);
				if ($prod)
					$prod_id = $prod->getId();
				else
					$prod_id = 0;
			}
			$prod_data = array(
			'product_name' => $prod_name,
			'quantity' => $q,
			'price' => $p,
			'vat' => $data[$i][3],
			'prod_id' => $prod_id,
			'quantity_ordered' => $data[$i][5],
			'line_price' => round($p * $q, 2),
			'has_vat' => $has_vat);

			// $product_name, $quantity, $quantity_ordered, $vat, $price, $line_price, $prod_id )
			$d->AddDeliveryLine( $prod_data );
		}

		if ( ! $d->getOrder()->setStatus( 'wc-awaiting-shipment' ) ) {
			FinanceLog( "can't update order status" );
			return 0;
		}

		return $d->delivery_id;
	}

	private function AddDeliveryLine( $prod_data )
	{
		$db_prefix = GetTablePrefix("delivery_lines");

		$product_name = str_replace("'", "", $prod_data['product_name']);
		$quantity = $prod_data['quantity'];
		if (! $quantity > 0) $quantity = 0;
		$quantity_ordered = $prod_data['quantity_ordered'];
		$vat = $prod_data['vat'];
		$price = $prod_data['price'];
		$line_price = $prod_data['line_price'];
		$prod_id = intval($prod_data['prod_id']);
		$has_vat = $prod_data['has_vat'];

		if (null === $has_vat) $has_vat = 'NULL';

		$product_name = preg_replace( '/[\'"%()]/', "", $product_name );
		$delivery_id = $this->delivery_id;

		$sql = "INSERT INTO ${db_prefix}delivery_lines (delivery_id, product_name, quantity, quantity_ordered, vat, price, line_price, prod_id, has_vat) VALUES ("
		       . $delivery_id . ", "
		       . "'" . urldecode( $product_name ) . "', "
		       . $quantity . ", "
		       . $quantity_ordered . ", "
		       . $vat . ", "
		       . $price . ', '
		       . round( $line_price, 2 ) . ', '
		       . $prod_id . ", $has_vat )";

		FinanceLog($sql);
		return SqlQuery( $sql );
	}

	static private function get_prodname($oid)
	{
		$sql                           = "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $oid;
		return SqlQuerySingleScalar( $sql );
	}

	public function CreateOrUpdateDelivery($order_id, $total, $vat, $lines, $edit, $fee, $delivery_id = null,
		$_draft = false	) {
		$draft = $_draft ? 1 : 0;
		FinanceLog(__FUNCTION__);

		if ( $edit ) {
			$sql = "UPDATE im_delivery SET vat = " . $vat . ", " .
			       " total = " . $total . ", " .
			       " dlines = " . $lines . ", " .
			       " draft = " . $draft . ", " .
			       " fee = " . $fee .
			       " WHERE order_id = " . $order_id;
			SqlQuery( $sql );
			$delivery_id = $this->delivery_id;
		} else {
			$sql = "INSERT INTO im_delivery (date, order_id, vat, total, dlines, fee) "
			       . "VALUES ( CURRENT_TIMESTAMP, "
			       . $order_id . ", "
			       . $vat . ', '
			       . $total . ', '
			       . $lines . ', '
			       . $fee . ')';
			SqlQuery( $sql );
			$delivery_id = SqlInsertId();
		}
		FinanceLog($sql);

		if ( ! ( $delivery_id > 0 ) ) {
			FinanceLog("Failed: no delivery id");
			die ( "Error no delivery id!" );
		}

		$user = new Finance_Client($this->getUserId());

		if ( $edit ) {
			FinanceLog("update transaction");
			$user->update_transaction( $total, $delivery_id);
			Finance::update_transaction( $delivery_id, $total, $fee );
		} else { // New!
			$date = date( "Y-m-d" );

			$user->add_transaction( $date, $total, $delivery_id, "משלוח" );
			Finance::add_transaction( $this->getUserId(), $date, $total, $fee, $delivery_id, 3 );
		}

		// Return the new delivery id!
		$this->delivery_id = $delivery_id;
		return $delivery_id;
	}

	private function getUserId()
	{
		$order = self::getOrder();
		if (! $order) throw new Exception("No order");
		return self::getOrder()->getCustomerId();
	}

	private function DeleteLines() {
		$db_prefix = GetTablePrefix("delivery_lines");
		$sql = "DELETE FROM ${db_prefix}delivery_lines WHERE delivery_id = " . $this->delivery_id;
		FinanceLog($sql);

		return SqlQuery( $sql );
	}

	private function DeleteLine($id) {
		$db_prefix = GetTablePrefix("delivery_lines");
		$sql = "DELETE FROM ${db_prefix}delivery_lines WHERE id = " . $id;
		FinanceLog($sql);

		return SqlQuery( $sql );
	}

	// Action to update delivery
	static public function save_wrap()
	{
		FinanceLog(__FUNCTION__);
		$send_email = GetParam("send_email");
		$del_id =  self::do_create_delivery(false);
		if ($del_id and $send_email) {
			Finance_Actions::delivery_send_mail($del_id);
		}
		return true;
	}

	static public function delete_wrap()
	{
		$delivery_id = GetParam("delivery_id", false);
		$order_id = GetParam("order_id", false);
		if (! $order_id and ! $delivery_id) {
			print "no id supplied";
			die (1);
		}
		FinanceLog(__FUNCTION__ . " $delivery_id");
		$d = new Finance_Delivery( $order_id, $delivery_id );
		if (! $d->delivery_id) {
			FinanceLog("no delivery for order $order_id");
			print "no delivery for order $order_id";
			return false;
		}
		$d->Delete();
		die(0);
	}

//	static public function delete_lines_wrap()
//	{
//		$delivery_id = GetParam("delivery_id", false);
//		$order_id = GetParam("order_id", false);
//		if (! $order_id and ! $delivery_id) {
//			print "failed: no id supplied";
//			die (1);
//		}
//		FinanceLog(__FUNCTION__ . " $delivery_id");
//		$d = new Finance_Delivery( $order_id, $delivery_id );
//		if (! $d->delivery_id) {
//			FinanceLog("no delivery for order $order_id");
//			print "no delivery for order $order_id";
//			return false;
//		}
//
//		$ids = GetParamArray("ids", true);
//		foreach ($ids as $item_id)
//			$d->DeleteLine($item_id);
//		die(0);
//	}

	public function Delete() {
		if (! $this->delivery_id) return;
		$db_prefix = GetTablePrefix("delivery_lines");

		// change the order back to processing

		$sql = "UPDATE wp_posts SET post_status = 'wc-processing' WHERE id = " . $this->order_id;

		SqlQuery( $sql );

		// Remove from client account
		$sql = "DELETE FROM ${db_prefix}client_accounts WHERE transaction_ref = " . $this->delivery_id;

		SqlQuery( $sql );

		// Remove the header
		$sql = "DELETE FROM ${db_prefix}delivery WHERE id = " . $this->delivery_id;

		SqlQuery( $sql );

		// Remove the lines
		$sql = "DELETE FROM ${db_prefix}delivery_lines WHERE delivery_id = " . $this->delivery_id;

		SqlQuery( $sql );

		//		return Finance::delete_transaction( $del_id );
	}

	static public function order_complete_wrap($order_id)
	{
	    $d = new Finance_Delivery( $order_id );
	    return $d->order_complete();
	}

	public function order_complete($force = false)
	{
		$order_id = $this->order_id;
		$O = new Finance_Delivery($order_id);

		$order = $O->getOrder();
		if ($order->getField("legacy")) return true;

		if (! $O->delivery_id) {
			$fee = $order->getShippingFee();
			// Check if there is delivery fee.
			if (! $fee) {
				FinanceLog("No delivery fee for order $order_id");
				WPF_Flavor::instance()->add_admin_notice("No delivery fee for order $order_id");
			}
			$del_id = $O->CreateDeliveryFromOrder($order_id, 1);
			WPF_Flavor::instance()->add_admin_notice("Delivery $del_id created");
		}
	}

	public function CreateDeliveryFromOrder( $order_id, $q ) {
//		ob_start();
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		remove_filter( 'woocommerce_stock_amount', 'filter_woocommerce_stock_amount', 10 );

		// $q = 1: take from order.
		// $q = 2: inventory
		$prods       = array();
		$order_items = $this->order->getItems();
		$total       = 0;
		$vat         = 0;
		$lines       = 0;
		foreach ( $order_items as $product ) {
			$lines ++;
			// $p = $product['price'];
			// push_array($prods, array($product['qty']));
			// $total += $p * $q;
			$P = new Finance_Product($product['product_id']);
			$prod_to_add                 = array();
			$prod_to_add['product_name'] = $product["name"];
			switch ( $q ) {
				case 1:
					$prod_to_add['quantity'] = $product["quantity"];
					break;
				case 2:
					$prod_to_add['quantity'] = inventory::GetQuantity( $product['product_id'] );
					break;
			}
			$prod_to_add['quantity_ordered'] = $prod_to_add['quantity'];
			$prod_to_add['vat']       = ($P->getVatPercent() ? apply_filters('vat_from_total', $product['total']) : 0);
			$vat += $prod_to_add['vat'];
			$quantity                 = $product["quantity"];

			if ( $q != 0 ) $prod_to_add['price'] = $quantity ? ( $product['total'] / $quantity ) : 0;
			$prod_to_add['line_price'] = $product['total'];
			$total              += $product['total'];
			$prod_to_add['prod_id']    = $product['product_id'];
			$prod_to_add['has_vat'] = null;

			array_push( $prods, $prod_to_add );
		}

		if ($fee = $this->order->getShippingFee()) {
			$total += $fee;
			$this_vat = apply_filters('vat_from_total', $fee);
			$vat += $this_vat;
			$lines ++;
			FinanceLog("fee vat: $vat $fee " . $this_vat);
		}

		$delivery_id = $this->CreateOrUpdateDelivery($order_id, $total, $vat, $lines, false, $fee);
		// print " מספר " . $delivery_id;

		foreach ( $prods as $prod_to_add ) {
			$this->AddDeliveryLine( $prod_to_add );
		}

		if ($fee) {
			// 'דמי משלוח', 1, 1, round( $fee / 1.17 * 0.17, 2 ), $fee, $fee, 0
			$prod_data = array(
				'product_name' => urldecode('דמי משלוח'),
				'quantity' => 1,
				'price' => $fee,
				'vat' => round( $fee / 1.17 * 0.17, 2 ),
				'prod_id' => 0,
				'quantity_ordered' => 1,
				'line_price' => $fee,
				'has_vat' => 1);

			$this->AddDeliveryLine( $prod_data);
		}

		return $delivery_id;
	}

	// Add link inside order action meta box.
	static function show_delivery_link()
	{
		global $theorder;

		switch ($theorder->get_status())
		{
			case 'on-hold':
				break;
			case 'processing':
				print Core_Html::GuiHyperlink(__("Create delivery note", "e-fresh"),
					AddParamToUrl("/wp-admin/admin.php?page=deliveries", array("operation"=> "delivery_show_create",
					                                                           "order_id" => $theorder->get_id())));
				break;
			default:
				print $theorder->get_status();
		}
	}

	static function get_price()
	{
		$prod_id = GetParam("prod_id", true);

		if ($prod_id) {
			$P = new Finance_Product( $prod_id );
			$price =  $P->getPrice();
			$output = apply_filters("delivery_product_price", $prod_id);
			if ($output != $prod_id)  print $output;
			else print $price;

			die (0);
		}
		return false;
		// print Fresh_Pricing::get_price_by_type()
	}

	static function getLink($id)
	{
		return "/wp-admin/admin.php?page=deliveries&delivery_id=" . $id;
	}

	function send_mail( $more_email = null, $edit = false ) {
		global $business_name;
		global $bank_info;
		global $support_email;

		$order_id = $this->order_id;

		if ( ! ( $order_id > 0 ) ) {
			die ( "can't get order id from delivery " . $this->delivery_id );
		}
		// print "oid= " . $order_id . "<br/>";
		$client_id = $this->getUserId();
		$C = new Fresh_Client($client_id);
		if ( ! ( $client_id > 0 ) ) {
			die ( "can't get client id from order " . $this->OrderId() );
		}

		$sql = "SELECT dlines FROM im_delivery WHERE id = " . $this->delivery_id;

		$dlines = SqlQuerySingleScalar( $sql );

		$del_user = $this->getOrder()->getOrderInfo( '_billing_first_name' );
		$message  = Core_Html::HeaderText();

		$message .= "<body>";
		$message .= "שלום " . $del_user . "!
<br><br>
המשלוח שלך ארוז ויוצא לדרך!";

		$message .= "<Br> להלן פרטי המשלוח";

		$message .= $this->Show(false);

		$message .= "<br> היתרה המעודכנת במערכת " . $C->balance();

		$message .= "<br /> לפרטים אודות מצב החשבון והמשלוח האחרון הכנס " .
		            Core_Html::GuiHyperlink( "מצב חשבון", get_site_url() . '/balance' ) .
		            "
 <br/>
 העברות בנקאיות מתעדכנות בחשבונכם אצלנו עד עשרה ימים לאחר התשלום.
<li>
למשלמים בהעברה בנקאית - פרטי החשבון: " . $bank_info . ". 
</li>
<li>המחאה לפקודת " . $business_name . ".
</li>
<li>
במידה ושילמתם כבר, המכתב נשלח לצורך פירוט עלות המשלוח בלבד ואין צורך לשלם שוב.
</li>

נשמח מאוד לשמוע מה דעתכם! <br/>
 לשאלות בנוגע למשלוח מוזמנים ליצור איתנו קשר במייל " . $support_email . "
</body>
</html>";

		$user_info = get_userdata( $client_id );
		FinanceLog( $user_info->user_email );
		$to = $user_info->user_email;
		// print "To: " . $to . "<br/>";
		if ( $more_email ) {
			$to = $to . ", " . $more_email;
		}
		// print "From: " . $support_email . "<br/>";
		// print "To: " . $to . "<br/>";
		// print "Message:<br/>";
		// print $message . "<br/>";
		$subject = "משלוח מספר " . $this->delivery_id . " בוצע";
		if ( $edit ) {
			$subject = "משלוח מספר " . $this->delivery_id . " - תיקון";
		}
		$from = (defined ("MAIL_SENDER") ? MAIL_SENDER : get_option('admin_email'));
		return send_mail($from, $to, $subject, $message );
		// print "mail sent to " . $to . "<br/>";
	}

}

class Finance_DocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3,    // Load and edit
		check = 4;  // Checkup
	// packing = 4;

}

class eDeliveryFields {
	const
		/// User interface
		line_select = 0,
		/// Product info
		product_name = 1,
		client_comment = 2,
		product_id = 3,
		term = 4,
		// Order info
		order_q = 5, // Only display
		order_q_units = 6,
		delivery_q = 7,
		price = 8,
		order_line = 9,
		// Delivery info
		has_vat = 10,
		line_vat = 11,
		delivery_line = 12,
		// Refund info
		refund_q = 13,
		refund_line = 14,
		buy_price = 15,
		line_margin = 16,
		packing_info = 17,
		line_type = 18,
		max_fields = 19;
}
