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
	'/wp-content/plugins/flavor/includes/core/data/data.js'));

//require_once 'delivery.php';
//require_once '../orders/orders-common.php';
//require_once( ROOT_DIR . "/init.php" );
//require_once( ROOT_DIR . "/routes/gui.php" );


//print HeaderText(array("script_files"=>array( "/fresh/tools.js", "/niver/gui/client_tools.js" )));
//print header_text( false, true, true,  );
//$id     = $_GET["id"];
$order_id = GetParam("order_id", false, null);

$send   = isset( $_GET["send"] );
$margin = isset( $_GET["margin"] );
$print  = isset( $_GET["print"] );
$d = null;
$O = null;

if ($order_id){
	$O        = new Fresh_Order( $order_id );
	$id = $O->getDeliveryId();
	$d = new Fresh_Delivery($id);
//	print "del_id=" . $O->getDeliveryId() . "<br/>";
} else {
	$id = GetParam("id", false, null);
}
//$d      = ($id ? new Fresh_Delivery( $id ) : null);

//$order_id = get_order_id( $id );

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

if ( ! $send ) {
	if ( sql_query_single_scalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $id ) ) {
		print "תעודה שולמה ולא ניתנת לעריכה או למחיקה";
	} else {
	    print Core_Html::GuiButton("btn_del", "delete document", array("action" => "deleteDelivery($id)") );
	    print Core_Html::GuiButton("btn_edit", "edit document", array("action" =>"editDelivery()"));
	    print Core_Html::GuiButton("btn_send", "send delivery", array("action" =>"sendDelivery()"));
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

    function sendDelivery() {
        window.location.href = "/fresh/account/account-post.php?operation=send&del_ids=<?php print $id; ?>";
    }

</script>
</body>
</html>
