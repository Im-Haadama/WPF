<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:58
 */

// test
// include_once( "../r-shop_manager.php" );
if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
	// print "ROOT_DIR: " . ROOT_DIR . "<br/>";
}

require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/data/header.php' );
require_once( ROOT_DIR . "/tools/mail.php" );
require_once( ROOT_DIR . "/tools/catalog/catalog.php" );

// print header_text(false);

// Supply status: 1 = new, 3 = sent, 5 = supplied, 8 = merged into other, 9 = delete

abstract class SupplyStatus {
	const NewSupply = 1;
	const Sent = 3;
	const Supplied = 5;
	const Merged = 8;
	const Deleted = 9;
}

class Supply {
	private $ID = 0;
	private $Status;
	private $Date;
	private $Supplier;
	private $Text;
	private $BusinessID;
	private $MissionID;

	/**
	 * Supply constructor.
	 *
	 * @param int $ID
	 */
	public function __construct( $ID ) {
		$this->ID       = $ID;
		$row            = sql_query_single( "SELECT status, date(date), supplier, text, business_id, mission_id FROM im_supplies WHERE id = " . $ID );
		$this->Status   = $row[0];
		$this->Date     = $row[1];
		$this->Supplier = $row[2];
		// print "sssss " . $this->Supplier;
		$this->Text       = $row[3];
		$this->BusinessID = $row[4];
		$this->MissionID  = $row[5];
	}


	public static function CreateFromFile( $file_name, $supplier_id, $debug = false ) {
		$debug               = true;
		$item_code_idx       = Array();
		$name_idx            = Array();
		$price_idx           = Array();
		$sale_idx            = Array();
		$detail_idx          = Array();
		$category_idx        = Array();
		$quantity_idx        = Array();
		$is_active_idx       = null;
		$picture_path_idx    = null;
		$parse_header_result = false;
		$inventory_idx       = 0;
		$prev_name           = "";

		$file = file( $file_name );
		for ( $i = 0; ! $parse_header_result and ( $i < 4 ); $i ++ ) {
			if ( $debug ) {
				print "trying to locate headers " . $file[ $i ] . "<br/>";

			}
			$parse_header_result = parse_header( $file[ $i ], $item_code_idx, $name_idx, $price_idx, $sale_idx, $inventory_idx,
				$detail_idx, $category_idx, $is_active_idx, $filter_idx, $picture_idx, $quantity_idx );
		}

		if ( ! count( $name_idx ) ) {
			print "Can't find name header.<br/>";

			return null;
		}
		if ( ! count( $quantity_idx ) ) {
			print "Can't find quantity header.<br/>";

			return null;
		}
		if ( ! count( $item_code_idx ) ) {
			print "Info: can't find item code header.<br/>";
		}
		if ( ! count( $price_idx ) ) {
			print "Can't find price header.<br/>";

			return null;
		}
		if ( $debug ) {
			print "headers: <br/>";
			print "price : " . $price_idx[0];
			print "<br/>";
			print "name: " . $name_idx[0];
			print "<br/>";
			print "quantity: " . $quantity_idx[0];
			print "<br/>";
		}

		$lines = array();
		for ( ; $i < count( $file ); $i ++ ) {
			$data = str_getcsv( $file[ $i ] );
			if ( $data ) {
				if ( isset( $is_active_idx ) and ( $data[ $is_active_idx ] == 1 ) ) {
					// print $data[ $name_idx[ 0 ] ] . " not active. Skipped<br/>";
					continue;
				}
				for ( $col = 0; $col < count( $price_idx ); $col ++ ) {
					$name = $data[ $name_idx[ $col ] ];
					if ( $name == $prev_name ) {
						continue;
					}
					$prev_name = $name;
					$quantity  = $data[ $quantity_idx[ $col ] ];
					$price     = $data[ $price_idx[ $col ] ];
					$item_code = $data[ $price_idx[ $col ] ];

					if ( isset( $detail_idx[ $col ] ) ) {
						$detail = $data[ $detail_idx[ $col ] ];
						$detail = rtrim( $detail, "!" );
					}

					if ( isset( $item_code_idx[ $col ] ) ) {
						$item_code = $data[ $item_code_idx[ $col ] ];
					}

					if ( $price > 0 ) {
						$new = array( $item_code, $quantity, $name, $price );
						array_push( $lines, $new );
					}
				}
			}
		}
		// var_dump($lines);

		$comments = "";
		if ( count( $lines ) ) {
//			$supplier_id = get_supplier_id($supplier_name);
			$Supply = Supply::CreateSupply( $supplier_id ); // create_supply( $supplier_id );
			if ( ! ( $Supply->getID() > 0 ) ) {
				die ( "שגיאה " );
			}

			if ( $debug ) {
				print "creating supply " . $Supply->getID() . " supplier id: " . $supplier_id . "<br/>";
			}

			foreach ( $lines as $line ) {
				$supplier_product_code = $line[0];
				$quantity              = $line[1];
				$name                  = $line[2];
				$price                 = $line[3];
//				print "name: " . $name;

				// $prod_id = get_product_id_by_name( $name );
				$prod_id = sql_query_single_scalar( "select product_id \n" .
				                                    " from im_supplier_mapping\n" .
				                                    " where supplier_product_name = " . quote_text( pricelist_strip_product_name( $name ) ) );
//				print "prod_id: " . $prod_id . "<br/>";

				if ( ! ( $prod_id > 0 ) ) {
					$comments .= " פריט בשם " . $name . " לא נמצא. כמות " . $quantity . "\n";
					continue;
				}

				if ( $debug ) {
					print "c=" . $supplier_product_code . " q=" . $quantity . " n=" . $name . " pid=" . $prod_id . " on=" . get_product_name( $prod_id ) . " " . $name . "<br/>";
				}
				// print "prod_id: " . $prod_id . " q= " . $quantity_idx . " price = " . $price . "<br/>";
				$Supply->AddLine( $prod_id, $quantity, $price );
				// supply_add_line($id, $prod_id, $quantity, $price);
			}

			print " הספקה " . $Supply->getID() . " נוצרה <br/>";

			$Supply->setText( $comments );

			return $Supply;
		}
	}

	public static function CreateSupply( $supplier_id ) {
		$sid = create_supply( $supplier_id );

		return new Supply( $sid );
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	public function AddLine( $prod_id, $quantity, $price, $units = 0 ) {
		if ( is_null( $price ) ) {
			$price = 0;
		}
		$sql = "INSERT INTO im_supplies_lines (supply_id, product_id, quantity, units, price) VALUES "
		       . "( " . $this->ID . ", " . $prod_id . ", " . $quantity . ", " . $units . ", " . $price . " )";

		// print $sql;
		sql_query( $sql );
		$product = new WC_Product( $prod_id );
		if ( $product->managing_stock() ) {
//		print "managed<br/>";
//		print "stock was: " . $product->get_stock_quantity() . "<br/>";

			$product->set_stock_quantity( $product->get_stock_quantity() + $quantity );
//		print "stock is: " . $product->get_stock_quantity() . "<br/>";
			$product->save();
		}

		return true;
	}

	public function UpdateField( $field_name, $value ) {
		sql_query( "update im_supplies " .
		           "set " . $field_name . '=' . quote_text( $value ) .
		           " where id = " . $this->ID );
	}

	public function Html( $internal, $edit ) {
		$data = "";

		$data .= $this->HtmlHeader( $edit );
		$data .= "<br/>";
		$data .= $this->HtmlLines( $internal, $edit );
		$data .= "<br/>";

		return $data;
	}

	public function HtmlHeader( $edit ) {
		$rows = array();
		$row  = array( "הערות" );
		// Text + Button
		if ( $edit ) {
			array_push( $row, gui_textarea( "comment", $this->Text, "onchange=\"update_comment()\"" ) );
		} else {
			array_push( $row, $this->Text );
		}

		array_push( $rows, $row );
		// Date
		$row = array( "תאריך הספקה" );

		if ( $edit ) {
			array_push( $row, gui_input_date( "date", "", $this->Date,
				"onchange=\"update_field('supplies-post.php', " . $this->ID . ",'date', '')\"" ) );
		} else {
			array_push( $row, $this->Date );
		}

		array_push( $rows, $row );

		$row = array( "שילוח" );
		if ( $edit ) {
			array_push( $row, gui_select_mission( "mis_" . $this->ID, $this->MissionID, "onchange=mission_changed(" . $this->ID . ")" ) );
		} else {
			array_push( $row, $this->getMissionID() );
		}

		array_push( $rows, $row );
		$data = gui_table( $rows );

		return $data;
	}

	/**
	 * @return mixed
	 */
	public function getMissionID() {
		return $this->MissionID;
	}

	/**
	 * @param mixed $MissionID
	 */
	public function setMissionID( $MissionID ) {
		$this->MissionID = $MissionID;

		return sql_query_single_scalar( "UPDATE im_supplies SET mission_id = " . $MissionID . " WHERE id = " .
		                                $this->ID );
	}


	// $internal - true = for our usage. false = for send to supplier.

	function HtmlLines( $internal, $edit = true ) {
		$data_lines = array();
		my_log( __FILE__, "id = " . $this->ID . " internal = " . $internal );
		$sql = 'select product_id, quantity, id, units '
		       . ' from im_supplies_lines where status = 1 and supply_id = ' . $this->ID;

		$result = sql_query( $sql );

		$data = "<table id=\"del_table\" border=\"1\"><tr><td>בחר</td><td>פריט</td><td>כמות</td><td>יחידות</td>";
		if ( ! $edit ) {
			$data .= gui_cell( "כמות לוקט" );
		}
		$data .= "<td>מידה</td><td>מחיר</td><td>סהכ";

		if ( $internal ) {
			$data .= "<td>מחיר מכירה</td>";
		}

		$data .= "</td>";

		$total = 0;
		// $vat_total = 0;
		$line_number = 0;

		$supplier_id = sql_query_single_scalar( "SELECT supplier FROM im_supplies WHERE id = " . $this->ID );
		// print "supplier_id: " . $supplier_id . "<br/>";

		while ( $row = mysqli_fetch_row( $result ) ) {
			$line_number  = $line_number + 1;
			$line         = "<tr>";
			$prod_id      = $row[0];
			$product_name = get_product_name( $prod_id );
			$quantity     = $row[1];
			$line_id      = $row[2];
			$units        = $row[3];

			// $vat_line = $row[2];
//		$item_price = pricelist_get_price( $prod_id );
			$item_price = Catalog::GetBuyPrice( $prod_id, $supplier_id );
			$total_line = $item_price * $quantity;
			$total      += $total_line;

			$line .= "<td><input id=\"chk" . $line_id . "\" class=\"supply_checkbox\" type=\"checkbox\"></td>";
			// Display item name
			$line .= "<td>" . $product_name . '</td>';
			if ( $edit ) {
				$line .= "<td>" . gui_input( $line_id, $quantity, array( 'onchange="changed(this)"' ) ) . "</td>";
				$line .= "<td>" . gui_input( $line_id, $units, array( 'onchange="changed(this)"' ) ) . "</td>";
			} else {
				$line .= gui_cell( $quantity );
				$line .= gui_cell( $units );
				$line .= gui_cell( "" ); // Collected info
			}

//        $line .= "<td>" . $quantity . "</td>";

			$attr_array = get_post_meta( $prod_id, '_product_attributes' );
			$attr_text  = "";
			if ( is_array( $attr_array ) )
				foreach ( $attr_array as $attr ) {
					foreach ( $attr as $i ) {
						if ( $i['name'] = 'unit' ) {
							$attr_text .= $i['value'];
						}
					}
				} else {
				print "Warning: " . __FILE__ . ":" . __LINE__;
				var_dump( $attr_array );
			}

			$line .= "<td>" . $attr_text . "</td>";

			if ( ! ( $item_price > 0 ) ) {
				$item_price = get_buy_price( $prod_id, $supplier_id );
				$total_line = $item_price * $quantity;
				$total      += $total_line;
			}
			//    $line .= "<td>" . $vat_line . "</td>";
			if ( $item_price > 0 ) {
				$line .= "<td>" . sprintf( '%0.2f', $item_price ) . "</td>";
				$line .= "<td>" . sprintf( '%0.2f', $total_line ) . "</td>";
			} else {
				$line .= "<td></td><td></td>";
			}
			if ( $internal ) {
				$sell_price = get_price( $prod_id );
				$line       .= "<td>" . sprintf( '%0.2f', $sell_price ) . "</td>";
				$line       .= "<td>" . orders_per_item( $prod_id, 1, true ) . "</td>";
			}
			$line  .= "</tr>";
			$terms = get_the_terms( $prod_id, 'product_cat' );
			// print $terms[0]->name . "<br/>";
			array_push( $data_lines, array( $terms[0]->name . "@" . $product_name, $line ) );
		}

		sort( $data_lines );

		$term = "";

		for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
			$line_term = strtok( $data_lines[ $i ][0], '@' );
			if ( $line_term <> $term ) {
				$term = $line_term;
				$data .= gui_row( array( '', "<b>" . $term . "</b>", '', '', '', '', '' ) );
			}
			$line = $data_lines[ $i ][1];
			$data .= trim( $line );
		}
		// $data .= trim( $line );

		$data .= "<tr><td>סהכ</td><td></td><td></td><td></td><td></td><td>" . $total . "</tdtd></tr>";

		$data = str_replace( "\r", "", $data );

		if ( $data == "" ) {
			$data = "\n(0) Records Found!\n";
		}

		$data .= "</table>";

		return "$data";
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->Status;
	}

	public function EditSupply( $internal ) {
		// print "edit<br/>";
		// print nl2br( sql_query_single_scalar( "SELECT text FROM im_supplies WHERE id = " . $this->ID ) ) . "<br/>";
		return $this->HtmlLines( $this->ID, $internal, true );
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->Date;
	}

	/**
	 * @return mixed
	 */
	public function getText() {
		return $this->Text;
	}

	/**
	 * @param mixed $Text
	 */
	public function setText( $Text ) {
		$this->Text = $Text;
		sql_query( "UPDATE im_supplies SET text = " . quote_text( $Text ) .
		           " WHERE id = " . $this->ID );
	}

	/**
	 * @return mixed
	 */
	public function getBusinessID() {
		return $this->BusinessID;
	}

	public function Send() {
		send_supplies( array( $this->ID ) );
	}

	public function Picked() {
		sql_query( "update im_supplies " .
		           " set picked = 1 " .
		           " where id = " . $this->getID()
		);
	}

	public function getSupplierName() {
		return get_supplier_name( $this->getSupplier() );
	}

	/**
	 * @return mixed
	 */
	public function getSupplier() {
		return $this->Supplier;
	}
}

function create_supply( $supplierID, $date = null ) {

	if ( ! $date )
		$date = date('Y-m-d');
	my_log( __METHOD__ . $supplierID );
	$sql = "INSERT INTO im_supplies (date, supplier, status) VALUES " . "('" . $date . "' , " . $supplierID . ", 1)";

	sql_query( $sql );

	return sql_insert_id(  );
}

function supply_add_line( $supply_id, $prod_id, $quantity, $price, $units = 0 ) {
	// For backward
	$s = new Supply( $supply_id );

	return $s->AddLine( $prod_id, $quantity, $price, $units = 0 );
}

function supply_get_supplier_id( $supply_id ) {
	$sql = "SELECT supplier FROM im_supplies WHERE id = " . $supply_id;

	return sql_query_single_scalar( $sql );
}

function supply_get_supplier( $supply_id ) {
	return get_supplier_name( supply_get_supplier_id( $supply_id ) );
}

function supply_get_mission_id( $supply_id ) {
	return sql_query_single_scalar( "SELECT mission_id FROM im_supplies WHERE id = " . $supply_id );
}

function supply_quantity_ordered( $prod_id ) {
	$sql = 'SELECT sum(quantity) FROM im_supplies_lines WHERE product_id = ' . $prod_id
	       . ' AND status = 1 AND supply_id IN (SELECT id FROM im_supplies WHERE status = 1 OR status = 3)';

	return sql_query_single_scalar( $sql );
}

function supply_delete( $supply_id ) {
	$sql    = "SELECT product_id, quantity FROM im_supplies_lines WHERE supply_id = " . $supply_id;
	$result = sql_query( $sql );

	while ( $row = sql_fetch_row( $result ) ) {
		$prod_id  = $row[0];
		$quantity = $row[1];

		$product = new WC_Product( $prod_id );
		if ( $product->managing_stock() ) {
			// print "managed<br/>";
			// print "stock was: " . $product->get_stock_quantity() . "<br/>";

			$product->set_stock_quantity( max( 0, $product->get_stock_quantity() - $quantity ) );
			// print "stock is: " . $product->get_stock_quantity() . "<br/>";
			$product->save();
		}

	}
	$sql = 'UPDATE im_supplies SET status = 9 WHERE id = ' . $supply_id;

	sql_query( $sql );
}

function supply_sent( $supply_id ) {
	global $conn;

	$sql = 'UPDATE im_supplies SET status = 3 WHERE id = ' . $supply_id;

	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die ( 1 );
	}
}

function supply_delete_line( $line_id ) {
	$sql = 'UPDATE im_supplies_lines SET status = 9 WHERE id = ' . $line_id;

	sql_query( $sql );

}

function supply_update_line( $line_id, $q ) {
	$result  = sql_query( "SELECT product_id, quantity FROM im_supplies_lines WHERE id = " . $line_id );
	$row     = sql_fetch_row( $result );
	$prod_id = $row[0];
	$old_q   = $row[1];

	$sql = 'UPDATE im_supplies_lines SET quantity = ' . $q . ' WHERE id = ' . $line_id;

	sql_query( $sql );

	$product = new WC_Product( $prod_id );

	if ( $product->managing_stock() ) {
//		print "managed<br/>";
//		print "old q: " . $old_q . "<br/>";
//		print "stock was: " . $product->get_stock_quantity() . "<br/>";

		$product->set_stock_quantity( $product->get_stock_quantity() + $q - $old_q );
//		print "stock is: " . $product->get_stock_quantity() . "<br/>";
		$product->save();
	}

}

function supply_change_status( $supply_id, $status ) {
	$sql = 'UPDATE im_supplies SET status = ' . $status . ' WHERE id = ' . $supply_id;

	sql_query( $sql );
}

function supply_close( $supply_id ) {
	supply_change_status( $supply_id, SupplyStatus::Closed );
}

function supply_status( $supply_id ) {
	return sql_query_single_scalar( "SELECT status FROM im_supplies WHERE id = " . $supply_id );
}

function send_supplies( $ids ) {
	print_page_header( false );
	print "שולח הזמנות...<br/>";

	foreach ( $ids as $id ) {
		print "id=" . $id . "<br/>";
		$supplier_id = supply_get_supplier_id( $id );

		$site = sql_query_single_scalar( "select site_id from im_suppliers where id = " . $supplier_id );
		if ( $site ) {
			send_supply_as_order( $id );
			continue;
		}
//        print "supplier_id = " .$supplier_id . "</br>";
		$email = sql_query_single_scalar( "SELECT email FROM im_suppliers WHERE id = " . $supplier_id );
//        print "email = " . $email . "<br/>";

		ob_start();

		print_page_header( true );

		print '<body dir="rtl">';

		print_supplies_table( array( $id ), false );

		print '</body>';
		print '</html>';

		$message = " If you cannot read, please press " .
		           gui_hyperlink( "<b>here</b>", get_site_tools_url( 1 ) . "/supplies/supply-get-open.php?id=" . $id );

		$message .= ob_get_contents();

		ob_end_clean();

		send_mail( "הזמנה מספר " . $id, $email . ", info@im-haadama.co.il.test-google-a.com", $message );
		print "הזמנוה מספר " . $id . " (ספק " . get_supplier_name( $supplier_id ) . ") נשלחה ל" . $email . "<br/>";

	}
}

function print_supplies_table( $ids, $internal ) {
//    print "<html dir=\"rtl\">";
	foreach ( $ids as $id ) {
		print "<h1>";
		print "אספקה מספר " . gui_hyperlink( $id, "../supplies/supply-get.php?id= " . $id ) . " " . supply_get_supplier( $id ) . " " . date( "Y-m-d" );
		print "</h1>";
		$s = new Supply( $id );
		print $s->Html( $internal, false );
		print "<p style=\"page-break-after:always;\"></p>";
	}
//    print "</html>";
}

function got_supply( $supply_id, $supply_total, $supply_number, $net_total, $document_type ) {
	global $conn;

	$id  = business_add_transaction( supply_get_supplier_id( $supply_id ), date( 'y-m-d' ), - $supply_total,
		0, $supply_number, 1, - $net_total, $document_type );
	$sql = "UPDATE im_supplies SET business_id = " . $id . " WHERE id = " . $supply_id;
	mysqli_query( $conn, $sql );
	mysqli_query( $conn, "UPDATE im_supplies SET status = " . SupplyStatus::Supplied . " WHERE id = " . $supply_id );
}

function supply_business_info( $supply_id ) {
	$sql = "SELECT business_id FROM im_supplies WHERE id = " . $supply_id;
	$bid = sql_query_single_scalar( $sql );
	if ( $bid > 0 ) {
		print business_supply_info( $bid );
	}
}

function delete_supplies( $params ) {
	for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
		$supply_id = $params[ $pos ];
		my_log( "delete supply " . $supply_id );
		supply_delete( $supply_id );
	}
}

function sent_supplies( $params ) {
	for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
		$supply_id = $params[ $pos ];
		my_log( "sent supply " . $supply_id );
		supply_sent( $supply_id );
	}
}

function delete_supply_lines( $params ) {
	for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
		$line_id = $params[ $pos ];
		my_log( "delete supply line" . $line_id );
		supply_delete_line( $line_id );
	}
}

function update_supply_lines( $params ) {
	for ( $pos = 0; $pos < count( $params ); $pos += 2 ) {
		$line_id = $params[ $pos ];
		$q       = $params[ $pos + 1 ];
		my_log( "update supply line" . $line_id . " q= " . $q );
		print "line_id: " . $line_id . " new q: " . $q . "<br/>";
		supply_update_line( $line_id, $q );
	}
}

//function do_merge_supply( $merged, $other ) {
//	global $conn;
//	if ( supply_get_supplier_id( $merged ) != supply_get_supplier_id( $other ) ) {
//		print "לא ניתן למזג אספקות של ספקים שונים. אספקה $other לא תמוזג ";
//
//		return;
//	}
//	my_log( "merging " . $merged . " and " . $other );
//
//	$sql = "select sum("
//	// Moved the lines to merged supply
////	$sql = "update im_supplies_lines set supply_id = $merged where supply_id = $other ";
//////    . $merged . ", product_id, quantity from im_supplies_lines where "
//////        . " supply_id = " . $other;
////
////	my_log( $sql );
////	mysqli_query( $conn, $sql );
//
//	// TODO: really merge. sum the quantities.
////	$sql = ""
////    $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) . "sql: " . $sql);
////
////    $sql = "update im_supplies set status = 8 " .
////        " where id = " . $other;
////
////    my_log ($sql);
////    $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) . "sql: " . $sql);
////
////    $sql = "update im_supplies_lines set status = 8 " .
////        " where supply_id = " . $other;
////
////    mysqli_query($conn, $sql);
//
//}

function merge_supplies( $params ) {
	for ( $i = 0; $i < count( $params ); $i ++ ) {
		if ( supply_status( $params[ $i ] ) != 1 ) {
			print "ניתן לאחד רק הספקות במצב חדש ";

			return;
		}
	}
	$supply_id = $params[0];
	unset( $params[0] );
	do_merge_supplies( $params, $supply_id );
	supplies_change_status( $params, SupplyStatus::Merged );
//	for ( $pos = 1; $pos < count( $params ); $pos ++ ) {
//		$supply_id = $params[ $pos ];
//		my_log( "merging $supply_id into $params[0]" );
//		do_merge_supply( $params[0], $supply_id );
//		supply_change_status( $supply_id, SupplyStatus::Merged );
//	}
}

function supplies_change_status( $params, $status ) {
	$sql = "UPDATE im_supplies SET status = " . $status .
	       " WHERE id IN (" . rtrim( implode( $params, "," ) ) . ")";

	sql_query( $sql );
}

function do_merge_supplies( $params, $supply_id ) {
	// Read sum of lines.
	$sql     = "SELECT sum(quantity), product_id, 1 FROM im_supplies_lines
WHERE status = 1 AND supply_id IN (" . $supply_id . ", " . rtrim( implode( ",", $params ) ) . ")" .
	           " GROUP BY product_id, status ";
	$result  = sql_query( $sql );
	$results = array();

	while ( $row = mysqli_fetch_row( $result ) ) {
		array_push( $results, $row );
	}

	// Move all lines to be in merged status
	$sql = "UPDATE im_supplies_lines SET status = " . SupplyStatus::Merged . " WHERE supply_id IN ("
	       . $supply_id . ", " . rtrim( implode( ",", $params ) ) . ")";

	sql_query( $sql );

	// Insert new lines
	$sql = "INSERT INTO im_supplies_lines (status, supply_id, product_id, quantity) VALUES ";
	foreach ( $results as $row ) {
		$sql .= "( " . SupplyStatus::NewSupply . ", " . $supply_id . ", " . $row[1] . ", " . $row[0] . "),";
	}
	$sql = rtrim( $sql, "," );
	sql_query( $sql );
}
//
//drop function get_product_name
//DELIMITER //
// CREATE function get_product_name(prod_id int) returns varchar(15)
//   BEGIN
//   declare pname varchar(20)
//     SELECT post_title into pname FROM wp_posts where id = prod_id and uid=1;
//    select pname;
//   END //
// DELIMITER ;
//
//call get_product_name(35);


function SuppliesTable( $week, $status = null ) {
	if ( is_null( $status ) ) {
		$status_query = "status in (1, 3, 5)";
	} else {
		$status_query = "status in (" . comma_implode( $status ) . ")";
	}
	$sql = "SELECT id, supplier, status, date(date), paid_date, status, business_id FROM im_supplies WHERE " . $status_query . "  AND 
			 first_day_of_week(date) = '" . $week . "'"
	             . " ORDER BY 4, 3, 2";

//	print $sql;

	return DoSuppliesTable( $sql );
}

function DoSuppliesTable( $sql )
{
	$result = sql_query( $sql );
	my_log( $sql);
	$has_lines = false;

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$result = sql_query( $sql );

	$lines = array();
	array_push( $lines, array( "בחר", "מספר", "תאריך", "משימה", "ספק", "סטטוס", "סכום", "תאריך תשלום" ) );
//	$data = "<table border='1'><tr><td>בחר</td><td><h3>מספר</h3></td><td><h3>תאריך</h3></td><td><h3>ספק</h3></td><td>סטטוס</td><td>סכום</td>";
	// $data .= gui_cell( "תאריך תשלום" ) . "</tr>";
	while ( $row = mysqli_fetch_row( $result ) ) {
		$supply_id   = $row[0];
		$supplier_id = $row[1];
		$status      = $row[5];
		$line        = array(
			gui_checkbox( "chk" . $supply_id, "supply_checkbox", "", "" ),
			gui_hyperlink( $supply_id, "supply-get.php?id=" . $supply_id ),
			$row[3],
			gui_select_mission( "mis_" . $supply_id, supply_get_mission_id( $supply_id ), "onchange=mission_changed(" . $supply_id . ")" ),
			get_supplier_name( $supplier_id ),
			get_supply_status_name( $supply_id )
		);

//		$value       = "<tr><td><input id=\"chk" . $supply_id . "\" class=\"supply_checkbox\" type=\"checkbox\"></td>";
//		$value       .= "<td><a href=\"supply-get.php?id=" . $supply_id . "\">" . $supply_id . '</a></td>';
//		$value       .= "<td>" . $row[3] . '</td>';
//		$value       .= "<td>" . get_supplier_name( $supplier_id ) . '</td>';
//		$value       .= "<td>" . get_supply_status_name( $supply_id ) . '</td>';
		if ( $status = 5 ) {
			array_push( $line, $row[6] );
			$business_id = $row[6];
//			 print "business id: " . $business_id . "<br/>";
			if ( $business_id > 0 ) {
				$amount = sql_query_single_scalar( "select amount from im_business_info where id = " . $business_id );
				array_push( $line, $amount );
//				$value  .= gui_cell( $amount );
			}
			$date = $row[4];
			if ( $date == "0000-00-00" ) {
				$date = null;
			}
			array_push( $line, $date );
//			$value .= gui_cell( $date );
		}

//		$value       .= "</tr>";
//
//		$data      .= $value;
		array_push( $lines, $line );
		$has_lines = true;
	}
	// $data .= "</table>";

	$data = gui_table( $lines );
	if ( $has_lines ) {
		return $data;
	}

	return null;

}

function display_active_supplies( $status ) {
	$in_key = info_get( 'inventory_in', true, 0);
	$sql    = "SELECT id, supplier, status, date(date), paid_date, status, business_id FROM im_supplies WHERE status IN (" .
	          implode( ",", $status ) . ") AND id > " . $in_key . "\n" .
	          " and date > DATE_SUB(curdate(), INTERVAL 2 WEEK)"
	          . " ORDER BY 4, 3, 2";

	return DoSuppliesTable( $sql );
}

function create_delta() {
	$sql = "select prod_id, q from i_total where q < 0";

	$s = create_supply( 0 );
	print "sid = " . $s . "<br/>";
	$rows = sql_query_array( $sql );
	foreach ( $rows as $row ) {
		// var_dump($row);
		//	print "add line " . $row[0] . " " . $row[1] . "<br/>";
		supply_add_line( $s, $row[0], - $row[1], 0 );
	}
}

function create_supplier_order( $supplier_id, $ids, $date = null ) {
	$supply_id = create_supply( $supplier_id, $date );
	print "creating supply " . gui_hyperlink( $supply_id, "supply-get.php?id=" . $supply_id) . "...";

	for ( $pos = 0; $pos < count( $ids ); $pos += 3 ) {
		$prod_id  = $ids[ $pos ];
		$quantity = $ids[ $pos + 1 ];
		$units    = $ids[ $pos + 2 ];
		// print "adding " . $prod_id . " quantity " . $quantity . " units " . $units . "<br/>";
		$price = get_buy_price( $prod_id, $supplier_id);

		// Calculate the price
//        $pricelist = new PriceList($supplier_id);
//        $buy_price = $pricelist->Get($product_name);
//        $sell_price = calculate_price($buy_price, $supplier_id);

//        my_log("supplier_id = " . $supplier_id . " name = " . $product_name);
		supply_add_line( $supply_id, $prod_id, $quantity, $price, $units );
	}
	print "done<br/>";
}

function create_supplies( $params ) {
	my_log( __METHOD__);
	$supplies = array();
	for ( $i = 0; $i < count( $params ); $i += 4 ) {
		$prod_id  = $params[ $i + 0 ];
		$supplier = $params[ $i + 1 ];
		$quantity = $params[ $i + 2 ];
		$units    = $params[ $i + 3 ];
		$price    = get_buy_price( $prod_id, $supplier );

		if ( is_null( $supplies[ $supplier ] ) ) {
			$supplies[ $supplier ] = create_supply( $supplier );
		}
		supply_add_line( $supplies[ $supplier ], $prod_id, $quantity, $price, $units );
		// print $prod_id . " " . $supplier . " " . $quantity . " " . $units . "<br/>";
	}
}


function supply_set_pay_date( $id, $date ) {
	$sql = "update im_supplies set paid_date = '" . $date . "' where id = " . $id;
	print $sql;
	sql_query( $sql);
}

function display_date( $date ) {
	if ( $date != "0000-00-00" ) {
		print $date;
	}
}

function display_status( $status ) {
	switch ( $status ) {
		case SupplyStatus::Supplied:
			print "סופק";
			break;
		case SupplyStatus::Sent:
			print "נשלח";
			break;
		case SupplyStatus::NewSupply:
			print "חדש";
			break;

	}
}

function supplier_report_data( $supplier_id, $start_date, $end_date ) {
	$sql = "SELECT prod_id, sum(quantity), get_product_name(prod_id) FROM im_delivery_lines dl\n"

	       . "JOIN im_delivery d\n"

	       . "WHERE date > '" . $start_date . "'\n"

	       . "AND date <= '" . $end_date . "'\n"

	       . "AND dl.delivery_id = d.id\n"

	       . "AND prod_id IN (SELECT product_id FROM im_supplier_mapping WHERE supplier_id = " . $supplier_id . ")\n"

	       . "GROUP BY 1\n"

	       . "ORDER BY 2 DESC";


// print $sql;
	return sql_query_array( $sql, true );
}

function send_supply_as_order( $id ) {
	$supplier_id = supply_get_supplier_id( $id );
	$row         = sql_query_single_assoc( "select site_id, user_id from im_suppliers where id = " . $supplier_id );

	$site_id    = $row["site_id"];
	$user_id    = $row["user_id"];
	$mission_id = 0;

	$prods      = array();
	$quantities = array();
	$units      = array();
	$sql        = "select product_id, quantity, units from im_supplies_lines where supply_id = " . $id;

	print $sql;
	$result = sql_query( $sql );
	while ( $row = sql_fetch_row( $result ) ) {
		$prod_id = $row[0];
//		print "prod_id= " . $prod_id . "<br/>";
//		var_dump($row); print "<br/>";
		$remote_prod_id = supplier_prod_id( $prod_id, $supplier_id );
		if ( $remote_prod_id ) {
			array_push( $prods, $remote_prod_id );
			array_push( $quantities, $row[1] );
			array_push( $units, $row[2] );
		} else {
			print "no remote prod" . get_product_name( $prod_id ) . "<br/>";
		}
	}
	$remote = "orders/orders-post.php?operation=create_order&user_id=" . $user_id .
	          "&prods=" . implode( ",", $prods ) .
	          "&quantities=" . implode( ",", $quantities ) .
	          "&comments=" . urlencode( "נשלח מאתר " . ImMultiSite::LocalSiteName() ) .
	          "&units=" . implode( ",", $units ) . // TODO: support units.
	          "&mission_id=" . $mission_id;

	print $remote . " site: " . $site_id . "<br/>";
	print ImMultiSite::sExecute( $remote, $site_id );
}