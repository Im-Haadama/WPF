<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/11/16
 * Time: 18:22
 */

function add_stylesheet_to_head() {
    global $post;
    require_once (ABSPATH . 'wp-content/plugins/fresh/includes/niver/fund.php');

    if (strstr($post->post_content, 'fresh')) {

        print load_style(  get_template_directory_uri() . '/css/management.css'); // Hides logo, search and white area contains them.
//        register_nav_menus(array('main-fresh' => __ ('Primary Menu', 'minezine')));

//	    add_action( 'after_setup_theme', 'register_my_menu' );


//	    $menu_id = wp_get_nav_menu_object('top-nav');

//	    var_dump($menu_id);

	    // Set up default menu items
//	    wp_update_nav_menu_item($menu_id, 0, array(
//		    'menu-item-title' =>  __('Home'),
//		    'menu-item-classes' => 'home',
//		    'menu-item-url' => home_url( '/' ),
//		    'menu-item-status' => 'publish'));

//	    wp_update_nav_menu_item($menu_id, 0, array(
//		    'menu-item-title' =>  __('Custom Page'),
//		    'menu-item-url' => home_url( '/custom/' ),
//		    'menu-item-status' => 'publish'));
    }

}



add_action( 'wp_head', 'add_stylesheet_to_head' );


return;

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
}


require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
require_once( ROOT_DIR . '/niver/data/sql.php' );
require_once( ROOT_DIR . '/niver/wp.php' );
require_once(ROOT_DIR . '/fresh/pricing.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );

add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );
add_action( 'woocommerce_checkout_order_processed', 'wc_minimum_order_amount' );
add_action( 'woocommerce_after_cart_table', 'wc_after_cart' );
add_action( 'wp_footer', 'im_footer' );

function im_footer() {
	global $power_version;
	$data = '<div style="color:#95bd3e" align="center">';
	$data .= 'Fresh store powered by ' . "<a href=\"http://aglamaz.com/\">Aglamaz.com</a> 2015-2019 ";
	$data .= 'Version ' . $power_version;
	$data .= "</div>";

	return $data;
}


// wp_enqueue_style( $handle, $src, $deps, $ver, $media );


function get_minimum_order() {
/// XXXXXXXXX
    return 0;
	global $woocommerce;

	$value = 85;

	$country  = $woocommerce->customer->get_shipping_country();
	// $state    = $woocommerce->customer->get_shipping_state();
	$postcode = $woocommerce->customer->get_shipping_postcode();

	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
			'state'    => '',
			'postcode' => $postcode,
		),
	) );
//    my_log ("zone_id = " . $zone1->get_id());

	$sql    = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone1->get_id();
	$result = sql_query( $sql );
	if ( $result ) {
		$row = mysqli_fetch_assoc( $result );
		//    my_log($row["min_order"]);

		if ( is_numeric( $row["min_order"] ) ) {
			$value = $row["min_order"];
		}
	}

	return $value;
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

function wc_after_cart() {
//    print "<a href=\"http://store.im-haadama.co.il/"
//	if ( $_SERVER['SERVER_NAME'] == 'fruity.co.il' ) {
//		print "<a href=\"../fresh/baskets/unfold.php\"" .
//		      "class=\"checkout-button button alt wc-forward\">החלף סלים במרכיביו</a>";
//	}
//המשך לתשלום</a>
//    print "<input class=\"button alt\" name=\"unfold_basket\" value=\"פרום סל\" />";
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
function custom_add_to_cart_quantity_handler() {
	if ( function_exists( 'wc_enqueue_js' ) ) {
		wc_enqueue_js( '
		jQuery( ".input-text.qty.text" ).on( "change input", ".quantity", function() {
			var add_to_cart_button = jQuery( this ).parents( ".product" ).find( ".add_to_cart_button" );

			// For AJAX add-to-cart actions
			add_to_cart_button.attr( "data-quantity", jQuery( this ).val() );
			alert("XX");

			// For non-AJAX add-to-cart actions
			add_to_cart_button.attr( "href", "?add-to-cart=" + add_to_cart_button.attr( "data-product_id" ) + "&XXXX&quantity=" + jQuery( this ).val() );
		});
	' );
	}
}

add_action( 'init', 'custom_add_to_cart_quantity_handler' );


/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );

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

add_action( 'woocommerce_before_calculate_totals', 'im_woocommerce_update_price', 99 );

function im_woocommerce_update_price() {
	global $site_id;
	if ( $site_id != 4 ) return;

	my_log( "cart start" );
	// TWEEK. Don't know why menu_op calls this method.
	// DONT remove without trying menu.php and cart.
    if (! sql_query_single_scalar("select 1")) {
        my_log ("not connected to db");
        print "not conneted do db";
        return;
    }
	$client_type = customer_type( get_user_id() );

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$prod_id = $cart_item['product_id'];
		$variation_id = $cart_item['variation_id'];
		if ( ! ( $prod_id > 0 ) ) {
			my_log( "cart - no prod_id" );
			continue;
		}
		$q          = $cart_item['quantity'];
		$sell_price = get_price_by_type( $prod_id, $client_type, $q, $variation_id );
		//my_log("set " . $sell_price);
		$cart_item['data']->set_sale_price( $sell_price );
		$cart_item['data']->set_price( $sell_price );
		my_log( $prod_id . " " . $q );
	}
}

return;

add_filter( 'woocommerce_cart_item_price', 'im_show_nonsale_price', 10, 2 );
function im_show_nonsale_price( $newprice, $product ) {
	global $site_id;
	if ( $site_id != 4 ) {
		return $newprice;
	}
	$_product   = $product['data'];
	$sale_price = $_product->get_sale_price();
	if ( ( $sale_price > 0 ) and ( $_product->get_sale_price() < $_product->get_regular_price() ) ) {
		$newprice = '';
		$newprice .= '<del><small style="color:#000000;">';
		$newprice .= wc_price( $_product->get_regular_price() );
		$newprice .= '</small></del> <strong>';
		$newprice .= wc_price( $sale_price );
		$newprice .= '</strong>';

		return $newprice;
	} else {
		$newprice = wc_price( $_product->price );

		return $newprice;
	}
}

add_filter( 'woocommerce_order_button_text', 'im_custom_order_button_text' );

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
add_filter( 'woocommerce_available_shipping_methods', 'hide_shipping_if_cat_is_orange', 10, 1 );

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
add_action( 'admin_menu', 'im_admin_menu' );

function im_admin_menu() {
//	add_menu_page( 'Fresh Store', 'Fresh Store', 'manage_options', 'im-haadama/admin.php', 'fresh_store_admin_page',
//        'dashicons-tickets', 6 );
	add_menu_page( 'Fresh Store', 'תפריט אריזה', 'manage_options', 'im-haadama/packing.php', 'fresh_store_packing_page',
		'dashicons-tickets', 6 );
	add_menu_page( 'Fresh Store', 'ניהול ספקים', 'manage_options', 'im-haadama/supplier_account.php', 'fresh_store_supplier_account_page',
		'dashicons-tickets', 6 );
}


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
?>
