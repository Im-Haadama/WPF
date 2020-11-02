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
	private $delivery_due_vat = 0;
	private $delivery_total_vat = 0;
	private $margin_total = 0;
	private $order_id = 0;

	static function init()
	{
	}

	static function init_hooks()
	{
		// Gui
		AddAction( "delivery_show_create", array( 'Finance_Delivery', "delivery_show_create" ) ); // gui: order->delivery
		AddAction( "delivery_show_edit", array( 'Finance_Delivery', "delivery_show_edit" ) ); // gui: order->delivery

		// Actions
		AddAction("delivery_save", array('Finance_Delivery', 'save_wrap')); // POST: create delivery
		AddAction("delivery_edit", array('Finance_Delivery', 'edit_wrap')); // POST: update delivery
		AddAction("delivery_delete", array('Finance_Delivery', "delete_wrap"));
		AddAction("delivery_get_price", array('Finance_Delivery', 'get_price'));

		// Complete order
		add_action('woocommerce_order_status_completed', array(__CLASS__, 'order_complete_wrap'));

		// Show link to delivery note
		AddAction('woocommerce_order_actions_start', array(__CLASS__, 'show_delivery_link'));
//		MyLog(__FUNCTION__);
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
		$this->order       = new Finance_Order($this->order_id);
	}

	public function getDeliveryTotal() : float
	{
		if (! $this->delivery_total and $this->delivery_id) {
			$this->delivery_total = SqlQuerySingleScalar("select sum(line_price) from im_delivery_lines where delivery_id = $this->delivery_id");
		}
		return round($this->delivery_total, 2);
	}

	public function getOrder() : Finance_Order {
		if (! $this->order) {
			$this->order = new Finance_Order($this->order_id);
		}
		return $this->order;
	}

	static function deliveries() {
		$report = "";
		$operation = GetParam( "operation", false, null, true );
//		print "del=" . GetParam("delivery_id") . "<br/>";
		if ($operation) {
			MyLog(__FUNCTION__ . ":" . $operation);
			$report = apply_filters($operation,  null);
//			var_dump($report);
			if (is_string($report)) {
				print $report;
				return true;
			}
//			print "$operation not handled<br/>";
		}

		$delivery_id = GetParam("delivery_id", false, 0);
		$order_id = GetParam("order_id", false, 0);
//		print "oid=$order_id del=$delivery_id<br/>";
		if ($delivery_id or $order_id)
		{
			$d = new Finance_Delivery($order_id, $delivery_id);
//			$report .= $d->OrderInfoBox();
			$report .= $d->Show();
			if ($d->delivery_id) {
				if ( $receipt = $d->getReceipt() ) {
					$report .= "תעודה שולמה ($receipt) ולא ניתנת לעריכה או למחיקה";
				} else {
					$report .= Core_Html::GuiHyperlink( "[Delete]", AddToUrl( "operation", "delivery_delete" ) ) . " ";
					$report .= Core_Html::GuiHyperlink( "[Edit]", AddToUrl( "operation", "delivery_show_edit" ) ) . " ";
					$report .= Core_Html::GuiButton( "btn_send", "send delivery", array( "action" => "sendDelivery('" . Flavor::getPost() . "', $delivery_id)" ) );
				}
			} else {
				$report .= __("No delivery note for order"). " " .$order_id;
			}
		} else {
			$date_format = 'Y-m-j';
			$date        = GetParam( "week", false, date( $date_format, strtotime( "last sunday" ) ) );
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

	static function week_deliveries($date)
	{
		$report = "";
		$args   = [];
		// Links to prev/next week

		// Show selected week
		$args["sql"]       = "select ID, date, order_id, client_from_delivery(ID) as client from im_delivery where first_day_of_week(date) = " . QuoteText( $date );
		$args["id_field"]  = "ID";
		$args["post_file"] = Flavor::getPost();

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

		return $delivery->ShowCreate();
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
		return self::do_create_delivery(true);
	}

	private static function CreateFromOrder( $order_id )
	{
		$id = Finance_Order::get_delivery_id( $order_id );

		$instance = new self( $id );

		$instance->SetOrderId( $order_id );

		return $instance;
	}

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
		return $this->getOrder()->infoBox();
	}

	private function getReceipt()
	{
		return $this->delivery_id and SqlQuerySingleScalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $this->delivery_id );
	}

	// Show - if delivery note created.
	function Show($edit = false)
	{
		$html = self::OrderInfoBox();
		$user_id = $this->getUserId();

		$events = 'onkeypress="quantity_changed()" onfocusout="quantity_changed()"';
		$args = array("fields"=>array("id", "product_name", "quantity_ordered", "quantity", "price", "line_price"),
			"where" => " delivery_id = " . $this->delivery_id,
			"skip_id"=>true,
			"header_fields" => array("product_name" => __("Product name", "e-fresh"),
				"quantity_ordered" => __("Quantity ordered"),
				"quantity" => __("Quantity supplied", "e-fresh"),
						"price"=>__("Price", "e-fresh"),
				"line_price" => __("Line total"),
			),
		"events"=>$events,
		"order_by"=>" order by id ",
		"edit"=>$edit,
			"form_table" => "del_table",
			"edit_cols" => array("quantity"=>1, "price"=>1));

		if ($this->delivery_id) {
			$html .= Core_Html::GuiTableContent( "delivery_lines", null, $args );
			$html .= Core_Html::GuiDiv( "total_div",
				__( "Total" ) . ": " .
				Core_html::gui_label( "total", $this->getDeliveryTotal() )
			);
			$html .= Core_Html::GuiLabel( "order_id", $this->order_id, array( "hidden" => true ) );
			if ( $edit ) {
				$html .= Core_Html::GuiButton("btn_add_line", "Add Line", "delivery_add_line('". Flavor::getPost() . "', $user_id)");
				$html .= Core_Html::GuiButton( "btn_save", "Save", "delivery_save_or_edit('" . Flavor::getPost() . "', 'delivery_edit')" );
				$html .= Core_Html::GuiButton( "btn_delete", "Delete", "delivery_delete('" . Flavor::getPost() . "')" );
			}
		}
//		$html .= "<table> id='row_insert' style='display: none'>
//			<tr>" .
//		"</tr></table>";
		return $html;
	}

	function ShowCreate()
	{
		$html = $this->getOrder()->infoBox( false, "יצירת תעודת משלוח ל" );

//		$table = array(array("product", "comment", "quantity_ordered", "qunatity_supplied", "price", "line_total"));
		$table = array("header"=>array("Product", "Comment", "Quantity ordered", "Quantity supplied", "Price", "Line Total"));

		$items = SqlQueryArray("select order_item_id, order_item_type from wp_woocommerce_order_items where order_id = " . $this->order_id);
//		var_dump($items);
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
					$table[ $item_id ] = array(
						"product_name"     => SqlQuerySingleScalar( "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $item_id ),
						"comment"          => self::get_order_itemmeta( $item_id, 'product_comment' ),
						"quantity_ordered" => $quantity,
						"quantity"         => '',
						"price"            => round( round( $line_total / $quantity, 2 ) ),
						"line_price"       => 0
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
						"line_price"=> $price
					);
					break;
				default:
					print "type $type not handled<br/>";
					die( 1 );
			}
		}
		$args = array("edit" => true,
			"edit_cols" => array("quantity" => 1,
				"price" => 1),
			"size"=>2,
			"events"=>'onfocusout="calcDelivery()" onkeypress="moveNextRow(' . $this->line_number . ');calcDelivery()" ' .
				'onfocusout="leaveQuantityFocus(' . $this->line_number . ')"',
			          "class"=>"widefat");
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
		$html  .= Core_Html::GuiButton("btn_add", "Create", "delivery_save_or_edit('" . Flavor::getPost() . "', 'delivery_save')");

		return $html;
	}

	static private function do_create_delivery($edit = false)
	{
		MyLog(__FUNCTION__ . " e=$edit");
		$json_params = file_get_contents("php://input");
		$data = json_decode($json_params);

		if (! $data) {
			MyLog("no post data");
			print "no post data";
			return false;
		}
		$order_id = $data[0][0];
		if (! ($order_id > 0)) {
			$message = "Bad order id $order_id";
			MyLog($message);
			print $message;
			return false;
		}
		MyLog($order_id);
		$total = $data[0][1];
		$vat = $data[0][2];

		$d = new Finance_Delivery($order_id);
		$lines = count($data) - 1;
		$fee = 0;

		if ($edit) {
			MyLog("Edit");
			$d->DeleteLines();
		}
		$d->CreateOrUpdateDelivery( $order_id, $total, $vat, $lines, $edit, $fee );

		for ($i = 1; $i < count($data); $i ++)
		{
			$product_name=urldecode($data[$i][0]);
			$q = $data[$i][1]; if (! ($q > 0)) $q = 0;
			$p = $data[$i][2]; if (! ($p > 0)) $p = 0;
			$vat = $data[$i][3];
			$prod_id = $data[$i][4];
			$quantity_ordered = $data[$i][5]; MyLog("qo=$quantity_ordered"); //  if (! ($quantity_ordered > 0)) $quantity_ordered = 0;
			$line_price = round($p * $q, 2);

			// $product_name, $quantity, $quantity_ordered, $vat, $price, $line_price, $prod_id )
			$d->AddDeliveryLine( $product_name, $q, $quantity_ordered, $vat, $p, $line_price, $prod_id );
		}

		return true;
	}

	private function AddDeliveryLine( $product_name, $quantity, $quantity_ordered, $vat, $price, $line_price, $prod_id )
	{
		$product_name = preg_replace( '/[\'"%()]/', "", $product_name );
		$delivery_id = $this->delivery_id;

		$sql = "INSERT INTO im_delivery_lines (delivery_id, product_name, quantity, quantity_ordered, vat, price, line_price, prod_id) VALUES ("
		       . $delivery_id . ", "
		       . "'" . urldecode( $product_name ) . "', "
		       . $quantity . ", "
		       . $quantity_ordered . ", "
		       . $vat . ", "
		       . $price . ', '
		       . round( $line_price, 2 ) . ', '
		       . $prod_id . ' )';

//		MyLog( "$delivery_id: $product_name $quantity $quantity_ordered $vat $price $line_price $prod_id", __FILE__);

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

		if ( ! ( $delivery_id > 0 ) ) {
			die ( "Error no delivery id!" );
		}

		$user = new Finance_Client($this->getUserId());

		if ( $edit ) {
			$user->update_transaction( $total, $delivery_id);
			Finance::update_transaction( $delivery_id, $total, $fee );
		} else { // New!
			$date = date( "Y-m-d" );

			$user->add_transaction( $date, $total, $delivery_id, "משלוח" );
			Finance::add_transaction( $this->getUserId(), $date, $total, $fee, $delivery_id, 3 );
		}
		// $order = new WC_Order( $order_id );
		if ( ! self::getOrder()->setStatus( 'wc-awaiting-shipment' ) ) {
			printbr( "can't update order status" );
		}

		// Return the new delivery id!
		$this->delivery_id = $delivery_id;
		return $delivery_id;
	}

	private function getUserId()
	{
		return self::getOrder()->getCustomerId();
	}

	private function DeleteLines() {
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->delivery_id;
		MyLog($sql);

		SqlQuery( $sql );
	}

	// Action to update delivery
	static public function save_wrap()
	{
		MyLog(__FUNCTION__);
		return self::do_create_delivery(false);
	}

	static public function delete_wrap()
	{
		$delivery_id = GetParam("delivery_id", false);
		$order_id = GetParam("order_id", false);
		if (! $order_id and ! $delivery_id) {
			print "no id supplied";
			die (1);
		}
		MyLog(__FUNCTION__ . " $delivery_id");
		$d = new Finance_Delivery( $order_id, $delivery_id );
		if (! $d->delivery_id) {
			MyLog("no delivery for order $order_id");
			print "no delivery for order $order_id";
			return false;
		}
		$d->Delete();
		print "done";
		die(0);
	}

	public function Delete() {
		// change the order back to processing

		$sql = "UPDATE wp_posts SET post_status = 'wc-processing' WHERE id = " . $this->order_id;

		SqlQuery( $sql );

		// Remove from client account
		$sql = 'DELETE FROM im_client_accounts WHERE transaction_ref = ' . $this->delivery_id;

		SqlQuery( $sql );

		// Remove the header
		$sql = 'DELETE FROM im_delivery WHERE id = ' . $this->delivery_id;

		SqlQuery( $sql );

		// Remove the lines
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->delivery_id;

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

		if (! $O->delivery_id) {
			$fee = $O->getOrder()->getShippingFee();
			// Check if there is delivery fee.
			if (! $fee) {
				MyLog("No delivery fee for order $order_id");
				Flavor::instance()->add_admin_notice("No delivery fee for order $order_id");
			}
			if ($fee and ($fee == $O->getDeliveryTotal())) { // Todo: No delivery at this stage.
				// order that is just delivery. Handled separately.
				return true;
			}
			$del_id = $O->CreateDeliveryFromOrder($order_id, 1);
			Flavor::instance()->add_admin_notice("Delivery $del_id created");
		}
	}

	public function CreateDeliveryFromOrder( $order_id, $q ) {
//		ob_start();
		MyLog(__FUNCTION__ . " $order_id");
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
			// var_dump($product);
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
			$prod_to_add['vat']       = ($P->getVatPercent() ? Israel_Shop::vatFromTotal($product['total']) :0);
			$vat += $prod_to_add['vat'];
			$quantity                 = $product["quantity"];

			if ( $q != 0 ) $prod_to_add['price'] = $quantity ? ( $product['total'] / $quantity ) : 0;
			$prod_to_add['line_price'] = $product['total'];
			$total              += $product['total'];
			$prod_to_add['prod_id']    = $product['product_id'];

			array_push( $prods, $prod_to_add );
		}

		if ($fee = $this->order->getShippingFee()) {
			$total += $fee;
			$vat += Israel_Shop::vatFromTotal($fee);
			$lines ++;
			MyLog("fee vat: $vat $fee " . Israel_Shop::vatFromTotal($fee));
		}

		$delivery_id = $this->CreateOrUpdateDelivery($order_id, $total, $vat, $lines, false, $fee);
		// print " מספר " . $delivery_id;

		foreach ( $prods as $prod_to_add ) {
			$this->AddDeliveryLine( $prod_to_add['product_name'], $prod_to_add['quantity'], $prod_to_add['quantity_ordered'],
				$prod_to_add['vat'], $prod_to_add['price'], $prod_to_add['line_price'], $prod_to_add['prod_id'] );
		}

		if ($fee)
			$this->AddDeliveryLine('דמי משלוח', 1, 1,  round($fee / 1.17 * 0.17, 2), $fee, $fee, 0 );

		return $delivery_id;
	}

	// Add link inside order action meta box.
	static function show_delivery_link()
	{
		global $theorder;

		switch ($theorder->status)
		{
			case 'on-hold':
				break;
			case 'processing':
				print Core_Html::GuiHyperlink(__("Create delivery note", "e-fresh"),
					AddParamToUrl("/wp-admin/admin.php?page=deliveries", array("operation"=> "delivery_show_create",
					                                                           "order_id" => $theorder->get_id())));
				break;
			default:
				print $theorder->status;
		}
	}

	static function get_price()
	{
		$user_id = GetParam("user_id", true);
		$prod_name = GetParam("prod_name", true);

		$sql  = "SELECT id FROM im_products WHERE post_title = '" . urldecode( $prod_name ) . "'";
		$id   = SqlQuerySingleScalar( $sql );

		$p = new Finance_Product($id);
		print $p->getPrice();
		return true;
		// print Fresh_Pricing::get_price_by_type()
	}

	static function getLink($id)
	{
		return "/wp-admin/admin.php?page=deliveries&delivery_id=" . $id;
	}
}

class Finance_DocumentType {
	const order = 1, // Client
		delivery = 2, // Client
		refund = 3, // Client
		invoice = 4, // Supplier
		supply = 5, // Supplier
		ship = 6,  // Legacy
		bank = 7,
		invoice_refund = 8, // Supplier
		invoice_receipt = 9, // Supplier
		count = 10;
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
