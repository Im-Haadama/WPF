<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:45
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
require_once( 'Supply.php' );
require_once( '../orders/orders-common.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
//require_once( "../business/business-post.php" );
// require_once( "../business/business_info.php" );

// my_log(__FILE__);
// print header_text(false, true, false);
if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	// my_log($operation);
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
			supply_business_info( $supply_id );
			break;

		case "got_supply":
			$supply_id     = $_GET["supply_id"]; // מספר הספקה שלנו
			$supply_total  = $_GET["supply_total"]; // סכום
			$supply_number = $_GET["supply_number"]; // מספר תעודת משלוח
			$net_amount    = get_param( "net_amount" );
			$is_invoice    = get_param( "is_invoice" );
			$doc_type      = $is_invoice ? ImDocumentType::invoice : ImDocumentType::supply;
			$document_date = get_param( "document_date" );
			$bid           = got_supply( $supply_id, $supply_total, $supply_number, $net_amount, $doc_type, $document_date );
			if ( ! $bid ) {
				print "fail";
			} else {
				print $bid;
			}
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

		case "create_delta":
			create_delta();
			break;

		case "create_supply":
			print "create supply ";

			$date        = get_param( "date" );
			$supplier_id = $_GET["supplier_id"];
			my_log( "supplier_id=" . $supplier_id );

			$create_info = $_GET["create_info"];
			$ids         = explode( ',', $create_info );
			$supply      = Supply::CreateSupply( $supplier_id );
			if ( ! $supply->getID() ) {
				return false;
			}
			for ( $pos = 0; $pos < count( $ids ); $pos += 3 ) {
				$prod_id  = $ids[ $pos ];
				$quantity = $ids[ $pos + 1 ];
				$units    = $ids[ $pos + 2 ];
				// print "adding " . $prod_id . " quantity " . $quantity . " units " . $units . "<br/>";
				$price = get_buy_price( $prod_id, $supplier_id );
				if ( ! $supply->AddLine( $prod_id, $quantity, $price, $units ) ) {
					return false;
				}
			}
			print $supply->getID();
			$mission_id = get_param( "mission_id" );
			if ( $mission_id ) {
				$s->setMissionID( $mission_id );
			}
			print " done";
			break;

		case "create_supplies":
			$params = $_GET["params"];
			create_supplies( explode( ',', $params ) );
			break;

		case "get_supply":
			$supply_id = $_GET["id"];
			$internal  = isset( $_GET["internal"] );
			$Supply    = new Supply( $supply_id );
			print $Supply->Html( $internal, true );
			break;

		case "get_supply_lines":
			$supply_id = $_GET["id"];
			$internal  = isset( $_GET["internal"] );
			HtmlLines( $supply_id, $internal );
			break;

		case "get_comment":
			$supply_id = $_GET["id"];
			$s         = new Supply( $supply_id );
			print $s->getText();
			break;

		case "get_all":
			my_log( "get all supplies" );
			print gui_header( 2, "אספקות לטיפול" );
			print display_active_supplies( array( 1 ) );
			print gui_header( 2, "אספקות בדרך" );
			print display_active_supplies( array( 3 ) );
			print gui_header( 2, "הזמנות התקבלו" );
			print display_active_supplies( array( 5 ) );
			print gui_hyperlink( "ארכיון", "c-get-all-supplies.php" );
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
			$params = get_param_array( "params" );
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

//			var request = post_file + "?operation=update_field" +
//			              "&field_name=" + field_name +
//			              "&value=" + encodeURI(value) +
//			              "&id=" + id;

		case 'update_field':
			$field_name = get_param( "field_name" );
			$value      = get_param( "value" );
			$id         = get_param( "id" );
			$s          = new Supply( $id );
			$s->UpdateField( $field_name, $value );

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
			// print "add_line<br/>";
			$prod_id = $_GET["prod_id"];
			// print $name . "<br/>";
			if ( ! $prod_id > 0 ) {
				die ( "no product id" );
			}
			$q = $_GET["quantity"];
			// print $q . "<br/>";
			if ( ! is_numeric( $q ) ) {
				die ( "no quantity" );
			}
			$supply_id = $_GET["supply_id"];
			print "supply id = " . $supply_id . "<br/>";
			if ( ! is_numeric( $supply_id ) ) {
				die ( "no supply_id" );
			}
			// $prod_id = get_product_id_by_name( $name );
			// print "name = " . $name . ", prod_id = " . $prod_id . "<br/>";

//			if ( ! is_numeric( $prod_id ) ) {
//				die ( "no prod_id for " . $name . "<br/>" );
//			}
			$supply = new Supply( $supply_id );
			// var_dump($supply);
			print "prod id=" . $prod_id . '<br/>';
			print 'supplier id=' . $supply->getSupplier() . "<br/>";
			$price = get_buy_price( $prod_id, $supply->getSupplier() );
			print "price: " . $price . '<br/>';
			supply_add_line( $supply_id, $prod_id, $q, $price );
			break;

		case "set_mission":
			$supply_id  = $_GET["supply_id"];
			$mission_id = $_GET["mission_id"];
			$s          = new Supply( $supply_id );
			$s->setMissionID( $mission_id);
			break;

		case "delivered":
			$ids = explode( ",", $_GET["ids"] );
			foreach ( $ids as $supply_id ) {
				got_supply( $supply_id, 0, 0 );
			}
			print "delivered";

			break;

		case "create_from_file":
			$supplier_id = get_param( "supplier_id" );
			print "יוצר הספקה לספק " . $supplier_id . " <br/>";

			$tmp_file = $_FILES["fileToUpload"]["tmp_name"];
			$s        = Supply::CreateFromFile( $tmp_file, $supplier_id, true );
			if ( $s ) {
				$s->EditSupply( true );
			}

			break;


		default:
			print $operation . " not handled <br/>";

	}
}




