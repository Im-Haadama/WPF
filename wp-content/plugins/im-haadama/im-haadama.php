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

define( 'TOOLS_DIR', dirname( dirname( dirname( IM_HAADAMA_PLUGIN_DIR ) ) ) . "/tools" );

// print TOOLS_DIR;


require_once( TOOLS_DIR . '/im_tools.php' );

// require_once(TOOLS_DIR . '/account/account.php');

// print "XXX";


// Delivery time select
//function delivery_options()
//{
//
//}
add_shortcode( 'basket-content', 'content_func' );

function content_func( $atts, $contents, $tag ) {
	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );

	$id = $my_atts['id'];

	$text = "תכולת הסל: ";
	$text .= get_basket_content( $id );;

	return $text;
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

function im_account_status( $atts, $contents, $tag ) {
	$user = wp_get_current_user();

	if ( strlen( $user->user_login ) > 2 ) {
		require_once( TOOLS_DIR . "/account/account.php" );

		return show_trans( $user->id, false, false );
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
		'label'                     => 'Awaiting shipment',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Awaiting shipment <span class="count">(%s)</span>', 'Awaiting shipment <span class="count">(%s)</span>' )
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
			$new_order_statuses['wc-awaiting-shipment'] = 'Awaiting shipment';
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