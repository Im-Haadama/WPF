<?php

if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once( FRESH_INCLUDES . '/fresh/r-shop_manager.php' );
require_once (FRESH_INCLUDES . '/fresh/delivery/delivery.php');
require_once( FRESH_INCLUDES . '/routes/gui.php' );

print header_text( false, false, is_rtl(),
    array("/fresh/delivery/delivery.js", "/core/gui/client_tools.js", "/core/data/data.js", "/fresh/orders/orders.js") );

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
}

$order_type = GetParam( "order_type" ); // comma separated. w - waiting to deliver. p - pending/on-hold

if ( ! $order_type ) {
     print Core_Html::GuiHyperlink("Add order", "/wp-admin/post-new.php?post_type=shop_order", "_blank" );
    print " ";
	print Core_Html::GuiHyperlink("Paper order", "order-page.php?operation=paper_order", "_blank" );

	// print Core_Html::GuiButton( "btn_new", "show_create_order()", "הזמנה חדשה" );
}

require( "new-order.php" );

$operation = GetParam( "operation" );

if ( $operation )
	switch ( $operation ) {
		case "cancel_order":
			$id = GetParam( "id" );
			$o  = new Order( $id );
			$o->ChangeStatus( "wc-cancelled" );
			break;
	}

?>

<script type="text/javascript" src="/core/gui/client_tools.js"></script>


</head>

<?php

if ( isset( $week ) ) {
	print "הזמנה לשבוע  " . $week . "<br/>";
	print orders_table( "wc-complete", false, 0, $week );
	die ( 0 );
}

print Core_Html::gui_header( 1, "הזמנות" );

DebugTimeLog( "reset" );

if ( current_user_can( "edit_shop_orders" ) and
     ( is_null( $order_type ) or in_array( "p", explode( ",", $order_type ) ) )
) {
} else {
	if ( ! current_user_can( "edit_shop_orders" ) ) {
		print "no permission";
	}
}


if ( current_user_can( "edit_shop_orders" ) and
     ( is_null( $order_type ) or in_array( "w", explode( ",", $order_type ) ) )
) {
}

// This month active users
$sql = 'SELECT distinct meta.meta_value ' .
       'FROM `wp_posts` posts, wp_postmeta meta ' .
       'WHERE `post_date` >= date_sub(now(), interval 30 day) ' .
       'and post_status like \'wc%\'' .
       'and meta.post_id = posts.id and meta.meta_key = \'_customer_user\' ' .
       'order by 1';

print "<br>";

// Now active users
$sql = 'SELECT meta.meta_value ' .
       'FROM `wp_posts` posts, wp_postmeta meta ' .
       'WHERE post_status like \'wc-%\' ' .
       'and meta.post_id = posts.id and meta.meta_key = \'_customer_user\' ' .
       'and post_date > curdate() - 7 ' .
       'order by 1 ';

$result = SqlQuery($sql );

function get_user_order_count( $u ) {
	$sql = 'SELECT count(*) ' .
	       ' FROM `wp_posts` posts, wp_postmeta meta ' .
	       ' WHERE post_status like \'wc-%\' ' .
	       ' and meta.meta_key = \'_customer_user\' and meta.meta_value = ' . $u .
	       ' and meta.post_id = posts.ID';

	return SqlQuerySingleScalar( $sql );
}

?>
<datalist id="units">
    <option value="קג"></option>
    <option value="יח"></option>
</datalist>


<div id="logging"></div>

</html>
