<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/01/19
 * Time: 11:59
 */
require_once( '../r-shop_manager.php' );
// require_once 'invoices-common.php';
require_once '../delivery/delivery.php';
require_once( "../suppliers/gui.php" );


if ( ! current_user_can( "show_business_info" ) ) {
	print "no permission";
	die ( 2 );
}

print header_text( false, false );

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
}

print Core_Html::GuiButton( "btn_new", "show_create_invoice()", "חשבונית חדשה" );

// require( "new-business.php" );

?>

<script type="text/javascript" src="/core/gui/client_tools.js"></script>

<script>
    function show_create_invoice() {
        var new_order = document.getElementById("new_invoice");
        new_invoice.style.display = 'block';
        // add_line();
        document.getElementById("supplier_select").focus();
    }

</script>

<div id="new_invoice">
	<?php
	print gui_select_supplier();
	?>
</div>
<!--<script>-->
<!--    function mission_changed(invoice_id) {-->
<!--        // "mis_"-->
<!--        //var invoice_id = field.name.substr(4);-->
<!--        var mis = document.getElementById("mis_" + invoice_id);-->
<!--        var mission_id = get_value(mis);-->
<!--//            $mission_id = $_GET["id"];-->
<!--//            $invoice_id   = $_GET["invoice_id"];-->
<!--        execute_url("invoices-post.php?operation=mission&invoice_id=" + invoice_id + "&id=" + mission_id);-->
<!--    }-->
<!---->
<!--    function start_handle() {-->
<!--        var collection = document.getElementsByClassName("select_invoice_wc-pending");-->
<!--        var invoice_ids = new Array();-->
<!---->
<!--        xmlhttp = new XMLHttpRequest();-->
<!--        xmlhttp.onreadystatechange = function () {-->
<!--            // Wait to get query result-->
<!--            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished-->
<!--            {-->
<!--                window.location = window.location;-->
<!--            }-->
<!--        }-->
<!---->
<!--        for (var i = 0; i < collection.length; i++) {-->
<!--            var invoice_id = collection[i].id.substr(4);-->
<!--            if (document.getElementById("chk_" + invoice_id).checked)-->
<!--                invoice_ids.push(invoice_id);-->
<!--        }-->
<!--        collection = document.getElementsByClassName("select_invoice_wc-on-hold");-->
<!--        for (var i = 0; i < collection.length; i++) {-->
<!--            invoice_id = collection[i].id.substr(4);-->
<!--            if (document.getElementById("chk_" + invoice_id).checked)-->
<!--                invoice_ids.push(invoice_id);-->
<!--        }-->
<!---->
<!--        var request = "invoices-post.php?operation=start_handle&ids=" + invoice_ids.join();-->
<!--        xmlhttp.open("GET", request, true);-->
<!--        xmlhttp.send();-->
<!--    }-->
<!---->
<!--    function cancel_invoice() {-->
<!--        var classes = ["select_invoice_wc-pending", "select_invoice_wc-processing", "select_invoice_wc-on-hold"];-->
<!--        var invoice_ids = new Array();-->
<!---->
<!--        for (var c = 0; c < classes.length; c++){-->
<!--            var collection = document.getElementsByClassName(classes[c]);-->
<!--            for (var i = 0; i < collection.length; i++) {-->
<!--                var invoice_id = collection[i].id.substr(4);-->
<!--                if (document.getElementById("chk_" + invoice_id).checked)-->
<!--                    invoice_ids.push(invoice_id);-->
<!--            }-->
<!--        }-->
<!--        xmlhttp = new XMLHttpRequest();-->
<!--        xmlhttp.onreadystatechange = function () {-->
<!--            // Wait to get query result-->
<!--            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished-->
<!--            {-->
<!--                window.location = window.location;-->
<!--            }-->
<!--        }-->
<!--        var request = "invoices-post.php?operation=cancel_invoices&ids=" + invoice_ids.join();-->
<!--        xmlhttp.open("GET", request, true);-->
<!--        xmlhttp.send();-->
<!--    }-->
<!---->
<!--    function delivered() {-->
<!--        var collection = document.getElementsByClassName("select_invoice_wc-awaiting-shipment");-->
<!--        var invoice_ids = new Array();-->
<!--        var table = document.getElementById("wc-awaiting-shipment");-->
<!---->
<!--        xmlhttp = new XMLHttpRequest();-->
<!--        xmlhttp.onreadystatechange = function () {-->
<!--            // Wait to get query result-->
<!--            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished-->
<!--            {-->
<!--                window.location = window.location;-->
<!--            }-->
<!--        }-->
<!---->
<!--        for (var i = 0; i < collection.length; i++) {-->
<!--            var invoice_id = collection[i].id.substr(4);-->
<!--            if (document.getElementById("chk_" + invoice_id).checked)-->
<!--                invoice_ids.push(invoice_id);-->
<!--        }-->
<!--        var request = "invoices-post.php?operation=delivered&ids=" + invoice_ids.join();-->
<!--        xmlhttp.open("GET", request, true);-->
<!--        xmlhttp.send();-->
<!--    }-->
<!---->
<!--    function select_invoices(table_name) {-->
<!--        var table = document.getElementById(table_name);-->
<!--        for (var i = 1; i < table.rows.length; i++)-->
<!--            table.rows[i].firstElementChild.firstElementChild.checked =-->
<!--                table.rows[0].firstElementChild.firstElementChild.checked;-->
<!---->
<!--    }-->
<!---->
<!--    function complete_status() {-->
<!--        xmlhttp = new XMLHttpRequest();-->
<!--        xmlhttp.onreadystatechange = function () {-->
<!--            // Wait to get delivery id.-->
<!--            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished-->
<!--                location.reload(true);-->
<!--            }-->
<!--        }-->
<!--        var request = "invoices_close_all_open.php";-->
<!--        xmlhttp.open("GET", request, true);-->
<!--        xmlhttp.send();-->
<!--    }-->
<!---->
<!--    function create_subs() {-->
<!--        xmlhttp = new XMLHttpRequest();-->
<!--        xmlhttp.onreadystatechange = function () {-->
<!--            // Wait to get delivery id.-->
<!--            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished-->
<!--                location.reload(true);-->
<!--            }-->
<!--        }-->
<!--        var request = "invoices-create-subs.php";-->
<!--        xmlhttp.open("GET", request, true);-->
<!--        xmlhttp.send();-->
<!--    }-->
<!---->
<!--    function replace_baskets() {-->
<!--        xmlhttp = new XMLHttpRequest();-->
<!--        xmlhttp.onreadystatechange = function () {-->
<!--            // Wait to get delivery id.-->
<!--            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished-->
<!--                location.reload(true);-->
<!--            }-->
<!--        }-->
<!--        var request = "invoices-post.php?operation=replace_baskets";-->
<!--        xmlhttp.open("GET", request, true);-->
<!--        xmlhttp.send();-->
<!--    }-->
<!---->
<!--</script>-->

</head>

<?php

print Core_Html::gui_header( 1, "חשבוניות" );

print invoices_table();


print "<br>";


?>
<!--<button id="btn" onclick="replace_baskets()">החלף סלים</button>-->
<datalist id="units">
    <option value="קג"></option>
    <option value="יח"></option>
</datalist>


<?php

function invoices_table( $statuses, $week = null ) {
	DebugTimeLog( "start" );

	global $order_header_fields;

	$show_fields = array();
	$empty_line  = array();
	for ( $i = 0; $i < Fresh_OrderFields::field_count; $i ++ ) {
		$empty_line[ $i ]  = "";
		$show_fields[ $i ] = true;
	}
	if ( ! current_user_can( "show_business_info" ) ) {
		$show_fields[ Fresh_OrderFields::total_order ] = false; // current_user_can("show_business_info");
		$show_fields[ Fresh_OrderFields::margin ]      = false;
		$show_fields[ Fresh_OrderFields::good_costs ]  = false;
	}

	$status_names = wc_get_order_statuses();
	$all_tables   = "";
	if ( ! is_array( $statuses ) ) {
		$statuses = array( $statuses );
	}
	DebugTimeLog( "1" );

	foreach ( $statuses as $status ) {
		// print $status . "<br/>";

		$order_header_fields[0] = gui_checkbox( "select_all_" . $status, "table", 0, "onclick=\"select_all_toggle('select_all_" . $status . "', 'select_order_" . $status . "')\"" );
		$rows                   = array( $order_header_fields );
		$sql                    = 'SELECT posts.id'
		                          . ' FROM `wp_posts` posts'
		                          . " WHERE post_status = '" . $status . "'";

		if ( $week ) {
			$sql = "SELECT order_id FROM im_delivery WHERE FIRST_DAY_OF_WEEK(date) = '" . $week . "'";
		}

		if ( $user_id ) {
			$sql .= " and order_user(id) = " . $user_id;
		}
		$sql .= " order by 1";

		// print $sql;
		// Build path
		$order_ids = sql_query_array_scalar( $sql );

		// If no orders in this status, move on.
		if ( sizeof( $order_ids ) < 1 ) {
			continue;
		}

		$i = count( $order_ids ) - 1;
//		if ( $build_path ) {
//			while ( $i >= 0 ) {
//				// print "<br/>handle " . $order_ids[$i] . ":";
//				print map_get_order_address($order_ids[$i]) . " " . get_distance( 1, $order_ids[ $i ] ) . "<br/>";
//				if ( get_distance( 1, $order_ids[ $i ] ) < 0 ) {
//					print "משלוח " . $order_ids[ $i ] . " לא נכלל במסלול" . "<br/>";
//////					//			    print "removing..";
//////					// var_dump($order_ids); print "<br/>";
//////					unset( $order_ids[ $i ] );
////					$order_ids = array_values( $order_ids );
//
//					// var_dump($order_ids);
//					// die (1);
//				}
//				$i --;
//			}
//		}
		$path = array();
//		if ( $build_path ) {
//			find_route_1( 1, $order_ids, $path, false );
//		}
		$result                = sql_query( $sql );
		$total_delivery_total  = 0;
		$total_order_total     = 0;
		$total_order_delivered = 0;
		$total_delivery_fee    = 0;
		$lines                 = array();

		if ( ! $result ) {
			continue;
		}

		$count = 0;
		global $invoice_user;
		global $invoice_password;

		$invoice = new Invoice4u( $invoice_user, $invoice_password );

		DebugTimeLog( "before loop" );
		while ( $row = mysqli_fetch_row( $result ) ) {
			DebugTimeLog( "after fetch" );
			$count ++;
			$order_id = $row[0];
			$order    = new Order( $order_id );

			$customer_id = $order->getCustomerId();

			$line = $empty_line;
			if ( $invoice->GetInvoiceUserId( $customer_id ) ) {
				$line [ Fresh_OrderFields::line_select ] = gui_checkbox( "chk_" . $order_id, "select_order_" . $status );
			} else {
				$line [ Fresh_OrderFields::line_select ] = Core_Html::GuiHyperlink( "לקוח חדש", "../account/new-customer.php?order_id=" . $order_id );
			}

			DebugTimeLog( "a1" );
			$line[ Fresh_OrderFields::type ] = order_get_shipping( $order_id );

			// display order_id with link to display it.
			// 1) order ID with link to the order
			$mission_id = order_get_mission_id( $order_id );
			// print $order_id. " ". $mission . "<br/>";

			$line[ Fresh_OrderFields::mission ]  = gui_select_mission( "mis_" . $order_id, $mission_id, "onchange=\"mission_changed(" . $order_id . ")\"" );
			$line[ Fresh_OrderFields::order_id ] = Core_Html::GuiHyperlink( $order_id, Core_Db_MultiSite::LocalSiteTools() . "/fresh/orders/get-order.php?order_id=" . $order_id );

			// 2) Customer name with link to his deliveries
			$line[ Fresh_OrderFields::customer ] = Core_Html::GuiHyperlink( get_customer_name( $customer_id ), Core_Db_MultiSite::LocalSiteTools() .
			                                                                                                   "/fresh/account/get-customer-account.php?customer_id=" . $customer_id );


			$line[ Fresh_OrderFields::recipient ] = get_postmeta_field( $order_id, '_shipping_first_name' ) . ' ' .
			                                        get_postmeta_field( $order_id, '_shipping_last_name' );

			DebugTimeLog( "middle" );

			// 3) Order total
			if ( $show_fields[ Fresh_OrderFields::total_order ] ) {
				$order_total = $order->GetTotal();
				// get_postmeta_field( $order_id, '_order_total' );
				$line[ Fresh_OrderFields::total_order ] = $order_total;
				$total_order_total                      += $order_total;
				DebugTimeLog( "total" );

			}

			// 4) Delivery note
			$delivery_id = get_delivery_id( $order_id );

			if ( $delivery_id > 0 ) {
				$delivery                                 = new Fresh_Delivery( $delivery_id );
				$line[ Fresh_OrderFields::delivery_note ] = Core_Html::GuiHyperlink( $delivery_id,
					Core_Db_MultiSite::LocalSiteTools() . "/fresh/delivery/get-delivery.php?id=" . $delivery_id );
				//if ( $delivery_id > 0 ) {
				$line[ Fresh_OrderFields::total_order ]  = $order_total; // $delivery->Price();
				$line[ Fresh_OrderFields::delivery_fee ] = $delivery->DeliveryFee();
				$percent                                 = "";
				if ( ( $order_total - $delivery->DeliveryFee() ) > 0 ) {
					$percent = round( 100 * ( $delivery->Price() - $delivery->DeliveryFee() ) / ( $order_total - $delivery->DeliveryFee() ), 0 ) . "%";
				}
				$line[ Fresh_OrderFields::percentage ] = $percent;
				$total_delivery_total                  += $delivery->Price();
				$total_delivery_fee                    = $delivery->DeliveryFee();
				$total_order_delivered                 += $order_total;
				if ( $delivery->isDraft() ) {
					$line [ Fresh_OrderFields::line_select ] = "טיוטא";
				}
				//	}
			} else {
				$line[ Fresh_OrderFields::delivery_note ] = Core_Html::GuiHyperlink( "צור", "../delivery/create-delivery.php?order_id=" . $order_id ) .
				                                            Core_Html::GuiHyperlink( "בטל", "orders-post.php?operation=cancel_orders&ids=" . $order_id );
				$total_delivery_fee                       = order_get_shipping_fee( $order_id );
			}
			$line[ Fresh_OrderFields::city ]         = order_info( $order_id, '_shipping_city' );
			$line[ Fresh_OrderFields::payment_type ] = get_payment_method_name( $customer_id );
			$line[ Fresh_OrderFields::good_costs ]   = $order->GetBuyTotal();
			$line[ Fresh_OrderFields::margin ]       = round( ( $line[ Fresh_OrderFields::total_order ] - $line[ Fresh_OrderFields::good_costs ] ), 0 );
			$line[ Fresh_OrderFields::delivery_fee ] = $total_delivery_fee; //

			array_push( $rows, $line );
			DebugTimeLog( "loop end" );
		}

		//   $data .= "<tr> " . trim($line) . "</tr>";

		DebugTimeLog( "before sort" );

		// sort( $lines );

		DebugTimeLog( "2" );

//		$data .= gui_row( array( "", "", 'סה"כ', "", "", "", $total_order_total, "", "", "", "" ) );
		//$data = str_replace( "\r", "", $data );

		// $data .= "</table>";

		if ( $count > 0 ) {
			$sums = null;

			if ( current_user_can( "show_business_info" ) ) {
				$sums = array(
					"סה\"כ",
					'',
					'',
					'',
					'',
					'',
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					array( 0, 'sum_numbers' ),
					0
				);
			}
			$data = Core_Html::gui_header( 2, $status_names[ $status ] );
			// gui_table( $rows, $id = null, $header = true, $footer = true, &$sum_fields = null, $style = null, $class = null, $links = null)
			$data       .= gui_table( $rows, $status, true, true, $sums, null, null, $show_fields );
			$all_tables .= $data;
		}
	}

	DebugTimeLog( "end" );

	return $all_tables;
}

{

}
?>
<div id="logging"></div>

</html>