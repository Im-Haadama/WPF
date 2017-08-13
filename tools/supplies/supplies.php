<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:58
 */

// test
include_once( "../tools.php" );
require_once( "../gui/inputs.php" );

// Supply status: 1 = new, 3 = sent, 5 = supplied, 8 = merged into other, 9 = delete

abstract class SupplyStatus {
	const NewSupply = 1;
	const Sent = 3;
	const Supplied = 5;
	const Merged = 8;
	const Deleted = 9;
}

function create_supply( $supplierID ) {
	$sql = "INSERT INTO im_supplies (date, supplier, status) VALUES " . "(CURRENT_TIMESTAMP, " . $supplierID . ", 1)";

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );

	$supply_id = mysql_insert_id();

	return $supply_id;
}

function supply_add_line( $supply_id, $prod_id, $quantity ) {
	$sql = "INSERT INTO im_supplies_lines (supply_id, product_id, quantity) VALUES "
	       . "( " . $supply_id . ", " . $prod_id . ", " . $quantity . ")";

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );
}

function supply_get_supplier_id( $supply_id ) {
	$sql = "SELECT supplier FROM im_supplies WHERE id = " . $supply_id;

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );

	$row = mysql_fetch_row( $export );

	return $row[0];
}

function supply_get_supplier( $supply_id ) {
	return get_supplier_name( supply_get_supplier_id( $supply_id ) );
}

function supply_quantity_ordered( $prod_id ) {
	$sql = 'SELECT sum(quantity) FROM im_supplies_lines WHERE product_id = ' . $prod_id
	       . ' AND status = 1 AND supply_id IN (SELECT id FROM im_supplies WHERE status = 1 OR status = 3)';

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );

	$row = mysql_fetch_row( $export );

	my_log( "prod_id = " . $prod_id . " ordered = " . $row[0] );

	return $row[0];

}

function supply_delete( $supply_id ) {
	$sql = 'UPDATE im_supplies SET status = 9 WHERE id = ' . $supply_id;

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );

	$row = mysql_fetch_row( $export );
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

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );

	$row = mysql_fetch_row( $export );
}

function supply_update_line( $line_id, $q ) {
	$sql = 'UPDATE im_supplies_lines SET quantity = ' . $q . ' WHERE id = ' . $line_id;

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "sql: " . $sql );

	$row = mysql_fetch_row( $export );
}

function supply_change_status( $supply_id, $status ) {
	global $conn;

	$sql = 'UPDATE im_supplies SET status = ' . $status . ' WHERE id = ' . $supply_id;

	if ( ! mysqli_query( $conn, $sql ) ) {
		sql_error( $sql );
	}

}

function supply_close( $supply_id ) {
	supply_change_status( $supply_id, SupplyStatus::Closed );
}

function supply_status( $supply_id ) {
	return sql_query_single_scalar( "SELECT status FROM im_supplies WHERE id = " . $supply_id );
}

function print_supply( $id, $internal ) {
	print nl2br( sql_query_single_scalar( "SELECT text FROM im_supplies WHERE id = " . $id ) ) . "<br/>";
	print_supply_lines( $id, $internal );
}

function print_supply_lines( $id, $internal ) {

	$data_lines = array();
	my_log( __FILE__, "id = " . $id . " internal = " . $internal );
	$sql = 'select product_id, quantity, id '
	       . ' from im_supplies_lines where status = 1 and supply_id = ' . $id;

	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$data = "<table id=\"del_table\" border=\"1\"><tr><td>בחר</td><td>פריט</td><td>כמות</td><td>מידה</td><td>מחיר</td><td>סהכ";

	if ( $internal ) {
		$data .= "<td>מחיר מכירה</td>";
	}

	$data .= "</td>";

	$total = 0;
	// $vat_total = 0;
	$line_number = 0;

	while ( $row = mysql_fetch_row( $export ) ) {
		$line_number  = $line_number + 1;
		$line         = "<tr>";
		$prod_id      = $row[0];
		$product_name = get_product_name( $prod_id );
		$quantity     = $row[1];
		$line_id      = $row[2];

		// $vat_line = $row[2];
		$item_price = pricelist_get_price( $prod_id );
		$total_line = $item_price * $quantity;
		$total      += $total_line;

		$line .= "<td><input id=\"chk" . $line_id . "\" class=\"supply_checkbox\" type=\"checkbox\"></td>";
		// Display item name
		$line .= "<td>" . $product_name . '</td>';
		$line .= "<td>" . gui_input( $line_id, $quantity, array( 'onchange="changed(this)"' ) ) . "</td>";
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
			$line       .= "<td>" . get_clients_per_product( $prod_id ) . "</td>";

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
		print "אספקה מספר " . $id . " " . supply_get_supplier( $id ) . " " . date( "Y-m-d" );
		print "</h1>";
		print_supply( $id, $internal );
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

function do_merge_supply( $merged, $other ) {
	global $conn;
	if ( supply_get_supplier_id( $merged ) != supply_get_supplier_id( $other ) ) {
		print "לא ניתן למזג אספקות של ספקים שונים. אספקה $other לא תמוזג ";

		return;
	}
	my_log( "merging " . $merged . " and " . $other );

	// Moved the lines to merged supply
	$sql = "update im_supplies_lines set supply_id = $merged where supply_id = $other ";
//    . $merged . ", product_id, quantity from im_supplies_lines where "
//        . " supply_id = " . $other;

	my_log( $sql );
	mysqli_query( $conn, $sql );

	// TODO: really merge. sum the quantities.
//    $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) . "sql: " . $sql);
//
//    $sql = "update im_supplies set status = 8 " .
//        " where id = " . $other;
//
//    my_log ($sql);
//    $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) . "sql: " . $sql);
//
//    $sql = "update im_supplies_lines set status = 8 " .
//        " where supply_id = " . $other;
//
//    mysqli_query($conn, $sql);

}

function merge_supplies( $params ) {
	for ( $i = 0; $i < count( $params ); $i ++ ) {
		if ( supply_status( $params[ $i ] ) != 1 ) {
			print "ניתן לאחד רק הספקות במצב חדש ";

			return;
		}
	}
	for ( $pos = 1; $pos < count( $params ); $pos ++ ) {
		$supply_id = $params[ $pos ];
		my_log( "merging $supply_id into $params[0]" );
		do_merge_supply( $params[0], $supply_id );
		supply_change_status( $supply_id, SupplyStatus::Merged );
	}
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


function get_clients_per_product( $prod_id ) {
	return orders_per_item( $prod_id, 1, true );
}

function display_active_supplies( $status ) {
	global $conn;

	$has_lines = false;
	$sql       = "SELECT id, supplier, status, date(date) FROM im_supplies WHERE status IN (" .
	             implode( ",", $status ) . ") AND id > (SELECT inventory_in FROM im_info)"
	             . " ORDER BY 4, 3, 2";

	$result = mysqli_query( $conn, $sql );

	if ( ! $result ) {
		sql_error( $sql );
		die( 1 );
	}
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

	$data = "<table border='1'><tr><td>בחר</td><td><h3>מספר</h3></td><td><h3>תאריך</h3></td><td><h3>ספק</h3></td><td>סטטוס</td></tr>";
	while ( $row = mysql_fetch_row( $export ) ) {
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

	for ( $pos = 0; $pos < count( $ids ); $pos += 2 ) {
		$prod_id  = $ids[ $pos ];
		$quantity = $ids[ $pos + 1 ];
		print "adding " . $prod_id . " quantity " . $quantity . "<br/>";

		// Calculate the price
//        $pricelist = new PriceList($supplier_id);
//        $buy_price = $pricelist->Get($product_name);
//        $sell_price = calculate_price($buy_price, $supplier_id);

//        my_log("supplier_id = " . $supplier_id . " name = " . $product_name);
		supply_add_line( $supply_id, $prod_id, $quantity );

	}
}