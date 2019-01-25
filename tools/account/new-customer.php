<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/12/18
 * Time: 11:21
 */

require_once( "../im_tools.php" );
require_once( '../r-shop_manager.php' );
require_once( "../orders/orders-common.php" );

?>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>

<body onload="update_display();">

<?php
print header_text( false, true, true );
print gui_header( 1, "לקוח חדש" );
$order_id = $_GET["order_id"];
print "1) צור קשר טלפוני עם הלקוח. עדכן אותו שהתקבלה ההזמנה.<br/>";
print "2) אמת את השם לחשבונית.<br/>";
print "3) אמת את הכתובת למשלוח. בדוק האם יש אינטרקום או קוד לגישה לדלת.<br/>";

$step      = 4;
$invoice   = new Invoice4u( $invoice_user, $invoice_password );
$client_id = $invoice->GetInvoiceUserId( order_get_customer_id( $order_id ) );

if ( ! $client_id ) {
	print $step ++ . ") לחץ על צור משתמש - במערכת invoice4u";
	print gui_button( "btn_create_user", "create_user()", "צור משתמש" );
	print "<br/>";
}

print $step ++ . ") קח פרטי תשלום" . gui_hyperlink( "כאן", "https://private.invoice4u.co.il/he/Customers/CustomerAddNew.aspx?type=edit&id=" . $client_id . "#tab-tokens" ) . "<br/>";
print "<br/>";
print "מספר הזמנה " . $order_id . "<br/>";
print order_info_table( $order_id );

print "<br/>";
?>
<script>
    function update_display() {
        if (get_value_by_name("invoice_client_id").length > 1)
            document.getElementById("btn_create_user").isDisabled = true;
    }

    function create_user() {
        var request = "account-post.php?operation=create_invoice_user&id=" +<?php print order_get_customer_id( $order_id ); ?>;
        //  window.alert(request);
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                document.getElementById("invoice_client_id").innerHTML = xmlhttp.response;
                update_display();
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