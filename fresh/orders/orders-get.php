<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once( ROOT_DIR . "/fresh/im_tools.php" );
require_once( ROOT_DIR . '/fresh/r-shop_manager.php' );
require_once (ROOT_DIR . '/fresh/delivery/delivery.php');
require_once (ROOT_DIR . '/routes/gui.php');


print header_text( false, false, is_rtl(),
    array("/fresh/delivery/delivery.js", "/niver/gui/client_tools.js", "/niver/data/data.js", "/fresh/orders/orders.js") );

if ( isset( $_GET["week"] ) ) {
	$week = $_GET["week"];
}

$order_type = get_param( "order_type" ); // comma separated. w - waiting to deliver. p - pending/on-hold

if ( ! $order_type ) {
     print gui_hyperlink("Add order", "/wp-admin/post-`new`.php?post_type=shop_order", "_blank" );

	// print gui_button( "btn_new", "show_create_order()", "הזמנה חדשה" );
}

require( "new-order.php" );

$operation = get_param( "operation" );

if ( $operation )
	switch ( $operation ) {
		case "cancel_order":
			$id = get_param( "id" );
			$o  = new Order( $id );
			$o->ChangeStatus( "wc-cancelled" );
			break;
	}

?>

<script type="text/javascript" src="/niver/gui/client_tools.js"></script>


</head>

<?php

if ( isset( $week ) ) {
	print "הזמנה לשבוע  " . $week . "<br/>";
	print orders_table( "wc-complete", false, 0, $week );
	die ( 0 );
}

print gui_header( 1, "הזמנות" );

debug_time_log( "reset" );

if ( current_user_can( "edit_shop_orders" ) and
     ( is_null( $order_type ) or in_array( "p", explode( ",", $order_type ) ) )
) {
	$pending = orders_table( array( "wc-pending", "wc-on-hold" ) );
	if ( strlen( $pending ) > 4 ) {
		print $pending;
		print gui_button( "btn_start", "start_handle()", "התחל טיפול" );
		print gui_button( "btn_cancel", "cancel_order()", "בטל" ) . "<br/>";
	}
} else {
	if ( ! current_user_can( "edit_shop_orders" ) ) {
		print "no permission";
	}
}

print orders_table( "wc-processing" );

if ( current_user_can( "edit_shop_orders" ) and
     ( is_null( $order_type ) or in_array( "w", explode( ",", $order_type ) ) )
) {
	$shipment = orders_table( "wc-awaiting-shipment" );

	if ( strlen( $shipment ) > 5 ) {
		print $shipment;
		print gui_button( "btn_delivered", "delivered_table()", "Delivered" ) . "<br/>";
	}
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

$result = sql_query($sql );

function get_user_order_count( $u ) {
	$sql = 'SELECT count(*) ' .
	       ' FROM `wp_posts` posts, wp_postmeta meta ' .
	       ' WHERE post_status like \'wc-%\' ' .
	       ' and meta.meta_key = \'_customer_user\' and meta.meta_value = ' . $u .
	       ' and meta.post_id = posts.ID';

	return sql_query_single_scalar( $sql );
}

?>
<datalist id="units">
    <option value="קג"></option>
    <option value="יח"></option>
</datalist>


<div id="logging"></div>

</html>
