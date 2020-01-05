<?php

/**
 * Plugin Name: fresh (full)
 * Plugin URI: https://aglamaz.com
 * Description:  wp-f backoffice for fresh goods store management.
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Fresh
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Fresh' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-fresh.php';
}
/**
 * Main instance of Fresh.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Fresh
  */

function fresh() {
	return Fresh::instance();
}

// Global for backwards compatibility.
$GLOBALS['fresh'] = fresh();

function run_fresh() {
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

run_fresh();

add_shortcode('pay-page', 'pay_page');

function pay_page($atts, $content = null)
{
	if (get_user_id()) {
		print do_shortcode("[woocommerce_checkout]");
		return;
	}
	print im_translate("In order to complete your order, register to this site.") . "<br/>";
	print im_translate("You can use existing user or create local user in the form below.") . "<br/>";
	print do_shortcode('[miniorange_social_login shape="longbuttonwithtext" theme="default" space="8" width="180" height="35" color="000000"]');

	print do_shortcode('[woocommerce_checkout]');

	print im_translate("Or with one of the following.") . "<br/>";

	return;
	// [woocommerce_checkout]
//    if (get_user_id())
//    {
//        do_shortcode("woocommerce_checkout");
//    } else {
//        print "need to login";
//    }
}
add_shortcode( 'im-page', 'im_page' );

function im_page() {
	$img_size = 50;
	$data     = "XXX";
	$data     .= gui_hyperlink( get_the_post_thumbnail( 4209, array( $img_size, $img_size ) ), "/how_to_use" );

	return $data;
	// [im-page]
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

add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );
add_action( 'woocommerce_checkout_order_processed', 'wc_minimum_order_amount' );
add_action( 'woocommerce_after_cart_table', 'wc_after_cart' );
add_action( 'wp_footer', 'im_footer' );


// wp_enqueue_style( $handle, $src, $deps, $ver, $media );

// in functions_im
//function get_minimum_order() {
///// XXXXXXXXX
//	return 0;
//	global $woocommerce;
//
//	$value = 85;
//
//	$country  = $woocommerce->customer->get_shipping_country();
//	// $state    = $woocommerce->customer->get_shipping_state();
//	$postcode = $woocommerce->customer->get_shipping_postcode();
//
//	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
//		'destination' => array(
//			'country'  => $country,
//			'state'    => '',
//			'postcode' => $postcode,
//		),
//	) );
////    my_log ("zone_id = " . $zone1->get_id());
//
//	$sql    = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone1->get_id();
//	$result = sql_query( $sql );
//	if ( $result ) {
//		$row = mysqli_fetch_assoc( $result );
//		//    my_log($row["min_order"]);
//
//		if ( is_numeric( $row["min_order"] ) ) {
//			$value = $row["min_order"];
//		}
//	}
//
//	return $value;
//}

// in functions_im thema
/*function wc_minimum_order_amount() {
	$minimum = get_minimum_order();

	if ( WC()->cart->total - WC()->cart->shipping_total < $minimum ) {
		if ( is_cart() ) {
			wc_print_notice(
				sprintf( 'הזמנת מינימום לאזורך %s. סך ההזמנה עד כה  %s.',
					wc_price( $minimum ),
					wc_price( WC()->cart->total - WC()->cart->shipping_total )
				), 'error'
			);

		} else {
			wc_add_notice(
				sprintf( 'הזמנת מינימום לאזורך %s. סך ההזמנה עד כה  %s.',
					wc_price( $minimum ),
					wc_price( WC()->cart->total - WC()->cart->shipping_total )
				), 'error'
			);
		}
	}
}*/

//function wc_after_cart() {
////    print "<a href=\"http://store.im-haadama.co.il/"
////	if ( $_SERVER['SERVER_NAME'] == 'fruity.co.il' ) {
////		print "<a href=\"../fresh/baskets/unfold.php\"" .
////		      "class=\"checkout-button button alt wc-forward\">החלף סלים במרכיביו</a>";
////	}
////המשך לתשלום</a>
////    print "<input class=\"button alt\" name=\"unfold_basket\" value=\"פרום סל\" />";
//}
//
////* Make Font Awesome available
//add_action( 'wp_enqueue_scripts', 'enqueue_font_awesome' );
//function enqueue_font_awesome() {
//	wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
//}
//
///**
// * Place a cart icon with number of items and total cost in the menu bar.
// *
// * Source: http://wordpress.org/plugins/woocommerce-menu-bar-cart/
// */
//add_filter( 'wp_nav_menu_items', 'sk_wcmenucart', 10, 2 );
//function sk_wcmenucart( $menu, $args ) {
//
//	// Check if WooCommerce is active and add a new item to a menu assigned to Primary Navigation Menu location
////    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || 'main' !== $args->theme_location )
////        return $menu;
//	ob_start();
//	global $woocommerce;
//	$viewing_cart        = __( 'View your shopping cart', 'your-theme-slug' );
//	$start_shopping      = __( 'Start shopping', 'your-theme-slug' );
//	$cart_url            = $woocommerce->cart->get_cart_url();
//	$shop_page_url       = get_permalink( woocommerce_get_page_id( 'shop' ) );
//	$cart_contents_count = $woocommerce->cart->cart_contents_count;
//	$cart_contents       = sprintf( _n( '%d item', '%d ', $cart_contents_count, 'your-theme-slug' ), $cart_contents_count );
//	$cart_total          = $woocommerce->cart->get_cart_total();
//	// Uncomment the line below to hide nav menu cart item when there are no items in the cart
//	// if ( $cart_contents_count > 0 ) {
//	if ( $cart_contents_count == 0 ) {
//		$menu_item = '<li class="right"><a class="wcmenucart-contents" href="' . $shop_page_url . '" title="' . $start_shopping . '">';
//	} else {
//		$menu_item = '<li class="right"><a class="wcmenucart-contents" href="' . $cart_url . '" title="' . $viewing_cart . '">';
//	}
//
//	$menu_item .= '<i class="fa fa-shopping-cart"></i> ';
//
//	$menu_item .= $cart_contents . ' - ' . $cart_total;
//	$menu_item .= '</a></li>';
//	// Uncomment the line below to hide nav menu cart item when there are no items in the cart
//	// }
//	echo $menu_item;
//	$social = ob_get_clean();
//	if ( $args->theme_location == 'top-navigation' ) {
//		return $menu;
//	}
//
//	return $menu . $social;
//
//}
//
//add_action( 'show_user_profile', 'my_show_extra_profile_fields' );
//add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );
//
//function my_show_extra_profile_fields( $user ) {