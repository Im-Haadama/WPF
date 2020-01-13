<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/12/18
 * Time: 11:21
 */

require_once( '../r-shop_manager.php' );
require_once( "../orders/orders-common.php" );

$order_id  = GetParam( "order_id" );
$O         = new Order( $order_id );
$client_id = $O->getCustomerId();

?>
<script type="text/javascript" src="/core/gui/client_tools.js"></script>
<script type="text/javascript" src="/fresh/tools.js"></script>
<body onload="update_display();">

<?php
print header_text( false, true, true );
print Core_Html::gui_header( 1, "לקוח חדש" );

print "1) צור קשר טלפוני עם הלקוח. עדכן אותו שהתקבלה ההזמנה.<br/>";
print "2) אמת את השם לחשבונית.<br/>";
print "3) אמת את הכתובת למשלוח. בדוק האם יש אינטרקום או קוד לגישה לדלת.<br/>";

$step      = 4;

$invoice_client_id = get_user_meta( $client_id, 'invoice_id', 1 );

print gui_table_args( array(
	$O->info_right_box_input( "shipping_city", true, "עיר" ),
	$O->info_right_box_input( "shipping_address_1", true, "רחוב ומספר" ),
	$O->info_right_box_input( "shipping_address_2", true, "כניסה, קוד אינטרקום, קומה ומספר דירה" )
) );

//print gui_table( array(
//        array("עיר",
//	gui_input("shipping_city", get_usermeta($client_id, "shipping_city"), "onchange=\"update_address(\"shipping_city\", "
//    . $client_id . "," . $order_id . ")\"")),
//	array(
//		"כתובת - רק רחוב ומספר בנין",
//		gui_input( "address_1", get_usermeta( $client_id, "shipping_address_1" ), "onchange=\"update_address(\"shipping_address_1\", "
//                                                                                  . $client_id . "," . $order_id . ")\"" )
//	),
//	array(
//		"קומה, מספר דירה, אינטרקום ופרטים נוספים",
//		gui_input( "address_2", get_usermeta( $client_id, "shipping_address_2" ), "onchange=\"update_address(\"shipping_address_1\", "
//                                                                                  . $client_id . "," . $order_id . ")\"" )
//	)
//));


if ( ! $invoice_client_id ) {
	print $step ++ . ") לחץ על צור משתמש - במערכת invoice4u";
	print Core_Html::GuiButton( "btn_create_user", "create_user()", "צור משתמש" );
	print Core_Html::GuiButton( "btn_update_user", "update_user()", "קשר משתמש" );
	print "<br/>";
}

print $step ++ . ") קח/י פרטי תשלום" . Core_Html::GuiHyperlink( "כאן", "https://private.invoice4u.co.il/he/Customers/CustomerAddNew.aspx?type=edit&id=" . $client_id . "#tab-tokens" ) . "<br/>";
print "<br/>";
print "מספר הזמנה " . $order_id . "<br/>";
print $O->infoBox();

print "<br/>";
?>
<script>
    function update_display() {
        // document.getElementById("btn_create_user").isDisabled = true;
    }

    function create_user() {
        var request = "account-post.php?operation=create_invoice_user&id=" +
		    <?php print $O->getCustomerId(); ?>;
        //  window.alert(request);
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                document.getElementById("invoice_client_id").innerHTML = xmlhttp.response;
                if (get_value_by_name("invoice_client_id").length > 1)
                    location.reload();
//                add_message(xmlhttp.response);
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function update_user() {
        var request = "account-post.php?operation=update_invoice_user&id=" +
		    <?php print $O->getCustomerId(); ?>;
        //  window.alert(request);
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                document.getElementById("invoice_client_id").innerHTML = xmlhttp.response;
                if (get_value_by_name("invoice_client_id").length > 1){
                    alert ("עדכון הצליח");
                    location.reload();
                }
//                add_message(xmlhttp.response);
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

</script>
<div id="log"></div>
מספר לקוח:
<label id="invoice_client_id">
	<?php
	print $invoice_client_id;
	?>

</label>

</body>