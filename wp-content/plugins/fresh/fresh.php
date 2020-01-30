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

function PayPage($atts, $content = null)
{
	if (get_user_id()) {
		print do_shortcode("[woocommerce_checkout]");
		return;
	}
	print ImTranslate("In order to complete your order, register to this site.") . "<br/>";
	print ImTranslate("You can use existing user or create local user in the form below.") . "<br/>";
	print do_shortcode('[miniorange_social_login shape="longbuttonwithtext" theme="default" space="8" width="180" height="35" color="000000"]');

	print do_shortcode('[woocommerce_checkout]');

	print ImTranslate("Or with one of the following.") . "<br/>";

	return;
	// [woocommerce_checkout]
//    if (get_user_id())
//    {
//        do_shortcode("woocommerce_checkout");
//    } else {
//        print "need to login";
//    }
}


// -#-#-#-#-#-#-#-#-#-#-#-
// Shipment Order Status #
// -#-#-#-#-#-#-#-#-#-#-#-

//add_action( 'wp_footer', 'im_footer' );


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
function wc_minimum_order_amount() {
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
}

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
if (0) {

	die(1);
	/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/11/16
 * Time: 18:22
 */
if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
}

require_once(ROOT_DIR . '/im-config.php');
require_once(ROOT_DIR . "/init.php");

require_once( ROOT_DIR . '/fresh/im_tools.php' );
require_once( ROOT_DIR . '/niver/data/sql.php' );
require_once( ROOT_DIR . '/niver/wp.php' );
require_once( ROOT_DIR . '/fresh/pricing.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );

//if ( ! isset( $woocommerce ) ) {
//	 print "Woocommerce is not present. Exiting";
//	return;
//}

//require_once('../../../../fresh/im_tools.php');
// require_once ("../../../../niver/wp.php");

function im_footer() {
	global $power_version;
	$data = '<div style="color:#95bd3e" align="center">';
	$data .= 'Fresh store powered by ' . gui_hyperlink( "Niver Dri Sol",
			"http://niver-dri-sol.com" ) . ' 2015-2019 ';
	$data .= 'Version ' . $power_version;
	$data .= "</div>";

	return $data;
}

function wc_minimum_order_amount() {

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
}

//* Make Font Awesome available
add_action( 'wp_enqueue_scripts', 'enqueue_font_awesome' );
function enqueue_font_awesome() {
	wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css' );
}

/**
 * Place a cart icon with number of items and total cost in the menu bar.
 *
 * Source: http://wordpress.org/plugins/woocommerce-menu-bar-cart/
 */
add_filter( 'wp_nav_menu_items', 'sk_wcmenucart', 10, 2 );
function sk_wcmenucart( $menu, $args ) {

	// Check if WooCommerce is active and add a new item to a menu assigned to Primary Navigation Menu location
//    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || 'main' !== $args->theme_location )
//        return $menu;
	ob_start();
	global $woocommerce;
	$viewing_cart        = __( 'View your shopping cart', 'your-theme-slug' );
	$start_shopping      = __( 'Start shopping', 'your-theme-slug' );
	$cart_url            = $woocommerce->cart->get_cart_url();
	$shop_page_url       = get_permalink( woocommerce_get_page_id( 'shop' ) );
	$cart_contents_count = $woocommerce->cart->cart_contents_count;
	$cart_contents       = sprintf( _n( '%d item', '%d ', $cart_contents_count, 'your-theme-slug' ), $cart_contents_count );
	$cart_total          = $woocommerce->cart->get_cart_total();
	// Uncomment the line below to hide nav menu cart item when there are no items in the cart
	// if ( $cart_contents_count > 0 ) {
	if ( $cart_contents_count == 0 ) {
		$menu_item = '<li class="right"><a class="wcmenucart-contents" href="' . $shop_page_url . '" title="' . $start_shopping . '">';
	} else {
		$menu_item = '<li class="right"><a class="wcmenucart-contents" href="' . $cart_url . '" title="' . $viewing_cart . '">';
	}

	$menu_item .= '<i class="fa fa-shopping-cart"></i> ';

	$menu_item .= $cart_contents . ' - ' . $cart_total;
	$menu_item .= '</a></li>';
	// Uncomment the line below to hide nav menu cart item when there are no items in the cart
	// }
	echo $menu_item;
	$social = ob_get_clean();
	if ( $args->theme_location == 'top-navigation' ) {
		return $menu;
	}

	return $menu . $social;

}

add_action( 'show_user_profile', 'my_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );

function my_show_extra_profile_fields( $user ) { ?>

    <h3>העדפות משתמש</h3>

    <table class="form-table">

        <tr>
            <th><label for="preference">העדפות</label></th>

            <td>
                <input type="text" name="preference" id="preference"
                       value="<?php echo esc_attr( get_the_author_meta( 'preference', $user->ID ) ); ?>"
                       class="regular-text"/><br/>
                <span class="description">הכנס העדפות משתמש.</span>
            </td>
            <td>
                <input type="text" name="auto_mail" id="auto_mail"
                       value="<?php echo esc_attr( get_the_author_meta( 'auto_mail', $user->ID ) ); ?>"
                       class="regular-text"/><br/>
                <span class="description">הכנס העדפות דיווח. למשל 1:15. יום ב', קטגוריה 15</span>
            </td>
            <td/>
            <input type="text" name="print_delivery_note" id="print_delivery_note"
                   value="<?php echo esc_attr( get_the_author_meta( 'print_delivery_note', $user->ID ) ); ?>"
                   class="regular-text"/><br/>
            <span class="description">האם להדפיס תעודת משלוח - P.<br/>
                    P - הדפסה
                </span>
            </td>
        </tr>
    </table>

    <!--    <h3>פרטי משלוח ברירת מחדל</h3>-->
    <!---->
    <!--    <table class="form-table">-->
    <!--        <tr>-->
    <!--            <th><label for="shipping_zone">איזור משלוח</label></th>-->
    <!---->
    <!--            <td>-->
    <!--                <input type="text" name="shipping_zone" id="shipping_zone"-->
    <!--                       value="--><?php //echo esc_attr( get_the_author_meta( 'shipping_zone', $user->ID ) ); ?><!--"-->
    <!--                       class="regular-text"/><br/>-->
    <!--                <span class="description">הכנס מספר איזור משלוח.</span>-->
    <!--            </td>-->
    <!--        </tr>-->
    <!---->
    <!--    </table>-->
<?php }

add_action( 'personal_options_update', 'my_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );

function my_save_extra_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	/* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
	update_usermeta( $user_id, 'preference', $_POST['preference'] );
	update_usermeta( $user_id, 'auto_mail', $_POST['auto_mail'] );
	update_usermeta( $user_id, 'print_delivery_note', $_POST['print_delivery_note'] );
    if (isset($_POST['shipping_zone']))	update_usermeta( $user_id, 'shipping_zone', $_POST['shipping_zone'] );
}


////////////////////////////////////
// Use decimal in quantity fields //
////////////////////////////////////

add_filter( 'woocommerce_quantity_input_min', 'min_decimal' );
function min_decimal( $val ) {
	return 0.5;
}

// Add step value to the quantity field (default = 1)
add_filter( 'woocommerce_quantity_input_step', 'nsk_allow_decimal' );
function nsk_allow_decimal( $val ) {
	return 0.5;
}

// Removes the WooCommerce filter, that is validating the quantity to be an int
remove_filter( 'woocommerce_stock_amount', 'intval' );

// Add a filter, that validates the quantity to be a float
add_filter( 'woocommerce_stock_amount', 'floatval' );

// Add unit price fix when showing the unit price on processed orders
add_filter( 'woocommerce_order_amount_item_total', 'unit_price_fix', 10, 5 );
function unit_price_fix( $price, $order, $item, $inc_tax = false, $round = true ) {
	$qty = ( ! empty( $item['qty'] ) && $item['qty'] != 0 ) ? $item['qty'] : 1;
	if ( $inc_tax ) {
		$price = ( $item['line_total'] + $item['line_tax'] ) / $qty;
	} else {
		$price = $item['line_total'] / $qty;
	}
	$price = $round ? round( $price, 2 ) : $price;

	return $price;
}

///*
//Plugin Name: Woocommerce add quantity on category pages
//Plugin URI:  http://uzzyraja.com
//Description: Adds a quantity field to your woocommerce category/archive page
//Version:     1.0
//Author:      Raja Usman Latif
//Author URI:  http://uzzyraja.com
//License:     GPL2
//License URI: https://www.gnu.org/licenses/gpl-2.0.html
//*/
///**
// * Add quantity field on the archive page. uzzyraja.com/sourcecodes/
// */
function custom_quantity_field_archive() {

	$product = wc_get_product( get_the_ID() );

	if ( ! $product->is_sold_individually() && 'variable' != $product->product_type && $product->is_purchasable() ) {
		woocommerce_quantity_input( array(
			'min_value' => 1,
			'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity()
		) );
	}
}

// add_action( 'woocommerce_after_shop_loop_item', 'custom_quantity_field_archive', 31);


/**
 * Add requires JavaScript. uzzyraja.com/sourcecodes/
 */

/**
 * Update the order meta with field value
 */

function my_custom_checkout_field_update_order_meta( $order_id ) {
	if ( ! empty( $_POST['mission_id'] ) ) {
		update_post_meta( $order_id, 'mission_id', sanitize_text_field( $_POST['mission_id'] ) );
	}
}

function woocommerce_form_field_radio( $key, $args, $value = '' ) {
	// global $woocommerce;
	$defaults = array(
		'type'        => 'radio',
		'label'       => '',
		'placeholder' => '',
		'required'    => false,
		'class'       => array(),
		'label_class' => array(),
		'return'      => false,
		'options'     => array()
	);
	$args     = wp_parse_args( $args, $defaults );
	if ( ( isset( $args['clear'] ) && $args['clear'] ) ) {
		$after = '<div class="clear"></div>';
	} else {
		$after = '';
	}
	$required = ( $args['required'] ) ? ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>' : '';
	switch ( $args['type'] ) {
		case "select":
			$options = '';
			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $option_key => $option_text ) {
					$options .= '<input type="radio" name="' . $key . '" id="' . $key . '" value="' . $option_key . '" ' . selected( $value, $option_key, false ) . 'class="select">' . $option_text . '' . "\r\n";
				}
			}
			$field = '<p class="form-row ' . implode( ' ', $args['class'] ) . '" id="' . $key . '_field">
<label for="' . $key . '" class="' . implode( ' ', $args['label_class'] ) . '">' . $args['label'] . $required . '</label>
' . $options . '
</p>' . $after;
			break;
	} //$args[ 'type' ]
	if ( $args['return'] ) {
		return $field;
	} else {
		echo $field;
	}
}



function im_custom_order_button_text() {
	return __( 'אשר הזמנתך', 'woocommerce' );
}

// Delivery based on products.
// Categories that can be send by post.

// a function to check if the cart has product from organge and it's sub category id
function cart_has_fresh_products() {
//Check to see if user has product in cart
	global $woocommerce;

//assigns a default negative value
// categories targeted 17, 18, 19

	$product_in_cart = false;

// start of the loop that fetches the cart items

	foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
		$_product = $values['data'];
		$terms    = get_the_terms( $_product->id, 'product_cat' );

// second level loop search, in case some items have several categories
		foreach ( $terms as $term ) {

			if ( ( $term === 17 ) || ( $term === 18 ) || ( $term === 19 ) ) {
//category is in cart!
				$product_in_cart = true;
			}
		}
	}

	return $product_in_cart;
}

// add filter and function to hide method

function hide_shipping_if_fresh( $available_methods ) {
	if ( cart_has_fresh_products() ) {
// remove the rate you want
		unset( $available_methods['flat_rate'] );
	}

// return the available methods without the one you unset.
	return $available_methods;
}

// Menu
//add_action( 'admin_page_packing_menu', 'im_packing_menu' );


function fresh_store_packing_page() {
	?>

	<?php

	print gui_table_args( array(//array("אריזה", "גביה", "קטלוג"),
		array( "packing" )
	) );

	// require_once("../fresh/menu_op.php");
}

function fresh_store_supplier_account_page() {
	?>

	<?php

	print gui_table_args( array(
		array( "supplier_account" )
	) );

	// require_once("../fresh/menu_op.php");
}


//add_filter( 'woocommerce_checkout_fields' , 'custom_wc_checkout_fields' );
//// Change order comments placeholder and label, and set billing phone number to not required.
//function custom_wc_checkout_fields( $fields ) {
////	$fields['order']['order_comments']['placeholder'] = 'Enter your placeholder text here.';
////	$fields['order']['order_comments']['label'] = 'Enter your label here.';
////	$fields['billing']['billing_phone']['required'] = false;
//    $fields['billing_postcode']
//    var_dump($fields);
//	return $fields;
//}

}

ini_set( 'display_errors', 'on' );
error_reporting( E_ALL );

add_shortcode('pay-page', 'pay_page');

function pay_page($atts, $content = null)
{
	if (get_user_id()) {
		print do_shortcode("[woocommerce_checkout]");
		return;
	}
	print ImTranslate("In order to complete your order, register to this site.") . "<br/>";
	print ImTranslate("You can use existing user or create local user in the form below.") . "<br/>";
	print do_shortcode('[miniorange_social_login shape="longbuttonwithtext" theme="default" space="8" width="180" height="35" color="000000"]');

	print do_shortcode('[woocommerce_checkout]');

	print ImTranslate("Or with one of the following.") . "<br/>";

	return;
	// [woocommerce_checkout]
//    if (get_user_id())
//    {
//        do_shortcode("woocommerce_checkout");
//    } else {
//        print "need to login";
//    }
}
