<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/16
 * Time: 19:29
 */
// require_once( "../r-shop_manager.php" );
if ( ! defined( 'TOOLS_DIR' ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( TOOLS_DIR . "/pricing.php" );
require_once( TOOLS_DIR . "/account/account.php" );
include_once( TOOLS_DIR . "/orders/orders-common.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
include_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/mail.php" );
require_once( TOOLS_DIR . "/account/gui.php" );
require_once( TOOLS_DIR . "/inventory/inventory.php" );
require_once( TOOLS_DIR . "/business/business.php" );
require_once( ROOT_DIR . "/tools/catalog/Basket.php" );
$debug = false;

class DeliveryFields {
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
		max_fields = 17;
}

$delivery_fields_names = array(
	"chk", // 0
	"nam", // 1
	"pid", // 2
	"ter", // 3
	"orq", // 4
	"oru", // 5
	"deq", // 6
	"prc", // 7
	"orl", // 8
	"hvt", // 9
	"lvt", // 10
	"del", // 11
	"req", // 12
	"ret",  // 13
	"buy", //14
	"mar", // 15
	"pac" // 16
);

$header_fields = array(
	"בחר",
	"פריט",
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
	"הערות"
);

class ImDocumentType {
	const order = 1, // Client
		delivery = 2, // Client
		refund = 3, // Client
		invoice = 4, // Supplier
		supply = 5, // Supplier
		ship = 6,  // Legacy
		count = 7;
}

$DocumentTypeNames = array(
	"",
	"הזמנה",
	"משלוח",
	"זיכוי",
	"חשבונית קבלה",
	"אספקה",
	"תעודת משלוח",
);


class ImDocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3;     // Load and edit
	// packing = 4;

}

class delivery {
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

	public static function CreateDeliveryFromOrder( $order_id, $q ) {
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
					$prod['quantity'] = $product["quantity"];
					break;
				case 2:
					$prod['quantity'] = inventory::GetQuantity( $product['product_id'] );
					break;
			}
			$prod['quantity_ordered'] = 0;
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

		$delivery_id = delivery::CreateDeliveryHeader( $order_id, $total, $vat, $lines, false, 0, 0, false );

		// print " מספר " . $delivery_id;

		foreach ( $prods as $prod ) {
			delivery::AddDeliveryLine( $prod['product_name'], $delivery_id, $prod['quantity'], $prod['quantity_ordered'], 0,
				$prod['vat'], $prod['price'], $prod['line_price'], $prod['prod_id'] );
		}

		// print " נוצרה <br/>";

//	$order = new WC_Order( $order_id );
//	$order->update_status( 'wc-completed' );

		global $track_email;
		$delivery = new delivery( $delivery_id );
		$delivery->send_mail( $track_email, false );

		return $delivery_id;
	}

	public static function CreateDeliveryHeader(
		$order_id, $total, $vat, $lines, $edit, $fee, $delivery_id = null,
		$_draft = false, $reason = null
	) {
		global $conn;

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
			       . quote_text( $reason )
			       . ')';
			sql_query( $sql );
			$delivery_id = mysqli_insert_id( $conn );
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

	public static function AddDeliveryLine( $product_name, $delivery_id, $quantity, $quantity_ordered, $unit_ordered, $vat, $price, $line_price, $prod_id ) {

		if ( ! ( $delivery_id > 0 ) ) {
			print "must send positive delivery id. Got " . $delivery_id . "<br/>";
			die ( 1 );
		}
		$product_name = preg_replace( '/[\'"%()]/', "", $product_name );
		// print "name: " . $product_name . "<br/>";

		$sql = "INSERT INTO im_delivery_lines (delivery_id, product_name, quantity, quantity_ordered, unit_ordered, vat, price, line_price, prod_id) VALUES ("
		       . $delivery_id . ", "
		       . "'" . urldecode( $product_name ) . "', "
		       . $quantity . ", "
		       . $quantity_ordered . ", "
		       . $unit_ordered . ", "
		       . $vat . ", "
		       . $price . ', '
		       . round( $line_price, 2 ) . ', '
		       . $prod_id . ' )';

// print $sql . "<br/>";

		my_log( $sql, "db-add-delivery-line.php" );

		sql_query( $sql );
	}

	function send_mail( $more_email = null, $edit = false ) {
		global $business_name;
		global $bank_info;
		global $support_email;
		global $mail_sender;

		$order_id = $this->OrderId();

		if ( ! ( $order_id > 0 ) ) {
			die ( "can't get order id from delivery " . $this->ID );
		}
		// print "oid= " . $order_id . "<br/>";
		$client_id = $this->getCustomerId();
		if ( ! ( $client_id > 0 ) ) {
			die ( "can't get client id from order " . $this->OrderId() );
		}

		my_log( __FILE__, "client_id = " . $client_id );

		$sql = "SELECT dlines FROM im_delivery WHERE id = " . $this->ID;

		$dlines = sql_query_single_scalar( $sql );

		my_log( __FILE__, "dlines = " . $dlines );

		$del_user = $this->getOrder()->getOrderInfo( '_billing_first_name' );
		$message  = header_text( true, true, true );

		$message .= "<body>";
		$message .= "שלום " . $del_user . "!
<br><br>
המשלוח שלך ארוז ויוצא לדרך!";

		$message .= "<Br> להלן פרטי המשלוח";

		$message .= $this->delivery_text( ImDocumentType::delivery, ImDocumentOperation::show );
		// file_get_contents("http://store.im-haadama.co.il/tools/delivery/get-delivery.php?id=" . $del_id . "&send=1");

		$message .= "<br> היתרה המעודכנת במערכת " . client_balance( $client_id );

		$message .= "<br /> לפרטים אודות מצב החשבון והמשלוח האחרון הכנס " .
		            gui_hyperlink( "מצב חשבון", get_site_url() . '/balance' ) .
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
		my_log( $user_info->user_email );
		$to = $user_info->user_email;
		// print "To: " . $to . "<br/>";
		if ( $more_email ) {
			$to = $to . ", " . $more_email;
		}
		// print "From: " . $support_email . "<br/>";
		// print "To: " . $to . "<br/>";
		// print "Message:<br/>";
		// print $message . "<br/>";
		$subject = "משלוח מספר" . $this->ID . " בוצע";
		if ( $edit ) {
			$subject = "משלוח מספר " . $this->ID . " - תיקון";
		}
		send_mail( $subject, $to, $message );
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
			$this->order = new Order( $this->OrderId() );
		}

		return $this->order;
	}

	function delivery_text( $document_type, $operation = ImDocumentOperation::show, $margin = false ) {
		global $delivery_fields_names;
		global $header_fields;
		global $debug;
		if ( false ) {
			print "Document type " . $document_type . "<br/>";
			print "operation: " . $operation . "<br/>";
		}
		global $global_vat;

		$expand_basket = false;

		$show_fields = array();
		for ( $i = 0; $i < DeliveryFields::max_fields; $i ++ ) {
			$show_fields[ $i ] = false;
		}

		if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
			$expand_basket                               = true;
			$show_fields[ DeliveryFields::packing_info ] = true;

		}

		// All fields:

		$show_fields[ DeliveryFields::product_name ]  = true;
		$show_fields[ DeliveryFields::order_q ]       = true;
		$show_fields[ DeliveryFields::order_q_units ] = true;
		$show_fields[ DeliveryFields::price ]         = true;

		$empty_array = array();
		for ( $i = 0; $i < DeliveryFields::max_fields; $i ++ ) {
			$empty_array[ $i ] = "";
		}

		switch ( $document_type ) {
			case ImDocumentType::order:
				$header_fields[ DeliveryFields::delivery_line ] = "סה\"כ למשלוח";
				if ( $operation == ImDocumentOperation::edit ) {
					$header_fields[ DeliveryFields::line_select ] = gui_checkbox( "chk", "line_chk", false );
					$show_fields[ DeliveryFields::line_select ]   = true;
				}
				$show_fields[ DeliveryFields::order_line ] = true;
				if ( $margin ) {
					$show_fields[ DeliveryFields::buy_price ]   = true;
					$show_fields[ DeliveryFields::line_margin ] = true;
				}
				break;
			case ImDocumentType::delivery:
				$show_fields[ DeliveryFields::delivery_q ] = true;
				if ( $operation != ImDocumentOperation::collect ) {
					$show_fields[ DeliveryFields::has_vat ]       = true;
					$show_fields[ DeliveryFields::line_vat ]      = true;
					$show_fields[ DeliveryFields::delivery_line ] = true;
				}
				if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect )
					$show_fields[ DeliveryFields::order_line ] = true;
				if ( $margin ) {
					$show_fields[ DeliveryFields::buy_price ]   = true;
					$show_fields[ DeliveryFields::line_margin ] = true;
				}
				break;
			case ImDocumentType::refund:
				$refund                                     = true;
				$show_fields[ DeliveryFields::refund_q ]    = true;
				$show_fields[ DeliveryFields::refund_line ] = true;
				break;
			default:
				print "Document type " . $document_type . " not handled " . __FILE__ . " " . __LINE__ . "<br/>";
				die( 1 );
		}

		$data = "";

		$client_id = $this->GetCustomerID();
//		print "cid=" . $client_id . "<br/>";
//		$client_type = customer_type( $client_id );
//		 print $client_type . "XX<br/>";

//		if ( $client_type > 0 ) {
//			$data .= "תעריף " . sql_query_single_scalar( "SELECT type FROM im_client_types WHERE id = " . $client_type );
//		}

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

		if ( $this->ID > 0 ) { // load delivery
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
				if ( $row["product_name"] == "הנחת כמות" ) {
					$volume_line = true;
				}
				$data .= $this->delivery_line( $show_fields, ImDocumentType::delivery, $row["id"], 0, $operation, $margin, $style );
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

				// $data .= $this->delivery_line($show_fields, $prod_id, $quantity_ordered, "", $quantity_ordered, $price, $has_vat, $prod_id, $refund, $unit );
				$data .= $this->delivery_line( $show_fields, $document_type, $order_item_ids, 0, $operation, $margin, $style, $var_id );
				// print "ex " . $expand_basket . " is " . is_basket($prod_id) . "<br/>";

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

			$delivery_line                                  = $empty_array;
			$delivery_line[ DeliveryFields::product_name ]  = "דמי משלוח";
			$delivery_line[ DeliveryFields::delivery_q ]    = 1;
			$delivery_line[ DeliveryFields::price ]         = $operation ?
				gui_input( "delivery", $del_price > 0 ? $del_price : "", "", null, null, 5 ) : $del_price;
			$delivery_line[ DeliveryFields::has_vat ]       = gui_checkbox( "hvt_del", "vat", true );
			$delivery_line[ DeliveryFields::line_vat ]      = $del_vat;
			$delivery_line[ DeliveryFields::delivery_line ] = $del_price;
			$delivery_line[ DeliveryFields::order_line ]    = $del_price;

			$sums = null;
			global $delivery_fields_names;
			$data                  .= gui_row( $delivery_line, "del", $show_fields, $sums, $delivery_fields_names );
			$this->order_vat_total += $del_vat;
			// Spare line for volume discount
		}

		if ( $operation != ImDocumentOperation::collect ) {

			if ( ! $volume_line ) {
				$delivery_line = $empty_array;
				$data          .= gui_row( $delivery_line, "dis", $show_fields, $sums, $delivery_fields_names );
			}
			// Summary
			// Due VAT
			$summary_line                                  = $empty_array;
			$summary_line[ DeliveryFields::product_name ]  = 'סה"כ חייב במע"מ';
			$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_due_vat;
			$summary_line[ DeliveryFields::order_line ]    = $this->order_due_vat;
			$data                                          .= gui_row( $summary_line, "due", $show_fields, $sum, $delivery_fields_names, $style );

			// Total VAT
			$summary_line                                  = $empty_array;
			$summary_line[ DeliveryFields::product_name ]  = 'מע"מ 17%';
			$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_total_vat;
			$summary_line[ DeliveryFields::order_line ]    = $this->order_vat_total;
			$data                                          .= gui_row( $summary_line, "vat", $show_fields, $sum, $delivery_fields_names, $style );

			// Total
			$summary_line                                  = $empty_array;
			$summary_line[ DeliveryFields::product_name ]  = "סה\"כ לתשלום";
			$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_total;
			$summary_line[ DeliveryFields::order_line ]    = $this->order_total;
			$summary_line[ DeliveryFields::line_margin ]   = $this->margin_total;
			$data                                          .= gui_row( $summary_line, "tot", $show_fields, $sum, $delivery_fields_names, $style );
		}

		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		$data .= "מספר שורות  " . $this->line_number . "<br/>";

		return "$data";
	}

//	private function GetCustomerID() {
//		if ( is_array( $this->d_OrderID ) ) {
//			return order_get_customer_id( $this->d_OrderID[0] );
//		}
//
//		return order_get_customer_id( $this->d_OrderID );
//	}

	public function delivery_line( $show_fields, $document_type, $line_ids, $client_type, $operation, $margin = false, $style = null, $var_id = 0 ) {
		global $delivery_fields_names;

		global $global_vat;

		$line_color = null;

		$line = array();
		for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
			$line[ $i ] = "";
		}

		if ( is_array( $line_ids ) )
			$line_id = $line_ids[0];
		else $line_id = $line_ids;

		$line[ DeliveryFields::line_select ] = gui_checkbox( "chk" . $line_id, "line_chk", false );

		$unit_ordered       = null;
		$quantity_delivered = 0;
		//////////////////////////////////////////
		// Fetch fields from the order/delivery //
		//////////////////////////////////////////
		$unit_q           = "";
		$load_from_order  = false;
		switch ( $document_type ) {
			case ImDocumentType::order:
				$load_from_order = true;
				break;

			case ImDocumentType::delivery:
				if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
					$load_from_order = true;
				} else {
					$load_from_order = false;
				}
				// TODO: check price
				break;
		}
		$has_vat = null;

		$P = null;

		if ( $load_from_order ) {
			// print "lid=". $line_id . "<br/>";
			$sql                                = "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $line_id;
			$prod_name                          = sql_query_single_scalar( $sql );
			$quantity_ordered                   = get_order_itemmeta( $line_ids, '_qty' );
			$unit_ordered                       = get_order_itemmeta( $line_id, 'unit' );
			$order_line_total                   = round( get_order_itemmeta( $line_ids, '_line_total' ), 1);
			$this->order_total                  += $order_line_total;
			$line[ DeliveryFields::order_line ] = $order_line_total;
			$prod_id                            = get_order_itemmeta( $line_id, '_product_id' );
			$P                                  = new Product( $prod_id );
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
		} else {
			$sql = "SELECT prod_id, product_name, quantity_ordered, unit_ordered, round(quantity, 1), price, line_price, vat FROM im_delivery_lines WHERE id = " . $line_id;

			$row = sql_query_single( $sql );
			if ( ! $row ) {
				sql_error( $sql );
				die ( 2 );
			}

			$prod_id          = $row[0];
			$P                = new Product( $prod_id );
			$prod_name        = $row[1];
			$quantity_ordered = $row[2];
			$unit_q           = $row[3];
//			if ( $unit_q > 0 and $quantity_ordered == 0 ) {
//				$quantity_ordered = "";
//			}
			$quantity_delivered = $row[4];
			$price              = $row[5];
			$delivery_line      = $row[6];
			$has_vat            = $row[7];

			if ( $quantity_delivered < ( 0.8 * $quantity_ordered ) or ( $unit_q > 0 and $quantity_delivered == 0 ) ) {
				$line_color = "yellow";
			}
		}

		// in Order price is total/q. in delivery get from db.
		// $price            = $this->item_price( $client_type, $prod_id, $order_line_total, $quantity_ordered );

		// Display item name. product_name
		$line[ DeliveryFields::product_name ] = $prod_name;
		$line[ DeliveryFields::product_id ]   = $prod_id;
		// $value .= "<td id='" . $prod_id . "'>" . $prod_name . '</td>'; // 1- name

		// q_quantity_ordered
		$line[ DeliveryFields::order_q ]       = $quantity_ordered;
		$line[ DeliveryFields::order_q_units ] = $unit_q;
		// $value .= "<td>" . $quantity_ordered . "</td>";                             // 2- ordered

		if ( is_null( $has_vat ) ) {
			if ( $P->getVatPercent() == 0 ) {
				$has_vat = false;
			} else {
				$has_vat = true;
			}
		}

		// price
		if ( $operation == ImDocumentOperation::create and $document_type == ImDocumentType::delivery ) {
			$line[ DeliveryFields::price ] = gui_input( "", $price, null, null, null, 5 );
		} else {
			$line[ DeliveryFields::price ] = $price;
		}

		// has_vat
		$line[ DeliveryFields::has_vat ] = gui_checkbox( "hvt_" . $prod_id, "has_vat", $has_vat > 0 ); // 6 - has vat

		// q_supply
		switch ( $document_type ) {
			case ImDocumentType::order:
				// TODO: get supplied q
				// $line[DeliveryFields::delivery_q] = $quantity_delivered;
				// $value .= gui_cell( $quantity_delivered, "", $show_fields[ DeliveryFields::delivery_q ] ); // 4-supplied
				// $value .= gui_cell( "הוזמן", $debug );
				break;

			case ImDocumentType::delivery:
				// $line[DeliveryFields::order_line] = $order_line_total;
				switch ( $operation ) {
					case ImDocumentOperation::edit:
					case ImDocumentOperation::create:

						$line[ DeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number,
							( $quantity_delivered > 0 ) ? $quantity_delivered : "",
							array( 'onkeypress="moveNextRow(' . $this->line_number . ')"' ), null, null, 5 );
						break;
					case ImDocumentOperation::collect:
						break;
					case ImDocumentOperation::show:
						$line[ DeliveryFields::delivery_q ] = $quantity_delivered;
						break;
					default:
				}
				if ( isset( $delivery_line ) ) {
					$line[ DeliveryFields::delivery_line ] = $delivery_line;
					$this->delivery_total                  += $delivery_line;
				}
				if ( $has_vat and isset( $delivery_line ) ) {
					$line[ DeliveryFields::line_vat ] = round( $delivery_line / ( 100 + $global_vat ) * $global_vat, 2 );
					// round($delivery_line / (100 + $global_vat));

					$this->delivery_due_vat   += $delivery_line;
					$this->delivery_total_vat += $line[ DeliveryFields::line_vat ];
				} else {
					$line[ DeliveryFields::line_vat ] = "";
				}

				break;
			case ImDocumentType::refund;
				$line[ DeliveryFields::delivery_q ] = $quantity_delivered;
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
		$line[ DeliveryFields::term ] = $terms_cell;
		//$value .= gui_cell( $terms_cell, "terms" . $this->line_number, false );                    // 9 - terms

		// Handle refund
		if ( $document_type == ImDocumentType::refund ) {
			$line[ DeliveryFields::refund_q ] = gui_cell( gui_input( "refund_" . $this->line_number, 0 ) );             // 10 - refund q
			// $value .= gui_cell( "0" );                                                              // 11 - refund amount
		}

		if ( $margin ) {
			$q                                   = ( $operation == ImDocumentType::delivery ) ? $quantity_delivered : $quantity_ordered;
			$line[ DeliveryFields::buy_price ]   = get_buy_price( $prod_id );
			$line[ DeliveryFields::line_margin ] = ( $price - get_buy_price( $prod_id ) ) * $q;
			$this->margin_total                  += $line[ DeliveryFields::line_margin ];
		}

		$this->line_number = $this->line_number + 1;
		$sums = null;
		if ( $line_color )
			$style .= 'bgcolor="' . $line_color . '"';

		// print $prod_id . " " . $P->getStock() . " " . $P->getStock(true). "<br/>";
		if ( $P->getOrderedDetails() > 0.8 * $P->getStock( true ) ) {
			$line[ DeliveryFields::packing_info ] = "מלאי: " . $P->getStock( true ) . ". הזמנות: " . $P->getOrderedDetails();
			$pending                              = $P->PendingSupplies();
			if ( $pending ) {
				foreach ( $pending as $p ) {
					if ( $p[1] == SupplyStatus::NewSupply ) {
						$line[ DeliveryFields::packing_info ] .= "<br/>" . "יש לשלוח הספקה מספר " .
						                                         gui_hyperlink( $p[0], "../supplies/supply-get.php?id=" . $p[0] ) . "!<br/>";
					}

					if ( $p[1] == SupplyStatus::Sent ) {
						$line[ DeliveryFields::packing_info ] .= " הספקה מספר  " . gui_hyperlink( $p[0], "../supplies/supply-get.php?id=" . $p[0] ) . " בביצוע<br/>";
					}
				}
			} else {
				$line[ DeliveryFields::packing_info ] .= " חסר! ";
			}
			// " אספקות:" . ;
		}

		return gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names, $style );
	}

	function OrderQuery() {
		if ( is_array( $this->order_id ) ) {
			return "order_id in (" . comma_implode( $this->order_id ) . ")";
		} else {
			return "order_id = " . $this->order_id;
		}
	}

	function expand_basket( $basket_id, $quantity_ordered, $level, $show_fields, $document_type, $line_id, $client_type, $edit, &$data ) {
		global $conn, $delivery_fields_names;
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result2 = mysqli_query( $conn, $sql2 );
		while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
			$prod_id  = $row2["product_id"];
			// print $prod_id . "<br/>";
			$P        = new Product( $prod_id );
			$quantity = $row2["quantity"];
			if ( is_basket( $prod_id ) ) {
//				$this->expand_basket( $prod_id, $client_type, $quantity_ordered * $quantity, $data, $level + 1 );
				$this->expand_basket( $prod_id, $quantity_ordered * $quantity, $level + 1, $show_fields, $document_type, $line_id, $client_type, $edit, $data );

			} else {
				// $price = $this->item_price( $client_type, $prod_id, 0, 0 );
				//                        print "prod_id = " . $prod_id . "price = " . $price . "<br/>";
				//				$data         .= $this->delivery_line( $prod_id, $quantity_ordered * $quantity, "", 0,
//					$price, round( $price * get_vat_percent( $prod_id ), 2 ), $prod_id, false, "" );

				$line = array();
				for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
					$line[ $i ] = "";
				}

				$line[ DeliveryFields::product_name ] = "===> " . get_product_name( $prod_id );
				$line[ DeliveryFields::price ]        = get_price_by_type( $prod_id, $client_type );
				$has_vat                              = true;

//				if ($P-> == 149) var_dump($P);
				if ( ! $P->getVatPercent() ) { // get_vat_percent( $prod_id ) == 0 ) {
//					print "has vat false<br/>";
					$has_vat = false;
				}
				$line[ DeliveryFields::product_id ] = $prod_id;
				$line[ DeliveryFields::has_vat ]    = gui_checkbox( "hvt_" . $prod_id, "has_vat", $has_vat > 0 );
				$line[ DeliveryFields::order_q ]    = $quantity_ordered;
				$line[ DeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number, "",
					array( 'onkeypress="moveNextRow(' . $this->line_number . ')"' ) );
				// $line[ DeliveryFields::line_vat]

				$this->line_number = $this->line_number + 1;
				$data              .= gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names );

				// $data .=- $this->delivery_line( $show_fields, $document_type, $line_id, $client_type, $edit );
			}
		}
		if ( $level == 0 ) {
			$line = array();
			for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
				$line[ $i ] = "";
			}
			$line[ DeliveryFields::product_name ] = gui_label( "ba", "הנחת סל" );
			// $line[DeliveryFields::has_vat] = gui_checkbox("", )
			$sums              = null;
			$data              .= gui_row( $line, "bsk" . $this->line_number, $show_fields, $sums, $delivery_fields_names );
			$this->line_number = $this->line_number + 1;


//			$data .= "<tr><td id='bsk_dis". $this->line_number .
//			         "'>הנחת סל</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
		}
	}

	public static function GuiCreateNewNoOrder() {
		$data = gui_table( array(
			array( "לקוח:", gui_select_client() ),
			array( "תאריך", gui_input_date( "delivery_date", "" ) ),
			array( gui_button( "btn_add_delivery", "", "הוסף תעודת משלוח" ) )
		) );

		return $data;
	}

	public static function CreateFromOrders( $order_ids ) {
		$order_id = array_shift( $order_ids );
		$instance = delivery::CreateFromOrder( $order_id );

		$instance->AdditionalOrders = $order_ids;

		return $instance;
	}

	public static function CreateFromOrder( $order_id ) {

		$id = get_delivery_id( $order_id );

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
			die ( __METHOD__ . " no ID" );
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
		global $conn;

		$sql = "SELECT date FROM im_delivery WHERE id = " . $this->ID;

		$result = $conn->query( $sql );

		if ( ! $result ) {
			print $sql;
			die ( "select error" );
		}

		$row = mysqli_fetch_assoc( $result );

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
			$option = 'MP';
		}

		return $option;
	}

	/**
	 * @return int
	 */
	public function getUserId() {
		if ( ! $this->user_id ) {
			$this->user_id = sql_query_single_scalar( "SELECT client_from_delivery(id) FROM im_delivery WHERE id = " . $this->user_id );
		}

		return $this->user_id;
	}

	// function expand_basket( $basket_id, $client_type, $quantity_ordered, &$data, $level ) {
	// Called when creating a delivery from an order.
	// After the basket line is shown, we print here the basket lines and basket discount line.

	function PrintDeliveries( $document_type, $operation, $margin = false ) {
		print $this->delivery_text( $document_type, $operation, $margin );
	}

	public function DeliveryFee() {
		$sql = 'SELECT fee FROM im_delivery WHERE id = ' . $this->ID;

		// print $sql;
		// my_log($sql);

		return sql_query_single_scalar( $sql);
	}
}

