<?php
/**
 * Plugin Name: Im-Haadama
 * Created by PhpStorm.
 * User: agla
 * Date: 19/06/16
 * Time: 17:42
 */

define( 'IM_HAADAMA_PLUGIN', __FILE__ );

define( 'IM_HAADAMA_PLUGIN_BASENAME', plugin_basename( IM_HAADAMA_PLUGIN ) );

define( 'IM_HAADAMA_PLUGIN_NAME', trim( dirname( IM_HAADAMA_PLUGIN_BASENAME ), '/' ) );

define( 'IM_HAADAMA_PLUGIN_DIR', untrailingslashit( dirname( IM_HAADAMA_PLUGIN ) ) );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname(dirname( dirname( dirname( IM_HAADAMA_PLUGIN)))));
}

if ( ! defined( 'TOOLS_DIR')) {
     define ('TOOLS_DIR', ROOT_DIR . '/tools');
}

require_once (ROOT_DIR . '/im-config.php');

// require_once( "functions_im.php" );

add_shortcode( 'im-page', 'im_page' );

function im_page() {
	$img_size = 50;
	$data     = "XXX";
	$data     .= gui_hyperlink( get_the_post_thumbnail( 4209, array( $img_size, $img_size ) ), "/how_to_use" );

	return $data;
	// [im-page]
}

add_shortcode( 'form-order', 'create_order_form' );

function create_order_form( $atts, $contents, $tag ) {
	require_once( TOOLS_DIR . "/orders/order-form-post.php" );
	$params = get_param_array( "params" );
	$name   = get_param( "name" );
	$phone  = get_param( "phone" );
	$group  = get_param( "group" );
	$user   = get_param( "user" );
	$method = get_param( "method" );
	$email  = get_param( "email" );

	return order_form( $params, $name, $phone, $group, $user, $method, $email );
}
// Express inventory
add_shortcode( 'show-inventory', 'show_inventory_func' );

function show_inventory_func( $atts, $contents, $tag ) {
	require_once( ROOT_DIR . "/tools/express/show-inventory.php" );
	show_inventory_client();
}

// Delivery time select
//function delivery_options()
//{
//
//}
add_shortcode( 'basket-content', 'content_func' );

function content_func( $atts, $contents, $tag ) {
	require_once( ROOT_DIR . '/tools/catalog/Basket.php' );

//	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
//	$id = $my_atts['id'];
//
	$text = "תכולת הסל: ";
	$text .= get_basket_content( get_the_ID() );;

//
	return $text;
}

add_shortcode( 'order_form', 'im_order_form' );

function im_order_form() {
	ob_start();
	require_once( TOOLS_DIR . "/orders/order-form.php" );
	$output = ob_get_clean();

	return $output;
}

function content_func_front( $atts, $contents, $tag ) {
//    $my_atts = shortcode_atts(['id' => get_the_ID()], $atts, $tag);
//
//    $id = $my_atts['id'];
//
//    $text = "תכולת הסל: ";
//
//    $URL = "http://store.im-haadama.co.il/tools/get_basket.php?id=" . $id;
//    $text .= file_get_contents($URL);
	$product = wc_get_product( 35 );
//    $text = $product->

	// $text = WC_Shortcodes::products();
	$atts = shortcode_atts( array(
		'per_page' => '4',
		'columns'  => '4',
		'orderby'  => 'date',
		'order'    => 'desc',
		'category' => '',  // Slugs
		'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
	), $atts, 'recent_products' );
	// return WC_Shortcodes::recent_products($atts);
}

add_shortcode( 'im-haadama', 'content_func' );
add_shortcode( 'im-haadama-front', 'content_func_front' );
add_shortcode( 'im-haadama-account-status', 'im_account_status' );
add_shortcode( 'im-haadama-open-orders', 'im_open_orders' );

function im_open_orders( $atts, $contents, $tag ) {
	$user = wp_get_current_user();

	$open = false;
	if ( $user->ID ) {
		require_once( TOOLS_DIR . "/account/account.php" );

		$sql = "select id from wp_posts where order_user(id) = " . $user->ID . " and post_status in 
		('wc-processing', 'wc-on-hold', 'wc-pending')";

		$orders = sql_query_array_scalar( $sql );

		if ( ! $orders ) {
			print "אין הזמנות בטיפול" . "<br/>";

			return "";
		}

		print gui_header( 2, "הזמנות פתוחות" ) . "<br/>";

		foreach ( $orders as $order ) {
			$open = true;
			if ( get_post_meta( $order, 'printed' ) ) {
				print "הזמנה " . $order . " עברה לטיפול. צור קשר עם שירות הלקוחות" . "<br/>";
			} else {
				print "הזמנה " . $order . " עדיין לא הוכנה. במידה ויתאפשר נוסיף מוצרים. נא לא לבטל פריטים טריים זמן קצר לפני ההספקה. לחץ לשינוי ";
				print gui_hyperlink( "הזמנה " . $order, ImMultiSite::LocalSiteTools() . "/orders/get-order.php?order_id=" . $order );
				print ".<br/>";
			}
		}


	} else {
		return "עליך להתחבר תחילה";
	}
}

function im_account_status( $atts, $contents, $tag ) {
	$user = wp_get_current_user();

	if ( strlen( $user->user_login ) > 2 ) {
		require_once( TOOLS_DIR . "/account/account.php" );

		return show_trans( $user->id, eTransview::from_last_zero );
	} else {
		return "עליך להתחבר תחילה";
	}
	// print "xxx";
}

add_shortcode( 'beth', 'beth_sign' );

function beth_sign( $atts, $contents, $tag ) {
	return 'נכתב ע"י בת אפשטיין-ברייר ' . '<a href="https://www.facebook.com/beth.brayer">' . 'נטרופת והרבליסט קלינית.' . '</a>';
}


// -#-#-#-#-#-#-#-#-#-#-#-
// Shipment Order Status #
// -#-#-#-#-#-#-#-#-#-#-#-

function register_awaiting_shipment_order_status() {
	register_post_status( 'wc-awaiting-shipment', array(
		'label'                     => 'ממתין למשלוח',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'ממתין למשלוח <span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
	) );

	register_post_status( 'wc-awaiting-document', array(
		'label'                     => 'Awaiting shipment document',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Awaiting shipment document<span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
	) );
}

add_action( 'init', 'register_awaiting_shipment_order_status' );

// Add to list of WC Order statuses
function add_awaiting_shipment_to_order_statuses( $order_statuses ) {

	$new_order_statuses = array();

	// add new order status after processing
	foreach ( $order_statuses as $key => $status ) {

		$new_order_statuses[ $key ] = $status;

		if ( 'wc-processing' === $key ) {
			$new_order_statuses['wc-awaiting-shipment'] = 'ממתין למשלוח';
			$new_order_statuses['wc-awaiting-document'] = 'ממתין לתעודת משלוח';
		}
	}

	return $new_order_statuses;
}

add_filter( 'wc_order_statuses', 'add_awaiting_shipment_to_order_statuses' );

add_action( 'wp_print_scripts', 'skyverge_add_custom_order_status_icon' );
function skyverge_add_custom_order_status_icon() {

	if ( ! is_admin() ) {
		return;
	}

	?>
    <style>
        /* Add custom status order icons */
        .column-order_status mark.awaiting-shipment,
        .column-order_status mark.building {
            content: url("http://store.im-haadama.co.il/wp-content/uploads/2017/02/CustomOrderStatus.png");
        }

        /* Repeat for each different icon; tie to the correct status */

    </style>
	<?php
}

if (file_exists(IM_HAADAMA_PLUGIN_DIR . "/tasks.php")){
    require_once("tasks.php");
}
