<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/10/18
 * Time: 17:03
 */
//require_once( FRESH_INCLUDES . '/core/data/sql.php' );
//require_once( FRESH_INCLUDES . '/catalog/bundles.php' );
//require_once( FRESH_INCLUDES . "/catalog/Basket.php" );
//require_once( FRESH_INCLUDES . "/orders/orders-common.php" );
//require_once( FRESH_INCLUDES . "/routes/gui.php" );
//require_once( FRESH_INCLUDES . "/core/wp.php" );

class Fresh_Order {
	private $order_id = 0;
	private $WC_Order = null;
	private $mission_id = 0;
	private $customer_id = 0;
	private $comments = null;

	// Load order
	public function __construct( $id ) {
		if ( ! is_numeric( $id ) ) die ("bad order id $id");
		$this->order_id = $id;
		$this->WC_Order = new WC_Order( $id );

		$this->mission_id = get_post_meta( $id, 'mission_id', true );;
	}

	public function getProducts()
	{
		$sql = "select * from wp_woocommerce_order_items where order_id = " . $this->order_id . " and order_item_type = 'line_item'";

		$sql_result = SqlQuery($sql);
		$rows = [];
		while ($row = SqlFetchAssoc($sql_result))
		{
			$item_id = $row['order_item_id'];
			$prod_id = Fresh_Packing::get_order_itemmeta($item_id, '_product_id');
			$quantity = Fresh_Packing::get_order_itemmeta($item_id, '_qty');
//			print "$prod_id $quantity<br/>";
			array_push($rows, array(
				'prod_id' => $prod_id,
				'quantity' => $quantity
			));
			$P = new Fresh_Product($prod_id);
			if ($P->is_basket())
			{
				foreach(Fresh_Basket::get_basket_content_array($prod_id, $item_id) as $prod_id => $quantity)
					array_push($rows, array(
						'prod_id' => $prod_id,
						'quantity' => $quantity
					));
			}
		}
		return $rows;
	}

	public function getMission()
	{
		return $this->mission_id;
	}

	public function getShipping(  ) {
		$order         = wc_get_order( $this->order_id );
		if ( ! $order ) {
			return 0;
		}
		foreach ( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ) {
			// if (get_class($shipping_item_obj) == "WC_Order_Item_Shipping") {
			// var_dump($shipping_item_obj); print "<br/>";
			$shipping_info = $shipping_item_obj['name'];

			return $shipping_info;
			//	break;
		}
		return null;
	}

	public function getShippingFee() : int
	{
		$sql2 = 'SELECT meta_value FROM `wp_woocommerce_order_itemmeta` WHERE order_item_id IN ( '
		        . 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE order_id = ' . $this->order_id
		        . ' AND order_item_type = \'shipping\' )  AND meta_key = \'cost\'; ';

		$result = SqlQuerySingleScalar( $sql2 );

		return ($result ? $result : 0);
	}

	// Create new
	static function CreateOrder(
		$user_id,
		$mission_id,
		$prods_and_quantity,
		$delivery_instance,
		$comments,
		$fee
	) {
		// Somehow functions_im.php doesn't apply.
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		remove_filter( 'woocommerce_stock_amount', 'filter_woocommerce_stock_amount', 10 );

		// Add a filter, that validates the quantity to be a float
		add_filter( 'woocommerce_stock_amount', 'floatval' );

		$debug    = false;
		$WC_Order = wc_create_order();

		// var_dump($order);
		$order_id = trim( str_replace( '#', '', $WC_Order->get_order_number() ) );
		if ( $debug ) {
			print $order_id . "<br/>";
		}

		$o           = new Fresh_Order( $order_id );
		$o->WC_Order = $WC_Order;
		// print "count: " . count($prods) . "<br/>";
		$extra_comments = "";

		$total = 0;

		if (! (($prods_and_quantity and count($prods_and_quantity)) or $delivery_instance)) {
			MyLog("empty order requested and refused");
			print "הזמנה ריקה לא נקלטה";
			return null;
		}

		// Handle products
		if ($prods_and_quantity and count($prods_and_quantity))
			for ( $i = 0; $i < count( $prods_and_quantity ); $i ++ ) {
				$prod_id = $prods_and_quantity[ $i ][0];
				$quantity = $prods_and_quantity[ $i ][1];

				if ( $prod_id > 0 ) {
					$total += $o->AddProduct( $prod_id, $quantity, false, $user_id);
				} else {
					print "פריט לא נמצא " . $prod_id . "<br/>";
					MyLog( "can't prod id for " . $prod_id );
				}
			}

		// Handle delivery method
		if ($delivery_instance){
			// Get a new instance of the WC_Order_Item_Shipping Object
			if (! $o->SetDeliveryMethod($delivery_instance, $fee))
				return null;
		}

		$comments .= "\n" . $extra_comments;

		$o->WC_Order->calculate_totals();

		$o->setCustomerId( $user_id );

		foreach (
			array(
				'billing_first_name',
				'billing_last_name',
				'billing_phone',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_postcode'
			)
			as $key
		) {
			$value = get_user_meta( $user_id, $key, true );
			$_key  = '_' . $key;
			update_post_meta( $order_id, $_key, $value );
		}

		// Set the mission id and comments.
		$o->setMissionID( $mission_id );

		$o->SetComments( $comments );

		$o->WC_Order->save();

		return $o;
	}

	function setMissionID( $mission_id ) {
		$this->mission_id = $mission_id;
		set_post_meta_field( $this->order_id, "mission_id", $this->mission_id );
	}

	function getStatus()
	{
		return SqlQuerySingleScalar( "select post_status from wp_posts where id = " . $this->order_id);
	}

	function AddProduct( $product_id, $quantity, $replace = false, $client_id = - 1, $unit = null, $type = null, $price = null ) {
		$debug = false;

		if ( $debug ) {
			print "pid= " . $product_id . " q= " . $quantity . "<br/>";
		}
		$total = 0;
		if ( ! ( $product_id > 0 ) ) {
			die( "no product id given." );
		}
		// If it's a new order we need to get the client_id. Otherwise get it from the order.
		if ( $client_id == - 1 ) {
			$client_id = $this->getCustomerId();
		}
		if ( $type ) {
			$customer_type = $type;
		} else {
			$customer_type = customer_type( $client_id );
		}

		MyLog( __METHOD__, __FILE__ );
		MyLog( "product = " . $product_id, __METHOD__ );
		if ( $replace and ( is_basket( $product_id ) ) ) {
			MyLog( "Add basket products " . $product_id );
			$sql = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $product_id;

			$result = SqlQuery( $sql );
			while ( $row = mysqli_fetch_row( $result ) ) {
				$prod_id = $row[0];
				$q       = $row[1];
				$total   += $this->AddProduct( $prod_id, $q * $quantity, true, $client_id, $customer_type );
			}
		} else {
			MyLog( __METHOD__ . ": adding product " . $product_id, __FILE__ );
			if ( 1 or ! self::user_dislike( $client_id, $product_id ) ) {
				$has_units = false;
				if ( $unit and strlen( $unit ) > 0 ) {
					$has_units = true;
					$q         = 1;
				} else {
					$q = $quantity;
				}
				// print  "<br/>" . get_product_name( $product_id ) . " " . $q . " ";
				$product = wc_get_product( $product_id );
				if ( $product ) {
					SqlQuery( "delete from im_need_orders where id = " . $this->order_id);
					//print "type: " . $customer_type . "<br/>";
					if (null === $price) {
						$price = Fresh_Pricing::get_price_by_type( $product_id, $customer_type, $quantity );
					}

					// print "price: " . $price . "<br/>";
					$product->set_price( $price );
					// print "xx" . $product . " " . $q . "<br/>";
					$oid = $this->WC_Order->add_product( $product, $q );

					// Remove the order from require products cache
					SqlQuery( "DELETE FROM im_need_orders WHERE order_id = " . $this->order_id );

					// print $oid . "<br/>";

					if ( $has_units ) {
						set_order_itemmeta( $oid, 'unit', array( $unit, $quantity ) );

						return $price; // Assume about 1 kg
					}

					return $price * $quantity;
				} else {
					print $product_id . " not found <br/>";
				}
			} else {
				print "client dislike " . get_product_name( $product_id ) . "<br/>";
			}
		}
	}

	public function removeFromBasket($item_id, $prod_id)
	{
		$current_removal = self::basketRemoved($item_id);

		if (! in_array($prod_id, $current_removal))
			array_push($current_removal, $prod_id);

		Fresh_Packing::set_order_itemmeta($item_id, 'basket_removed', serialize($current_removal));
		self::update_basket_comment($item_id);
	}

	public function addToBasket($item_id, $prod_id)
	{
		$current_added = self::basketAdded($item_id);

		if (! in_array($prod_id, $current_added))
			array_push($current_added, $prod_id);

		Fresh_Packing::set_order_itemmeta($item_id, 'basket_added', serialize($current_added));
		self::update_basket_comment($item_id);
	}

	public function update_basket_comment($item_id)
	{
		$comment = "";
		$current_removal = self::basketRemoved($item_id);
		if ($current_removal) {
			$comment .= "הוסרו: ";
			foreach ( $current_removal as $prod ) {
				$p       = new Fresh_Product( $prod );
				$comment .= $p->getName() . ", ";
			}
			$comment = trim( $comment, ", " ) . ".<br/>";
		}

		$current_added = self::basketAdded($item_id);
		if ($current_added) {
			$comment .= "הוספו: ";
			foreach ( $current_added as $prod ) {
				$p       = new Fresh_Product( $prod );
				$comment .= $p->getName() . ", ";
			}
			$comment = trim( $comment, ", " ) . ". ";
		}

		Fresh_Packing::set_order_itemmeta($item_id, "product_comment", EscapeString($comment));
	}

	static function basketRemoved($item_id)
	{
		$current_removal = unserialize(Fresh_Packing::get_order_itemmeta($item_id, "basket_removed"));
		if (! $current_removal) $current_removal = array();
		return $current_removal;
	}

	static function basketAdded($item_id)
	{
		$current_addon = unserialize(Fresh_Packing::get_order_itemmeta($item_id, "basket_added"));
		if (! $current_addon) $current_addon = array();
		return $current_addon;
	}

	/**
	 * @return mixed
	 */
	public function getCustomerId() {
		if ( ! $this->customer_id ) {
			$this->customer_id = get_post_meta( $this->order_id, "_customer_user", true );
		}

		return $this->customer_id;
	}

	/**
	 * @param mixed $customer_id
	 */
	public function setCustomerId( $customer_id ) {
		$this->customer_id = $customer_id;
		update_post_meta( $this->order_id, '_customer_user', $this->customer_id );
	}

	function SetDeliveryMethod( $method, $delivery_price ) {
//		$postcode = get_user_meta( $user_id, 'shipping_postcode', true );
//		$package  = array( 'destination' => array( 'country' => 'IL', 'state' => '', 'postcode' => $postcode ) );
//		$zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
//		if (! $zone){
//			print "Zone for package not found<br/>";
//			return false;
//		}
//		$methods  = $zone->get_shipping_methods();
//		if (! $methods or (count($methods) == 0)) {
//			print "No methods found for zone ". $zone->get_zone_name();
//			return false;
//		}
//		var_dump($method);
		$ship = new WC_Order_Item_Shipping($method);
		$ship->set_total($delivery_price);
		$rc = $this->WC_Order->add_item( $ship );
		if ($rc === false) return false;
		return true;
	}

	public static function NeedToOrder( &$needed_products ) {
		$result = array();
		self::CalculateNeeded( $needed_products );
		foreach ( $needed_products as $prod_id => $product ) {
			$p = new Fresh_Product( $prod_id );
			if ( $p->getStock() < $needed_products[ $prod_id ][0] ) {
				array_push( $result, $product );
			}
		}

		return $result;
	}

	public static function CalculateNeeded( &$needed_products, $user_id = 0, &$user_table = null) {
		/// print "user id " . $user_id . "<br/>";
		$debug_product = 0; // 141;
		if ( ! $user_id ) {
			if ( self::check_cache_validity() ) {
//				if (get_user_id() == 1) print "cache valid<br/>";
				$needed_products = array();

				$sql = " SELECT prod_id, need_q, need_u, prod_get_name(prod_id) FROM im_need ORDER BY 4 ";

				$result = SqlQuery( $sql );

				while ( $row = mysqli_fetch_row( $result ) ) {
					$prod_or_var = $row[0];
					$q           = $row[1];
					$u           = $row[2];

					$needed_products[ $prod_or_var ][0] = $q;
					$needed_products[ $prod_or_var ][1] = $u;
				}

				return $needed_products;
			}

//			print "cache not valid<br/>";
			// Cache not vaild.
			// Clean the im_need_orders, im_need table
			$sql = "truncate table im_need_orders";
			SqlQuery( $sql );

			$sql = "truncate table im_need";
			SqlQuery( $sql );
		}
		// Do the calculation
		$sql = "SELECT id, post_status FROM wp_posts " .
		       " WHERE (post_status LIKE '%wc-processing%' OR post_status = 'wc-awaiting-shipment')";

		if ( $user_id ) {
			$sql .= " and order_user(id) = " . $user_id;
		}

		// print $sql;
		$result = SqlQuery( $sql );

		// Loop open orders.
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$id     = $row["id"];
			$O = new Fresh_Order($id);
			if (is_array($user_table)) array_push($user_table, $O->getCustomerId());
//			print "handling order $id</br>";
			$status = $row["post_status"];
			$del_id = 0;
			// print "status = " . $status . "<br/>";

			if ( $status == 'wc-awaiting-shipment' ) {
				// print "ship<br/>";
				$del_id = SqlQuerySingleScalar( "SELECT id FROM im_delivery WHERE order_id = " . $id );
				// print "del id = " . $del_id . "<br/>";
			}

			//		print "order: " . $id . "<br/>";

			// Update im_need_orders table
			if ( ! $user_id ) {
				$sql1 = "INSERT INTO im_need_orders (order_id) VALUE (" . $id . ") ";
				SqlQuery( $sql1 );
			}

			$order       = new WC_Order( $id );
			$order_items = $order->get_items();

			foreach ( $order_items as $item ) {
				$prod_or_var = $item['product_id'];

				$variation = null;
				if ( isset( $item["variation_id"] ) && $item["variation_id"] > 0 ) {
					$prod_or_var = $item["variation_id"];
				}
				$qty  = $item['qty'];
				$unit = $item['unit'];

				if ( $unit ) {
					$unit_array = explode( ",", $unit );
					$unit_t     = $unit_array[0];
					$key        = array( $prod_or_var, $unit_t );
					$qty        = $unit_array[1];
				} else {
					$key = array( $prod_or_var, '' );
				}

				if ( $prod_or_var == $debug_product ) {
					print "<br/>" . get_product_name( $prod_or_var ) . " ";
				}
				// print "status: " . $status . "<br/>";
				if ( $status == 'wc-processing' ) {
					// print "prc" . $order->get_id() . "<br/>";
					if ( $debug_product == $prod_or_var ) {
						print "proccessing " . $qty . " ";
					}
					// Adds with bundle consideration
					Fresh_Order::AddProducts( $key, $qty, $needed_products, $item->get_id() );
				} else {
					// Check if order line supplied.
					$sql = "SELECT sum(quantity) FROM im_delivery_lines WHERE prod_id = " . $prod_or_var .
					       " AND quantity > 0 AND delivery_id = " . $del_id;

					// print $sql . "<br/>";

					$supplied = SqlQuerySingleScalar( $sql );
					// if ($prod_or_var == $debug_product) print " c= " . $c. " " ;
					// 	print get_product_name($prod_or_var) . " " . $supplied . " " . $qty . "<br/>";
					if ( round( $supplied, 1 ) < round( $qty, 1 ) ) {
						// print get_product_name($prod_or_var) . " " . $qty . " " . $needed_products[0] . "<br/>";
						// if ($prod_or_var == $debug_product) print " adding " . $qty;
						Fresh_Order::AddProducts( $key, $qty - $supplied, $needed_products );
					}
					// print $prod_or_var . " " . $c . "<br/>";
				}

				//   print $item['product_id'] . " " . $item['qty'] . "<br/>";
			}
		}
		// Update im_need table
		foreach ( $needed_products as $prod_or_var => $v1 ) {
			$q = 0;
			$u = 0;
			if ( isset( $needed_products[ $prod_or_var ][0] ) ) {
				$q = $needed_products[ $prod_or_var ][0];
			}
			if ( isset( $needed_products[ $prod_or_var ][1] ) ) {
				$u = $needed_products[ $prod_or_var ][1];
			}
			if ( ! $user_id ) {
				$sql = "INSERT INTO im_need (prod_id, need_q, need_u) " .
				       " VALUES (" . $prod_or_var . "," . $q . "," . $u . ")";

				SqlQuery( $sql );
			}
		}
	}

	private static function AddProducts( $prod_key, $qty, &$needed_products, $item_id = 0 ) {

		// var_dump($prod_key); print "<br/>";
		// Prod key is array(prod_id or var_id, unit)

		// Handle baskets recursively
		$prod_or_var = $prod_key[0];
		$p = new Fresh_Product($prod_or_var);
//		if ($item_id == 369) print "---------------------------" . $p->getName() . "<br/>";

		if ( $p->is_basket( $prod_or_var ) ) {
			// Add basket content.
			foreach (Fresh_Basket::get_basket_content_array( $prod_or_var,  $item_id ) as $basket_prod => $basket_q) {
				Fresh_Order::AddProducts( array( $basket_prod, '' ), $qty * $basket_q, $needed_products );
			}
			// Add also the basket
			if ( ! isset( $needed_products[ $prod_or_var ][ 0 ] ) ) $needed_products[ $prod_or_var ][ 0 ] = 0;
			$needed_products[$prod_or_var][0] += $qty;
		} else {
			$unit_key = '';
			// Handle single product:
			$unit_str = $prod_key[1];

			switch ( $unit_str ) {
				case 'קג':
				case '':
					$unit_key = 0;
					break;
				case 'יח':
					$unit_key = 1;
					break;
				default:
					print "error: new unit ignored - " . $unit_str;
			}

			// Check if this product
			if ( $p->is_bundle( ) ) {
				// print get_product_name($prod_or_var) . " is bundle " . "<br/>";
				$b = Fresh_Bundle::CreateFromBundleProd( $prod_or_var );
				$p = $b->GetProdId();
				if ( ! ( $p > 0 ) ) {
					print "bad prod id for $prod_or_var<br/>";

					return;
				}
				// print $b->getName() . "q=$qty bq=" . $b->GetQuantity() . "<br/>";
				$qty         = $qty * $b->GetQuantity();
				$prod_or_var = $p;
			}

			if ( ! isset( $needed_products[ $prod_or_var ][ $unit_key ] ) ) $needed_products[ $prod_or_var ][ $unit_key ] = 0;

//			print "QQQQ adding $qty to " . $prod_or_var . "<br/>";
//			print "before: " . $needed_products[ $prod_or_var ][ $unit_key ] . "<br/>";
			$needed_products[ $prod_or_var ][ $unit_key ] += $qty;
//			print "after: " . $needed_products[ $prod_or_var ][ $unit_key ] . "<br/>";
			//if ($key == 354) { print "array:"; var_dump($needed_products[$prod_or_var]); print "<br/>";}
		}
	}

	public function OrderDate( $f = "%d/%m/%Y" ) {
		$sql = "select DATE_FORMAT(post_date, '$f') from wp_posts where id = " . $this->order_id;

		// print $sql;
		return SqlQuerySingleScalar( $sql );
	}

	public function CustomerName() {
		$user = get_user_by( "id", $this->getCustomerId() );
		if ( $user ) {
			return $user->user_firstname . " " . $user->user_lastname;
		}

		return "לא נבחר לקוח";
	}

	static function GetAllComments(){
		$sql = "SELECT id FROM wp_posts " .
		       " WHERE (post_status LIKE '%wc-processing%' OR post_status = 'wc-awaiting-shipment')";

		$orders = SqlQueryArrayScalar($sql);

		$table = array(array("מספר הזמנה", "מזמין", "הערות"));

		$have_comments = false;
		foreach ($orders as $order)
		{
			$o = new Fresh_Order($order);
			if (strlen($o->GetComments())) {
				$have_comments = true;
				array_push( $table, array( $o->getLink( $o->order_id ), $o->CustomerName(), $o->GetComments() ) );
			}
		}
		if (! $have_comments) return null;
		return Core_Html::GuiHeader( 1, "הערות לקוח" ) .

		Core_Html::gui_table_args($table);
	}

	public function Missing() {
		$needed = array();

		$result = array();
		$this->CalculateNeeded( $needed, $this->getCustomerId() );

		// var_dump($needed); print "<br/>";
		foreach ( $needed as $id => $p ) {
			array_push($result, array(Core_Html::gui_checkbox("chk_" . $id, "product_checkbox") .  get_product_name($id), round($p[0], 1)));
			// $result .= get_product_name( $id ) . " " . round( $p[0], 1 ) . "<br/>";
			// if ($p[0]) $result .= "x" . $p[0] . "<br/>";
		}

		return gui_table($result);
	}

	public function SuppliersOnTheGo()
	{
		$needed = array();
		$suppliers = null;
		$this->CalculateNeeded( $needed, $this->getCustomerId() );
		foreach ( $needed as $prod_id => $p ) {
			$P = new Fresh_Product($prod_id);
			if ($s = $P->PendingSupplies()){
//				print "s:"; var_dump($s); print "<br/>";
				if (!$suppliers) $suppliers = array();
				foreach ($s as $supplies){
					if ($supplies[3] and ! $supplies[4] and ! in_array($supplies[2], $suppliers)){ // Self collect and not picked
//						print "order " . $this->order_id . " supplier " . get_supplier_name($supplies[2]) . "<br/>";
						array_push($suppliers, $supplies[2]);
					}
				}
			}
		}
		return $suppliers;
	}

	public function getItems() {
		return $this->WC_Order->get_items();
	}

	function delivered(&$message) {
		$message = "";
		$new_status = "wc-completed";

		$c = SqlQuerySingleScalar( "SELECT count(*) FROM wp_posts " .
		                           " WHERE id = " . $this->order_id .
		                           " AND post_excerpt LIKE '%משלוח המכולת%'" );

		if ( $c ) { // legacy
			$new_status = "wc-awaiting-document";
		} else {
			$d = Fresh_Delivery::CreateFromOrder( $this->order_id );
			if (! $d->getID()) {
				$message = "no delivery note";
				return false;
			}

			if ( $d->isDraft() ) {
				$message = "is draft";
				return false;
			}
		}

		// if order is legacy, create delivery.
		$this->setStatus( $new_status );

		$message = "delivered";
		return true;
	}

	function setStatus( $status, $ids = null ) {
		if ( $ids and is_array( $ids ) ) {
			$args = $ids;
		} else {
			$args = array( $this->order_id );
		}
		foreach ( $args as $id ) {
			$order = new WC_Order( $id );
			MyLog( __METHOD__, $id . " changed to " . $status );
			// var_dump($order);
			switch ( $status ) {
				case 'wc-completed':
					$order->payment_complete();
					$order->update_status( $status );
					break;
				default:
					$order->update_status( $status );
			}
			// var_dump($order);
		}

		return true;
	}

	function update_levels() {
		// OK. We supplied the order.
		// We check if delivered different from ordered and change the stock level.
		$order_items = $this->order->get_items();
		$d_id        = $this->getDeliveryId();
		foreach ( $order_items as $item ) {
			$prod_or_var  = $item['product_id'];
			$q_in_ordered = $item->get_quantity();
			$p            = new Fresh_Product( $prod_or_var );
			$q_supplied   = SqlQuery( "SELECT quantity FROM im_delivery_lines" .
			                          " WHERE prod_id = " . $prod_or_var .
			                          " AND delivery_id = " . $d_id );
			if ( $q_in_ordered != $q_supplied ) {
				MyLog( __METHOD__ . " change stock by " . ($q_supplied - $q_in_ordered) );
				$p->setStock( $q_supplied - $q_in_ordered );
			}
		}
	}

	public function getDeliveryId() {
		return SqlQuerySingleScalar( "SELECT id FROM im_delivery WHERE order_id = " . $this->order_id );
	}

	public function GetTotal() {
		$order_items = $this->WC_Order->get_items();
		$total       = 0;
		// print "cid= " . $this->CustomerId() . "<br/>";

		$client_type = get_user_meta( $this->getCustomerId(), '_client_type', true );

		// print "cty= " . $client_type . "<br/>";
		foreach ( $order_items as $item ) {
			$prod_or_var = $item['product_id'];
			$q           = $item->get_quantity();

			if ( $prod_or_var > 0 and $q > 0 and
			                          is_numeric( Fresh_Pricing::get_price_by_type( $prod_or_var, $client_type ) )
			) {
				$line = Fresh_Pricing::get_price_by_type( $prod_or_var, $client_type ) * $q;
				if ( is_numeric( $line ) ) {
					$total += $line;
				}
			}
		}

		return $total;
	}

	public function GetBuyTotal() {
		$order_items = $this->WC_Order->get_items();
		$total       = 0;

		foreach ( $order_items as $item ) {
			$prod_or_var = $item['product_id'];
			$q           = $item->get_quantity();

			$p = new Fresh_Product($prod_or_var);
			if ( is_numeric( $q ) and is_numeric( $p->getBuyPrice() ) ) {
				$total += $q * $p->getBuyPrice();
			}
		}

		return $total;
	}

	function checkInfoBox()
	{
		$result = Core_Html::gui_header(1, __("Order number") . ":" . $this->order_id) . "<br/>";
		$result .= __("Client") . ":" . $this->getOrderInfo( '_billing_first_name' ) . ' '
		                                                                      . $this->getOrderInfo( '_billing_last_name') . "<br/>";

		$result .= __("Client comments") . ":" . $this->GetComments() . "<br/>";
		return $result;

//		$row_data = [];
//		$row_data["header"] = array_assoc(array("client_name" => "Client name", "order_id" => "Order number", "comments"=>"Comments"));
//		$row_data[$this->order_id] = array("value" =>,
//			"comments" => $this->GetComments());
////		$row_data[$this->order_id] = array("info" =>  ));
//
//		$args = [];
////		$args = ["transpose" => true, "include_id" => true];
//		return gui_table_args($row_data, "order_" . $this->order_id . "_info", $args);
//		$data      = "<table><tr><td rowspan='4'>";
//		$data      .= '<table>';
//		$client_id = $this->getCustomerId();
//		// Client info
//		$user_edit = "../";
//		$row_text  = '<tr><td>לקוח:</td><td>' . Core_Html::GuiHyperlink( , $user_edit ) . '</td><tr>';
//		$data      .= $row_text;
//		$data      .= '<tr><td>טלפון:</td><td>' . $this->getOrderInfo( '_billing_phone' ) . '</td><tr>';
//		$data      .= '<tr><td>הוזמן:</td><td>' . $this->GetOrderDate() . '</td><tr>';
//
//		// Shipping info
//		$row_text = '<tr><td>משלוח:</td><td>' . $this->getOrderInfo( '_shipping_first_name' ) . ' '
//		            . $this->getOrderInfo( '_shipping_last_name' ) . '</td><tr>';
//		$data     .= $row_text;
////	$row_text = '<tr><td>כתובת:</td><td>' . order_info( $order_id, '_shipping_address_1' ) . ' '
////	            . order_info( $order_id, '_shipping_address_2' ) . '</td><tr>';
////	$data     .= $row_text;
//
//		//		$row_text = '<tr><td>כתובת:</td><td>' .
//		$row_text .= $this->info_right_box_input( "shipping_city", $edit, "עיר" );
//		$row_text .= $this->info_right_box_input( "shipping_address_1", $edit, "רחוב ומספר" );
//		$row_text .= $this->info_right_box_input( "shipping_address_2", $edit, "כניסה, קוד אינטרקום, קומה ומספר דירה" );
//		$row_text .= gui_row(array("מזהה לקוח", get_user_meta( $client_id, 'invoice_id', 1)));
//		$row_text .= $this->user_info_right_box_input( "preference", $edit, "העדפות לקוח" );
//		$data     .= $row_text;
//
//
//		$rows = array("client", )
	}
	function infoBox( $edit_order = false, $operation = null, $addition_orders = null ) {
		if ( ! $this->WC_Order ) {
			throw new Exception( "no WC_Order" );
		}

		global $logo_url;
		$header = "";
		if ( $operation ) {
			$header .= $operation;
		}
		$header .= " מספר הזמנה " . $this->order_id;
		if ( $addition_orders ) {
			$header .= CommaImplode( $addition_orders );
		}
		$data = Core_Html::GuiHeader( 1, $header, true );
		// $data  .= Core_Html::gui_header( 2, $order->order_date, true);

		$d_id = self::get_delivery_id( $this->order_id );
		if ( $d_id > 0 ) {
			$d          = new Fresh_Delivery( $d_id );
			$draft_text = "";
			if ( $d->isDraft() ) {
				$draft_text = " טיוטא " . $d->draftReason();
			}

			$data .= Core_Html::gui_header( 2, "משלוח מספר " . $d_id . $draft_text );
		}
		$data     .= $this->infoRightBox( $edit_order );
		$data     .= "</td>";
		$data     .= '<tr><td><img src=' . $logo_url . ' height="100"></td></tr>';
		$data     .= "<td height='16'>" . Core_Html::GuiHeader( 2, "הערות לקוח להזמנה" ) . "</td></tr>";
		$excerpt  = $this->GetComments();
// TODO: find why save excerpt cause window reload
		if ( $edit_order ) {
			$data .= Core_Html::gui_cell( Core_Html::gui_textarea( "order_excerpt", htmlspecialchars( $excerpt ) ) );
			$data .= Core_Html::gui_cell( Core_Html::GuiButton( "btn_save_excerpt","שמור הערה", array("action"=> "save_excerpt(" . $this->order_id . ")") ));
		} else {
			$data .= "<tr><td valign='top'>" . nl2br( $excerpt ) . "</td></tr>";

		}
		if ( true or get_delivery_id( $this->order_id ) > 0 ) { // Done
			$data .= "<tr></tr>";
			$data .= "<tr></tr>";
		} else {
			$days = get_postmeta_field( $this->order_id, "pack_day" );
			if ( strlen( $days ) > 1 ) {
				$data .= "<tr><td>" . Core_Html::gui_header( 2, "יום ביצוע" . $days ) . "</td></tr>";
			} else {
				$options = array( array( "id" => 1, "name" => 'א' ), array( "id" => 2, "name" => 'ב' ) );
				$select  = gui_select( "day", "name", $options, "onchange=save_day()", null );
				$data    .= "<tr><td>" . $select . "</td></tr>";
			}
		}

		$data .= "</table>";
		print  $data;
	}

	function GetMissionId( $debug = false )
	{
		if ( ! is_numeric( $this->order_id ) ) {
			print "Bad order id: $this->order_id<br/>";
			die( 1 );
		}
		$mission = get_post_meta( $this->order_id, 'mission_id', true );
		if ( $debug ) {
			var_dump( $mission );
			print "<br/>";
		}
		if ( is_array( $mission ) ) {
			$mission_id = $mission[0];
		} else {
			$mission_id = $mission;
		}
		if ( ! is_numeric( $mission_id ) ) {
			return 0;
		}

		return $mission_id;
	}

	static function get_delivery_id( $order_id_or_array ) {
		$order_id = 0;

		if ( is_array( $order_id_or_array ) ) {
			$order_id = $order_id_or_array[0];
		} else if ( is_numeric( $order_id_or_array ) ) {
			$order_id = $order_id_or_array;
		}
		if ( is_numeric( $order_id ) ) {
			return SqlQuerySingleScalar( 'SELECT id FROM im_delivery WHERE order_id = ' . $order_id );
		}

		print "Must send a number to get_delivery_id!";

		return 0;
	}

	function infoRightBox( $edit = false ) {
		if ( ! $this->WC_Order ) {
			return new Exception( "no WC_Order" );
		}

		$data      = "<table><tr><td rowspan='4'>";
		$data      .= '<table>';
		$client_id = $this->getCustomerId();
		// Client info
		$user_edit = "../";
		$row_text  = '<tr><td>לקוח:</td><td>' . Core_Html::GuiHyperlink( $this->getOrderInfo( '_billing_first_name' ) . ' '
		                                                       . $this->getOrderInfo( '_billing_last_name' ), $user_edit ) . '</td><tr>';
		$data      .= $row_text;
		$data      .= '<tr><td>טלפון:</td><td>' . $this->getOrderInfo( '_billing_phone' ) . '</td><tr>';
		$data      .= '<tr><td>הוזמן:</td><td>' . $this->GetOrderDate() . '</td><tr>';

		// Shipping info
		$row_text = '<tr><td>משלוח:</td><td>' . $this->getOrderInfo( '_shipping_first_name' ) . ' '
		            . $this->getOrderInfo( '_shipping_last_name' ) . '</td><tr>';
		$data     .= $row_text;
//	$row_text = '<tr><td>כתובת:</td><td>' . order_info( $order_id, '_shipping_address_1' ) . ' '
//	            . order_info( $order_id, '_shipping_address_2' ) . '</td><tr>';
//	$data     .= $row_text;

		//		$row_text = '<tr><td>כתובת:</td><td>' .
		$row_text .= $this->info_right_box_input( "shipping_city", $edit, "עיר" );
		$row_text .= $this->info_right_box_input( "shipping_address_1", $edit, "רחוב ומספר" );
		$row_text .= $this->info_right_box_input( "shipping_address_2", $edit, "כניסה, קוד אינטרקום, קומה ומספר דירה" );
		$row_text .= Core_Html::gui_row(array("מזהה לקוח", get_user_meta( $client_id, 'invoice_id', 1)));
		$row_text .= $this->user_info_right_box_input( "preference", $edit, "העדפות לקוח" );
		$data     .= $row_text;

//		 = "";
//		$wp_pref    = get_user_meta( $client_id, "preference", 1 );
//		if ( $wp_pref ) {
//			foreach ( $wp_pref as $pref ) {
//				$preference .= $pref;
//			}
//		}

//	$data .= gui_row(array("משימה:", order_get_mission_name($order_id)));
//		./ata .= gui_row( array( "העדפות לקוח:", $preference ) );

//	$data .= gui_row( array( "איזור משלוח ברירת מחדל:", get_user_meta( $client_id, 'shipping_zone', true ) ) );

		// $zone = order_get_zone( $this->order_id );
//    $data .= $zone;

		// Todo: check if it's the catch all zone
//		if ( $zone == 0 ) {
//			$postcode  = get_postmeta_field( $this->order_id, '_shipping_postcode' );
//			$zone_name = "אנא הוסף מיקוד " . $postcode . " לאזור המתאים ";
//		} else {
//			$zone_name = zone_get_name( $zone );
//		}

//	$data    .= gui_row( array(
//		"איזור משלוח:",
//		$zone_name,
//		"ימים: ",
//		sql_query_single_scalar( "SELECT delivery_days FROM wp_woocommerce_shipping_zones WHERE zone_id =" . $zone )
//	) );
		$mission = $this->GetMissionId();
//	 print "XCXmission: " . $mission . "<br/>";
		$data .= Core_Html::gui_row( array( Fresh_Packing::gui_select_mission( "mission_select", $mission, "onchange=\"save_mission()\"" ) ) );

		$data .= '</table>';

		return $data;
	}

	function getOrderInfo( $field_name ) {
		$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
		       . ' WHERE pm.post_id = ' . $this->order_id
		       . ' AND (`meta_key` = \'' . $field_name . '\'' .
		       ' or meta_key = ' . QuoteText( '_' . $field_name ) . ')';

		$value = SqlQuerySingleScalar( $sql );

		// print $field_name . " " . $value . "<br/>";
		return $value;
	}

	function GetOrderDate() {
		if ( ! $this->WC_Order ) {
			throw new Exception( "no WC_Order" );
		}

		return $this->WC_Order->order_date;
	}

	function info_right_box_input( $field, $edit, $title ) {
		$data = array( $title );
		if ( $edit ) {
			array_push( $data, Core_Html::gui_input( $field, $this->getOrderInfo( '_' . $field ),
				"onchange=\"update_address('" . $field . "', " . $this->getCustomerId() . ", " . $this->GetID() . ")\"" ) );
		} else {
			array_push( $data, $this->getOrderInfo( $field ) );
		}

		return Core_Html::gui_row( $data );
	}

	public function GetID() {
		return $this->order_id;
	}

	function user_info_right_box_input( $field, $edit, $title ) {
		$data = array( $title );
		if ( $edit ) {
			array_push( $data, Core_Html::gui_input( $field, get_user_meta( $this->getCustomerId(), "preference", 1 ),
				"onchange=\"update_preference(" . $this->getCustomerId() . ")\"" ) );
		} else {
			array_push( $data, get_user_meta( $this->getCustomerId(), "preference", 1 ) );
		}

		return Core_Html::gui_row( $data );
	}

	function GetComments() {

		if ( $this->comments ) {
			return $this->comments;
		}

		$sql = "SELECT post_excerpt FROM wp_posts WHERE id=" . $this->order_id;

		$this->comments = SqlQuerySingleScalar( $sql );

		return $this->comments;
	}

	function SetComments( $comments ) {
		$this->comments = $comments;
		$sql            = "UPDATE wp_posts SET post_excerpt = '" . $this->comments . "' WHERE id=" . $this->order_id;
		SqlQuery( $sql );
	}

	function getAddress() {
		return $this->getOrderInfo( 'shipping_city' ) . " " . $this->getOrderInfo( 'shipping_address_1' ) .
		       " " . $this->getOrderInfo( 'shipping_address_2' );
	}

	function quantity_in_order( $order_item_id ) {
// Get and display item quantity
		if ( is_numeric( $order_item_id ) ) {
			$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
			        . ' WHERE order_item_id = ' . $order_item_id
			        . ' AND `meta_key` = \'_qty\''
			        . ' ';

			return SqlQuerySingleScalar( $sql2 );
		}

		return 0;
	}

	function DeleteLines( $lines ) {
//		print "order_delete_lines<br/>";
		foreach ( $lines as $line ) {
//			print $line;
			if (! wc_delete_order_item( $line )) return false;
		}
		$sql = "delete from im_need_orders where order_id = " . $this->order_id;
//		print $sql;
		SqlQuery( $sql);
		return true;
	}

	function PrintHtml( $selectable = false ) {
		$fields = array();

		if ( $selectable ) array_push( $fields, Core_Html::gui_checkbox( "chk" . $this->order_id, "deliveries", true ) );

		array_push( $fields, Core_Db_MultiSite::LocalSiteName() );

		$client_id     = $this->getCustomerId();
		$client = new Fresh_Client($client_id);
		$ref           = $this->getLink($this->order_id);
		$address       = self::getAddress();
		$receiver_name = GetMetaField( $this->order_id, '_shipping_first_name' ) . " " .
		                 GetMetaField( $this->order_id, '_shipping_last_name' );
		$shipping2     = GetMetaField( $this->order_id, '_shipping_address_2', true );

		array_push( $fields, $ref );

		array_push( $fields, $client_id );

		array_push( $fields, $receiver_name );

		array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

		array_push( $fields, $shipping2 );

		array_push( $fields, get_user_meta( $client_id, 'billing_phone', true ) );
		$payment_method = $client->get_payment_method_name( );
		if ( $payment_method <> "מזומן" and $payment_method <> "המחאה" ) $payment_method = "";
		array_push( $fields, $payment_method );

		array_push( $fields, self::getMission() );

		array_push( $fields, Core_Db_MultiSite::LocalSiteID() );

		return  "<tr> " . self::delivery_table_line( 1, $fields ) . "</tr>";
	}

	static function delivery_table_line( $ref, $fields, $edit = false ) {
		$row_text = "";
		if ( $edit ) $row_text = gui_cell( Core_Html::gui_checkbox( "chk_" . $ref, "", "", null ) );

		foreach ( $fields as $field ) // display customer name
		{
			$row_text .= Core_Html::gui_cell( $field );
		}

		return $row_text;
	}

	function OrdersTable($args)
	{
		$result = "";
		$args["id_field"] = "ID";
		$args["query"] = "post_type = 'shop_order' and post_status in ('wc-awaiting-shipment', 'wc-processing')";
		$args["order"] = " ID desc";
		$args["links"] = array("ID" => AddToUrl("row_id", "%s"));
		$args["fields"] = array("ID", "post_date", "post_status" );

		$result .= Core_Gem::GemTable("wp_posts", $args);

		return $result;
	}

	function handle_order_operation($operation)
	{
		$page = GetParam("page", false, 0);
		$args = [];
		if ($page) $args["page"] = $page;
		switch ($operation){
//		case "show_new_order":
//			print
//			break;

			case "show_orders":
				print OrdersTable($args);
				break;

			case "paper_order":
				$args = [];
				$args["print_logo"] = false;
				print HeaderText($args);
				print Core_Html::gui_header(2, "הזמנה/אספקה שבועית");
				$last_delivery = SqlQuerySingleScalar("select max(delivery_id) from im_delivery_lines");
				$sql = "select distinct prod_id, sum(quantity_ordered) from im_delivery_lines where delivery_id > $last_delivery - 20 group by prod_id order by 2 desc limit 38";
				$prods = SqlQueryArrayScalar($sql);

				$data = [];
				// array_push($data, array("מוצר", "כמות"));
				foreach ($prods as $prod) {
					if(! is_basket($prod) and ! is_bundle($prod)) {
						$prod_name = get_product_name($prod, true);
						array_push($data, $prod_name);
					}
				}

				sort($data);

				$table_lines = array();
				$table_lines["header"] = array("מוצר", "כמות", "מוצר", "כמות");
				$line_number = round((count($prods))/ 2, 0);
//			print count($data) . "<br/>" . $line_number . "<br/>";

				for ($i = 0; $i < $line_number; $i++){
					$space = '';
					$new_line = array($data[$i], $space, isset($data[$i + $line_number]) ? $data[$i + $line_number] : "", $space);
					$table_lines[$i] = $new_line;
				}
				$args = [];
				$args["width"] = '100%';
				$args["col_width"] = array('25%', '25%', '25%', '25%');
				print gui_table_args($table_lines, "products", $args);

				break;

			default:
				print __FUNCTION__ . " " . $operation . " not handled<br/>";
		}
	}

	static function handle_operation($operation)
	{
		if (substr($operation, 0, 6) == "order_")
			$operation = substr($operation, 6);

		switch ( $operation ) {
			case "update_address":
				$address   = GetParam( "address", true );
				$field     = GetParam( "f", true );
				$client_id = GetParam( "client_id", true );
				update_usermeta( $client_id, $field, $address );

				$order_id = GetParam( "order_id" );

				if ( $order_id ) {
					update_post_meta( $order_id, "_" . $field, $address );
				}
				$o = new Order( $order_id );

				print " המידע עודכן" . "<br/>" . get_user_address( $client_id, true ) . "<br/>" .
				      $o->getAddress();
				break;

			case "update_preference":
				$preference = GetParam( "preference", true );
				$client_id  = GetParam( "client_id", true );
				update_usermeta( $client_id, "preference", $preference );
				break;

			case "get_rate":
				$user_id = $_GET["id"];
				print customer_type_name( $user_id );
				break;

			case "get_client_info":
				$user_id = $_GET["id"];
				print customer_type_name( $user_id );
				print "\n";
				print get_user_address( $user_id );
				break;

			case "save_order_excerpt":
				$excerpt  = $_GET["excerpt"];
				$order_id = $_GET["order_id"];
				$Order    = new Order( $order_id );
				$Order->SetComments( $excerpt );
				break;

			case "create_order":
				$user_id    = $_GET["user_id"];
				$prods      = $_GET["prods"];
				$quantities = $_GET["quantities"];
				$comments   = $_GET["comments"];
				$units      = $_GET["units"];
				$mission_id = $_GET["mission_id"];
				$type       = null;
				if ( isset( $_GET["type"] ) ) {
					$type = $_GET["type"];
				}

				// print header_text();
				// print "creating order for " . get_user_name( $user_id );
//		print "pos: " . $pos . "<br/>";
				if (!$prods){
					MyLog("empty order requested and refused");
					print "הזמנה ריקה לא נקלטה";
					return null;

				}
				$o = Order::CreateOrder( $user_id, $mission_id, explode( ",", $prods ),
					explode( ",", $quantities ), $comments, explode( ",", $units ), $type );

				print "הזמנה " . $o->GetID() . " נקלטה בהצלחה.";

				break;

			case "add_item":
				$prod_id = GetParam("prod_id", true);
				$q = $_GET["quantity"];
				if ( ! is_numeric( $q ) ) {
					die ( "no quantity" );
				}
				$units = null;
				if ( isset ( $_GET["units"] ) ) {
					$units = $_GET["units"];
				}
				$order_id = $_GET["order_id"];
				if ( ! is_numeric( $order_id ) ) {
					die ( "no order_id" );
				}

				if ( ! is_numeric( $prod_id ) ) {
					die ( "invalid product id" );
				}
				$o = new Order( $order_id );
				$o->AddProduct( $prod_id, $q, false, - 1, $units );
				break;

			case "delete_lines":
				$order_id = $_GET["order_id"];
				if ( ! is_numeric( $order_id ) ) {
					die ( "no order_id" );
				}
				$lines = GetParamArray( "params" );
				// var_dump($lines);
				$o     = new Order( $order_id );
				$o->DeleteLines($lines);
				break;

			case "start_handle":
				$ids = GetParamArray( "ids" );
				foreach ( $ids as $id ) {
					$o = new Fresh_Order( $id );
					if (! $o->setStatus( "wc-processing" )) return false;
				}
				return true;

			case "cancel_orders":
				$ids = GetParamArray( "ids" );
				foreach ( $ids as $id ) {
					$o = new Order( $id );
					$o->ChangeStatus( "wc-cancelled" );
				}
				break;

			case "delivered":
				$ids = $_GET["ids"];
				foreach ( explode( ",", $ids ) as $id ) {
					$o = new Order( $id );
					$message ="";
					$o->delivered($message);
				}
				print $message;
				break;

			case "mission":
//		print ( "change mission" );
				$mission_id = $_GET["id"];
				$order_id   = $_GET["order_id"];
				MyLog( "mission=" . $mission_id . " order_id=" . $order_id );
				$o = new Order( $order_id );
				$o->setMissionID( $mission_id );
				break;

			case "replace_baskets":
				replace_baskets();
				break;

			case "check_delivery":
				$email = GetParam( "email" );
				if ( ! $email or strlen( $email ) < 5 ) {
					print "u"; // unknown

					return;
				}
				$u       = get_user_by( "email", $email );
				if ( $u ) {
					$user_id = $u->ID;
					print customer_delivery_options( $user_id );
				} else {
					print "אין לקוח כתובת מייל זאת";
				}
				break;

			case "check_waiting_count":
				$count =  SqlQuerySingleScalar("select count(*) from wp_posts where post_status = 'wc-awaiting-shipment'");

				print $count;
				break;

			case "set_mission":
				$order_id = GetParam("order_id", true);
				$mission_id = GetParam("mission_id", true);
				$order = new Fresh_Order($order_id);
				$order->setMissionID($mission_id);
				return true;


			default:
				// die("operation " . $operation . " not handled<br/>");
		}

	}
	function order_page_by_term()
	{
		for ($i = 0; $i < count($prods); $i++){
			$prod_id =$prods[$i];
			$p = new Fresh_Product($prod_id);
			if ($p and $p->getTerms()) {
				$term = $p->getTerms()[0]->term_id;
				if (! isset($prods_by_term[$term])) $prods_by_term[$term] = [];
				array_push($prods_by_term[$term], $prod_id);
			}
		}

		$data = [];
		// array_push($data, array("מוצר", "כמות"));
		foreach ($prods_by_term as $term => $prods_per_term) {
			$temp_table = [];
			foreach ($prods_per_term as $prod_id)
				if (!is_basket($prod_id)) array_push($temp_table, get_product_name($prod_id));
			if (count($temp_table)){
				sort ($temp_table);
				array_push($data, Core_Html::gui_header(3, get_term_name($term)));

				foreach ($temp_table as $prod_name)
					array_push($data, array($prod_name, ""));
			}
			// print get_product_name($prod_id);
		}

		$table_lines = array();
		$table_lines["header"] = array("מוצר", "כמות", "מוצר", "כמות");
//			print gui_table_args($data);
		$line_number = round((count($prods) + 1)/ 2, 0);

//		print count($prods) . '<br/>';
//		print $line_number . '<br/>';
		for ($i = 0; $i < $line_number + 1; $i++){
			$new_line = array($data[$i], "", $data[$i + $line_number], "");
//				$line = array(get_product_name($prods[$i]), "", ($i < count($prods) -1 ? get_product_name($prods[$i+1]) : ""), "");
			$table_lines[$i] = $new_line;
		}
		$args = [];
		print gui_table_args($table_lines, "products", $args);

	}

	static function get_status_name($status)
	{
		$status_names = wc_get_order_statuses();

		if (isset($status_names[ $status ])) return $status_names[$status];
		else return $status;
	}

	static function check_cache_validity() {
		return false;
//	$sql = "SELECT count(p.id)
//	FROM wp_posts p
//	 LEFT JOIN im_need_orders o
//	  ON p.id = o.order_id
//	  WHERE p.id IS NULL OR o.order_id IS NULL AND post_status LIKE '%wc-processing%'";
		$sql = "SELECT count(id)
	FROM wp_posts p
  	where post_status like '%wc-processing%'
  	and id not in (select order_id from im_need_orders)";
		$new = SqlQuerySingleScalar( $sql );
//	print "new: " . $new . "<br/>";

		$sql  = "SELECT count(id)
	  FROM im_need_orders
	  WHERE order_id NOT IN (SELECT id FROM wp_posts WHERE post_status LIKE '%wc-processing%')";
		$done = SqlQuerySingleScalar( $sql );
//	print "done: " . $done . "<br/>";

		if ( $done > 0 or $new > 0 ) {
			return false;
		}

		return true;
	}

	function getLink($text = null)
	{
		if (! $text) $text = __("Order number") . " " . $this->order_id;
		return Core_Html::GuiHyperlink($text, "/wp-admin/post.php?post=". $this->order_id . "&action=edit");
	}

	function getCustomerType()
	{
		print $this->customer_id;
		return get_user_meta( $this->customer_id, "_client_type", true );

	}

	static function get_minimum_order($customer_id) {
		$value = 0; // No min.

		$customer = new Fresh_Client($customer_id);

		$customer_zone = $customer->getZone();

		$sql    = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $customer_zone->get_id();
		$result = SqlQuery( $sql, false );
		if ( $result ) {
			$row = mysqli_fetch_assoc( $result );
			//    my_log($row["min_order"]);

			if ( is_numeric( $row["min_order"] ) ) {
				$value = $row["min_order"];
			}
		}

		return $value;
	}
}

