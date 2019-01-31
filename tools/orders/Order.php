<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/10/18
 * Time: 17:03
 */
require_once( ROOT_DIR . '/niver/data/sql.php' );
require_once( TOOLS_DIR . '/catalog/bundles.php' );
require_once( ROOT_DIR . "/tools/catalog/Basket.php" );
require_once( ROOT_DIR . "/tools/orders/orders-common.php" );


class Order {
	private $order_id = 0;
	private $WC_Order = null;
	private $mission_id = 0;
	private $customer_id = 0;
	private $comments = null;

	// Load order
	public function __construct( $id ) {
		$this->order_id = $id;
		$this->WC_Order = new WC_Order( $id );
	}

	// Create new
	static function CreateOrder(
		$user_id, $mission_id, $prods, $quantities, $comments, $units = null, $type = null,
		$method = null
	) {
		$debug    = false;
		$WC_Order = wc_create_order();
		// var_dump($order);
		$order_id = trim( str_replace( '#', '', $WC_Order->get_order_number() ) );
		if ( $debug ) {
			print $order_id . "<br/>";
		}

		$o           = new Order( $order_id );
		$o->WC_Order = $WC_Order;
		// print "count: " . count($prods) . "<br/>";
		$extra_comments = "";

		$o->SetMissionID( $mission_id );

		$total = 0;

		for ( $i = 0; $i < count( $prods ); $i ++ ) {
			$prod_id = $prods[ $i ];

			if ( $prod_id > 0 ) {
				$total += $o->AddProduct( $prod_id, $quantities[ $i ], false, $user_id, $units[ $i ], $type );
			} else {
				print "פריט לא נמצא " . $prods[ $i ] . "<br/>";
				my_log( "can't prod id for " . $prods[ $i ] );
			}
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
//		    print $key . "<br/>";
			$value = get_user_meta( $user_id, $key, true );
			$_key  = '_' . $key;
//		print $_key . " " . $value . "<br/>";
			update_post_meta( $order_id, $_key, $value );
		}

		if ( $method ) {
			$o->SetDeliveryMethod( $method );
		}

// 	print "comments: " . $comments . "<br/>";
		$o->WC_Order->save();

		//		$order = null;

		$o->SetComments( $comments );

		return $o;
	}

	function SetMissionID( $mission_id ) {
		$this->mission_id = $mission_id;
		set_post_meta_field( $this->order_id, "mission_id", $this->mission_id );
	}

	function AddProduct( $product_id, $quantity, $replace = false, $client_id = - 1, $unit = null, $type = null ) {
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

		my_log( __METHOD__, __FILE__ );
		my_log( "product = " . $product_id, __METHOD__ );
		if ( $replace and ( is_basket( $product_id ) ) ) {
			my_log( "Add basket products " . $product_id );
			$sql = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $product_id;

			$result = sql_query( $sql );
			while ( $row = mysqli_fetch_row( $result ) ) {
				$prod_id = $row[0];
				$q       = $row[1];
				$total   += $this->AddProduct( $prod_id, $q * $quantity, true, $client_id, $customer_type );
			}
		} else {
			my_log( __METHOD__ . ": adding product " . $product_id, __FILE__ );
			if ( ! user_dislike( $client_id, $product_id ) ) {
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
					//print "type: " . $customer_type . "<br/>";
					$price = get_price_by_type( $product_id, $customer_type, $quantity );
					// print "price: " . $price . "<br/>";
					$product->set_price( $price );
					// print "xx" . $product . " " . $q . "<br/>";
					$oid = $this->WC_Order->add_product( $product, $q );

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
		// Remove the order from require products cache
		sql_query( "DELETE FROM im_need_orders WHERE order_id = " . $this->order_id );

	}

	/**
	 * @return mixed
	 */
	public function getCustomerId() {
		if ( ! $this->customer_id ) {
			$this->customer_id = get_postmeta_field( $this->order_id, "_customer_user" );
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

	function SetDeliveryMethod( $method ) {
		$postcode = get_user_meta( $this->getCustomerId(), 'shipping_postcode', true );
		$package  = array( 'destination' => array( 'country' => 'IL', 'state' => '', 'postcode' => $postcode ) );
		$zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
		$methods  = $zone->get_shipping_methods();
		$m        = $methods[ $method ];
		// var_dump($m->instance_settings['cost']);
		//  print "m = " . $m->get_cost() . "<br/>";

		$si = new WC_Shipping_Rate( 'ss', $m->get_title(), $m->instance_settings['cost'], "ffoo", $method );
		// $m = new WC_Shipping_Method($method);
		$this->WC_Order->add_shipping( $si );
	}

	public function GetID() {
		return $this->order_id;
	}

	public function OrderDate( $f = "%d/%m/%Y" ) {
		$sql = "select DATE_FORMAT(post_date, '$f') from wp_posts where id = " . $this->order_id;

		// print $sql;
		return sql_query_single_scalar( $sql );
	}

	public function CustomerName() {
		$user = get_user_by( "id", $this->getCustomerId() );
		if ( $user ) {
			return $user->user_firstname . " " . $user->user_lastname;
		}

		return "לא נבחר לקוח";
	}

	public function Missing() {
		$needed = array();

		$result = "";
		$this->CalculateNeeded( $needed, $this->getCustomerId() );

		// var_dump($needed); print "<br/>";
		foreach ( $needed as $id => $p ) {
			$result .= get_product_name( $id ) . " " . round( $p[0], 1 ) . "<br/>";
			// if ($p[0]) $result .= "x" . $p[0] . "<br/>";
		}

		return $result;
	}

	public static function CalculateNeeded( &$needed_products, $user_id = 0 ) {
		// print "user id " . $user_id . "<br/>";
		$debug_product = 0; // 141;
		global $conn;
		if ( ! $user_id ) {
			if ( 0 and check_cache_validity() ) {
				print "cv</br>";
				$needed_products = array();

				$sql = " SELECT prod_id, need_q, need_u, prod_get_name(prod_id) FROM im_need ORDER BY 4 ";

				$result = sql_query( $sql );

				while ( $row = mysqli_fetch_row( $result ) ) {
					$prod_or_var = $row[0];
					$q           = $row[1];
					$u           = $row[2];

					$needed_products[ $prod_or_var ][0] = $q;
					$needed_products[ $prod_or_var ][1] = $u;
				}

				return $needed_products;
			}

			// print "not valid<br/>";
			// Cache not vaild.
			// Clean the im_need_orders, im_need table
			$sql = "truncate table im_need_orders";
			sql_query( $sql );

			$sql = "truncate table im_need";
			sql_query( $sql );
		}
		// Do the calculation
		$sql = "SELECT id, post_status FROM wp_posts " .
		       " WHERE (post_status LIKE '%wc-processing%' OR post_status = 'wc-awaiting-shipment') ";

		if ( $user_id ) {
			$sql .= " and order_user(id) = " . $user_id;
		}

		// print $sql;
		$result = mysqli_query( $conn, $sql );

		// Loop open orders.
		while ( $row = mysqli_fetch_assoc( $result ) ) {
			$id     = $row["id"];
			$status = $row["post_status"];
			$del_id = 0;
			// print "status = " . $status . "<br/>";

			if ( $status == 'wc-awaiting-shipment' ) {
				// print "ship<br/>";
				$del_id = sql_query_single_scalar( "SELECT id FROM im_delivery WHERE order_id = " . $id );
				// print "del id = " . $del_id . "<br/>";
			}
//		print "order: " . $id . "<br/>";

			// Update im_need_orders table
			if ( ! $user_id ) {
				$sql1 = "INSERT INTO im_need_orders (order_id) VALUE (" . $id . ") ";
				sql_query( $sql1 );
			}

			$order       = new WC_Order( $id );
			$order_items = $order->get_items();

			foreach ( $order_items as $item ) {
				$prod_or_var = $item['product_id'];
				$is_bundle   = is_bundle( $prod_or_var );

				$variation = null;
				if ( isset( $item["variation_id"] ) && $item["variation_id"] > 0 ) {
					$prod_or_var = $item["variation_id"];
				}
				// print get_product_name($prod_or_var) . " " . $prod_or_var . ":<br/>";
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
					Order::AddProducts( $key, $qty, $needed_products );
				} else {
					// Check if order line supplied.
					$sql = "SELECT sum(quantity) FROM im_delivery_lines WHERE prod_id = " . $prod_or_var .
					       " AND quantity > 0 AND delivery_id = " . $del_id;

					// print $sql . "<br/>";

					$supplied = sql_query_single_scalar( $sql );
					// if ($prod_or_var == $debug_product) print " c= " . $c. " " ;
					// 	print get_product_name($prod_or_var) . " " . $supplied . " " . $qty . "<br/>";
					if ( round( $supplied, 1 ) < round( $qty, 1 ) ) {
						// print get_product_name($prod_or_var) . " " . $qty . " " . $needed_products[0] . "<br/>";
						// if ($prod_or_var == $debug_product) print " adding " . $qty;
						Order::AddProducts( $key, $qty - $supplied, $needed_products );
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

				sql_query( $sql );
			}
		}
	}

	private static function AddProducts( $prod_key, $qty, &$needed_products ) {
		// var_dump($prod_key); print "<br/>";
		// Prod key is array(prod_id or var_id, unit)

		// Handle baskets recursively
		$prod_or_var = $prod_key[0];
		if ( is_basket( $prod_or_var ) ) {
//                print $prod_id . " is basket ";
			foreach (
				get_basket_content_array( $prod_or_var ) as $basket_prod =>
				$basket_q
			) {
				Order::AddProducts( array( $basket_prod, '' ), $qty * $basket_q, $needed_products );
			}
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
			if ( is_bundle( $prod_or_var ) ) {
				// print get_product_name($prod_or_var) . " is bundle " . "<br/>";
				$b = Bundle::CreateFromBundleProd( $prod_or_var );
				$p = $b->GetProdId();
				if ( ! ( $p > 0 ) ) {
					print "bad prod id for $prod_or_var<br/>";

					return;
				}
				$qty         = $qty * $b->GetQuantity();
				$prod_or_var = $p;
			}

			if ( ! isset( $needed_products[ $prod_or_var ][ $unit_key ] ) ) {
				$needed_products[ $prod_or_var ][ $unit_key ] = 0;
			}
			$needed_products[ $prod_or_var ][ $unit_key ] += $qty;
			//if ($key == 354) { print "array:"; var_dump($needed_products[$prod_or_var]); print "<br/>";}
		}
	}

	public function getItems() {
		return $this->WC_Order->get_items();
	}

	function delivered() {
		$new_status = "wc-completed";

		$c = sql_query_single_scalar( "SELECT count(*) FROM wp_posts " .
		                              " WHERE id = " . $this->order_id .
		                              " AND post_excerpt LIKE '%משלוח המכולת%'" );

		if ( $c ) { // legacy
			$new_status = "wc-awaiting-document";
		} else {
			$d = delivery::CreateFromOrder( $this->order_id );
			if ( $d->isDraft() ) {
				return "draft";
			}
		}

//		if (! $d->getID())
//			return "No delivery";

		// if order is legacy, create delivery.
		$this->ChangeStatus( $new_status );

		return true;
	}

	function ChangeStatus( $status, $ids = null ) {
		if ( $ids and is_array( $ids ) ) {
			$args = $ids;
		} else {
			$args = array( $this->order_id );
		}
		foreach ( $args as $id ) {
			$order = new WC_Order( $id );
			my_log( __METHOD__, $id . " changed to " . $status );
			// var_dump($order);
			switch ( $status ) {
				case 'wc-processing';
					$order->payment_complete();
					break;
				case 'wc-completed':
					$o = new Order( $id );
					$order->update_status( $status );
					// $o->update_levels();
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
			$p            = new Product( $prod_or_var );
			$q_supplied   = sql_query( "SELECT quantity FROM im_delivery_lines" .
			                           " WHERE prod_id = " . $prod_or_var .
			                           " AND delivery_id = " . $d_id );
			if ( $q_in_ordered != $q_supplied ) {
				my_log( __METHOD__ . " change stock by " . $q_supplied - $q_in_ordered );
				$p->setStock( $q_supplied - $q_in_ordered );
			}
		}
	}

	public function getDeliveryId() {
		return sql_query_single_scalar( "SELECT id FROM im_delivery WHERE order_id = " . $this->order_id );
	}

	public function GetTotal() {
		$order_items = $this->WC_Order->get_items();
		$total       = 0;
		// print "cid= " . $this->CustomerId() . "<br/>";

		$client_type = customer_type( $this->getCustomerId() );

		// print "cty= " . $client_type . "<br/>";
		foreach ( $order_items as $item ) {
			$prod_or_var = $item['product_id'];
			$q           = $item->get_quantity();

			if ( $prod_or_var > 0 and $q > 0 and
			                          is_numeric( get_price_by_type( $prod_or_var, $client_type ) )
			) {
				$line = get_price_by_type( $prod_or_var, $client_type ) * $q;
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
		// print "cid= " . $this->CustomerId() . "<br/>";

		//function order_good_costs( $order_id ) {
//	$order = new WC_Order( $order_id );
//	$total = 0;
//	foreach ( $order->get_items() as $item ) {
//		// if ($order_id == 2230) print $item->get_name() . "<br/>";
//		$q = $item->get_quantity();
//		$p = get_buy_price( $item->get_product_id() );
//		if (is_numeric($q) and is_numeric($p)) $total +=  $p * $q;
//	}
//
//	return $total;
//}

		// print "cty= " . $client_type . "<br/>";
		foreach ( $order_items as $item ) {
			$prod_or_var = $item['product_id'];
			$q           = $item->get_quantity();

			if ( is_numeric( $q ) and is_numeric( get_buy_price( $prod_or_var ) ) ) {
				$total += $q * get_buy_price( $prod_or_var );
			}
		}

		return $total;
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
			$header .= comma_implode( $addition_orders );
		}
		$data = gui_header( 1, $header, true );
		// $data  .= gui_header( 2, $order->order_date, true);

		$d_id = get_delivery_id( $this->order_id );
		if ( $d_id > 0 ) {
			$d          = new delivery( $d_id );
			$draft_text = "";
			if ( $d->isDraft() ) {
				$draft_text = " טיוטא " . $d->draftReason();
			}

			$data .= gui_header( 2, "משלוח מספר " . $d_id . $draft_text );
		}
		$data    .= $this->infoRightBox();
		$data    .= "</td>";
		$data    .= '<tr><td><img src=' . $logo_url . ' height="100"></td></tr>';
		$data    .= "<td height='16'>" . gui_header( 2, "הערות לקוח להזמנה" ) . "</td></tr>";
		$excerpt = $this->GetComments();
// TODO: find why save excerpt cause window reload
		if ( $edit_order ) {
			$data .= gui_cell( gui_textarea( "order_excerpt", htmlspecialchars( $excerpt ) ) );
			$data .= gui_cell( gui_button( "btn_save_excerpt", "save_excerpt(" . $this->order_id . ")", "שמור הערה" ) );
		} else {
			$data .= "<tr><td valign='top'>" . nl2br( $excerpt ) . "</td></tr>";

		}
		if ( true or get_delivery_id( $this->order_id ) > 0 ) { // Done
			$data .= "<tr></tr>";
			$data .= "<tr></tr>";
		} else {
			$days = get_postmeta_field( $this->order_id, "pack_day" );
			if ( strlen( $days ) > 1 ) {
				$data .= "<tr><td>" . gui_header( 2, "יום ביצוע" . $days ) . "</td></tr>";
			} else {
				$options = array( array( "id" => 1, "name" => 'א' ), array( "id" => 2, "name" => 'ב' ) );
				$select  = gui_select( "day", "name", $options, "onchange=save_day()", null );
				$data    .= "<tr><td>" . $select . "</td></tr>";
			}
		}

		$data .= "</table>";

		return $data;
	}

	function infoRightBox() {
		if ( ! $this->WC_Order ) {
			return new Exception( "no WC_Order" );
		}

		$data      = "<table><tr><td rowspan='4'>";
		$data      .= '<table>';
		$client_id = $this->getCustomerId();
		// Client info
		$user_edit = "../";
		$row_text  = '<tr><td>לקוח:</td><td>' . gui_hyperlink( $this->getOrderInfo( '_billing_first_name' ) . ' '
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
		$row_text = '<tr><td>כתובת:</td><td>' . $this->getOrderInfo( '_shipping_city' ) . ' ' .
		            $this->getOrderInfo( '_shipping_address_1' ) . ' ' .
		            $this->getOrderInfo( '_shipping_address_2' ) . ' ' .
		            '</td><tr>';
		$data     .= $row_text;

		$preference = "";
		$wp_pref    = get_user_meta( $client_id, "preference" );
		if ( $wp_pref ) {
			foreach ( $wp_pref as $pref ) {
				$preference .= $pref;
			}
		}

//	$data .= gui_row(array("משימה:", order_get_mission_name($order_id)));
		$data .= gui_row( array( "העדפות לקוח:", $preference ) );

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
		$mission = order_get_mission_id( $this->order_id );
//	 print "XCXmission: " . $mission . "<br/>";
		$data .= gui_row( array( gui_select_mission( "mission_select", $mission, "onchange=\"save_mission()\"" ) ) );

		$data .= '</table>';

		return $data;
	}

	function getOrderInfo( $field_name ) {
		$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
		       . ' WHERE pm.post_id = ' . $this->order_id
		       . ' AND `meta_key` = \'' . $field_name . '\'';

		return sql_query_single_scalar( $sql );
	}

	function GetOrderDate() {
		if ( ! $this->WC_Order ) {
			throw new Exception( "no WC_Order" );
		}

		return $this->WC_Order->order_date;
	}

	function GetComments() {

		if ( $this->comments ) {
			return $this->comments;
		}

		$sql = "SELECT post_excerpt FROM wp_posts WHERE id=" . $this->order_id;

		$this->comments = sql_query_single_scalar( $sql );

		return $this->comments;
	}

	function SetComments( $comments ) {
		$this->comments = $comments;
		$sql            = "UPDATE wp_posts SET post_excerpt = '" . $this->comments . "' WHERE id=" . $this->order_id;
		sql_query( $sql );
	}

	function quantity_in_order( $order_item_id ) {
// Get and display item quantity
		if ( is_numeric( $order_item_id ) ) {
			$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
			        . ' WHERE order_item_id = ' . $order_item_id
			        . ' AND `meta_key` = \'_qty\''
			        . ' ';

			return sql_query_single_scalar( $sql2 );
		}

		return 0;
	}

	function DeleteLines( $lines ) {
		print "order_delete_lines<br/>";
		foreach ( $lines as $line ) {
			print $line;
			print wc_delete_order_item( $line );
		}

	}

	function PrintHtml( $selectable = false ) {
		$site_tools = ImMultiSite::LocalSiteTools();

		$fields = array();

		if ( $selectable ) {
			array_push( $fields, gui_checkbox( "chk" . $this->order_id, "deliveries", true ) );
		}

		array_push( $fields, ImMultiSite::LocalSiteName() );

		$client_id     = $this->getCustomerId();
		$ref           = "<a href=\"" . $site_tools . "/orders/get-order.php?order_id=" . $this->order_id . "\">" . $this->order_id . "</a>";
		$address       = order_get_address( $this->order_id );
		$receiver_name = get_meta_field( $this->order_id, '_shipping_first_name' ) . " " .
		                 get_meta_field( $this->order_id, '_shipping_last_name' );
		$shipping2     = get_meta_field( $this->order_id, '_shipping_address_2', true );

		array_push( $fields, $ref );

		array_push( $fields, $client_id );

		array_push( $fields, $receiver_name );

		array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );

		array_push( $fields, $shipping2 );

		array_push( $fields, get_user_meta( $client_id, 'billing_phone', true ) );
		$payment_method = get_payment_method_name( $client_id );
		if ( $payment_method <> "מזומן" and $payment_method <> "המחאה" ) {
			$payment_method = "";
		}
		array_push( $fields, $payment_method );

		array_push( $fields, order_get_mission_id( $this->order_id ) );

		array_push( $fields, ImMultiSite::LocalSiteID() );
		// array_push($fields, get_delivery_id($order_id));

		$line = "<tr> " . delivery_table_line( 1, $fields ) . "</tr>";

//	print "line=  " . $line ."<br/>";
		// get_field($order_id, '_shipping_city');

		return $line;
	}

}
