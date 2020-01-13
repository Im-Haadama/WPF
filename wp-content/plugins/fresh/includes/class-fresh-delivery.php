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

class eDeliveryFields {
	const
		/// User interface
		line_select = 0,
		/// Product info
		product_name = 1,
		product_id = 2,
		term = 3,
		// Order info
		order_q = 4, // Only display
		order_q_units = 5,
		delivery_q = 6,
		price = 7,
		order_line = 8,
		// Delivery info
		has_vat = 9,
		line_vat = 10,
		delivery_line = 11,
		// Refund info
		refund_q = 12,
		refund_line = 13,
		buy_price = 14,
		line_margin = 15,
		packing_info = 16,
		line_type = 17,
		max_fields = 18;
}
//
//$delivery_fields_names = array(
//	"chk", // 0
//	"nam", // 1
//	"pid", // 2
//	"ter", // 3
//	"orq", // 4
//	"oru", // 5
//	"deq", // 6
//	"prc", // 7
//	"orl", // 8
//	"hvt", // 9
//	"lvt", // 10
//	"del", // 11
//	"req", // 12
//	"ret",  // 13
//	"buy", //14
//	"mar", // 15
//	"pac", // 16,
//	"typ" // 17
//);
//
//$header_fields = array(
//	"בחר",
//	"פריט",
//	"ID",
//	"קטגוריה",
//	"כמות הוזמן",
//	"יחידות הוזמנו",
//	"כמות סופק",
//	"מחיר",
//	"סה\"כ להזמנה",
//	"חייב מע\"מ",
//	"מע\"מ",
//	"סה\"כ",
//	"כמות לזיכוי",
//	"סה\"כ זיכוי",
//	"מחיר עלות",
//	"סה\"כ מרווח שורה",
//	"הערות"
//);
//

class FreshDocumentType {
	const order = 1, // Client
		delivery = 2, // Client
		refund = 3, // Client
		invoice = 4, // Supplier
		supply = 5, // Supplier
		ship = 6,  // Legacy
		bank = 7,
		invoice_refund = 8, // Supplier
		count = 9;
}
//
//
//
class Fresh_DocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3,    // Load and edit
		check = 4;  // Checkup
	// packing = 4;

}

class Fresh_Delivery {
	private $ID = 0;
	private $order_id = 0;
	private $AdditionalOrders = null;
	private $order = null;
	private $order_total = 0;
	private $order_vat_total = 0;
	private $order_due_vat = 0;
	private $line_number = 0;
	private $del_price = 0;
	private $delivery_total = 0;
	private $delivery_due_vat = 0;
	private $delivery_total_vat = 0;
	private $margin_total = 0;
	private $user_id = 0;

	public function __construct( $id ) {
		$this->ID = $id;
	}

	public function CustomerView()
	{
		$result = "";
		$order = $this->getOrder();

		$result .= Core_Html::gui_header(1, __("הזמנה") . " " . $this->OrderId()) . "<br/>";
		$result .= im_translate("Order date") . ": " . $order->GetOrderDate() . "<br/>";
		$result .= __("Supply date") . ": " . $this->GetDate() . "<br/>";

		$args = [];
		$args["fields"] = array("id", "product_name", "quantity_ordered", "quantity", "price", "line_price");
//		$lines = TableData("select * from im_delivery_lines where delivery_id = " . $this->getID());
//	$result .= gui_table_args($lines);
		$args["where"] = "delivery_id = " . $this->getID();
		$args["id_field"] = "id";
		$args["header_fields"] = array("product_name" => "Product name", "quantity_ordered" => "Quantity ordred",
		                               "quantity"=>"Quantity", "price" => "Price", "line_price" => "Line total");
		$args["hide_col"] = array("id");

		$sql = "select " . CommaImplode($args["fields"]) . " from im_delivery_lines where delivery_id = " . $this->getID();
		$rows = Core_Data::TableData($sql, $args);

		$total_fields = array("quantity", "quantity_ordered", "line_price");

		// Create sum line
		$rows["sums"] = array();
		foreach($args["fields"] as $key) $rows["sums"][$key] = "";

		foreach ($total_fields as $field) $rows["sums"][$field] = 0;

		foreach ($rows as $row_id => $not_used)
			foreach ($total_fields as $field){
				if (! in_array($row_id, array("header", "sums")))
					$rows["sums"][$field] += $rows[$row_id][$field];
			}

		$result .= Core_Html::gui_table_args($rows);

		return $result;
	}

	public static function CreateDeliveryFromOrder( $order_id, $q ) {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		remove_filter( 'woocommerce_stock_amount', 'filter_woocommerce_stock_amount', 10 );

		// $q = 1: take from order.
		// $q = 2: inventory
		$prods       = array();
		$order       = new Order( $order_id );
		$order_items = $order->getItems();
		$total       = 0;
		$vat         = 0;
		$lines       = 0;
		foreach ( $order_items as $product ) {
			$lines ++;
			// $p = $product['price'];
			// push_array($prods, array($product['qty']));
			// $total += $p * $q;
			// var_dump($product);
			$prod                 = array();
			$prod['product_name'] = $product["name"];
			switch ( $q ) {
				case 1:
//					print "q=" . $product["quantity"];
					$prod['quantity'] = $product["quantity"];
					break;
				case 2:
					$prod['quantity'] = inventory::GetQuantity( $product['product_id'] );
					break;
			}
			$prod['quantity_ordered'] = $prod['quantity'];
			$prod['vat']              = 0;
			$quantity                 = $product["quantity"];

			if ( $q != 0 ) {
				$prod['price'] = $quantity ? ( $product['total'] / $quantity ) : 0;
			}
			$prod['line_price'] = $product['total'];
			$total              += $product['total'];
			$prod['prod_id']    = $product['product_id'];

			// var_dump($prod);
			array_push( $prods, $prod );
		}

		$delivery_id = Fresh_Delivery::CreateDeliveryHeader( $order_id, $total, $vat, $lines, false, 0, 0, false );
		// print " מספר " . $delivery_id;

		foreach ( $prods as $prod ) {
			Fresh_Delivery::AddDeliveryLine( $prod['product_name'], $delivery_id, $prod['quantity'], $prod['quantity_ordered'], 0,
				$prod['vat'], $prod['price'], $prod['line_price'], $prod['prod_id'], 0 );
		}

		// print " נוצרה <br/>";

//	$order = new WC_Order( $order_id );
//	$order->update_status( 'wc-completed' );

		send_deliveries($delivery_id);

		return $delivery_id;
	}

	private function load_line_from_db($line_id, &$P, &$prod_id, &$prod_name, &$quantity_ordered, &$quantity_delivered, &$price, &$delivery_line, &$has_vat, &$line_color)
	{
		// Loading from database
		$sql = "SELECT prod_id, product_name, quantity_ordered, unit_ordered, round(quantity, 1), price, line_price, vat, part_of_basket FROM im_delivery_lines WHERE id = " . $line_id;

		$row = sql_query_single( $sql );
		if ( ! $row ) {
			sql_error( $sql );
			die ( 2 );
		}

		$prod_id          = $row[0];
		$P                = new Fresh_Product( $prod_id );
		$prod_name        = ($row[8] ? "===>" : "" ) . $row[1];
		$quantity_ordered = $row[2];
		$unit_q           = $row[3];
		$quantity_delivered = $row[4];
		$price              = $row[5];
		$delivery_line      = $row[6];
		$has_vat            = $row[7];

		if ( ($quantity_delivered < ( 0.8 * $quantity_ordered ) or ( $unit_q > 0 and $quantity_delivered == 0 )) and ! $P->is_basket($prod_id) ) {
			$line_color = "yellow";
		}
	}

	private function load_line_from_order($line_ids, $client_type, &$prod_id, &$prod_name, &$quantity_ordered, &$unit_q, &$P, &$price )
	{
		$line_id = $line_ids[0];
		// print "lid=". $line_id . "<br/>";
		$sql                                 = "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $line_id;
		$prod_name                           = sql_query_single_scalar( $sql );
		$quantity_ordered                    = get_order_itemmeta( $line_ids, '_qty' );
		$unit_ordered                        = get_order_itemmeta( $line_id, 'unit' );
		$order_line_total                    = round( get_order_itemmeta( $line_ids, '_line_total' ), 1);
		$this->order_total                   += $order_line_total;
		$line[ eDeliveryFields::order_line ] = $order_line_total;
		$prod_id                             = get_order_itemmeta( $line_id, '_product_id' );
		$P                                   = new Fresh_Product( $prod_id );
		// $line_price       = get_order_itemmeta( $line_id, '_line_total' );

		// Todo: handle prices
		switch ( $client_type ) {
			case 0:
				if ( $quantity_ordered )
					$price = round( $order_line_total / $quantity_ordered, 1 );
				else
					$price = get_price( $prod_id);
				break;
			case 1:
				$price = siton_price( $prod_id );
				break;
			case 2:
				$price = get_buy_price( $prod_id );
				break;
			default:
				$price = round( 1.3 * get_buy_price( $prod_id ), 1);
		}

		if ( $unit_ordered ) {
			$quantity_ordered = "";
			$unit_array       = explode( ",", $unit_ordered );
			$unit_q           = $unit_array[1];
			// print "unit: " ; var_dump($unit) ; print "<br/>";
		}
		return $price;
	}

	public static function CreateDeliveryHeader(
		$order_id, $total, $vat, $lines, $edit, $fee, $delivery_id = null,
		$_draft = false, $reason = null
	) {
		$draft = $_draft ? 1 : 0;

		if ( $edit ) {
			$sql = "UPDATE im_delivery SET vat = " . $vat . ", " .
			       " total = " . $total . ", " .
			       " dlines = " . $lines . ", " .
			       " draft = " . $draft . ", " .
			       " fee = " . $fee .
			       " WHERE order_id = " . $order_id;
			sql_query( $sql );
		} else {
			$sql = "INSERT INTO im_delivery (date, order_id, vat, total, dlines, fee, draft, draft_reason) "
			       . "VALUES ( CURRENT_TIMESTAMP, "
			       . $order_id . ", "
			       . $vat . ', '
			       . $total . ', '
			       . $lines . ', '
			       . $fee . ', '
			       . $draft . ', '
			       . QuoteText( $reason )
			       . ')';
			sql_query( $sql );
			$delivery_id = sql_insert_id();
		}

		if ( ! ( $delivery_id > 0 ) ) {
			die ( "Error!" );
		}
		$order     = new Order( $order_id );
		$client_id = $order->getCustomerId();

		if ( $edit ) {
			account_update_transaction( $total, $delivery_id, $client_id );
			business_update_transaction( $delivery_id, $total, $fee );
		} else { // New!
			$date = date( "Y-m-d" );

			account_add_transaction( $client_id, $date, $total, $delivery_id, "משלוח" );
			business_add_transaction( $client_id, $date, $total, $fee, $delivery_id, 3 );
		}
		// $order = new WC_Order( $order_id );
		if ( ! $order->ChangeStatus( 'wc-awaiting-shipment' ) ) {
			printbr( "can't update order status" );
		}

		// Return the new delivery id!

		return $delivery_id;
	}

	public static function AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id, $part_of_basket ) {

		if ( ! ( $delivery_id > 0 ) ) {
			print "must send positive delivery id. Got " . $delivery_id . "<br/>";
			die ( 1 );
		}
		$product_name = preg_replace( '/[\'"%()]/', "", $product_name );
		print "name: " . $product_name . "<br/>";

		$sql = "INSERT INTO im_delivery_lines (delivery_id, product_name, quantity, quantity_ordered, unit_ordered, vat, price, line_price, prod_id, part_of_basket) VALUES ("
		       . $delivery_id . ", "
		       . "'" . urldecode( $product_name ) . "', "
		       . $quantity . ", "
		       . $quantity_ordered . ", "
		       . $unit_ordered . ", "
		       . $vat . ", "
		       . $price . ', '
		       . round( $line_price, 2 ) . ', '
		       . $prod_id . ', '
		       . $part_of_basket . ' )';

// print $sql . "<br/>";

		MyLog( $sql, "db-add-delivery-line.php" );

		sql_query( $sql );
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
		if ( ! ( $client_id > 0 ) ) {
			die ( "can't get client id from order " . $this->OrderId() );
		}

		MyLog( __FILE__, "client_id = " . $client_id );

		$sql = "SELECT dlines FROM im_delivery WHERE id = " . $this->ID;

		$dlines = sql_query_single_scalar( $sql );

		MyLog( __FILE__, "dlines = " . $dlines );

		$del_user = $this->getOrder()->getOrderInfo( '_billing_first_name' );
		$message  = header_text( true, true, true );

		$message .= "<body>";
		$message .= "שלום " . $del_user . "!
<br><br>
המשלוח שלך ארוז ויוצא לדרך!";

		$message .= "<Br> להלן פרטי המשלוח";

		$message .= $this->delivery_text( FreshDocumentType::delivery, Fresh_DocumentOperation::show );
		// file_get_contents("http://store.im-haadama.co.il/fresh/delivery/get-delivery.php?id=" . $del_id . "&send=1");

		$message .= "<br> היתרה המעודכנת במערכת " . client_balance( $client_id );

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
		send_mail($subject, $to, $message );
		print "mail sent to " . $to . "<br/>";
	}

	public function OrderId() {
		if ( ! ( $this->order_id > 0 ) ) {
			$sql = "SELECT order_id FROM im_delivery WHERE id = " . $this->ID;

			$this->order_id = sql_query_single_scalar( $sql );
		}

		return $this->order_id;
	}

	public function getCustomerId() {
		return $this->getOrder()->getCustomerID();
	}

	private function getOrder() {
		if ( ! $this->order ) {
			$this->order = new Fresh_Order( $this->OrderId() );
		}

		return $this->order;
	}

	function delivery_text( $document_type, $operation = Fresh_DocumentOperation::show, $margin = false, $show_inventory = false ) {
		global $delivery_fields_names;
		global $header_fields;
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

		if ( $operation == Fresh_DocumentOperation::create or $operation == Fresh_DocumentOperation::collect ) {
			$expand_basket                                = true;
			$show_fields[ eDeliveryFields::packing_info ] = true;
		}

		// All fields:
		$show_fields[ eDeliveryFields::product_name ]  = true;
		$show_fields[ eDeliveryFields::order_q ]       = true;
		$show_fields[ eDeliveryFields::order_q_units ] = false; // For now ordering by units is not supported
		$show_fields[ eDeliveryFields::price ]         = true;

		$empty_array = array();
		for ( $i = 0; $i < eDeliveryFields::max_fields; $i ++ ) {
			$empty_array[ $i ] = "";
		}

		switch ( $document_type ) {
			case FreshDocumentType::order:
				$header_fields[ eDeliveryFields::delivery_line ] = "סה\"כ למשלוח";
				if ( $operation == Fresh_DocumentOperation::edit ) {
					$header_fields[ eDeliveryFields::line_select ] = Core_Html::gui_checkbox( "chk", "line_chk", false );
					$show_fields[ eDeliveryFields::line_select ]   = true;
				}
				$show_fields[ eDeliveryFields::order_line ] = true;
				if ( $margin ) {
					$show_fields[ eDeliveryFields::buy_price ]   = true;
					$show_fields[ eDeliveryFields::line_margin ] = true;
				}
				break;
			case FreshDocumentType::delivery:
				$show_fields[ eDeliveryFields::delivery_q ] = true;
				if ( $operation != Fresh_DocumentOperation::collect) {
					$show_fields[ eDeliveryFields::has_vat ]       = true;
					$show_fields[ eDeliveryFields::line_vat ]      = true;
					$show_fields[ eDeliveryFields::delivery_line ] = true;
				}
				if ( $operation == Fresh_DocumentOperation::create or $operation == Fresh_DocumentOperation::collect )
					$show_fields[ eDeliveryFields::order_line ] = true;
				if ( $margin ) {
					$show_fields[ eDeliveryFields::buy_price ]   = true;
					$show_fields[ eDeliveryFields::line_margin ] = true;
				}
				break;
			case FreshDocumentType::refund:
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
		$data  .= gui_row( $header_fields, "header", $show_fields, $sum, null, $style );

		if ( $this->ID > 0 and $document_type == FreshDocumentType::delivery) { // load delivery
			$delivery_loaded = true;
			$sql             = 'select id, product_name, round(quantity, 1), quantity_ordered, vat, price, line_price, prod_id ' .
			                   'from im_delivery_lines ' .
			                   'where delivery_id=' . $this->ID . " order by 1";

			$result = sql_query( $sql );

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

				$line = $this->delivery_line(  FreshDocumentType::delivery, $row["id"], 0, $operation,
					$margin, $line_style, $show_inventory);

				if ( $operation == Fresh_DocumentOperation::check) { // Todo: Need to rewrite this function;
					for($i = 0; $i < eDeliveryFields::max_fields; $i ++)
						$show_fields[$i] = false;

					$show_fields[eDeliveryFields::product_name] = true;
					$show_fields[eDeliveryFields::order_q]      = true;
					$show_fields[eDeliveryFields::delivery_q]   = true;
				}

				$data .= gui_row( $line, ++$this->line_number, $show_fields, $sums, $delivery_fields_names, $line_style );
			}
		} else {
			// For group orders - first we get the needed products and then accomulate the quantities.
			$sql = 'select distinct woim.meta_value,  order_line_get_variation(woi.order_item_id) '
			       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
			       . ' where ' . $this->OrderQuery()
			       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\'';

			$prods_result = sql_query( $sql );
			while ( $row = sql_fetch_row( $prods_result ) ) {
				$prod_id = $row[0];
				$var_id  = $row[1];

				$items_sql      = 'select woim.order_item_id'
				                  . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
				                  . ' where ' . $this->OrderQuery()
				                  . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
				                  . ' and woim.meta_value = ' . $prod_id
				                  . ' and order_line_get_variation(woi.order_item_id) = ' . $var_id
				                  . ' order by 1';
				$order_item_ids = sql_query_array_scalar( $items_sql );

				if ( is_basket($prod_id)){
					$basket_header = array();
					for ($i = 0; $i < eDeliveryFields::max_fields; $i++)	$basket_header[$i] = "";
					$basket_header[eDeliveryFields::product_name] = get_product_name($prod_id);
					$basket_header[eDeliveryFields::order_q]      = get_order_itemmeta( $order_item_ids, '_qty' );
					$basket_header[eDeliveryFields::price]        = get_price($prod_id);
					$basket_header[eDeliveryFields::line_type]    = "bsk";
					$basket_header[eDeliveryFields::product_id]   = $prod_id;

					$data .= gui_row($basket_header, ++$this->line_number, $show_fields, $sums, $delivery_fields_names, $style);
				} else {
					$line = $this->delivery_line( $document_type, $order_item_ids, 0, $operation, $margin, $style, $show_inventory );
					$data .= gui_row( $line, ++$this->line_number, $show_fields, $sums, $delivery_fields_names, $style );
				}
				if ( $expand_basket && is_basket( $prod_id ) ) {
					$quantity_ordered = get_order_itemmeta( $order_item_ids, '_qty' ); //, $client_type, $operation, $data );

					$this->expand_basket( $prod_id, $quantity_ordered, 0, $show_fields, $document_type,
						$order_item_ids, customer_type( $client_id ), $operation, $data );
				}
			}

			// Get and display order delivery price
			$sql2 = 'SELECT meta_value FROM `wp_woocommerce_order_itemmeta` WHERE order_item_id IN ( '
			        . 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE ' . $this->OrderQuery()
			        . ' AND order_item_type = \'shipping\' )  AND meta_key = \'cost\'; ';

			$del_price = sql_query_single_scalar( $sql2 );
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
				gui_input( "delivery", $del_price > 0 ? $del_price : "", "", null, null, 5 ) : $del_price;
			$delivery_line[ eDeliveryFields::has_vat ]       = Core_Html::gui_checkbox( "hvt_del", "vat", true );
			$delivery_line[ eDeliveryFields::line_vat ]      = $del_vat;
			$delivery_line[ eDeliveryFields::delivery_line ] = $del_price;
			$delivery_line[ eDeliveryFields::order_line ]    = $del_price;

			$sums = null;
			global $delivery_fields_names;

			$data                  .= gui_row( $delivery_line, "del", $show_fields, $sums, $delivery_fields_names );
			$this->order_vat_total += $del_vat;
			// Spare line for volume discount
		}

		if ( $operation != Fresh_DocumentOperation::collect ) {
			if ( ! $volume_line ) {
				$delivery_line = $empty_array;
				$dis_line = gui_row( $delivery_line, "dis", $show_fields, $sums, $delivery_fields_names );
				$data          .= $dis_line;
			}
			// Summary
			// Due VAT
			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = 'סה"כ חייב במע"מ';
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_due_vat;
			$summary_line[ eDeliveryFields::order_line ]    = $this->order_due_vat;
			$data                                           .= gui_row( $summary_line, "due", $show_fields, $sum, $delivery_fields_names, $style );

			// Total VAT
			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = 'מע"מ 17%';
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_total_vat;
			$summary_line[ eDeliveryFields::order_line ]    = $this->order_vat_total;
			$data                                           .= gui_row( $summary_line, "vat", $show_fields, $sum, $delivery_fields_names, $style );

			// Total
			$summary_line                                   = $empty_array;
			$summary_line[ eDeliveryFields::product_name ]  = "סה\"כ לתשלום";
			$summary_line[ eDeliveryFields::delivery_line ] = $this->delivery_total;
			$summary_line[ eDeliveryFields::order_line ]    = $this->order_total;
			$summary_line[ eDeliveryFields::line_margin ]   = $this->margin_total;
			$data                                           .= gui_row( $summary_line, "tot", $show_fields, $sum, $delivery_fields_names, $style );
		}

		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		$data .= "מספר שורות  " . $this->line_number . "<br/>";

		return "$data";
	}

	public function delivery_line( $document_type, $line_ids, $client_type, $operation, $margin = false, &$style = null,
		$show_inventory = false ) {

		global $global_vat;

		$line_color = null;

		$line = array(); for ( $i = 0; $i <= eDeliveryFields::max_fields; $i ++ ) $line[ $i ] = "";
		if ( is_array( $line_ids ) )$line_id = $line_ids[0]; else $line_id = $line_ids;
		$line[ eDeliveryFields::line_select ] = Core_Html::gui_checkbox( "chk" . $line_id, "line_chk", false );

		$unit_ordered       = null;
		$quantity_delivered = 0;
		//////////////////////////////////////////
		// Fetch fields from the order/delivery //
		//////////////////////////////////////////
		$unit_q           = "";
		$load_from_order  = false;
		switch ( $document_type ) {
			case FreshDocumentType::order:
				$load_from_order = true;
				break;

			case FreshDocumentType::delivery:
				$load_from_order = ( $operation == Fresh_DocumentOperation::create or $operation == Fresh_DocumentOperation::collect );
				// TODO: check price
				break;
		}
		$has_vat = null;

		$P = null;

		if ( $load_from_order ) {
			$this->load_line_from_order($line_ids, $client_type, $prod_id, $prod_name, $quantity_ordered, $unit_q, $P, $price );
		} else {
			$this->load_line_from_db($line_id, $P, $prod_id, $prod_name, $quantity_ordered, $quantity_delivered, $price, $delivery_line, $has_vat, $line_color);
		}

		// in Order price is total/q. in delivery get from db.
		// $price            = $this->item_price( $client_type, $prod_id, $order_line_total, $quantity_ordered );

		// Display item name. product_name
		$line[ eDeliveryFields::product_name ] = $prod_name;
		$line[ eDeliveryFields::product_id ]   = $prod_id;

		// q_quantity_ordered
		$line[ eDeliveryFields::order_q ]       = $quantity_ordered;
		$line[ eDeliveryFields::order_q_units ] = $unit_q;

		if ( is_null( $has_vat ) ) $has_vat = ( $P->getVatPercent() != 0 );

		// price
		if ( $operation == Fresh_DocumentOperation::create and $document_type == FreshDocumentType::delivery ) {
			$line[ eDeliveryFields::price ] = gui_input( "", $price, null, null, null, 5 );
		} else {
			$line[ eDeliveryFields::price ] = $price;
		}

		// has_vat
		$line[ eDeliveryFields::has_vat ] = Core_Html::gui_checkbox( "hvt_" . $prod_id, "has_vat", $has_vat > 0 ); // 6 - has vat

		// q_supply
		switch ( $document_type ) {
			case FreshDocumentType::order:
				// TODO: get supplied q
				// $line[DeliveryFields::delivery_q] = $quantity_delivered;
				// $value .= gui_cell( $quantity_delivered, "", $show_fields[ DeliveryFields::delivery_q ] ); // 4-supplied
				// $value .= gui_cell( "הוזמן", $debug );
				break;

			case FreshDocumentType::delivery:
				// $line[DeliveryFields::order_line] = $order_line_total;
				switch ( $operation ) {
					case Fresh_DocumentOperation::edit:
					case Fresh_DocumentOperation::create:

						if (! is_basket($prod_id))
							$line[ eDeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number,
							( $quantity_delivered > 0 ) ? $quantity_delivered : "",
							array( 'onfocusout=leaveQuantityFocus(' . $this->line_number . ')',
								'onkeypress="moveNextRow(' . $this->line_number . ')"' ), null, null, 5 );
						break;
					case Fresh_DocumentOperation::collect:
						break;
					case Fresh_DocumentOperation::show:
						$line[ eDeliveryFields::delivery_q ] = $quantity_delivered;
						break;
					default:
				}
				if ( isset( $delivery_line ) ) {
					$line[ eDeliveryFields::delivery_line ] = $delivery_line;
					$this->delivery_total                   += $delivery_line;
				}
				if ( $has_vat and isset( $delivery_line ) ) {
					$line[ eDeliveryFields::line_vat ] = round( $delivery_line / ( 100 + $global_vat ) * $global_vat, 2 );
					// round($delivery_line / (100 + $global_vat));

					$this->delivery_due_vat   += $delivery_line;
					$this->delivery_total_vat += $line[ eDeliveryFields::line_vat ];
				} else {
					$line[ eDeliveryFields::line_vat ] = "";
				}

				break;
			case FreshDocumentType::refund;
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
		if ( $document_type == FreshDocumentType::refund ) {
			$line[ eDeliveryFields::refund_q ] = gui_cell( gui_input( "refund_" . $this->line_number, 0 ) );             // 10 - refund q
			// $value .= gui_cell( "0" );                                                              // 11 - refund amount
		}

		if ( $margin ) {
			$q                                    = ( $operation == FreshDocumentType::delivery ) ? $quantity_delivered : $quantity_ordered;
			$line[ eDeliveryFields::buy_price ]   = get_buy_price( $prod_id );
			$line[ eDeliveryFields::line_margin ] = ( $price - get_buy_price( $prod_id ) ) * $q;
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
					if ( $p[1] == SupplyStatus::NewSupply ) {
						$line[ eDeliveryFields::packing_info ] .= "<br/>" . "יש לשלוח אספקה מספר " .
						                                          Core_Html::GuiHyperlink( $p[0], "../supplies/supply-get.php?id=" . $p[0] ) . "!<br/>";
					}

					if ( $p[1] == SupplyStatus::Sent ) {
						$line[ eDeliveryFields::packing_info ] .= " אספקה מספר  " . Core_Html::GuiHyperlink( $p[0], "../supplies/supply-get.php?id=" . $p[0] ) . " בביצוע<br/>";
					}
				}
			} else {
				$line[ eDeliveryFields::packing_info ] .= " חסר! ";
			}
			// " אספקות:" . ;
		}

		$line[eDeliveryFields::line_type] = Fresh_Delivery::line_type($prod_id);

		// return gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names, $style );
		return $line;
	}

	private static function line_type($prod_id)
	{
		if ($prod_id == -1) return "dis";
		$b = new Fresh_Basket($prod_id);
		if ($b->is_basket($prod_id)) return "bsk";
		return "prd";
	}

	function OrderQuery() {
		if ( is_array( $this->order_id ) ) {
			return "order_id in (" . CommaImplode( $this->order_id ) . ")";
		} else {
			return "order_id = " . $this->order_id;
		}
	}

	function expand_basket( $basket_id, $quantity_ordered, $level, $show_fields, $document_type, $line_id, $client_type, $edit, &$data ) {
		global $delivery_fields_names;
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result2 = sql_query( $sql2 );
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

				$line[ eDeliveryFields::product_name ] = "===> " . get_product_name( $prod_id );
				$line[ eDeliveryFields::price ]        = get_price_by_type( $prod_id, $client_type );
				$has_vat                               = true;

				if ( ! $P->getVatPercent() ) {
					$has_vat = false;
				}
				$line[ eDeliveryFields::product_id ] = $prod_id;
				$line[ eDeliveryFields::has_vat ]    = Core_Html::gui_checkbox( "hvt_" . $prod_id, "has_vat", $has_vat > 0 );
				$line[ eDeliveryFields::order_q ]    = $quantity_ordered;
				$line[ eDeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number, "",
					array( 'onkeypress="moveNextRow(' . $this->line_number . ')"' ) );

				$this->line_number = $this->line_number + 1;
				$data              .= gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names );
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
			$dis_line                              = gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names );
			// print "<table><tr>" . $dis_line . "</tr></table>";
			$data .= $dis_line;
		}
	}

	public static function GuiCreateNewNoOrder() {
		$data = gui_table_args( array(
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

	public static function CreateFromOrder( $order_id ) {

		$id = Fresh_Order::get_delivery_id( $order_id );

		$instance = new self( $id );

		$instance->SetOrderId( $order_id );

		return $instance;
	}

	private function SetOrderID( $order_id ) {
		$this->order_id = $order_id;
	}

	public function OrderInfoBox() {
		return $this->getOrder()->infoBox();
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	public function isDraft() {
		if ( $this->ID ) {
			return sql_query_single_scalar( "select draft from im_delivery where ID = " . $this->ID );
		} else {
			die ( __METHOD__ . " no ID" . DB_NAME );
		}
	}

	public function draftReason() {
		if ( $this->ID ) {
			return sql_query_single_scalar( "select draft_reason from im_delivery where ID = " . $this->ID );
		} else {
			die ( __METHOD__ . " no ID" );
		}
	}

	public function DeliveryDate() {
		$sql = "SELECT date FROM im_delivery WHERE id = " . $this->ID;

		$row = sql_query_single_scalar($sql);

		return $row["date"];
	}

	public function Delete() {
		// change the order back to processing
		$order_id = $this->OrderId();
		if ( ! $order_id ) {
			die ( "no order id: Delete" );
		}

		$sql = "UPDATE wp_posts SET post_status = 'wc-processing' WHERE id = " . $order_id;

		sql_query( $sql );

		// Remove from client account
		$sql = 'DELETE FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;

		sql_query( $sql );

		// Remove the header
		$sql = 'DELETE FROM im_delivery WHERE id = ' . $this->ID;

		sql_query( $sql );

		// Remove the lines
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		sql_query( $sql );

	}

	public function DeleteLines() {
		// TODO:
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		sql_query( $sql );
	}

	public function Price() {
		// $sql = 'SELECT round(transaction_amount, 2) FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;
		$sql = 'SELECT round(total, 2) FROM im_delivery WHERE id = ' . $this->ID;
		// my_log($sql);

		return sql_query_single_scalar( $sql );
	}

	public function getDate() {
		return sql_query_single_scalar("select date from im_delivery where id = " . $this->ID);
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
			$this->user_id = sql_query_single_scalar( $sql);
		}

		return $this->user_id;
	}

	// function expand_basket( $basket_id, $client_type, $quantity_ordered, &$data, $level ) {
	// Called when creating a delivery from an order.
	// After the basket line is shown, we print here the basket lines and basket discount line.

	function PrintDeliveries( $document_type, $operation, $margin = false, $show_inventory = false ) {
		print $this->delivery_text( $document_type, $operation, $margin, $show_inventory );
	}

	public function DeliveryFee() {
		$sql = 'SELECT fee FROM im_delivery WHERE id = ' . $this->ID;

		// print $sql;
		// my_log($sql);

		return sql_query_single_scalar( $sql);
	}

	function handle_delivery_operation($operation)
	{
		$debug = 0;
		if ($debug)	print "operation: " . $operation . "<br/>";
		switch ($operation){
			case "show_this_week":
				$args = [];
				// Links to prev/next week
				$date_format = 'Y-m-j';
				$date = GetParam("week", false, date($date_format, strtotime("last sunday")));
				print Core_Html::GuiHyperlink("last week", AddParamToUrl(GetUrl(), "week", date($date_format, strtotime( $date . " -1 week")))) . " ";
				print Core_Html::GuiHyperlink("next week", AddParamToUrl(GetUrl(), "week", date($date_format, strtotime( $date . " +1 week"))));

				print "<br/>";

				// Show selected week
				$args["sql"] = "select ID, date, order_id, client_from_delivery(ID) from im_delivery where first_day_of_week(date) = " . QuoteText($date);
				$args["id_field"] = "ID";

				// $args["links"] = array("ID" => add_param_to_url(get_url(), "operation", "show_id", "row_id", "%s"));
				$args["links"] = array("ID" => "/fresh/delivery/get-delivery.php?id=%s");
				$table =  Core_Gem::GemTable("im_delivery", $args);
				if (strlen($table) < 100)
					print "No deliveries done this week<br/>";
				else
					print $table;
				break;

			default:
				print __FUNCTION__ . ": " . $operation . " not handled <br/>";

				die(1);
		}
		return;
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
		return sql_query_single_scalar("select max(id) from im_delivery where client_id_from_delivery(id) = " . $user_id);
	}
}
