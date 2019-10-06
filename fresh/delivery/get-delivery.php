<?php
define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));

require_once 'delivery.php';
require_once '../orders/orders-common.php';

print header_text( false, true, true, array( "/fresh/tools.js", "/niver/gui/client_tools.js" ) );
$id     = $_GET["id"];
$send   = isset( $_GET["send"] );
$margin = isset( $_GET["margin"] );
$print  = isset( $_GET["print"] );
$d      = new Delivery( $id );

$order_id = get_order_id( $id );
$O        = new Order( $order_id );

if ( ! ( $order_id > 0 ) ) {
	print "תעודה לא קיימת";

	return;
}

//print current_user_can("edit_shop_orders") ."<br/>";
//print order_get_customer_id($order_id)."<br/>";
//print get_current_user_id()."<br/>";
if ( ( ! current_user_can( "edit_shop_orders" ) ) and ( $O->getCustomerId() != get_current_user_id() ) ) {
	print "אין הרשאה ". get_current_user_id() . "<br/>";
	var_dump ( wp_get_current_user());
	die( 0 );
}

print $O->infoBox( $order_id );

print $d->delivery_text( ImDocumentType::delivery, ImDocumentOperation::show, $margin );

if ( ! $send ) {
	if ( sql_query_single_scalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $id ) ) {
		print "תעודה שולמה ולא ניתנת לעריכה או למחיקה";
	} else {
		print '<button id="btn_del" onclick="deleteDelivery()">מחק תעודה</button>';
		print '<button id="btn_edit" onclick="editDelivery()">ערוך תעודה</button>';
	}
}

?>

<script>

	<?php if ( $print ) {
		print 'window.print();';
	} ?>
    function editDelivery() {
        window.location.href = "create-delivery.php?id=<?php print $id; ?>";
    }

    function deleteDelivery() {
        var request = "delivery-post.php?operation=delete_delivery&delivery_id=<?php print $id; ?>";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                if (window.history)
                    window.history.back();
                else {
                    alert("תעודה נמחקה. יש לסגור את החלון");
                }
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

</script>
</body>
</html>
