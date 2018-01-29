<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:58
 */

// test
include_once( "../r-shop_manager.php" );
require_once( "../gui/inputs.php" );
require_once( "../mail.php" );
require_once( "../catalog/catalog.php" );

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

	/**
	 * Supply constructor.
	 *
	 * @param int $ID
	 */
	public function __construct( $ID ) {
		$this->ID         = $ID;
		$row              = sql_query_single( "SELECT status, date, supplier, text, business_id FROM im_supplies" );
		$this->Status     = $row[0];
		$this->Date       = $row[1];
		$this->Supplier   = $row[2];
		$this->Text       = $row[3];
		$this->BusinessID = $row[4];
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->Status;
	}

	// $internal - true = for our usage. false = for send to supplier.
	public function PrintSupply( $internal ) {
//		var_dump($this);
		print nl2br( sql_query_single_scalar( "SELECT text FROM im_supplies WHERE id = " . $this->ID ) ) . "<br/>";
		switch ( $this->Status ) {
			case SupplyStatus::NewSupply:
				print_supply_lines( $this->ID, $internal, false );
				break;
			case SupplyStatus::Sent:
				print_supply_lines( $this->ID, $internal, false );
				break;
			default:
				print_supply_lines( $this->ID, $internal, false );
				break;

		}
	}

	public function EditSupply( $internal ) {
		print "edit<br/>";
		print nl2br( sql_query_single_scalar( "SELECT text FROM im_supplies WHERE id = " . $this->ID ) ) . "<br/>";
		print_supply_lines( $this->ID, $internal, true );
	}

}
function create_supply( $supplierID ) {
	global $conn;
	$sql = "INSERT INTO im_supplies (date, supplier, status) VALUES " . "(CURRENT_TIMESTAMP, " . $supplierID . ", 1)";

	sql_query( $sql );

	return mysqli_insert_id( $conn );
}

function supply_add_line( $supply_id, $prod_id, $quantity, $units = 0 ) {
	$sql = "INSERT INTO im_supplies_lines (supply_id, product_id, quantity, units) VALUES "
	       . "( " . $supply_id . ", " . $prod_id . ", " . $quantity . ", " . $units . ")";

	sql_query( $sql );
}

function supply_get_supplier_id( $supply_id ) {
	$sql = "SELECT supplier FROM im_supplies WHERE id = " . $supply_id;

	return sql_query_single_scalar( $sql );
}

function supply_get_supplier( $supply_id ) {
	return get_supplier_name( supply_get_supplier_id( $supply_id ) );
}

function supply_quantity_ordered( $prod_id ) {
	$sql = 'SELECT sum(quantity) FROM im_supplies_lines WHERE product_id = ' . $prod_id
	       . ' AND status = 1 AND supply_id IN (SELECT id FROM im_supplies WHERE status = 1 OR status = 3)';

	return sql_query_single_scalar( $sql );
}

function supply_delete( $supply_id ) {
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
	$sql = 'UPDATE im_supplies_lines SET quantity = ' . $q . ' WHERE id = ' . $line_id;

	sql_query( $sql );
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


function print_supply_lines( $id, $internal, $edit = true ) {
	$data_lines = array();
	my_log( __FILE__, "id = " . $id . " internal = " . $internal );
	$sql = 'select product_id, quantity, id, units '
	       . ' from im_supplies_lines where status = 1 and supply_id = ' . $id;

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

	$supplier_id = sql_query_single_scalar( "SELECT supplier FROM im_supplies WHERE id = " . $id );
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
		foreach ( $attr_array as $attr ) {
			foreach ( $attr as $i ) {
				if ( $i['name'] = 'unit' ) {
					$attr_text .= $i['value'];
				}
			}
		}

		$line .= "<td>" . $attr_text . "</td>";

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
		$line .= "</tr>";

		array_push( $data_lines, array( $product_name, $line ) );
	}

	sort( $data_lines );

	for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
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

	print "$data";
}

function print_comment( $id ) {
	$sql = "SELECT text FROM im_supplies" .
	       " WHERE id = " . $id;

	// print $sql;
	print sql_query_single_scalar( $sql );
}

function send_supplies( $ids ) {
	print_page_header( false );
	print "שולח הזמנות...<br/>";

	foreach ( $ids as $id ) {
		$supplier_id = supply_get_supplier_id( $id );
//        print "supplier_id = " .$supplier_id . "</br>";
		$email = sql_query_single_scalar( "SELECT email FROM im_suppliers WHERE id = " . $supplier_id );
//        print "email = " . $email . "<br/>";

		ob_start();

		print_page_header( true );

		print '<body dir="rtl">';

		print_supplies( array( $id ), false );

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

function print_supplies( $ids, $internal ) {
//    print "<html dir=\"rtl\">";
	foreach ( $ids as $id ) {
		print "<h1>";
		print "אספקה מספר " . gui_hyperlink( $id, "../supplies/supply-get.php?id= " . $id ) . " " . supply_get_supplier( $id ) . " " . date( "Y-m-d" );
		print "</h1>";
		$s = new Supply( $id );
		$s->PrintSupply( $internal );
		print "<p style=\"page-break-after:always;\"></p>";
	}
//    print "</html>";
}

function got_supply( $supply_id, $supply_total, $supply_number ) {
	global $conn;

	$id  = business_add_transaction( supply_get_supplier_id( $supply_id ), date( 'y-m-d' ), - $supply_total, 0, $supply_number, 1 );
	$sql = "UPDATE im_supplies SET business_id = " . $id . " WHERE id = " . $supply_id;
	mysqli_query( $conn, $sql );
	mysqli_query( $conn, "UPDATE im_supplies SET status = " . SupplyStatus::Supplied . " WHERE id = " . $supply_id );
}

function supply_business_info( $supply_id ) {
	global $conn;
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

function display_active_supplies( $status ) {
	global $conn;

	$has_lines = false;
	$sql       = "SELECT id, supplier, status, date(date) FROM im_supplies WHERE status IN (" .
	             implode( ",", $status ) . ") AND id > (SELECT info_data FROM im_info where info_key='inventory_in')"
	             . " ORDER BY 4, 3, 2";

	// print $sql;
	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$result = sql_query( $sql );

	$data = "<table border='1'><tr><td>בחר</td><td><h3>מספר</h3></td><td><h3>תאריך</h3></td><td><h3>ספק</h3></td><td>סטטוס</td></tr>";
	while ( $row = mysqli_fetch_row( $result ) ) {
		$supply_id   = $row[0];
		$supplier_id = $row[1];
		$value       = "<tr><td><input id=\"chk" . $supply_id . "\" class=\"supply_checkbox\" type=\"checkbox\"></td>";
		$value       .= "<td><a href=\"supply-get.php?id=" . $supply_id . "\">" . $supply_id . '</a></td>';
		$value       .= "<td>" . $row[3] . '</td>';
		$value       .= "<td>" . get_supplier_name( $supplier_id ) . '</td>';
		$value       .= "<td>" . get_supply_status_name( $supply_id ) . '</td>';
		$value       .= "</tr>";

		$data      .= $value;
		$has_lines = true;
	}
	$data .= "</table>";

	if ( $has_lines ) {
		return $data;
	}

	return null;
	// print $data;
}

function create_supplier_order( $supplier_id, $ids ) {
	$supply_id = create_supply( $supplier_id );
	print "created supply " . $supply_id . "<br/>";

	for ( $pos = 0; $pos < count( $ids ); $pos += 3 ) {
		$prod_id  = $ids[ $pos ];
		$quantity = $ids[ $pos + 1 ];
		$units    = $ids[ $pos + 2 ];
		print "adding " . $prod_id . " quantity " . $quantity . " units " . $units . "<br/>";

		// Calculate the price
//        $pricelist = new PriceList($supplier_id);
//        $buy_price = $pricelist->Get($product_name);
//        $sell_price = calculate_price($buy_price, $supplier_id);

//        my_log("supplier_id = " . $supplier_id . " name = " . $product_name);
		supply_add_line( $supply_id, $prod_id, $quantity, $units );

	}
}