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

	public function getDeliveryTotal()
	{
		if (! $this->delivery_total and $this->delivery_id) {
			$this->delivery_total = SqlQuerySingleScalar("select sum(line_price) from im_delivery_lines where delivery_id = $this->delivery_id");
		}
		return $this->delivery_total;
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
		print "oid=$order_id del=$delivery_id<br/>";
		if ($delivery_id or $order_id)
		{
			$d = new Finance_Delivery($order_id, $delivery_id);
//			$report .= $d->OrderInfoBox();
			$report .= $d->Show();
			if ( $receipt = $d->getReceipt() ) {
				$report .= "תעודה שולמה ($receipt) ולא ניתנת לעריכה או למחיקה";
			} else {
				$report .= Core_Html::GuiHyperlink("[Delete]", AddToUrl("operation", "delivery_delete")) . " ";
					// Core_Html::GuiButton("btn_del", "delete document", array("action" => "deleteDelivery('".Fresh::getPost()."', $delivery_id)") );
				$report .= Core_Html::GuiHyperlink("[Edit]", AddToUrl("operation", "delivery_show_edit")) . " ";
					// Core_Html::GuiButton("btn_edit", "edit document", array("action" =>"editDelivery()"));
				$report .= Core_Html::GuiButton("btn_send", "send delivery", array("action" =>"sendDelivery('" .Fresh::getPost()."', $delivery_id)"));
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
		// "/fresh/delivery/get-delivery.php?id=%s");
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
		$order_id = GetParam( "order_id", true );
		$delivery        = new Finance_Delivery( $order_id );

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
		return SqlQuerySingleScalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $this->delivery_id );
	}

	// Show - if delivery note created.
	function Show($edit = false)
	{
		$html = self::OrderInfoBox();

		$events = 'onkeypress="quantity_changed()" onfocusout="quantity_changed()"';
		$args = array("fields"=>array("id", "product_name", "quantity_ordered", "quantity", "price", "line_price"),
			"where" => " delivery_id = " . $this->delivery_id,
			"skip_id"=>true,
			"header_fields" => array("product_name" => __("Product name", "e-fresh"),
				"quantity_ordered" => __("Quantity ordered", "e-fresh"),
				"quantity" => __("Quantity supplied", "e-fresh"),
						"price"=>__("Price", "e-fresh"),
				"line_price" => __("Line total"),
			),
		"events"=>$events,
		"edit"=>$edit,
			"form_table" => "del_table",
			"edit_cols" => array("quantity"=>1, "price"=>1));

		$html .= Core_Html::GuiTableContent("delivery_lines", null, $args);
		$html .= Core_Html::GuiDiv("total_div",
			__("Total: ") . " ".
			Core_html::gui_label("total", $this->getDeliveryTotal())
		);
		$html .= Core_Html::GuiLabel("order_id", $this->order_id, array("hidden"=>true));
		if ($edit) {
			$html .= Core_Html::GuiButton( "btn_save", "Save", "delivery_save_or_edit('" . Flavor::getPost() . "', 'delivery_edit')" );
			$html .= Core_Html::GuiButton( "btn_delete", "Delete", "delivery_delete('" . Flavor::getPost() . "')" );
		}
		return $html;
	}

	function ShowCreate()
	{
		$html = $this->getOrder()->infoBox( false, "יצירת תעודת משלוח ל" );

//		$table = array(array("product", "comment", "quantity_ordered", "qunatity_supplied", "price", "line_total"));
		$table = array("header"=>array("Product", "Comment", "Quantity Ordered", "Quantity Supplied", "Price", "Line Total"));

		$sql = 'select distinct woim.meta_value, order_line_get_variation(woi.order_item_id) '
		       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
		       . ' where ' . $this->OrderQuery()
		       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\' ' .
		' order by woi.order_item_id';

		$rows = SqlQueryArray( $sql );
		foreach ($rows as $row) {
			$prod_id = $row[0];
			$var_id  = $row[1];

			$items_sql      = 'select woim.order_item_id'
			                  . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
			                  . ' where ' . $this->OrderQuery()
			                  . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
			                  . ' and woim.meta_value = ' . $prod_id
			                  . ' and order_line_get_variation(woi.order_item_id) = ' . $var_id
			                  . ' order by 1';
			$order_item_ids = SqlQueryArrayScalar( $items_sql ); // For group order. (not tested in this version).
			$order_item_id = $order_item_ids[0];
			$line_total = self::get_order_itemmeta( $order_item_id, '_line_total' );
			$quantity = self::get_order_itemmeta( $order_item_id, '_qty' );
			$table[$order_item_id] = array(
				"product_name" => SqlQuerySingleScalar("SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $order_item_id),
				"comment" => self::get_order_itemmeta($order_item_id, 'product_comment'),
				"quantity_ordered" => $quantity,
				"quantity" => '',
				"price" => round(round($line_total / $quantity, 2)),
				"line_price" => 0);
		}
		$args = array("edit" => true,
			"edit_cols" => array("quantity" => 1,
				"price" => 1),
			"size"=>2,
			"events"=>'onfocusout="calcDelivery()" onkeypress="moveNextRow(' . $this->line_number . ');calcDelivery()" ' .
				'onfocusout="leaveQuantityFocus(' . $this->line_number . ')"');
		$html .= Core_Html::gui_table_args($table, "del_table", $args);
		$html .= Core_Html::GuiLabel("order_id", $this->order_id, array("hidden"=>true));
		$html .= Core_Html::GuiDiv("total_div",
			__("Total: ") . " ".
			Core_html::gui_label("total", 0)
		);
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
			MyLog("Bad order id $order_id");
			print $order_id;
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

		MyLog( "$delivery_id: $product_name $quantity $quantity_ordered $vat $price $line_price $prod_id", __FILE__);

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
		$order_id = GetParam("order_id", true, 0, true);
		MyLog(__FUNCTION__ . " $order_id");
		$d = new Finance_Delivery( $order_id );
//		$del_id = $d->delivery_id;
//		MyLog("del id: $del_id");
		return $d->Delete();

//		return Finance::delete_transaction( $del_id );
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
