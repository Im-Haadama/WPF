<html dir="rtl">
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));

require_once(dirname(dirname(ROOT_DIR)) . '/wp-config.php');
require_once(dirname(dirname(ROOT_DIR)) . '/im-config.php');
print Core_Fund::load_scripts(array('/wp-content/plugins/flavor/includes/core/gui/client_tools.js',
	'/wp-content/plugins/fresh/includes/js/delivery.js',
	'/wp-content/plugins/flavor/includes/core/data/data.js',
    '/wp-content/plugins/finance/includes/account.js'
    ));

$order_id = GetParam("order_id", false, null);

$send   = isset( $_GET["send"] );
$margin = isset( $_GET["margin"] );
$print  = isset( $_GET["print"] );
$d = null;
$O = null;

if ($order_id){
	$O        = new Fresh_Order( $order_id );
	$id = $O->getDeliveryId();
	if (! ($id > 0)) {
		print "delivery note not found";

		return;
	}

	$d = new Fresh_Delivery($id);
//	print "del_id=" . $O->getDeliveryId() . "<br/>";
} else {
	$id = GetParam("id", false, null);
	$d = new Fresh_Delivery($id);
	$order_id = $d->OrderId();
	$O = new Fresh_Order($order_id);
}

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

$O->infoBox( $order_id );

print $d->delivery_text( FreshDocumentType::delivery, Fresh_DocumentOperation::show, $margin );

$customer_id = $O->getCustomerId();
print Core_Html::GuiButton("btn_pay", " בצע חיוב על היתרה", array("action"=>"pay_credit_client('" . Finance::getPostFile() . "', $customer_id)")) ."<br/>";

if ( ! $send ) {
	if ( SqlQuerySingleScalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $id ) ) {
		print "תעודה שולמה ולא ניתנת לעריכה או למחיקה";
	} else {
	    print Core_Html::GuiButton("btn_del", "delete document", array("action" => "deleteDelivery('".Fresh::getPost()."', $id)") );
	    print Core_Html::GuiButton("btn_edit", "edit document", array("action" =>"editDelivery()"));
	    print Core_Html::GuiButton("btn_send", "send delivery", array("action" =>"sendDelivery('" .Fresh::getPost()."', $id)"));
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

    function sendDelivery(post_file, id) {
        let request = post_file + '?operation=delivery_send_mail&id=' + id;
        execute_url(request, success_message);
    }

</script>
</body>
</html>
