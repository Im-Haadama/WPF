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





/*-- Start product search filter --*/
function searchfilter($query) {
 
    if ($query->is_search && !is_admin() ) {
        $query->set('post_type',array('product'));
    }
 
    return $query;
}
 
add_filter('pre_get_posts','searchfilter');

/*-- End product search filter --*/


/*-- Start custom add to cart search product --*/

add_action('wp_ajax_ql_woocommerce_ajax_add_to_cart', 'sm_woocommerce_ajax_add_to_cart'); 
add_action('wp_ajax_nopriv_ql_woocommerce_ajax_add_to_cart', 'sm_woocommerce_ajax_add_to_cart');          

function sm_woocommerce_ajax_add_to_cart() {  

    $product_id = apply_filters('ql_woocommerce_add_to_cart_product_id', absint($_POST['product_id'])); 
    
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']); 

    $variation_id = absint($_POST['variation_id']); 

    $passed_validation = apply_filters('ql_woocommerce_add_to_cart_validation', true, $product_id, $quantity); 

    $product_status = get_post_status($product_id); 

    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id) && 'publish' === $product_status) { 

        do_action('ql_woocommerce_ajax_added_to_cart', $product_id); 

            if ('yes' === get_option('ql_woocommerce_cart_redirect_after_add')) { 

                wc_add_to_cart_message(array($product_id => $quantity), true); 

            } 

            WC_AJAX :: get_refreshed_fragments(); 

    } else { 

        $data = array( 

            'error' => true, 

            'product_url' => apply_filters('ql_woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)); 

        echo wp_send_json($data); 

    } 

    wp_die(); 

}


function custom_enqueue_script() {   
    wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'js/add_to_cart_on_serach.js' );
    wp_enqueue_script( 'custom_script', plugin_dir_url( __FILE__ ) . 'js/custom_script.js' );
}
add_action('wp_enqueue_scripts', 'custom_enqueue_script');

/*-- End custom add to cart search product --*/

/*-- Start remove product sorting option --*/
add_filter( 'woocommerce_catalog_orderby', 'sm_remove_sorting_option_woocommerce_shop' );
  
function sm_remove_sorting_option_woocommerce_shop( $options ) {
   unset( $options['rating'] );
   unset( $options['price'] ); 
   unset( $options['date'] ); 
   unset( $options['price-desc'] ); 
   return $options;
}
/*-- End remove product sorting option --*/

/*-- Start add alphabetical product sort option --*/

add_filter( 'woocommerce_get_catalog_ordering_args', 'sm_alphabetical_woocommerce_shop_ordering' );
function sm_alphabetical_woocommerce_shop_ordering( $sort_args ) {
  $orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
 
    if ( 'alphabetical' == $orderby_value ) {
        $sort_args['orderby'] = 'title';
        $sort_args['order'] = 'asc';
        $sort_args['meta_key'] = '';
    }
 
    return $sort_args;
}


add_filter( 'woocommerce_default_catalog_orderby_options', 'sm_custom_woocommerce_catalog_orderby' );
add_filter( 'woocommerce_catalog_orderby', 'sm_custom_woocommerce_catalog_orderby' );
function sm_custom_woocommerce_catalog_orderby( $sortby ) {
    $sortby['alphabetical'] = 'Sort by name';
    return $sortby;
}

/*-- End add alphabetical product sort option --*/


/*-- Start add menu page-- */
function payment_list() {
    include('payment_list.php');  
}
function payment_list_menu() 
{
	if(current_user_can('administrator'))
	{
		add_menu_page('Payment', 'Payment List', 'administrator', 'payment_list','payment_list');
	}
}
add_action('admin_menu', 'payment_list_menu');
/*-- end menu page-- */

/*-- Start add css & js-- */
function wp_payment_list_admin_styles()
{	
	if (isset($_GET['page']) && $_GET['page'] == 'payment_list')
	{	
		wp_register_style('jquery_ui4', plugins_url().'/fresh/css/jquery-ui.min4.css');
		wp_enqueue_style('jquery_ui4');

		wp_register_style('bootstrap.min', plugins_url().'/fresh/css/bootstrap.min.css');
		wp_enqueue_style('bootstrap.min');
		
		wp_register_style('dataTables.bootstrap.min', plugins_url().'/fresh/css/dataTables.bootstrap.min.css',array(), '1.10.16');
		wp_enqueue_style('dataTables.bootstrap.min');

		wp_register_style('custom', plugins_url().'/fresh/css/custom.css');
		wp_enqueue_style('custom');

		wp_register_style('jquery-ui', plugins_url().'/fresh/css/jquery-ui.css');
		wp_enqueue_style('jquery-ui');

	}
	
}
add_action('admin_print_styles', 'wp_payment_list_admin_styles');

function wp_payment_list_admin_script() {   

    wp_enqueue_script( 'dataTables.min', plugins_url(). '/fresh/js/jquery.dataTables.min.js',array('jquery') );

    wp_enqueue_script( 'dataTables.bootstrap.min', plugins_url(). '/fresh/js/dataTables.bootstrap.min.js' );

    wp_enqueue_script( 'dataTables.buttons.min', plugins_url(). '/fresh/js/dataTables.buttons.min.js' );

}
add_action('admin_init', 'wp_payment_list_admin_script');
/*-- End add css & js-- */

/*-- Start create payment table --*/
function payment_info_table(){
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE `im_payment_info` (
	    `id` int(11) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	    `full_name` varchar(255) NOT NULL,
	    `email` varchar(255) NOT NULL,
	    `card_number` varchar(50) NOT NULL,
	    `card_four_digit` varchar(50) NOT NULL,
	    `card_type` varchar(100) NOT NULL,
	    `exp_date_month` tinyint(4) NOT NULL,
	    `exp_date_year` int(11) NOT NULL,
	    `cvv_number` varchar(20) NOT NULL,
	    `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
register_activation_hook(__FILE__, 'payment_info_table');
/*-- End create payment table --*/

/*-- Start save payment info --*/
add_action('woocommerce_thankyou', 'insert_payment_info', 10, 1);
function insert_payment_info( $order_id ) {

	
    if ( ! $order_id )
        return;
    if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {

        $order = wc_get_order( $order_id );
        
         
        $first_name = get_post_meta($order_id, '_billing_first_name', TRUE);
        $last_name = get_post_meta($order_id, '_billing_last_name', TRUE);
        $full_name = $first_name.' '.$last_name;
        $billing_email = get_post_meta($order_id, '_billing_email', TRUE);
        $card_number = get_post_meta($order_id, 'card_number', TRUE); 

        function setCreditCard($cc){
		    $cc_length = strlen($cc);

		    for($i=0; $i<$cc_length-4; $i++){
		        if($cc[$i] == '-'){continue;}
		        $cc[$i] = 'X';
		    }
		    return $cc;
		}

        $card_last_4_digit = setCreditCard($card_number);
        $card_type = get_post_meta($order_id, 'card_type', TRUE);
        $exp_date_month = get_post_meta($order_id, 'expdate_month', TRUE);
        $exp_date_year = get_post_meta($order_id, 'expdate_year', TRUE);
        $cvv_number = get_post_meta($order_id, 'cvv_number', TRUE);
        $billing_id_number = get_post_meta($order_id, 'id_number', TRUE);
        
        if($card_number != ''){
	        global $wpdb;
			$table = 'im_payment_info';
			$data = array('full_name' => $full_name, 'email' => $billing_email, 'card_number' => $card_number, 'card_four_digit' => $card_last_4_digit, 'card_type' => $card_type, 'exp_date_month' => $exp_date_month, 'exp_date_year' => $exp_date_year, 'cvv_number' => $cvv_number,'id_number' => $billing_id_number );
			$wpdb->insert($table,$data);
			$last_id = $wpdb->insert_id;
			if($last_id){
				delete_post_meta($order_id, 'card_number');
				delete_post_meta($order_id, 'cvv_number');
			}
		}	

    }
}
/*-- End save payment info --*/

/*-- Start payment gateway--*/
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(fresh_custom_payment_is_woocommerce_active()){
	add_filter('woocommerce_payment_gateways', 'add_other_payment_gateway');
	function add_other_payment_gateway( $gateways ){
		$gateways[] = 'WC_Other_Payment_Gateway';
		return $gateways; 
	}

	add_action('plugins_loaded', 'init_other_payment_gateway');
	function init_other_payment_gateway(){
		require 'includes/class-fresh-payment-gateway.php';
	}

	add_action( 'plugins_loaded', 'other_payment_load_plugin_textdomain' );
	function other_payment_load_plugin_textdomain() {
	  load_plugin_textdomain( 'woocommerce-other-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}



}

function fresh_custom_payment_is_woocommerce_active()
{
	$active_plugins = (array) get_option('active_plugins', array());

	if (is_multisite()) {
		$active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
	}

	return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}
/*-- End payment gateway--*/