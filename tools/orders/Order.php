<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/10/18
 * Time: 17:03
 */
require_once( ROOT_DIR . '/agla/sql.php' );
require_once( TOOLS_DIR . '/catalog/bundles.php' );
require_once( ROOT_DIR . "/tools/catalog/Basket.php" );
require_once( ROOT_DIR . "/tools/orders/orders-common.php" );


class Order {
	private $order_id;
	private $order;

	public function __construct( $id ) {
		$this->order_id = $id;
		$this->order    = new WC_Order( $id );
	}

//	function order_info( $order_id, $field_name ) {
//		$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
//		       . ' WHERE pm.post_id = ' . $order_id
//		       . ' AND `meta_key` = \'' . $field_name . '\'';
//
//		return sql_query_single_scalar( $sql );
//	}

	public function GetID() {
		return $this->order_id;
	}

	public function OrderDate( $f = "%d/%m/%Y" ) {
		$sql = "select DATE_FORMAT(post_date, '$f') from wp_posts where id = " . $this->order_id;

		// print $sql;
		return sql_query_single_scalar( $sql );
	}

	public function CustomerName() {
		$user = get_user_by( "id", $this->CustomerId() );
		if ( $user ) {
			return $user->user_firstname . " " . $user->user_lastname;
		}

		return "לא נבחר לקוח";
	}

	public function CustomerId() {
		return get_postmeta_field( $this->order_id, "_customer_user" );
	}

	public function Missing() {
		$needed = array();

		$result = "";
		$this->CalculateNeeded( $needed, $this->CustomerId() );

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
		$order_items = $this->order->get_items();
		$total       = 0;
		// print "cid= " . $this->CustomerId() . "<br/>";

		$client_type = customer_type( $this->CustomerId() );

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
		$order_items = $this->order->get_items();
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

}