<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/16
 * Time: 19:29
 */
require_once( "../tools_wp_login.php" );
require_once( "../pricing.php" );
require_once( "../account/account.php" );
include_once( "../orders/orders-common.php" );
include_once( "../gui/inputs.php" );
include_once( "../multi-site/multi-site.php" );
require_once( "../mail.php" );
require_once( "../account/gui.php" );
require_once( "../delivery/delivery-common.php" );

$debug = false;
class delivery {
	private $ID = 0;
	private $d_OrderID = 0;
	private $order_total = 0;
	private $order_vat_total = 0;
	private $order_due_vat = 0;
	private $line_number = 0;
	private $del_price = 0;
	private $delivery_total = 0;
	private $delivery_due_vat = 0;
	private $delivery_total_vat = 0;

	function delivery( $id ) {
//        my_log("CONS. id = " . $id);
		$this->ID = $id;
	}

	public static function GuiCreateNewNoOrder() {
		$data = gui_table( array(
			array( "לקוח:", gui_select_client( 30, true ) ),
			array( "תאריך", gui_input_date( "delivery_date", "" ) ),
			array( gui_button( "btn_add_delivery", "", "הוסף תעודת משלוח" ) )
		) );

		return $data;
	}
	public static function CreateFromOrder( $order_id ) {

		$id = get_delivery_id( $order_id );

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

	public function OrderId() {
		if ( ! ( $this->d_OrderID > 0 ) ) {
			$sql = "SELECT order_id FROM im_delivery WHERE id = " . $this->ID;

			$this->d_OrderID = sql_query_single_scalar( $sql );
		}

		return $this->d_OrderID;
	}

	public function DeleteLines() {
		$sql = 'DELETE FROM im_delivery_lines WHERE delivery_id = ' . $this->ID;

		sql_query( $sql );
	}

	public function Price() {
		$sql = 'SELECT transaction_amount FROM im_client_accounts WHERE transaction_ref = ' . $this->ID;
		// my_log($sql);

		return sql_query_single_scalar( $sql );
	}

	function print_delivery( $document_type, $operation ) {
		print $this->delivery_text( $document_type, $operation );
	}

	function delivery_text( $document_type, $operation = ImDocumentOperation::show ) {
		// $expand_basket = false, $refund = false, $edit_order = false
		global $delivery_fields_names;
		global $header_fields;
		global $debug;
		if ( $debug ) {
			print "Document type " . $document_type . "<br/>";
			print "operation: " . $operation . "<br/>";
		}
		global $global_vat;
		global $conn;

		$expand_basket = false;

		if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
			$expand_basket = true;
		}

		// All fields:
		$show_fields = array();
		for ( $i = 0; $i < DeliveryFields::max_fields; $i ++ ) {
			$show_fields[ $i ] = false;
		}

		$show_fields[ DeliveryFields::product_name ]  = true;
		$show_fields[ DeliveryFields::order_q ]       = true;
		$show_fields[ DeliveryFields::order_q_units ] = true;
		$show_fields[ DeliveryFields::price ]         = true;

		$empty_array = array();
		for ( $i = 0; $i < DeliveryFields::max_fields; $i ++ ) {
			$empty_array[ $i ] = "";
		}

//		p_id = 6,
//		term_id = 8,
		$refund = false;

		switch ( $document_type ) {
			case ImDocumentType::order:
				$header_fields[ DeliveryFields::delivery_line ] = "סה\"כ למשלוח";
				if ( $operation == ImDocumentOperation::edit ) {
					$header_fields[ DeliveryFields::line_select ] = gui_checkbox( "chk", "line_chk", false );
					$show_fields[ DeliveryFields::line_select ]   = true;
				}
				$show_fields[ DeliveryFields::order_line ] = true;
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

		$delivery_loaded = false;
		$volume_line = false;

		$data .= "<style> " .
		         "table.prods { border-collapse: collapse; } " .
		         " table.prods, td.prods, th.prods { border: 1px solid black; } " .
		         " </style>";

		$data .= "<table class=\"prods\" id=\"del_table\" border=\"1\">";
		// <tr><td>פריט</td><td>כמות הוזמן</td><td>יחידה</td></td><td>כמות סופק</td><td>חייב מע\"מ</td><td>מע\"מ</td><td>מחיר</td><td>סהכ</td>";

		// Print header
		$sum  = null;
		$data .= gui_row( $header_fields, "header", $show_fields, $sum );
//		$data .= "<tr>";
//		for ( $i = 0; $i < sizeof( $header_fields ); $i ++ ) {
//			// print $i . " " . $show_fields[$i] . "<br/>";
//			$data .= gui_cell( $header_fields[ $i ], "header" . $i, $show_fields[ $i ] );
//			//	print $i . " " . $show_fields[$i]. "<br/>";
//		}
//		$data .= "</tr>";

		if ( $this->ID > 0 ) { // load delivery
			$delivery_loaded = true;
			$sql    = 'select id, product_name, quantity, quantity_ordered, vat, price, line_price, prod_id ' .
			          'from im_delivery_lines ' .
			          'where delivery_id=' . $this->ID . " order by 1";

			if ( $debug ) {
				print $sql . "<Br/>";
			}

			$result = $conn->query( $sql );

			if ( ! $result ) {
				print $sql;
				die ( "select error" );
			}

			while ( $row = mysqli_fetch_assoc( $result ) ) {
				if ( $row["product_name"] == "הנחת כמות" ) {
					$volume_line = true;
				}
				// $data .= $this->delivery_line($show_fields, $row["id"], $row["quantity_ordered"], "", $row["quantity"], $row["price"], $row["vat"], $row["prod_id"], $refund, "" );
				// TODO: client Type
				$data .= $this->delivery_line( $show_fields, ImDocumentType::delivery, $row["id"], $client_type, $operation );
				// $data .= $this->delivery_line($show_fields, $document_type, $line_id, $client_type, $edit = false)
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

			$result = sql_query( $sql);

			while ( $row = mysqli_fetch_row( $result ) ) {
				$prod_name = $row[0];
				$prod_id   = $row[1];
				// print $prod_id . "<br/>";
				$order_item_id    = $row[2];

				//print $price . "<br/>";
				$has_vat = true;
				if ( get_vat_percent( $prod_id ) == 0 ) {
					$has_vat = false;
				}

				// $data .= $this->delivery_line($show_fields, $prod_id, $quantity_ordered, "", $quantity_ordered, $price, $has_vat, $prod_id, $refund, $unit );
				$data .= $this->delivery_line( $show_fields, $document_type, $order_item_id, $client_type, $operation);
				// print "ex " . $expand_basket . " is " . is_basket($prod_id) . "<br/>";

				if ( $expand_basket && is_basket( $prod_id ) ) {
					// $this->expand_basket( $prod_id, 0, , $data, 0 );
//					$this->expand_basket($prod_id, 0, $show_fields, $document_type,
					$quantity_ordered = get_order_itemmeta( $order_item_id, '_qty' ); //, $client_type, $operation, $data );

					$this->expand_basket( $prod_id, $quantity_ordered, 0, $show_fields, $document_type,
						$order_item_id, $client_type, $operation, $data );
				}
			}

			// Get and display order delivery price
			$sql2 = 'SELECT meta_value FROM `wp_woocommerce_order_itemmeta` WHERE order_item_id IN ( '
			        . 'SELECT order_item_id FROM wp_woocommerce_order_items WHERE order_id = ' . $this->OrderId()
			        . ' AND order_item_type = \'shipping\' )  AND meta_key = \'cost\'; ';

			$del_price = sql_query_single_scalar( $sql2);
			if ( ! is_numeric( $del_price ) ) {
				$del_price = 0;
			}
		}
//        print "del_vat = " . $del_vat . "<br/>";
//        print "del_price = " . $del_price . "<br/>";
//        print "vat_percent = " . $global_vat . "<br/>";
		if ( ! $delivery_loaded ) {
			$this->order_total   += $del_price;
			$this->order_due_vat += $del_price;

			$del_vat         = round( $del_price / ( 100 + $global_vat ) * $global_vat, 2 );

//			$value           = '<tr><td>דמי משלוח</td>'
//			                   . '<td></td><td></td>'
//			                   . '<td><input type="text" name="del_quantity' . '" value="1"></td>'
//			                   . '<td><input id="has_vat" type = "checkbox" checked></button></td>'
//			                   . '<td>' . $del_vat . '</td>'
//			                   . '<td><input type="text" name="del_price' . '" value="' . $del_price . '"></td>'
//			                   . '<td>' . $del_price . '</td></tr>';

			$delivery_line                                  = $empty_array;
			$delivery_line[ DeliveryFields::product_name ]  = "דמי משלוח";
			$delivery_line[ DeliveryFields::delivery_q ]    = 1;
			$delivery_line[ DeliveryFields::price ]         = $operation ? gui_input( "delivery", $del_price, "" ) : $del_price;
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

		if ( ! $volume_line ) {
			$delivery_line = $empty_array;
			$data          .= gui_row( $delivery_line, "dis", $show_fields, $sums, $delivery_fields_names );
		}

		// Handle round field
//        $round = round(round($this->total) - $this->total, 2);
//        $data .= "<tr><td>עיגול</td><td></td><td></td><td></td><td></td><td></td><td>" . $round . "</td></tr>";

		// Summary
		// Due VAT
		$summary_line                                  = $empty_array;
		$summary_line[ DeliveryFields::product_name ]  = "סה\"כ חייבי מע\"מ";
		$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_due_vat;
		$summary_line[ DeliveryFields::order_line ]    = $this->order_due_vat;
		$data                                          .= gui_row( $summary_line, "due", $show_fields, $sum, $delivery_fields_names );


		// Total VAT
		$summary_line                                  = $empty_array;
		$summary_line[ DeliveryFields::product_name ]  = "סכום מע\"מ";
		$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_total_vat;
		$summary_line[ DeliveryFields::order_line ]    = $this->order_vat_total;
		$data                                          .= gui_row( $summary_line, "vat", $show_fields, $sum, $delivery_fields_names );

		// Total
		$summary_line                                  = $empty_array;
		$summary_line[ DeliveryFields::product_name ]  = "סה\"כ לתשלום";
		$summary_line[ DeliveryFields::delivery_line ] = $this->delivery_total;
		$summary_line[ DeliveryFields::order_line ]    = $this->order_total;
		$data                                          .= gui_row( $summary_line, "tot", $show_fields, $sum, $delivery_fields_names );


		$data = str_replace( "\r", "", $data );

		$data .= "</table>";

		$data .= "מספר שורות  " . $this->line_number . "<br/>";

		return "$data";
	}

	// Used for:
	// Creating new delivery.
	// - Prices are taken from order for regular clients, discount for siton and buy prices for owner
	// display delivery
	// - Prices are taken from the database - delivery

	// public function delivery_line($show_fields, $prod_id, $quantity_ordered, $unit_ordered, $quantity_delivered, $price, $has_vat, $document_type, $edit)
	// Delivery or Order line.
	// If Document is delivery, line_id is delivery line id.
	// If Document is order, line_id is order line id.
	public function delivery_line( $show_fields, $document_type, $line_id, $client_type, $operation ) {
		global $delivery_fields_names;

		global $debug;
		if ( $debug ) {
			print "Operation: " . $operation . "<br/>";
			print " Document type: " . $document_type . "<br/>";
			print " line id " . $line_id . "<br/>";
		}

		global $global_vat;
		$vat = 0;

		$line = array();
		for ( $i = 0; $i <= DeliveryFields::max_fields; $i ++ ) {
			$line[ $i ] = "";
		}

		$line[ DeliveryFields::line_select ] = gui_checkbox( "chk" . $line_id, "line_chk", false );

//		if ( $show_fields[  ] ) {
//			$value .= gui_cell(  ); // 0 - Checkbox
//		} else {
//			$value .= gui_cell( "", "", false );
//		}
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
				if ( $debug ) {
					print "load from order";
				}
				break;

			case ImDocumentType::delivery:
				if ( $operation == ImDocumentOperation::create or $operation == ImDocumentOperation::collect ) {
					$load_from_order = true;
					if ( $debug )
						print "create. load from order";
				} else {
					$load_from_order = false;
				}
				// TODO: check price
				break;
		}
		$has_vat = null;

		if ( $load_from_order ) {
			if ( $debug ) {
				print "loading from order<br/>";
			}
			$sql              = "SELECT order_item_name FROM wp_woocommerce_order_items WHERE order_item_id = " . $line_id;
			$prod_name        = sql_query_single_scalar( $sql );
			$quantity_ordered = get_order_itemmeta( $line_id, '_qty' );
			$unit_ordered     = get_order_itemmeta( $line_id, 'unit' );
			$order_line_total = get_order_itemmeta( $line_id, '_line_total' );
			$this->order_total += $order_line_total;
			$line[ DeliveryFields::order_line ] = $order_line_total;
			$prod_id          = get_order_itemmeta( $line_id, '_product_id' );
			// $line_price       = get_order_itemmeta( $line_id, '_line_total' );
			switch ( $client_type ) {
				case 0:
					$price = round( $order_line_total / $quantity_ordered, 2 );
					break;
				case 1:
					$price = siton_price( $prod_id );
					break;
				case 2:
					$price = get_buy_price( $prod_id );
					break;
			}

			if ( $unit_ordered ) {
				$quantity_ordered = "";
				$unit_array       = explode( ",", $unit_ordered );
				$unit_q           = $unit_array[1];
				// print "unit: " ; var_dump($unit) ; print "<br/>";
			}

		} else {
			if ( $debug ) {
				print "loading from im_delivery_lines<br/>";
			}
			$sql = "SELECT prod_id, product_name, quantity_ordered, unit_ordered, quantity, price, line_price, vat FROM im_delivery_lines WHERE id = " . $line_id;

			$row = sql_query_single( $sql );
			if ( ! $row ) {
				sql_error( $sql );
				die ( 2 );
			}

			$prod_id          = $row[0];
			$prod_name        = $row[1];
			$quantity_ordered = $row[2];
			$unit_q           = $row[3];
			if ( $unit_q > 0 and $quantity_ordered == 0 ) {
				$quantity_ordered = "";
			}
			$quantity_delivered = $row[4];
			if ( $debug )
				print "q = " . $quantity_delivered . "<br/>";
			$price               = $row[5];
			$delivery_line_price = $row[6];
			$has_vat             = $row[7];

			// $unit_ordered     = ;
			// $order_line_total = $row[5];

// ????			$prod_name = get_product_name( $prod_id );
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
			if ( get_vat_percent( $prod_id ) == 0 ) {
				// print "<br/>" . $prod_id . " " . get_vat_percent($prod_id) . " " . get_product_name($prod_id) ;
				$has_vat = false;
			} else {
				$has_vat = true;
			}
		}

		if ( $has_vat ) {
			$vat = round( ( $line_price * $global_vat ) / 100, 2 );
		}

		// price
		if ( $operation == ImDocumentOperation::create and $document_type == ImDocumentType::delivery ) {
			$line[ DeliveryFields::price ] = gui_input( "", $price );
		} else {
			$line[ DeliveryFields::price ] = $price;
		}
		// $value .= gui_cell( $price, "price" . $this->line_number );                                   // 5 - price

		// has_vat
		$line[ DeliveryFields::has_vat ] = gui_checkbox( "has_vat" . $prod_id, "has_vat", $has_vat > 0 ); // 6 - has vat

//		$line[DeliveryFields::order_line] = $line_price;

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
						$line[ DeliveryFields::delivery_q ] = gui_input( "quantity" . $this->line_number,
							( $quantity_delivered > 0 ) ? $quantity_delivered : "",
							array( 'onkeypress="moveNextRow(' . $this->line_number . ')"' ) );
						break;
					case ImDocumentOperation::collect:
						break;
					case ImDocumentOperation::show:
						$line[ DeliveryFields::delivery_q ] = $quantity_delivered;
						break;
					default:
				}
				// Order info
				// $order_line                       = $line[DeliveryFields::price] * $line[DeliveryFields::order_q];
				// $line[DeliveryFields::order_line] = $order_line;
				// $this->delivery_total             += $order_line;

				// Delivery info
				$delivery_line                         = $line[ DeliveryFields::price ] * $line[ DeliveryFields::delivery_q ];
				$line[ DeliveryFields::delivery_line ] = $delivery_line;
				// if ($line[$delivery_line])
				$this->delivery_total += $delivery_line;
				if ( $has_vat ) {
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

		// Line total
//		$value .= gui_cell( $line_price, "order_line_total" . $this->line_number );                   // 8 - line total

		// $this->delivery_total += $line_price;
		// Accumulate vat
		if ( $vat > 0 ) {
			$this->delivery_due_vat   += $line_price;
			$this->delivery_total_vat += $vat;
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

		// Handle totals
		switch ( $document_type ) {
			case ImDocumentType::order:
				break;

			case ImDocumentType::delivery:
				$this->delivery_total += $line_price;
				// print "total: " . $this->delivery_total . "<br/>";
				$this->delivery_total_vat += $vat;
				break;

			case ImDocumentType::refund;
				break;
		}

		$this->line_number = $this->line_number + 1;

//		return "<tr> " . trim( $value ) . "</tr>";
		// var_dump($show_fields);

		$sums = null;

		return gui_row( $line, $this->line_number, $show_fields, $sums, $delivery_fields_names);
	}

	public function DeliveryFee() {
		$sql = 'SELECT fee FROM im_delivery WHERE id = ' . $this->ID;
		// my_log($sql);

		return sql_query_single_scalar( $sql);
	}


//	function item_price( $client_type, $prod_id, $line_total, $quantity_ordered ) {
////    	print get_product_name($prod_id) . " ";
////		print "YY<br/>";
//		$price = - 1;
//
//		switch ( $client_type ) {
//			case 0:
//				if ( $line_total == 0 ) {
//					$price = get_price( $prod_id );
//
//					// print $price;
//				} else {
//					$price = round( $line_total / $quantity_ordered, 2 );
//				}
//				break;
//			case 1:
//				$price = siton_price( $prod_id );
//				break;
//			case 2:
//				$price = get_buy_price( $prod_id );
//				break;
//		}
//
//		//      print $price . "<br/>";
//		return $price;
//	}

	// function expand_basket( $basket_id, $client_type, $quantity_ordered, &$data, $level ) {
	// Called when creating a delivery from an order.
	// After the basket line is shown, we print here the basket lines and basket discount line.
	function expand_basket( $basket_id, $quantity_ordered, $level, $show_fields, $document_type, $line_id, $client_type, $edit, &$data ) {
		global $conn, $delivery_fields_names;
		$sql2 = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result2 = mysqli_query( $conn, $sql2 );
		while ( $row2 = mysqli_fetch_assoc( $result2 ) ) {
			$prod_id  = $row2["product_id"];
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
				$line[ DeliveryFields::price ]        = get_price( $prod_id, $client_type );
				$has_vat                              = true;
				if ( get_vat_percent( $prod_id ) == 0 ) {
					//print "has vat false<br/>";
					$has_vat = false;
				}
				$line[ DeliveryFields::product_id ] = $prod_id;
				$line[ DeliveryFields::has_vat ]    = gui_checkbox( "has_vat" . $prod_id, "has_vat", $has_vat > 0 );
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
			$line[ DeliveryFields::product_name ] = gui_lable( "ba", "הנחת סל" );
			// $line[DeliveryFields::has_vat] = gui_checkbox("", )
			$sums              = null;
			$data              .= gui_row( $line, "bsk" . $this->line_number, $show_fields, $sums, $delivery_fields_names );
			$this->line_number = $this->line_number + 1;


//			$data .= "<tr><td id='bsk_dis". $this->line_number .
//			         "'>הנחת סל</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
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

		$dlines = sql_query_single_scalar( $sql);

		my_log( __FILE__, "dlines = " . $dlines );

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

		$message .= $this->delivery_text( ImDocumentType::delivery, ImDocumentOperation::show);
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