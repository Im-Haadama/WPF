<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:45
 */
require_once( '../r-shop_manager.php' );
require_once( 'supplies.php' );
require_once( '../orders/orders-common.php' );
require_once( '../gui/inputs.php' );
require_once( "../business/business-post.php" );
require_once( "../business/business_info.php" );

// print header_text(false, true, false);
if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	switch ( $operation ) {
		case "supply_pay":
			print "supply pay<br/>";
			$id   = $_GET["id"];
			$date = $_GET["date"];
			supply_set_pay_date( $id, $date );
			break;
		case "get_business":
			// print header_text(false); DONT!!!
			$supply_id = $_GET["supply_id"]; // מספר הספקה שלנו
			print supply_business_info( $supply_id );
			break;

		case "got_supply":
			$supply_id     = $_GET["supply_id"]; // מספר הספקה שלנו
			$supply_total  = $_GET["supply_total"]; // סכום
			$supply_number = $_GET["supply_number"]; // מספר תעודת משלוח
			got_supply( $supply_id, $supply_total, $supply_number );
			break;

		case "send":
			$params = $_GET["id"];
			$ids    = explode( ',', $params );
			send_supplies( $ids );
			sent_supplies( $ids );
			break;

		case "print":
			$params = $_GET["id"];
			$ids    = explode( ',', $params );
			print_supplies_table( $ids, true );
			break;

		case "create_supply":
			print "create supply<br/>";
//			"&prods=" + prods.join() +
//			"&quantities=" + quantities.join() +
			// "&comments="   + encodeURI(comment) +
//			"&units=" + units.join() +

			$supplier_id = $_GET["supplier_id"];
			my_log( "supplier_id=" . $supplier_id );

			$create_info = $_GET["create_info"];
			$ids         = explode( ',', $create_info );
			create_supplier_order( $supplier_id, $ids );
			break;

		case "get_supply":
			$supply_id = $_GET["id"];
			$internal  = $_GET["internal"];
			$Supply    = new Supply( $supply_id );
			$Supply->EditSupply( $internal );
			break;

		case "get_supply_lines":
			$supply_id = $_GET["id"];
			$internal  = $_GET["internal"];
			print_supply_lines( $supply_id, $internal );
			break;

		case "get_comment":
			$supply_id = $_GET["id"];
			print_comment( $supply_id );
			break;

		case "get_all":
			my_log( "get all supplies" );
			print gui_header( 2, "אספקות לטיפול" );
			print display_active_supplies( array( 1 ) );
			print gui_header( 2, "אספקות בדרך" );
			print display_active_supplies( array( 3 ) );
			print gui_header( 2, "הזמנות התקבלו" );
			print display_active_supplies( array( 5 ) );
			break;

		case "delete_supplies":
			my_log( "delete supplies" );
			$params = explode( ',', $_GET["params"] );
			delete_supplies( $params );
			break;

		case "sent_supplies":
			my_log( "sent supplies" );
			$params = explode( ',', $_GET["params"] );
			sent_supplies( $params );
			break;

		case "delete_lines":
			my_log( "delete lines" );
			$params = explode( ',', $_GET["params"] );
			delete_supply_lines( $params );
			break;

		case "merge_supplies":
			my_log( "merge supplies" );
			$params = explode( ',', $_GET["params"] );
			merge_supplies( $params );
			break;

		case 'update_lines':
			my_log( "update lines" );
			$params = explode( ',', $_GET["params"] );
			update_supply_lines( $params );
			break;

		case 'save_comment':
			$comment = $_GET["text"];
			print $comment . "<br/>";
			$supply_id = $_GET["id"];
			print $supply_id . "<br/>";
			$sql = "UPDATE im_supplies SET text = '" . $comment .
			       "' WHERE id = " . $supply_id;

			global $conn;

			mysqli_query( $conn, $sql );
			print $sql;
			break;

		case "add_item":
			print "add_line<br/>";
			$name = $_GET["name"];
			print $name . "<br/>";
			if ( ! strlen( $name ) > 2 ) {
				die ( "no product name" );
			}
			$q = $_GET["quantity"];
			print $q . "<br/>";
			if ( ! is_numeric( $q ) ) {
				die ( "no quantity" );
			}
			$supply_id = $_GET["supply_id"];
			print $supply_id . "<br/>";
			if ( ! is_numeric( $supply_id ) ) {
				die ( "no supply_id" );
			}
			$prod_id = get_product_id_by_name( $name );
			print "name = " . $name . ", prod_id = " . $prod_id . "<br/>";

			if ( ! is_numeric( $prod_id ) ) {
				die ( "no prod_id for " . $name . "<br/>" );
			}
			$supply = new Supply( $supply_id );
			print 'id=' . $supply->getSupplier() . "<br/>";
			$price = get_buy_price( $prod_id, $supply->getSupplier() );
			supply_add_line( $supply_id, $prod_id, $q, $price );
			break;

		default:
			print $operation . " not handled <br/>";

	}
}

function gui_select_supplier() {

	return gui_select_table( "supplier_select", "im_suppliers", null, "", "", "supplier_name",
		null, true, true );
//		$sql_where );
}


my_log( __FILE__, $operation );

