<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/16
 * Time: 19:29
 */
// require_once( "../r-shop_manager.php" );

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

$debug = false;

class Fresh_Delivery extends Finance_Delivery {
	private $ID = 0;
	private $order_id = 0;
	private $AdditionalOrders = null;
	private $del_price = 0;

	/**
	 * Fresh_Delivery constructor.
	 *
	 * @param int $del_price
	 */
	public function __construct( int $id ) {
		parent::__construct(0, $id);
		$this->ID = $id;
	}


	static public function init_hooks($loader)
	{
//		static $count = 0;
//		if ($count >0) {
//			print debug_trace(10);
//			die (1);
//		}
		$loader->AddAction('update_by_customer_type', __CLASS__, 'Fresh_Delivery', 'update_by_customer_type');
		$loader->Addfilter("prepare_delivery_lines", __CLASS__, 'prepare_line');
		$loader->Addfilter("finance_show_vat", __CLASS__, 'finance_show_vat');
		$loader->Addfilter("finance_has_vat", __CLASS__, 'finance_has_vat', 10, 2);
		$loader->Addfilter("finance_vat", __CLASS__, 'finance_vat', 10, 2); // Show has vat in deliveries
		$loader->AddFilter('delivery_product_price', __CLASS__, 'delivery_product_price');
//		$count ++;
	}

	static public function update_by_customer_type()
	{
		$del_id = GetParam("id", true);

		$d = new Fresh_Delivery($del_id);
		$d->updateByCustomerType();
	}

	// Temporary until integration of delivery edit
	public function UpdateByCustomerType()
	{
		$vat_precent = Fresh_Pricing::getVatPercent();
		$db_prefix = GetTablePrefix("delivery_lines");

		$user = new Fresh_Client(self::getUserId());
		$customer_type = $user->customer_type();
		$sql = " select id, quantity, price, prod_id, vat from ${db_prefix}delivery_lines " .
		       " where delivery_id = " .$this->getID() .
		       " and prod_id > 0";
		$rows = SqlQuery($sql);
		$total = 0;
		$total_vat = 0;
		while ($row = SqlFetchAssoc($rows))
		{
			$row_id = $row['id'];
			$prod_id = $row['prod_id'];
			$vat  = $row['vat'];
			$new_price = Fresh_Pricing::get_price_by_type($prod_id, $customer_type);
			$quantity = $row['quantity'];
			$line_total = round($new_price * $quantity, 2);
			$total += $line_total;
			$new_vat = Fresh_Pricing::vatFromTotal($line_total);
			$total_vat += $new_vat;
//			print "$prod_id $price " .  . "<br/>";
			$sql = "update ${db_prefix}delivery_lines " .
			          "	set price = " .  $new_price .
			          ", line_price = " . $line_total;

			if ($vat > 0)
				$sql .= ", vat = " . $new_vat;

			$sql .= " where id = " . $row_id;

			SqlQuery($sql);
		}

		$sql = "update im_delivery set total = $total " .
		       ", vat = $total_vat " .
		       " where id = " . $this->getID();

		SqlQuery($sql);
	}

	public function CustomerView($edit)
	{
		$result = "";
		$order = $this->getOrder();

		$result .= $order->infoBox($edit);
//		$result .= ImTranslate("Order date") . ": " . $order->GetOrderDate() . "<br/>";
		$result .= __("Supply date") . ": " . $this->GetDate() . "<br/>";

		$args = [];
		$args["fields"] = array("id", "product_name", "quantity_ordered", "quantity", "price", "vat as has_vat", "vat", "line_price");
		$args["where"] = "delivery_id = " . $this->getID();
		$args["id_field"] = "id";
		$args["header_fields"] = array("product_name" => "Product name", "quantity_ordered" => "Quantity ordered",
		                               "quantity"=>"Quantity", "price" => "Price", "has_vat" =>"Has Vat", "line_price" => "Line total");
		$args["hide_col"] = array("id");

		$sql = "select " . CommaImplode($args["fields"]) . " from im_delivery_lines where delivery_id = " . $this->getID() . " order by id asc";
		$rows = Core_Data::TableData($sql, $args);

		$total_fields = array("quantity", "quantity_ordered", "line_price");

		// Create sum line
		$rows["sums"] = array();
		foreach($args["fields"] as $key) $rows["sums"][$key] = "";

		foreach ($total_fields as $field) $rows["sums"][$field] = 0;

		foreach ($rows as $row_id => $not_used)
			foreach ($total_fields as $key => $field){
				if (! in_array($row_id, array("header", "sums")))
					$rows["sums"][$field] += $rows[$row_id][$field];
			}

		if ($edit) foreach ($rows as $row_id => $row) {
			if ( $row_id > 0 ) {
				$rows[ $row_id ]["quantity"] = Core_Html::GuiInput( "qua_" . $row_id, $rows[ $row_id ]["quantity"] );
				$rows[$row_id]["has_vat"] = Core_Html::GuiCheckbox("hvt_". $row_id, $rows[$row_id]["vat"],
					array("events"=>"onchange=\"change_vat('" . Fresh::getPost() . "', $row_id )\"" ));
			}
		}

		$result .= Core_Html::gui_table_args($rows);

		return $result;
	}

	public function paid()
	{
		return SqlQuerySingleScalar( "select payment_receipt from im_delivery where id = " . $this->getID());
	}

	private function load_line_from_order($line_ids, &$prod_id, &$prod_name, &$quantity_ordered, &$unit_q, &$P, &$price, &$prod_comment )
	{
		$line_id = $line_ids[0];
		$sql                                 = "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $line_id;
		$prod_name                           = SqlQuerySingleScalar( $sql );
		$quantity_ordered                    = Finance_Delivery::get_order_itemmeta( $line_ids, '_qty' );
		$unit_ordered                        = Finance_Delivery::get_order_itemmeta( $line_id, 'unit' );
		$prod_comment = Finance_Delivery::get_order_itemmeta($line_id, 'product_comment');

		$order_line_total                    = round( Finance_Delivery::get_order_itemmeta( $line_ids, '_line_total' ), 1);
		$this->order_total                   += $order_line_total;
		$line[ eDeliveryFields::order_line ] = $order_line_total;
		$prod_id                             = Finance_Delivery::get_order_itemmeta( $line_id, '_product_id' );
		$P                                   = new Fresh_Product( $prod_id );
		$line_price       = Finance_Delivery::get_order_itemmeta( $line_ids, '_line_total' );

		$price = round($line_price / $quantity_ordered, 2);
//		$price = Fresh_Pricing::get_price_by_type( $prod_id, $client_type = "", $quantity = 1

		if ( $unit_ordered ) {
			$quantity_ordered = "";
			$unit_array       = explode( ",", $unit_ordered );
			$unit_q           = $unit_array[1];
		}
		return $price;
	}

	function send_mail( $more_email = null, $edit = false ) {
		global $business_name;
		global $bank_info;
		global $support_email;

		$order_id = $this->OrderId();

		if ( ! ( $order_id > 0 ) ) {
			die ( "can't get order id from delivery " . $this->ID );
		}
		// print "oid= " . $order_id . "<br/>";
		$client_id = $this->getCustomerId();
		$C = new Fresh_Client($client_id);
		if ( ! ( $client_id > 0 ) ) {
			die ( "can't get client id from order " . $this->OrderId() );
		}

		MyLog( __FILE__, "client_id = " . $client_id );

		$sql = "SELECT dlines FROM im_delivery WHERE id = " . $this->ID;

		$dlines = SqlQuerySingleScalar( $sql );

		MyLog( __FILE__, "dlines = " . $dlines );

		$del_user = $this->getOrder()->getOrderInfo( '_billing_first_name' );
		$message  = Core_Html::HeaderText();

		$message .= "<body>";
		$message .= "שלום " . $del_user . "!
<br><br>
המשלוח שלך ארוז ויוצא לדרך!";

		$message .= "<Br> להלן פרטי המשלוח";

		$message .= $this->delivery_text( Finance_DocumentType::delivery, Finance_DocumentOperation::show );

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
		MyLog( $user_info->user_email );
		$to = $user_info->user_email;
		// print "To: " . $to . "<br/>";
		if ( $more_email ) {
			$to = $to . ", " . $more_email;
		}
		// print "From: " . $support_email . "<br/>";
		// print "To: " . $to . "<br/>";
		// print "Message:<br/>";
		// print $message . "<br/>";
		$subject = "משלוח מספר " . $this->ID . " בוצע";
		if ( $edit ) {
			$subject = "משלוח מספר " . $this->ID . " - תיקון";
		}
		$from = (defined ("MAIL_SENDER") ? MAIL_SENDER : get_option('admin_email'));
		return send_mail($from, $to, $subject, $message );
		// print "mail sent to " . $to . "<br/>";
	}

	public function OrderId() {
		if ( ! ( $this->order_id > 0 ) ) {
			$sql = "SELECT order_id FROM im_delivery WHERE id = " . $this->ID;

			$this->order_id = SqlQuerySingleScalar( $sql );
		}

		return $this->order_id;
	}

	public function getCustomerId() {
		return $this->getOrder()->getCustomerID();
	}

	public function getCustomerType() {
		return $this->getOrder()->getCustomerType();
	}

//	public function getOrder() {
//		if ( ! $this->order ) {
//			$this->order = new Fresh_Order( $this->OrderId() );
//		}
//
//		return $this->order;
//	}

	function expand_basket( $basket_id, $quantity_ordered, $level, $show_fields, $document_type, $line_id, $client_type, $edit, &$data ) {
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result2 = SqlQuery( $sql2 );
		while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
			$prod_id  = $row2["product_id"];
			// print $prod_id . "<br/>";
			$P        = new Fresh_Product( $prod_id );
			$quantity = $row2["quantity"];
			$basket_or_prod = new Fresh_Basket($prod_id);
			if ( $basket_or_prod->is_basket( $prod_id ) ) {
				$this->expand_basket( $prod_id, $quantity_ordered * $quantity, $level + 1, $show_fields, $document_type, $line_id, $client_type, $edit, $data );
			} else {
				$line = array();
				for ( $i = 0; $i <= eDeliveryFields::max_fields; $i ++ ) {
					$line[ $i ] = "";
				}

				$line[ eDeliveryFields::product_name ] = "===> " . $P->getName();
				$line[ eDeliveryFields::price ]        = $P->getPrice($client_type );

				$has_vat = ($P->getVatPercent() > 0);
				$line[ eDeliveryFields::product_id ] = $prod_id;
				$line[ eDeliveryFields::has_vat ]    = Core_Html::GuiCheckbox( "hvt_" . $prod_id, $has_vat);
				$line[ eDeliveryFields::order_q ]    = $quantity_ordered;
				$line[ eDeliveryFields::delivery_q ] = Core_Html::gui_input( "quantity" . $this->line_number, "",
					array( 'onkeypress="moveNextRow(' . $this->line_number . ')"', 'onfocusout="leaveQuantityFocus(' . $this->line_number . ')" ' ) );

				$this->line_number = $this->line_number + 1;
				$data              .= Core_Html::gui_row( $line, $this->line_number, $show_fields, $sums, $this->delivery_fields_names );
			}
		}
		if ( $level == 0 ) {
			$line = array();
			for ( $i = 0; $i <= eDeliveryFields::max_fields; $i ++ ) {
				$line[ $i ] = "";
			}
			$line[0]                               = "dis"; // Discount line
			$line[ eDeliveryFields::product_name ] =Core_Html::gui_label( "ba", "הנחת סל" );
			$line [eDeliveryFields::product_id]    = -1;
			$line[eDeliveryFields::line_type]      = "dis";
			$line[eDeliveryFields::price]          = 0;
			$sums                                  = null;
			$this->line_number                     = $this->line_number + 1;
			$dis_line                              = Core_Html::gui_row( $line, $this->line_number, $show_fields, $sums, $this->delivery_fields_names );
			// print "<table><tr>" . $dis_line . "</tr></table>";
			$data .= $dis_line;
		}
	}

	public static function GuiCreateNewNoOrder() {
		$data = Core_Html::gui_table_args( array(
			array( "לקוח:", gui_select_client("client", null, null) ),
			array( "תאריך", gui_input_date( "delivery_date", "" ) ),
			array( Core_Html::GuiButton( "btn_add_delivery", "", "הוסף תעודת משלוח" ) )
		) );

		return $data;
	}

	public static function CreateFromOrders( $order_ids ) {
		$order_id = array_shift( $order_ids );
		$instance = Fresh_Delivery::CreateFromOrder( $order_id );

		$instance->AdditionalOrders = $order_ids;

		return $instance;
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	public function isDraft() {
		if ( $this->ID ) {
			return SqlQuerySingleScalar( "select draft from im_delivery where ID = " . $this->ID );
		} else {
			die ( __METHOD__ . " no ID" . DB_NAME );
		}
	}

	public function draftReason() {
		if ( $this->ID ) {
			return SqlQuerySingleScalar( "select draft_reason from im_delivery where ID = " . $this->ID );
		} else {
			die ( __METHOD__ . " no ID" );
		}
	}

	public function DeliveryDate() {
		$sql = "SELECT date FROM im_delivery WHERE id = " . $this->ID;

		$row = SqlQuerySingleScalar($sql);

		return $row["date"];
	}

	public function Price() {
		// $sql = 'SELECT round(transaction_amount, 2) FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;
		$sql = 'SELECT round(total, 2) FROM im_delivery WHERE id = ' . $this->ID;
		// my_log($sql);

		return SqlQuerySingleScalar( $sql );
	}

	public function getDate() {
		return SqlQuerySingleScalar( "select date from im_delivery where id = " . $this->ID);
	}


//	public function delivery_line_group($show_fields, $document_type, $line_ids, $client_type, $operation, $margin = false, $style = null)
//	{
//
//	}

	// Used for:
	// Creating new delivery.
	// - Prices are taken from order for regular clients, discount for siton and buy prices for owner
	// display delivery
	// - Prices are taken from the database - delivery

	// public function delivery_line($show_fields, $prod_id, $quantity_ordered, $unit_ordered, $quantity_delivered, $price, $has_vat, $document_type, $edit)
	// Delivery or Order line.
	// If Document is delivery, line_id is delivery line id.
	// If Document is order, line_id is order line id.

	public function getPrintDeliveryOption() {
		$user_id = $this->getUserId();

		$option = get_user_meta( $user_id, "print_delivery_note" );

		// Mail
		// Print
		if ( $option == null ) {
			// Setting the default - Send mail, and Print
			// 8/9/2019 - Changed the default to mail.
			$option = 'M';
		}

		return $option;
	}

	/**
	 * @return int
	 */
	public function getUserId() {
		if ( ! $this->user_id ) {
			$sql = "SELECT client_id_from_delivery(id) FROM im_delivery WHERE id = " . $this->getID() ;
//			print $sql;
			$this->user_id = SqlQuerySingleScalar( $sql);
		}

		return $this->user_id;
	}

	public function DeliveryFee() {
		$sql = 'SELECT fee FROM im_delivery WHERE id = ' . $this->ID;

		// print $sql;
		// my_log($sql);

		return SqlQuerySingleScalar( $sql);
	}

	static function fresh_deliveries()
	{
		$operation = GetParam("operation", false, "show_this_week");

		print self::handle_delivery_operation($operation);
	}

	function send_deliveries($ids)
	{
		global $support_email;
		if (!is_array($ids)) $ids = array($ids);
		foreach ($ids as $delivery_id){
			$delivery = new Fresh_Delivery( $delivery_id );
			$delivery->send_mail( $support_email, false );
		}
	}

	static public function CustomerLast($user_id)
	{
		return SqlQuerySingleScalar( "select max(id) from im_delivery where client_id_from_delivery(id) = " . $user_id);
	}

	/**
	 * @return int
	 */
	public function getOrderId(): int {
		return $this->order_id;
	}

	/**
	 * @return null
	 */
	public function getAdditionalOrders() {
		return $this->AdditionalOrders;
	}

	/**
	 * @return int
	 */
	public function getOrderTotal(): int {
		return $this->order_total;
	}

	/**
	 * @return int
	 */
	public function getOrderVatTotal(): int {
		return $this->order_vat_total;
	}

	/**
	 * @return int
	 */
	public function getOrderDueVat(): int {
		return $this->order_due_vat;
	}

	/**
	 * @return int
	 */
	public function getLineNumber(): int {
		return $this->line_number;
	}

	/**
	 * @return int
	 */
	public function getDelPrice(): float {
		return $this->del_price;
	}

	/**
	 * @return int
	 */
	public function getDeliveryTotal(): float {
//		print "total=" .$this->delivery_total . "<br/>";
		return $this->delivery_total;
	}

	/**
	 * @return int
	 */
	public function getDeliveryDueVat(): float {
		$db_prefix = GetTablePrefix();
		$sql = "select sum(line_price) " .
		       " from ${db_prefix}delivery_lines " .
		       " where delivery_id = " . $this->getID() . " and vat > 0 " .
		       " and product_name != 'משלוח'";
		$rc = SqlQuerySingleScalar($sql);
		if (! $rc) return 0;
		return round($rc, 2);
	}

	/**
	 * @return int
	 */
	public function getDeliveryTotalVat(): float {
		return $this->delivery_total_vat;
	}

	/**
	 * @return int
	 */
	public function getMarginTotal(): int {
		return $this->margin_total;
	}

	/**
	 * @return string[]
	 */
	public function getDeliveryFieldsNames(): array {
		return $this->delivery_fields_names;
	}

	public function add_delivery_lines( $delivery_id, $lines, $edit ) {
		$debug = true;
		if ( $edit ) {
			$d = new Fresh_Delivery( $delivery_id );
			if (! $d) return false;
			$d->DeleteLines();
		}

		for ( $pos = 0; $pos < count( $lines ); $pos += 8 ) {
			$prod_id = $lines[ $pos ];

			$p = new Fresh_Product($prod_id);
			if ($prod_id == -1)
				$product_name = "הנחת סל";
			else
				if ( is_numeric( $prod_id ) ) {
					$product_name = $p->getName();
				} else {
					if ( strstr( $prod_id, ")" ) ) {
						$prod_id      = substr( $prod_id, 0, strstr( $prod_id, ")" ) );
						$product_name = substr( $prod_id, strstr( $prod_id, ")" ) );
					} else {
						$product_name = $prod_id;
						$prod_id      = 0;
					}
				}
			$quantity         = $lines[ $pos + 1 ];
			$quantity_ordered = $lines[ $pos + 2 ];
			$unit_ordered     = $lines[ $pos + 3 ];
			if ( ! ( strlen( $unit_ordered ) > 0 ) ) {
				$unit_ordered = "NULL";
			} // print $unit_ordered . "<br/>";
			$vat        = $lines[ $pos + 4 ];
			$price      = $lines[ $pos + 5 ];
			$line_price = $lines[ $pos + 6 ];
			$part_of_basket = $lines[$pos + 7];
			if ($debug)
				MyLog("id: " . $prod_id . ", name: " . $product_name . " delivery_id: " . $delivery_id . " quantity: " . $quantity . " quantity_ordred: " . $quantity_ordered .
			      "units: " . $unit_ordered . " vat: " . $vat . " price: " . $price . " line_price: " . $line_price );

			$rc =  self::AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id, $part_of_basket );
			if (! $rc) return false;
		}
		return true;
	}

	static function link($id)
	{
		return "/wp-admin/admin.php?page=deliveries&delivery_id=$id";
	}

	static function prepare_line($row)
	{
		$prod_id = $row['prod_id'];
		if (null == $row['has_vat']) {
			if ( $prod_id ) {
				$p              = new Fresh_Product( $prod_id );
				$row['has_vat'] = ( $p->isFresh() ? false : true );
			} else {
				$row['has_vat'] = true;
			}
		}
		return $row;
	}

	static function finance_has_vat($has_vat, $prod_id)
	{
		$p = new Fresh_Product($prod_id);
		return $p->getVatPercent() > 0;
	}

	static function finance_show_vat()
	{
		return 1;
	}

	static function delivery_product_price($prod_id)
	{
		$user_id = GetParam("user_id", true);
		$u = new Fresh_Client($user_id);
		$p = new Fresh_Product($prod_id);
		$vat = ! $p->isFresh();
		return Fresh_Pricing::get_price_by_type($prod_id, $u->customer_type()) . ",$vat";
	}

	static function finance_vat()
	{
		return 1;
	}
}
