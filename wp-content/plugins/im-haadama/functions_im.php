<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/11/16
 * Time: 18:22
 */
require_once( STORE_DIR . '/tools/im_tools.php' );
require_once( STORE_DIR . '/tools/sql.php' );
require_once( STORE_DIR . '/tools/wp.php' );

//require_once('../../../../tools/im_tools.php');
// require_once ("../../../../tools/wp.php");
add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );
add_action( 'woocommerce_checkout_order_processed', 'wc_minimum_order_amount' );
add_action( 'woocommerce_after_cart_table', 'wc_after_cart' );


function get_minimum_order() {
	global $woocommerce;

	$value = 85;

	$country  = $woocommerce->customer->get_shipping_country();
	$state    = $woocommerce->customer->get_shipping_state();
	$postcode = $woocommerce->customer->get_shipping_postcode();
//    my_log("country " . $country);
//    my_log("state " . $state);
//    my_log("post code " . $postcode);
//    $package = WC()->cart->get_shipping_packages();
//    ob_start();
//    var_dump($package);
//    $result = ob_get_clean();
//    my_log ($result);
	$zone1 = WC_Shipping_Zones::get_zone_matching_package( array(
		'destination' => array(
			'country'  => $country,
//            'state'    => $state,
			'postcode' => $postcode,
		),
	) );
//    my_log ("zone_id = " . $zone1->get_id());

	$sql = "SELECT min_order FROM wp_woocommerce_shipping_zones WHERE zone_id = " . $zone1->get_id();
	my_log( $sql );
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
	print "<a href=\"../tools/baskets/unfold.php\"" .
	      "class=\"checkout-button button alt wc-forward\">החלף סלים במרכיביו</a>";
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
        </tr>
    </table>

    <h3>פרטי משלוח ברירת מחדל</h3>

    <table class="form-table">
        <tr>
            <th><label for="shipping_zone">איזור משלוח</label></th>

            <td>
                <input type="text" name="shipping_zone" id="shipping_zone"
                       value="<?php echo esc_attr( get_the_author_meta( 'shipping_zone', $user->ID ) ); ?>"
                       class="regular-text"/><br/>
                <span class="description">הכנס מספר איזור משלוח.</span>
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
	update_usermeta( $user_id, 'shipping_zone', $_POST['shipping_zone'] );
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

add_action( 'init', 'custom_add_to_cart_quantity_handler' );

/***************************
 * Select date of delivery *
 ***************************/

//add_action( 'woocommerce_after_order_notes', 'checkout_select_date' );
//
//function format_date($date)
//{
//    $days = ["ראשון", "שני", "שלישי", "רביעי", "חמישי", "שישי"];
//
//    return $days[date("N", strtotime($date))] . " " . $date;
//}
//
//function checkout_select_date( $checkout ) {
//    $user_id = get_current_user_id();
//    if ($user_id > 0){
//// print "postcode: " . $postcode . "<br/>";
//	    $customer = new WC_Customer($user_id);
//	    $postcode = $customer->get_shipping_postcode();
//        $package  = array( 'destination' => array( 'country' => 'IL', 'postcode' => $postcode ) );
//        $zone     = WC_Shipping_Zones::get_zone_matching_package( $package );
//        $zone_id = $zone->get_id();
//        my_log("finding missions. postcode = " . $postcode . " zone = ". $zone_id);
//        $sql = "select id, zones, date, start_h, end_h from im_missions where date > curdate() order by 3";
//
//        $result = sql_query($sql);
//
//        $options = array();
//        while ($row = mysqli_fetch_row($result))
//        {
//            $zones = $row[1];
//            // print "zones: " . $zones . "<br/>";
//            if (in_array($zone_id, explode(",", $zones))){
//                $options[$row[0]] = format_date($row[2]) . " " . $row[3] . ":00-" . $row[4] . ":00<br/>";
//            }
//        }
//    }
//    if (sizeof($options) < 1) {
//        $options[1] = "מוקדם ככל האפשר<br/>";
//        $options[2] = "תאמו איתי";
//    }
//
//	echo '<div id="date_selection" style="background: lightgoldenrodyellow;"><h3>' . __( 'בחר תאריך משלוח' ) . '</h3>';
//	woocommerce_form_field_radio( 'mission_id', array(
//		'type' => 'select',
//		'class' => array(
//			'date_selection form-row-wide'
//		),
//		'label' => __( '' ),
//		'placeholder' => __( '' ),
//		'required' => true,
//		'options' => $options
//	), $checkout->get_value( 'date_selection' ) );
//	echo '</div>';
//}
//
//add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');
//
//function my_custom_checkout_field_process() {
//	// Check if set, if its not set add an error.
//	if ( ! $_POST['mission_id'] )
//		wc_add_notice( __( 'אנא בחר תאריך משלוח.' ), 'error' );
//}

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


if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
}

add_action( 'woocommerce_before_calculate_totals', 'im_woocommerce_update_price', 99 );

function im_woocommerce_update_price() {
	my_log( "cart start" );
	// TWEEK. Don't know why menu_op calls this method.
	// DONT remove without trying menu.php and cart.
	global $conn;
	if ( ! $conn ) {
		return;
	}
	//  if (!defined (get_postmeta_field)) return;
// my_log("cart");
	$client_type = customer_type( get_user_id() );
	// print "S" . get_user_id() ." " . $client_type;
	//if ($client_type > 0) print "T". $client_type;

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
//	    ob_start();
//		var_dump($cart_item);
//		$result = ob_get_clean();
//		my_log($result);
//		return;

		$prod_id = $cart_item['product_id'];
		// my_log($prod_id);
		if ( ! ( $prod_id > 0 ) ) {
			my_log( "cart - no prod_id" );
			continue;
		}
		$q          = $cart_item['quantity'];
		$sell_price = get_postmeta_field( $prod_id, '_price' ); // Regular price.
		switch ( $client_type ) {
			case 0: // Regular client - gets discount for quantities greater than 8.
				if ( $q >= 8 ) {
					$buy_price = get_postmeta_field( $prod_id, 'buy_price' );
					if ( $buy_price > 0 ) {
						$quantity_price = round( 1.4 * $buy_price, 1 );
					} else {
						$quantity_price = $sell_price;
					}
					// my_log("quantity price " . $quantity_price . ". sell " . $sell_price);
					if ( $quantity_price < $sell_price ) {
						$sell_price = $quantity_price;
						//   my_log("sell price " . $sell_price);
					}
				} else {
					$sell_price = get_postmeta_field( $prod_id, '_price' );
				}
				break;
			case 1: // siton - pay 15% about buy price
				$sell_price = min( round( get_buy_price( $prod_id ) * 1.15, 1 ), $sell_price );
				break;

			case 2: // owner - pay buy_price
				$sell_price = min( round( get_buy_price( $prod_id ), 1 ), $sell_price );
				break;
		}
		//my_log("set " . $sell_price);
		$cart_item['data']->set_sale_price( $sell_price );
		$cart_item['data']->set_price( $sell_price );
		my_log( $prod_id . " " . $q . " " . $quantity_price );

	}
	//		ob_start();
}

add_filter( 'woocommerce_cart_item_price', 'im_show_nonsale_price', 10, 2 );
function im_show_nonsale_price( $newprice, $product ) {
	$_product  = $product['data'];
	$saleprice = $_product->sale_price;
	if ( $_product->sale_price < $_product->regular_price ) {
		$newprice = '';
		$newprice .= '<del><small style="color:#000000;">';
		$newprice .= wc_price( $_product->regular_price );
		$newprice .= '</small></del> <strong>';
		$newprice .= wc_price( $_product->sale_price );
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
