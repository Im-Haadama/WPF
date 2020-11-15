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

class Fresh_Delivery {
	private $ID = 0;
	private $order_id = 0;
	private $AdditionalOrders = null;
	private $order = null;
	private $del_price = 0;

	public function __construct( $id ) {
		$this->ID = $id;
		$this->delivery_fields_names = array(
			"chk", // 0
			// product
			"nam", // 1
			"com", // 2
			"pid", // 3
			"ter", // 4
			"orq", // 5
			"oru", // 6
			"deq", // 7
			"prc", // 8
			"orl", // 9
			"hvt", // 10
			"lvt", // 11
			"del", // 12
			"req", // 13
			"ret",  // 14
			"buy", // 15
			"mar", // 16
			"pac", // 17,
			"typ" // 18
		);
		if ($id > 0){
			$del_info = SqlQuerySingleAssoc("select * from im_delivery where id = $id");
//			var_dump($del_info);
			$this->delivery_total = $del_info['total'];
		}
		$this->calculated = false;
	}

	static public function init_hooks($loader)
	{
		$loader->AddAction('update_by_customer_type', __CLASS__, 'Fresh_Delivery', 'update_by_customer_type');
		$loader->Addfilter("prepare_delivery_lines", __CLASS__, 'prepare_line');
		$loader->Addfilter("finance_show_vat", __CLASS__, 'finance_show_vat');
		$loader->Addfilter("finance_has_vat", __CLASS__, 'finance_has_vat', 10, 2);
		$loader->AddFilter('delivery_product_price', __CLASS__, 'delivery_product_price');

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
		$quantity_ordered                    = Fresh_Packing::get_order_itemmeta( $line_ids, '_qty' );
		$unit_ordered                        = Fresh_Packing::get_order_itemmeta( $line_id, 'unit' );
		$prod_comment = Fresh_Packing::get_order_itemmeta($line_id, 'product_comment');

		$order_line_total                    = round( Fresh_Packing::get_order_itemmeta( $line_ids, '_line_total' ), 1);
		$this->order_total                   += $order_line_total;
		$line[ eDeliveryFields::order_line ] = $order_line_total;
		$prod_id                             = Fresh_Packing::get_order_itemmeta( $line_id, '_product_id' );
		$P                                   = new Fresh_Product( $prod_id );
		$line_price       = Fresh_Packing::get_order_itemmeta( $line_ids, '_line_total' );

		$price = round($line_price / $quantity_ordered, 2);
//		$price = Fresh_Pricing::get_price_by_type( $prod_id, $client_type = "", $quantity = 1

		if ( $unit_ordered ) {
			$quantity_ordered = "";
			$unit_array       = explode( ",", $unit_ordered );
			$unit_q           = $unit_array[1];
			// print "unit: " ; var_dump($unit) ; print "<br/>";
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

	public function getOrder() {
		if ( ! $this->order ) {
			$this->order = new Fresh_Order( $this->OrderId() );
		}

		return $this->order;
	}

	function delivery_text( $document_type = Finance_DocumentType::delivery, $operation = Finance_DocumentOperation::show, $margin = false ) {
		$db_prefix = GetTablePrefix("delivery_lines");

		$this->delivery_total = 0;
		$header_fields = array(
			"בחר",
			"פריט",
			"הערה",
			"ID",
			"קטגוריה",
			"כמות הוזמן",
			"יחידות הוזמנו",
			"כמות סופק",
			"מחיר",
			"סה\"כ להזמנה",
			"חייב מע\"מ",
			"מע\"מ",
			"סה\"כ",
			"כמות לזיכוי",
			"סה\"כ זיכוי",
			"מחיר עלות",
			"סה\"כ מרווח שורה",
			"מידע לאריזה",
			"סוג שורה"
		);

		if ( false ) {
			print "Document type " . $document_type . "<br/>";
			print "operation: " . $operation . "<br/>";
		}
		global $global_vat;

		$expand_basket = false;

		$show_fields = array();
		for ( $i = 0; $i < eDeliveryFields::max_fields; $i ++ ) {
			$show_fields[ $i ] = false;
		}

		if ( InfoGet("delivery_expand_basket") and $operation == Finance_DocumentOperation::create or $operation == Finance_DocumentOperation::collect ) {
			$expand_basket                                = true;
			$show_fields[ eDeliveryFields::packing_info ] = true;
		}

		// All fields:
		$show_fields[ eDeliveryFields::product_name ]  = true;
		$show_fields[ eDeliveryFields::order_q ]       = true;
		$show_fields[ eDeliveryFields::order_q_units ] = false; // For now ordering by units is not supported
		$show_fields[ eDeliveryFields::price ]         = true;
		$show_fields[ eDeliveryFields::client_comment ]         = true;


		$empty_array = array();
		for ( $i = 0; $i < eDeliveryFields::max_fields; $i ++ ) {
			$empty_array[ $i ] = "";
		}

		switch ( $document_type ) {
			case Finance_DocumentType::order:
				$header_fields[ eDeliveryFields::delivery_line ] = "סה\"כ למשלוח";
				if ( $operation == Finance_DocumentOperation::edit ) {
					$header_fields[ eDeliveryFields::line_select ] = Core_Html::gui_checkbox( "chk", "line_chk", false );
					$show_fields[ eDeliveryFields::line_select ]   = true;
				}
				$show_fields[ eDeliveryFields::order_line ] = true;
				if ( $margin ) {
					$show_fields[ eDeliveryFields::buy_price ]   = true;
					$show_fields[ eDeliveryFields::line_margin ] = true;
				}
				break;
			case Finance_DocumentType::delivery:
				$show_fields[ eDeliveryFields::delivery_q ] = true;
				if ( $operation != Finance_DocumentOperation::collect) {
					$show_fields[ eDeliveryFields::has_vat ]       = true;
//					$show_fields[ eDeliveryFields::line_vat ]      = true;
					$show_fields[ eDeliveryFields::delivery_line ] = true;
				}
				if ( $operation == Finance_DocumentOperation::create or $operation == Finance_DocumentOperation::collect )
					$show_fields[ eDeliveryFields::order_line ] = false;
				if ( $margin ) {
					$show_fields[ eDeliveryFields::buy_price ]   = true;
					$show_fields[ eDeliveryFields::line_margin ] = true;
				}
				break;
			case Finance_DocumentType::refund:
				$refund                                      = true;
				$show_fields[ eDeliveryFields::refund_q ]    = true;
				$show_fields[ eDeliveryFields::refund_line ] = true;
				break;
			default:
				print "Document type " . $document_type . " not handled " . __FILE__ . " " . __LINE__ . "<br/>";
				die( 1 );
		}
		$data = "";

		$client_id = $this->GetCustomerID();
		$client = new Fresh_Client($client_id);
		$client_type = $client->customer_type();

		$delivery_loaded = false;
		$volume_line = false;

		$data .= "<style> " .
		         "table.prods { border-collapse: collapse; } " .
		         " table.prods, td.prods, th.prods { border: 1px solid black; } " .
		         " </style>";

		// Orig: $data .= "<table class=\"prods\" id=\"del_table\" border=\"1\">";
		$data .= "<table style='border-collapse: collapse'  id=\"del_table\">";

		// Print header
		$sum   = null;
		$style = 'style="border: 2px solid #dddddd; text-align: right; padding: 8px;"';
		$data  .= Core_Html::gui_row( $header_fields, "header", $show_fields, $sum, null, $style );

		if ( $this->ID > 0 and $document_type == Finance_DocumentType::delivery) { // load delivery
			$delivery_loaded = true;
			$sql             = 'select id, product_name, round(quantity, 1), quantity_ordered, vat, price, line_price, prod_id ' .
			                   "from ${db_prefix}delivery_lines " .
			                   'where delivery_id=' . $this->ID . " order by 1";

			$result = SqlQuery( $sql );

			if ( ! $result ) {
				print $sql;
				die ( "select error" );
			}

			while ( $row = mysqli_fetch_assoc( $result ) ) {
				$line_style = $style;
				if ( $row["product_name"] == "הנחת כמות" ) $volume_line = true;

				// delivery_line( $document_type, $line_ids, $client_type, $operation, $margin = false, $style = null, $show_inventory = false );
				$prod_id = $row["prod_id"];

				if ($prod_id == -1 and ($row["line_price"] == 0)) $line_style = "hidden ";// Discount line.

				$line = $this->delivery_line(  Finance_DocumentType::delivery, $row["id"], $operation,
					$margin, $line_style);

				if ( $operation == Finance_DocumentOperation::check) { // Todo: Need to rewrite this function;
					for($i = 0; $i < eDeliveryFields::max_fields; $i ++)
						$show_fields[$i] = false;

					$show_fields[eDeliveryFields::product_name] = true;
					$show_fields[eDeliveryFields::order_q]      = true;
					$show_fields[eDeliveryFields::delivery_q]   = true;
				}

				$data .= Core_Html::gui_row( $line, ++$this->line_number, $show_fields, $sums, $this->delivery_fields_names, $line_style );
			}
		} else {
			// For group orders - first we get the needed products and then accomulate the quantities.
			$sql = 'select distinct woim.meta_value,  order_line_get_variation(woi.order_item_id) '
			       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
			       . ' where ' . $this->OrderQuery()
			       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\'';

			$prods_result = SqlQuery( $sql );
			while ( $row = SqlFetchRow( $prods_result ) ) {
				$prod_id = $row[0];
				$var_id  = $row[1];

				$items_sql      = 'select woim.order_item_id'
				                  . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
				                  . ' where ' . $this->OrderQuery()
				                  . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
				                  . ' and woim.meta_value = ' . $prod_id
				                  . ' and order_line_get_variation(woi.order_item_id) = ' . $var_id
				                  . ' order by 1';
				$order_item_ids = SqlQueryArrayScalar( $items_sql );

				$b = new Fresh_Basket($prod_id);
				if ( $expand_basket and $b->is_basket()){
					$basket_header = array();
					for ($i = 0; $i < eDeliveryFields::max_fields; $i++)	$basket_header[$i] = "";
					$basket_header[eDeliveryFields::product_name] = $b->getName();
					$basket_header[eDeliveryFields::order_q]      = Fresh_Packing::get_order_itemmeta( $order_item_ids, '_qty' );
					$basket_header[eDeliveryFields::price]        = $b->getPrice();
					$basket_header[eDeliveryFields::line_type]    = "bsk";
					$basket_header[eDeliveryFields::product_id]   = $prod_id;
					$basket_header[eDeliveryFields::client_comment] = Fresh_Packing::get_order_itemmeta($order_item_ids[0], 'product_comment');


					$data .= Core_Html::gui_row($basket_header, ++$this->line_number, $show_fields, $sums, $this->delivery_fields_names, $style);
				} else {
					$line = $this->delivery_line( $document_type, $order_item_ids, $operation, $margin, $style );
					$data .= Core_Html::gui_row( $line, ++$this->line_number, $show_fields, $sums, $this->delivery_fields_names, $style );
				}
				if ( $expand_basket && $b->is_basket() ) {
					$quantity_ordered = Fresh_Packing::get_order_itemmeta( $order_item_ids, '_qty' ); //, $client_type, $operation, $data );

					$this->expand_basket( $prod_id, $quantity_ordered, 0, $show_fields, $document_type,
						$order_item_ids, $client_type, $operation, $data );
				}
			}

			// Get and display order delivery price
			$sql2 = 'SELECT meta_value FROM `wp_woocommerce_order_itemmeta` WHERE order_item_id IN ( '
			        . 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE ' . $this->OrderQuery()
			        . ' AND order_item_type = \'shipping\' )  AND meta_key = \'cost\'; ';

			$del_price = SqlQuerySingleScalar( $sql2 );
			if ( ! is_numeric( $del_price ) ) {
				$del_price = 0;
			}
		}

		if ( ! $delivery_loaded ) {
			$this->order_total   += $del_price;
			$this->order_due_vat += $del_price;

			$del_vat         = round( $del_price / ( 100 + $global_vat ) * $global_vat, 2 );

			$delivery_line                                   = $empty_array;
			$delivery_line[ eDeliveryFields::product_name ]  = "דמי משלוח";
			$delivery_line[ eDeliveryFields::delivery_q ]    = 1;
			$delivery_line[ eDeliveryFields::price ]         = $operation ?
				Core_Html::GuiInput( "delivery", $del_price > 0 ? $del_price : "" ) : $del_price;
			$delivery_line[ eDeliveryFields::has_vat ]       = Core_Html::GuiCheckbox( "hvt_del", true, array("class"=>"vat" ));
			$delivery_line[ eDeliveryFields::line_vat ]      = $del_vat;
			$delivery_line[ eDeliveryFields::delivery_line ] = $del_price;
			$delivery_line[ eDeliveryFields::order_line ]    = $del_price;

			$sums = null;

			$data                  .= Core_Html::gui_row( $delivery_line, "del", $show_fields, $sums, $this->delivery_fields_names );
			$this->order_vat_total += $del_vat;
			// Spare line for volume discount
		}

		if ( $operation != Finance_DocumentOperation::collect ) {
			if ( ! $volume_line ) {
				$delivery_line = $empty_array;
				$dis_line = Core_Html::gui_row( $delivery_line, "dis", $show_fields, $sums, $this->delivery_fields_names );
				$data          .= $dis_line;
			}
			// Summary
			// Due VAT
			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = 'סה"כ חייב במע"מ';
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_due_vat;
			$summary_line[ eDeliveryFields::order_line ]    = $this->order_due_vat;
			$data                                           .= Core_Html::gui_row( $summary_line, "due", $show_fields, $sum, $this->delivery_fields_names, $style );

			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = 'סה"כ בשיעור מע"מ 0';
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_total - $this->delivery_due_vat; // $this->delivery_due_vat;
//			$summary_line[ eDeliveryFields::order_line ]    = 'bb'; // $this->order_due_vat;
			$data                                           .= Core_Html::gui_row( $summary_line, "va0", $show_fields, $sum, $this->delivery_fields_names, $style );

			// Total VAT
			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = 'מע"מ 17%';
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_total_vat;
			$summary_line[ eDeliveryFields::order_line ]    = $this->order_vat_total;
			$data                                           .= Core_Html::gui_row( $summary_line, "vat", $show_fields, $sum, $this->delivery_fields_names, $style );

			// Total
			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = "סה\"כ לתשלום";
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_total;
			$summary_line[ eDeliveryFields::order_line ]    = $this->order_total;
			$summary_line[ eDeliveryFields::line_margin ]   = $this->margin_total;
			$data                                           .= Core_Html::gui_row( $summary_line, "tot", $show_fields, $sum, $this->delivery_fields_names, $style );
		}

		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		$data .= "מספר שורות  " . $this->line_number . "<br/>";

		$this->calculated = true;

		return "$data";
	}

	public function delivery_line( $document_type, $line_ids, $operation, $margin = false, &$style = null ) {

		$show_inventory = false;

		$line_color = null;

		$line = array(); for ( $i = 0; $i <= eDeliveryFields::max_fields; $i ++ ) $line[ $i ] = "";
		if ( is_array( $line_ids ) )$line_id = $line_ids[0]; else $line_id = $line_ids;
		$line[ eDeliveryFields::line_select ] = Core_Html::GuiCheckbox( "chk" . $line_id, false, array("class" => "line_chk" ));

		$unit_ordered       = null;
		$quantity_delivered = 0;
		//////////////////////////////////////////
		// Fetch fields from the order/delivery //
		//////////////////////////////////////////
		$unit_q           = "";
		$load_from_order  = false;
		switch ( $document_type ) {
			case Finance_DocumentType::order:
				$load_from_order = true;
				break;

			case Finance_DocumentType::delivery:
				$load_from_order = ( $operation == Finance_DocumentOperation::create or $operation == Finance_DocumentOperation::collect );
				// TODO: check price
				break;
		}
		$has_vat = null;

		$P = null;
		$prod_comment = "";

		if ( $load_from_order ) {
			$this->load_line_from_order($line_ids,  $prod_id, $prod_name, $quantity_ordered, $unit_q, $P, $price, $prod_comment );
		} else {
			$this->load_line_from_db($line_id, $P, $prod_id, $prod_name, $quantity_ordered, $quantity_delivered, $price, $delivery_line, $has_vat, $line_color);
		}

		// in Order price is total/q. in delivery get from db.
		// $price            = $this->item_price( $client_type, $prod_id, $order_line_total, $quantity_ordered );

		// Display item name. product_name
		$line[ eDeliveryFields::product_name ] = $prod_name;
		$line[ eDeliveryFields::product_id ]   = $prod_id;

		$p = new Fresh_Basket($prod_id);

		// q_quantity_ordered
		$line[ eDeliveryFields::order_q ]       = $quantity_ordered;
		$line[ eDeliveryFields::order_q_units ] = $unit_q;

		if ( is_null( $has_vat ) ) $has_vat = ( $P->getVatPercent() != 0 );

		// price
		if ( $operation == Finance_DocumentOperation::create and $document_type == Finance_DocumentType::delivery ) {
			$line[ eDeliveryFields::price ] = Core_Html::gui_input( "prc_" .  $prod_id, $price, null, null, null, 5 );
		} else {
			$line[ eDeliveryFields::price ] = $price;
		}

		// has_vat
		$line[ eDeliveryFields::has_vat ] = Core_Html::GuiCheckbox( "hvt_" . $prod_id, $has_vat > 0, array("class"=> "has_vat")); // 6 - has vat

		// q_supply
		switch ( $document_type ) {
			case Finance_DocumentType::order:
				// TODO: get supplied q
				// $line[DeliveryFields::delivery_q] = $quantity_delivered;
				// $value .= gui_cell( $quantity_delivered, "", $show_fields[ DeliveryFields::delivery_q ] ); // 4-supplied
				// $value .= gui_cell( "הוזמן", $debug );
				break;

			case Finance_DocumentType::delivery:
				 $line[eDeliveryFields::order_line] = 99; // $order_line_total;
				switch ( $operation ) {
					case Finance_DocumentOperation::edit:
					case Finance_DocumentOperation::create:

//						if (! $p->is_basket())
							$line[ eDeliveryFields::delivery_q ] = Core_Html::GuiInput("quantity" . $this->line_number,
							( $quantity_delivered > 0 ) ? $quantity_delivered : "",
							array( "events" => 'onfocusout="leaveQuantityFocus(' . $this->line_number . ')" ' .
								'onkeypress="moveNextRow(' . $this->line_number . ')"')  );
						break;
					case Finance_DocumentOperation::collect:
						break;
					case Finance_DocumentOperation::show:
						$line[ eDeliveryFields::delivery_q ] = $quantity_delivered;
						break;
					default:
				}
				if ( isset( $delivery_line ) ) {
					$line[ eDeliveryFields::delivery_line ] = $delivery_line;
					$this->delivery_total                   += $delivery_line;
				}
				if ( $has_vat and isset( $delivery_line ) ) {
					$line[ eDeliveryFields::line_vat ] = Fresh_Pricing::vatFromTotal($delivery_line);

					$this->delivery_due_vat   += $delivery_line;
					$this->delivery_total_vat += $line[ eDeliveryFields::line_vat ];
				} else {
					$line[ eDeliveryFields::line_vat ] = "";
				}

				break;
			case Finance_DocumentType::refund;
				$line[ eDeliveryFields::delivery_q ] = $quantity_delivered;
				// $value .= gui_cell( $quantity_delivered );                                              // 4- Supplied
				break;
		}

		if ( ! is_numeric( $price ) ) {
			$price = 0;
		}

		// terms
		// Check if this product eligible for quantity discount.
		$terms = get_the_terms( $prod_id, 'product_cat' );
		$terms_cell = "";
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$terms_cell .= $term->term_id . ",";
			}
			$terms_cell = rtrim( $terms_cell, "," );
		}
		$line[ eDeliveryFields::term ] = $terms_cell;
		//$value .= gui_cell( $terms_cell, "terms" . $this->line_number, false );                    // 9 - terms

		// Handle refund
		if ( $document_type == Finance_DocumentType::refund ) {
			$line[ eDeliveryFields::refund_q ] = gui_cell( gui_input( "refund_" . $this->line_number, 0 ) );             // 10 - refund q
			// $value .= gui_cell( "0" );                                                              // 11 - refund amount
		}

		if ( $margin ) {
			$q                                    = ( $operation == Finance_DocumentType::delivery ) ? $quantity_delivered : $quantity_ordered;
			$line[ eDeliveryFields::buy_price ]   = Fresh_Pricing::get_buy_price( $prod_id );
			$line[ eDeliveryFields::line_margin ] = ( $price - Fresh_Pricing::get_buy_price( $prod_id ) ) * $q;
			$this->margin_total                   += $line[ eDeliveryFields::line_margin ];
		}

		$sums = null;
		if ( $line_color )
			$style .= 'bgcolor="' . $line_color . '"';

		// print $prod_id . " " . $P->getStock() . " " . $P->getStock(true). "<br/>";
		if ( $show_inventory and $P->getOrderedDetails() > 0.8 * $P->getStock( true ) ) {
			$line[ eDeliveryFields::packing_info ] = "מלאי: " . $P->getStock( true ) . ". הזמנות: " . $P->getOrderedDetails();
			$pending                               = $P->PendingSupplies();
			if ( $pending ) {
				foreach ( $pending as $p ) {
					if ( $p[1] == eSupplyStatus::NewSupply ) {
						$line[ eDeliveryFields::packing_info ] .= "<br/>" . "יש לשלוח אספקה מספר " .
						                                          Core_Html::GuiHyperlink( $p[0], "../supplies/supply-get.php?id=" . $p[0] ) . "!<br/>";
					}

					if ( $p[1] == eSupplyStatus::Sent ) {
						$line[ eDeliveryFields::packing_info ] .= " אספקה מספר  " . Core_Html::GuiHyperlink( $p[0], "../supplies/supply-get.php?id=" . $p[0] ) . " בביצוע<br/>";
					}
				}
			} else {
				$line[ eDeliveryFields::packing_info ] .= " חסר! ";
			}
			// " אספקות:" . ;
		}

		$line[eDeliveryFields::line_type] = self::line_type($prod_id);
		$line[eDeliveryFields::client_comment] = $prod_comment;

		return $line;
	}

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
		if (! $this->calculated)
			$this->delivery_text( FreshDocumentType::delivery, Fresh_DocumentOperation::show );
		return $this->delivery_due_vat;
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
		print __FUNCTION__;
		var_dump($row);
		$prod_id = $row['prod_id'];
		if ($prod_id) {
			print "aaa";
			$p              = new Fresh_Product( $prod_id );
			$row['has_vat'] = ($p->isFresh() ? false : true);
		} else {
			$row['has_vat'] = true;
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

}
