<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/12/18
 * Time: 11:21
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
require_once( "../orders/orders-common.php" );

$operation = get_param( "operation" );

if ( $operation ) {
	switch ( $operation ) {
		case "update_address":
			$i         = get_param( "i", true );
			$address   = get_param( "address", true );
			$client_id = get_param( "client_id", true );
			update_usermeta( $client_id, "shipping_address_" . $i, $address );

			$order_id = get_param( "order_id" );

			if ( $order_id ) {
				update_post_meta( $order_id, "shipping_address_" . $i, $address );
			}

			print " המידע עודכן" . get_user_address( $client_id, true );
			break;
	}
	die ( 0 );
}

$order_id  = get_param( "order_id" );
$O         = new Order( $order_id );
$client_id = $O->getCustomerId();

?>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>
<script>
    function update_address(i) {
        var address = get_value_by_name("address_" + i);

        var request = "new-customer.php?operation=update_address&i=" + i + "&address=" + encodeURI(address) +
            "&client_id=" + <?php print $client_id; ?> +
                "&order_id=" + <?php print $order_id; ?>;

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                document.getElementById("invoice_client_id").innerHTML = xmlhttp.response;
                if (get_value_by_name("invoice_client_id").length > 1)
                // location.reload();
                    add_message(xmlhttp.response);
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
</script>
<body onload="update_display();">

<?php
print header_text( false, true, true );
print gui_header( 1, "לקוח חדש" );

print "1) צור קשר טלפוני עם הלקוח. עדכן אותו שהתקבלה ההזמנה.<br/>";
print "2) אמת את השם לחשבונית.<br/>";
print "3) אמת את הכתובת למשלוח. בדוק האם יש אינטרקום או קוד לגישה לדלת.<br/>";

$step      = 4;
$invoice   = new Invoice4u( $invoice_user, $invoice_password );
try {
	$invoice_client_id = $invoice->GetInvoiceUserId( $O->getCustomerId() );
} catch ( Exception $e ) {

}

print gui_table( array(
	array(
		"כתובת - רק רחוב ומספר בנין",
		gui_input( "address_1", get_usermeta( $client_id, "shipping_address_1" ), "onchange=update_address(1)" )
	),
	array(
		"קומה, מספר דירה, אינטרקום ופרטים נוספים",
		gui_input( "address_2", get_usermeta( $client_id, "shipping_address_2" ), "onchange=update_address(2)" )
	)
));


if ( ! $client_id ) {
	print $step ++ . ") לחץ על צור משתמש - במערכת invoice4u";
	print gui_button( "btn_create_user", "create_user()", "צור משתמש" );
	print "<br/>";
}

print $step ++ . ") קח/י פרטי תשלום" . gui_hyperlink( "כאן", "https://private.invoice4u.co.il/he/Customers/CustomerAddNew.aspx?type=edit&id=" . $client_id . "#tab-tokens" ) . "<br/>";
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

    function add_message(message) {
        var log = document.getElementById("log");

        log.innerHTML += message;
        // alert(message);
    }

</script>

מספר לקוח:
<label id="invoice_client_id">
	<?php
	print $client_id;
	?>

</label>

</body>