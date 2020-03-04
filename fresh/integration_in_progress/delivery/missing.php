<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/10/18
 * Time: 19:32
 */


require_once( "../orders/Order.php" );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once (FRESH_INCLUDES . '/fresh/orders/orders.js');

$none = true;

print header_text( true, true, true );

$sql = 'SELECT posts.id, order_is_group(posts.id), order_user(posts.id) '
       . ' FROM `wp_posts` posts'
       . ' WHERE `post_status` in (\'wc-awaiting-shipment\')';

$sql .= ' order by 1';

$orders = sql_query( $sql );

while ( $order = sql_fetch_row( $orders ) ) {
	$order_id   = $order[0];
	$is_group   = $order[1];
	$order_user = $order[2];

	$order       = new Order( $order_id );
	$delivery_id = $order->getDeliveryId();
	$m           = $order->Missing();
	if ( strlen( $m ) ) {
		$link = Core_Html::GuiHyperlink( $order_id, "../orders/get-order.php?order_id=" . $order_id );
		if ( $delivery_id ) {
			$link = Core_Html::GuiHyperlink( "ת.מ " . $delivery_id, "create-delivery.php?id=" . $delivery_id );
		}

		print Core_Html::gui_header( 1, $order->CustomerName() . " " . $link );
		print $m;
		$none = false;
	}
}


if ( $none ) {
	print "הידד, אין חוסרים בהזמנות שממתינות למשלוח";
} else {
	print Core_Html::GuiButton("btn_draft", "draft_products(\"product_checkbox\")", "הפוך לטיוטא מסומנים");
}

?>
