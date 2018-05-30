<?php
require_once 'delivery.php';
require_once '../orders/orders-common.php';

print header_text( false );
$id     = $_GET["id"];
$send   = $_GET["send"];
$margin = isset( $_GET["margin"] );

$d = new Delivery( $id );

$order_id = get_order_id( $id );
//print current_user_can("edit_shop_orders") ."<br/>";
//print order_get_customer_id($order_id)."<br/>";
//print get_current_user_id()."<br/>";
if ( ( ! current_user_can( "edit_shop_orders" ) ) and ( order_get_customer_id( $order_id ) != get_current_user_id() ) ) {
	print "אין הרשאה";
	die( 0 );
}

print order_info_data( $order_id );
print $d->delivery_text( ImDocumentType::delivery, ImDocumentOperation::show, $margin );

if ( ! $send ) {
	print '<button id="btn_del" onclick="deleteDelivery()">מחק תעודה</button>';
	print '<button id="btn_edit" onclick="editDelivery()">ערוך תעודה</button>';
}

?>

<script>

    function editDelivery() {
        window.location.href = "create-delivery.php?id=<?php print $id; ?>";
    }

    function deleteDelivery() {
        var request = "delete-delivery.php?id=<?php print $id; ?>";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                window.history.back();
                window.reload();
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }


</script>
</body>
</html>
