<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/16
 * Time: 19:29
 */
require_once( "../tools_wp_login.php" );
require_once( "../account/account.php" );
include_once( "../orders/orders-common.php" );
include_once( "../gui/inputs.php" );
include_once( "../multi-site/multi-site.php" );

class delivery {
	private $ID = 0;
	private $d_OrderID = 0;
	private $total = 0;
	private $vat_total = 0;
	private $due_vat = 0;
	private $line_number = 0;
	private $del_price = 0;

	function delivery( $id ) {
//        my_log("CONS. id = " . $id);
		$this->ID = $id;
	}

	public static function CreateFromOrder( $order_id ) {
		$sql = "SELECT id FROM im_delivery WHERE order_id = " . $order_id;

		$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );

		$row = mysql_fetch_row( $export );

		$id = $row[0];

		$instance = new self( $id );

		$instance->SetOrderId( $order_id );

		return $instance;
	}

	private function SetOrderID( $order_id ) {
		$this->d_OrderID = $order_id;
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

		$sql = "UPDATE wp_posts SET post_status = 'wc-processing' WHERE id = " . $order_id;

		$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );

		// Remove from client account
		$sql = 'DELETE FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );

		// Remove the header
		$sql = 'DELETE FROM im_delivery WHERE id = ' . $this->ID;

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );

		// Remove the lines
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );

	}

	public function OrderId() {
		if ( ! ( $this->d_OrderID > 0 ) ) {
			$sql = "SELECT order_id FROM im_delivery WHERE id = " . $this->ID;

			$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );

			$row = mysql_fetch_row( $export );

			$this->d_OrderID = $row[0];
		}

		return $this->d_OrderID;
	}

	public function DeleteLines() {
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
	}

	public function Price() {
		$sql = 'SELECT transaction_amount FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;
		// my_log($sql);

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );

		$row = mysql_fetch_row( $export );

		return $row[0];
	}

	function print_delivery( $expand_basket = false, $print_comments = false, $refund = false ) {
		print $this->delivery_text( $expand_basket, $print_comments, $refund );
	}

	function delivery_text( $expand_basket = false, $print_comments = false, $refund = false ) {
		global $global_vat;
		global $conn;

		$data      = ""; // gui_image(get_logo_url());
		$del_price = 0;

		$client_id   = get_customer_id_by_order_id( $this->OrderId() );
		$client_type = customer_type( $client_id );
		switch ( $client_type ) {
			case 1:
				$data .= " תעריף סיטונאי" . "<br/>";
				break;
			case 2:
				$data .= " תעריף בעלים" . "<br/>";
				break;
		}

		$loaded      = false;
		$volume_line = false;

		if ( $this->ID > 0 ) { // load delivery
			$loaded = true;
			$sql    = 'select id, product_name, quantity, quantity_ordered, vat, price, line_price, prod_id ' .
			          'from im_delivery_lines ' .
			          'where delivery_id=' . $this->ID . " order by 1";

			$result = $conn->query( $sql );

			if ( ! $result ) {
				print $sql;
				die ( "select error" );
			}

			$data .= "<style> " .
			         "table.prods { border-collapse: collapse; } " .
			         " table.prods, td.prods, th.prods { border: 1px solid black; } " .
			         " </style>";

			$data .= "<table class=\"prods\" id=\"del_table\" border=\"1\"><tr><td>פריט</td><td>כמות הוזמן</td><td>כמות סופק</td><td>חייב מעם</td><td>מעם</td><td>מחיר</td><td>סהכ</td>";
			if ( $refund ) {
				$data .= gui_cell( "כמות לזיכוי" ) . gui_cell( "סהכ זיכוי" );
			}
			$data .= "</tr>";
			while ( $row = mysqli_fetch_assoc( $result ) ) {
				if ( $row["product_name"] == "הנחת כמות" ) {
					$volume_line = true;
				}
				$data .= $this->delivery_line( $row["product_name"], $row["quantity_ordered"], $row["quantity"], $row["price"], $row["vat"], $row["prod_id"], $refund );
			}
			$del_price = $this->DeliveryFee();
		} else {
			// Get order lines
			$sql = 'select '
			       . ' woi.order_item_name, woim.meta_value, woim.order_item_id'
			       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
			       . ' where order_id = ' . $this->OrderID()
			       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_product_id\''
			       . ' order by 3';

			$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

			$data               .= "<table id=\"del_table\" border=\"1\"><tr><td>פריט</td><td>כמות הוזמן</td><td>כמות סופק</td><td>חייב מעם</td><td>מעם</td><td>מחיר</td><td>סהכ</td></tr>";
			$quantity_delivered = 0;

			while ( $row = mysql_fetch_row( $export ) ) {
				$has_vat   = true;
				$prod_name = $row[0];
				$prod_id   = $row[1];
				// print $prod_id . "<br/>";
				$order_item_id    = $row[2];
				$quantity_ordered = get_order_itemmeta( $order_item_id, '_qty' );
				$line_total       = get_order_itemmeta( $order_item_id, '_line_total' );

				$price = $this->item_price( $client_type, $prod_id, $line_total, $quantity_ordered );
				//print $price . "<br/>";

				if ( get_vat_percent( $prod_id ) == 0 ) {
					$has_vat = false;
				}
				$data .= $this->delivery_line( $prod_name, $quantity_ordered, $quantity_delivered, $price, $has_vat, $prod_id );
				// print "ex " . $expand_basket . " is " . is_basket($prod_id) . "<br/>";

				if ( $expand_basket && is_basket( $prod_id ) ) {
					$this->expand_basket( $prod_id, 0, $quantity_ordered, $data, 0 );
				}
			}
			// Get and display order delivery price
			$sql2 = 'SELECT meta_value FROM `wp_woocommerce_order_itemmeta` WHERE order_item_id IN ( '
			        . 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE order_id = ' . $this->OrderId()
			        . ' AND order_item_type = \'shipping\' )  AND meta_key = \'cost\'; ';

			$export2 = mysql_query( $sql2 ) or die ( "Sql error : " . mysql_error() );

			$row2      = mysql_fetch_row( $export2 );
			$del_price = $row2[0];
			if ( ! is_numeric( $del_price ) ) {
				$del_price = 0;
			}
		}
//        print "del_vat = " . $del_vat . "<br/>";
//        print "del_price = " . $del_price . "<br/>";
//        print "vat_percent = " . $global_vat . "<br/>";
		if ( ! $loaded ) {
			$this->total   += $del_price;
			$this->due_vat += $del_price;

			$del_vat         = round( $del_price / ( 100 + $global_vat ) * $global_vat, 2 );
			$value           = '<tr><td>משלוח</td>'
			                   . '<td></td>'
			                   . '<td><input type="text" name="del_quantity' . '" value="1"></td>'
			                   . '<td><input id="has_vat" type = "checkbox" checked></button></td>'
			                   . '<td>' . $del_vat . '</td>'
			                   . '<td><input type="text" name="del_price' . '" value="' . $del_price . '"></td>'
			                   . '<td>' . $del_price . '</td></tr>';
			$data            .= $value;
			$this->vat_total += $del_vat;
			// Spare line for volume discount
		}

		if ( ! $volume_line ) {
			$data .= "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
		}

		// Handle round field
//        $round = round(round($this->total) - $this->total, 2);
//        $data .= "<tr><td>עיגול</td><td></td><td></td><td></td><td></td><td></td><td>" . $round . "</td></tr>";

		// Summary
		$data .= "<tr><td>סהכ חייבי מעם</td><td></td><td></td><td></td><td></td><td></td><td>" . $this->due_vat . "</td>";
		if ( $refund ) {
			$data .= gui_cell( "סהכ זיכוי חייבי מעם" ) . gui_cell( 0 );
		}
		$data .= "</tr>";
		$data .= "<tr><td>סכום מעם</td><td></td><td></td><td></td><td></td><td></td><td>" . $this->vat_total . "</td>";
		if ( $refund ) {
			$data .= gui_cell( "סהכ זיכוי מעם" ) . gui_cell( 0 );
		}
		$data .= "</tr>";
		$data .= "<tr><td>סהכ לתשלום</td><td></td><td></td><td></td><td></td><td></td><td id=\"total\">" . ( $this->total ) . "</td>";
		if ( $refund ) {
			$data .= gui_cell( "סהכ זיכוי" ) . gui_cell( 0 );
		}
		$data .= "</tr>";

		$data = str_replace( "\r", "", $data );

		if ( $data == "" ) {
			$data = "\n(0) Records Found!\n";
		}

		$data .= "</table>";

		if ( $print_comments ) {
			$data  .= "<b>" . "הערות לקוח" . "</b><br/>";
			$order = new WC_Order( $this->OrderId() );
			$data  .= nl2br( $order->customer_message );
		}

		return "$data";
	}

	// Used for:
	// Creating new delivery.
	// - Prices are taken from order for regular clients, discount for siton and buy prices for owner
	// display delivery
	// - Prices are taken from the database - delivery

	public function delivery_line( $prod_name, $quantity_ordered, $quantity_delivered, $price, $has_vat, $prod_id, $refund = false ) {
		global $global_vat;
		$vat = 0;

		// Display item name
		$value = "<td id='" . $prod_id . "'>" . $prod_name . '</td>';

		$value .= "<td>" . $quantity_ordered . "</td>";

		$value .= '<td>';
		if ( ! $refund ) {
			$value .= '<input type="text" name="quantity' . $this->line_number . '"';
			if ( $this->ID > 0 ) {
				$value .= " value=" . $quantity_delivered;
			}
			$value .= ' onkeypress="moveNextRow(' . $this->line_number . ')">';
		} else {
			$value .= $quantity_delivered;
		}

		$value .= '</td>';

		if ( ! is_numeric( $price ) ) {
			$price = 0;
		}

		$value .= '<td><input id="has_vat" type = "checkbox" ';
		if ( $has_vat > 0 ) {
			$value .= "checked";
		}
		$value .= ' ></td>';

		$line_price = round( $quantity_delivered * $price, 2 );

		if ( $has_vat ) {
			$vat           = round( $price * $quantity_delivered / ( 100 + $global_vat ) * $global_vat, 2 );
			$this->due_vat += $price * $quantity_delivered;
		}

		$value .= '<td id="vat' . $this->line_number . '">' . $vat . '</td>'
		          . '<td id ="price' . $this->line_number . '">' . $price . '</td>'
		          . '<td id ="line_total' . $this->line_number . '">' . $line_price . '</td>';

		// Check if this product eligible for quantity discount.
		$terms = get_the_terms( $prod_id, 'product_cat' );
		if ( $terms ) {
			$terms_cell = '<td style="display:none;">';
			foreach ( $terms as $term ) {
				$terms_cell .= $term->term_id . ",";
			}
			$terms_cell = rtrim( $terms_cell, "," );
			$terms_cell .= '</td>';
		} else {
			$terms_cell = "<td style=\"display:none;\"></td>";
			if ( $prod_id > 0 ) {
				print "no terms for " . $prod_id;
			}
		}

		$value .= $terms_cell;

		if ( $refund ) {
			$value .= gui_cell( gui_input( "refund_" . $this->line_number, 0 ) );
			$value .= gui_cell( "0" );
		}

		$this->total       = $this->total + $line_price;
		$this->vat_total   = $this->vat_total + $vat;
		$this->line_number = $this->line_number + 1;

		return "<tr> " . trim( $value ) . "</tr>";
	}

	public function DeliveryFee() {
		$sql = 'SELECT fee FROM im_delivery WHERE id = ' . $this->ID;
		// my_log($sql);

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );

		$row = mysql_fetch_row( $export );

		return $row[0];
	}

	function item_price( $client_type, $prod_id, $line_total, $quantity_ordered ) {
//    	print get_product_name($prod_id) . " ";
		$price = - 1;
		print $line_total;
		switch ( $client_type ) {
			case 0:
				if ( $line_total == 0 ) {
					$price = get_price( $prod_id );
					print $price;
				} else {
					$price = round( $line_total / $quantity_ordered, 2 );
				}
				break;
			case 1:
				$price = siton_price( $prod_id );
				break;
			case 2:
				$price = get_buy_price( $prod_id );
				break;
		}

		//      print $price . "<br/>";
		return $price;
	}

	function expand_basket( $basket_id, $client_type, $quantity_ordered, &$data, $level ) {
		global $conn;
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result2 = mysqli_query( $conn, $sql2 );
		while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
			$prod_id  = $row2["product_id"];
			$quantity = $row2["quantity"];
			if ( is_basket( $prod_id ) ) {
				$this->expand_basket( $prod_id, $client_type, $quantity_ordered * $quantity, $data, $level + 1 );
			} else {
				$price = $this->item_price( $client_type, $prod_id, 0, 0 );
				//                        print "prod_id = " . $prod_id . "price = " . $price . "<br/>";
				$product_name = "===> " . get_product_name( $prod_id );
				$data         .= $this->delivery_line( $product_name, $quantity_ordered * $quantity, 0,
					$price, round( $price * get_vat_percent( $prod_id ), 2 ), $prod_id );
			}
		}
		if ( $level == 0 ) {
			$data .= "<tr><td>הנחת סל</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
		}
	}

	function send_mail( $more_email = null ) {
		print $more_email;
		global $business_name;
		global $bank_info;
		global $support_email;

		$order_id  = get_order_id( $this->ID );
		$client_id = get_customer_id_by_order_id( $this->OrderId() );

		my_log( __FILE__, "client_id = " . $client_id );

		$sql = "SELECT dlines FROM im_delivery WHERE id = " . $this->ID;
		$export = mysql_query( $sql ) or sql_error( $sql );

		$row    = mysql_fetch_row( $export );
		$dlines = $row[0];
		my_log( __FILE__, "dlines = " . $dlines );

		$message  = file_get_contents( "../header.php" );
		$del_user = order_info( $order_id, '_billing_first_name' );
		$message  = header_text();
		$message  .= "
<html lang=\"he\">
<head>
<meta charset=\"utf-8\" />
<title>משלוח חדש</title>
</head>
<body dir=\"rtl\">
שלום " . $del_user . "!
<br><br>
המשלוח שלך ארוז ויוצא לדרך!";
		$message  .= "<br> היתרה המעודכנת במערכת " . client_balance( $client_id );

		$message .= "<Br> להלן פרטי המשלוח";

		$message .= $this->delivery_text();
		// file_get_contents("http://store.im-haadama.co.il/tools/delivery/get-delivery.php?id=" . $del_id . "&send=1");

		$message .= "<br /> לפרטים אודות מצב החשבון והמשלוח האחרון הכנס
<a href = \"" . get_site_tools_url( Multisite::LocalSiteID() ) . "/account/get-customer-account.php?customer_id=" . $client_id . "\"> לאתר</a>.
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
		print "To: " . $to . "<br/>";
		if ( $more_email ) {
			$to = $to . ", " . $more_email;
		}
		print "To: " . $to . "<br/>";
		print "Message:<br/>";
		print $message . "<br/>";
		send_mail( "משלוח " . $this->ID . " בוצע", $to, $message );

		// print "mail sent to " . $to . "<br/>";
	}
}