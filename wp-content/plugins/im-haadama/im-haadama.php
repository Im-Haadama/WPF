<?php
/**
 * Plugin Name: Im-Haadama
 * Created by PhpStorm.
+ * User: agla
 * Date: 19/06/16
 * Time: 17:42
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

define( 'IM_HAADAMA_PLUGIN', __FILE__ );

define( 'IM_HAADAMA_PLUGIN_BASENAME', plugin_basename( IM_HAADAMA_PLUGIN ) );

define( 'IM_HAADAMA_PLUGIN_NAME', trim( dirname( IM_HAADAMA_PLUGIN_BASENAME ), '/' ) );

define( 'IM_HAADAMA_PLUGIN_DIR', untrailingslashit( dirname( IM_HAADAMA_PLUGIN ) ) );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname(dirname( dirname( dirname( IM_HAADAMA_PLUGIN)))));
}

if ( ! defined( 'TOOLS_DIR')) {
     define ('TOOLS_DIR', ROOT_DIR . '/fresh');
}

require_once (ABSPATH . '/im-config.php');

////////////////////
// Parent process //
////////////////////

// require_once( "functions_im.php" );
if (file_exists(TOOLS_DIR . "/im_tools_light.php"))
    require_once(TOOLS_DIR . "/im_tools_light.php");

if (function_exists("im_init"))
try {
	require_once( ROOT_DIR . "/init.php" );
} catch ( Exception $e ) {
    print "Database error. Please contact support";
}

add_shortcode('store-locator', 'store_locator');
function store_locator()
{

}

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
	require_once( ROOT_DIR . "/fresh/express/show-inventory.php" );
	show_inventory_client();
}

// Delivery time select
//function delivery_options()
//{
//
//} [basket-content id=1085]
add_shortcode( 'basket-content', 'content_func' );

// [category-content id=18]
add_shortcode( 'category-content', 'category_content_func' );

function category_content_func($atts, $content, $tag)
{
    if (! file_exists(ROOT_DIR . '/fresh/wp/Product.php')) return "not installed";

	require_once (ROOT_DIR . '/fresh/wp/Product.php');

	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
	$id = $my_atts['id'];

	$iter = new ProductIterator();
	$iter->iterateCategory( $id );

	$result = "";
	while ( $prod_id = $iter->next()) $result .= get_product_name($prod_id) . ", ";

	return rtrim($result, ", ");
}

function content_func( $atts, $contents, $tag ) {
	require_once( ROOT_DIR . '/fresh/catalog/Basket.php' );

	$my_atts = shortcode_atts( [ 'id' => get_the_ID() ], $atts, $tag );
//
	$id = $my_atts['id'];

	$text = "תכולת הסל: ";
	$text .= get_basket_content( $id );

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
//    $URL = "http://store.im-haadama.co.il/fresh/get_basket.php?id=" . $id;
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


add_shortcode( 'beth', 'beth_sign' );

function beth_sign( $atts, $contents, $tag ) {
	return 'נכתב ע"י בת אפשטיין-ברייר ' . '<a href="https://www.facebook.com/beth.brayer">' . 'נטרופת והרבליסט קלינית.' . '</a>';
}



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

add_shortcode( 'med_plants', 'med_plants' ); // [med_plants]

function med_plants( $atts, $contents, $tag ) {
    $result = "";
	$result .= gui_header(1, "Relevant plants:");
	$post_id = get_the_ID();

    $terms = get_the_terms(get_the_ID(), 'category');

    foreach ($terms as $term){
        $term_result = gui_header(1, get_term_name($term));
        $query_args = array(
    		'post_type'      => 'post',
    //		'posts_per_page' => 100,
    //		'tax_query'      => array( array( 'terms' => $term_ids ) ), // 'taxonomy' => 'posts', 'field' => 'term_id',
            'cat' => $term->term_id,
            'orderby'        => 'name',
            'order'          => 'ASC'
        );

        $loop = new WP_Query( $query_args );
        $has_result = false;
        while ( $loop->have_posts() ) {
            $loop->the_post();
            $post =get_post();
            if ($post->ID != $post_id)  {
                $term_result .= GuiHyperlink($post->post_title, $post->guid) . gui_br();
                $has_result = true;

	            // TODO: organize the private stuff
	            $post->post_status = 'private';
	            wp_update_post( $post );
            }
        }
        if ($has_result) $result .= $term_result;
    }
	return $result;
}


// array(3) {
// [0]=> object(WP_Term)#11862 (10) { ["term_id"]=> int(432) ["name"]=> string(40) "אדפטוגנים מאזני חיסון" ["slug"]=> string(116) "%d7%90%d7%93%d7%a4%d7%98%d7%95%d7%92%d7%a0%d7%99%d7%9d-%d7%9e%d7%90%d7%96%d7%a0%d7%99-%d7%97%d7%99%d7%a1%d7%95%d7%9f" ["term_group"]=> int(0) ["term_taxonomy_id"]=> int(433) ["taxonomy"]=> string(8) "category" ["description"]=> string(0) "" ["parent"]=> int(431) ["count"]=> int(2) ["filter"]=> string(3) "raw" }
// [1]=> object(WP_Term)#11863 (10) { ["term_id"]=> int(433) ["name"]=> string(23) "נוגדי אלרגיה" ["slug"]=> string(67) "%d7%a0%d7%95%d7%92%d7%93%d7%99-%d7%90%d7%9c%d7%a8%d7%92%d7%99%d7%94" ["term_group"]=> int(0) ["term_taxonomy_id"]=> int(434) ["taxonomy"]=> string(8) "category" ["description"]=> string(0) "" ["parent"]=> int(430) ["count"]=> int(0) ["filter"]=> string(3) "raw" }
// [2]=> object(WP_Term)#11864 (10) { ["term_id"]=> int(429) ["name"]=> string(18) "סימפטומים" ["slug"]=> string(54) "%d7%a1%d7%99%d7%9e%d7%a4%d7%98%d7%95%d7%9e%d7%99%d7%9d" ["term_group"]=> int(0) ["term_taxonomy_id"]=> int(430) ["taxonomy"]=> string(8) "category" ["description"]=> string(0) "" ["parent"]=> int(0) ["count"]=> int(0) ["filter"]=> string(3) "raw" } }
// object(WP_Query)#11861 (50) { ["query"]=> array(4) { ["post_type"]=> string(4) "post" ["cat"]=> int(432) ["orderby"]=> string(4) "name" ["order"]=> string(3) "ASC" } ["query_vars"]=> array(64) { ["post_type"]=> string(4) "post" ["cat"]=> int(432) ["orderby"]=> string(4) "name" ["order"]=> string(3) "ASC" ["error"]=> string(0) "" ["m"]=> string(0) "" ["p"]=> int(0) ["post_parent"]=> string(0) "" ["subpost"]=> string(0) "" ["subpost_id"]=> string(0) "" ["attachment"]=> string(0) "" ["attachment_id"]=> int(0) ["name"]=> string(0) "" ["pagename"]=> string(0) "" ["page_id"]=> int(0) ["second"]=> string(0) "" ["minute"]=> string(0) "" ["hour"]=> string(0) "" ["day"]=> int(0) ["monthnum"]=> int(0) ["year"]=> int(0) ["w"]=> int(0) ["category_name"]=> string(116) "%d7%90%d7%93%d7%a4%d7%98%d7%95%d7%92%d7%a0%d7%99%d7%9d-%d7%9e%d7%90%d7%96%d7%a0%d7%99-%d7%97%d7%99%d7%a1%d7%95%d7%9f" ["tag"]=> string(0) "" ["tag_id"]=> string(0) "" ["author"]=> string(0) "" ["author_name"]=> string(0) "" ["feed"]=> string(0) "" ["tb"]=> string(0) "" ["paged"]=> int(0) ["meta_key"]=> string(0) "" ["meta_value"]=> string(0) "" ["preview"]=> string(0) "" ["s"]=> string(0) "" ["sentence"]=> string(0) "" ["title"]=> string(0) "" ["fields"]=> string(0) "" ["menu_order"]=> string(0) "" ["embed"]=> string(0) "" ["category__in"]=> array(0) { } ["category__not_in"]=> array(0) { } ["category__and"]=> array(0) { } ["post__in"]=> array(0) { } ["post__not_in"]=> array(0) { } ["post_name__in"]=> array(0) { } ["tag__in"]=> array(0) { } ["tag__not_in"]=> array(0) { } ["tag__and"]=> array(0) { } ["tag_slug__in"]=> array(0) { } ["tag_slug__and"]=> array(0) { } ["post_parent__in"]=> array(0) { } ["post_parent__not_in"]=> array(0) { } ["author__in"]=> array(0) { } ["author__not_in"]=> array(0) { } ["ignore_sticky_posts"]=> bool(false) ["suppress_filters"]=> bool(false) ["cache_results"]=> bool(true) ["update_post_term_cache"]=> bool(true) ["lazy_load_term_meta"]=> bool(true) ["update_post_meta_cache"]=> bool(true) ["posts_per_page"]=> int(10) ["nopaging"]=> bool(false) ["comments_per_page"]=> string(2) "50" ["no_found_rows"]=> bool(false) } ["tax_query"]=> object(WP_Tax_Query)#11866 (6) { ["queries"]=> array(1) { [0]=> array(5) { ["taxonomy"]=> string(8) "category" ["terms"]=> array(1) { [0]=> int(432) } ["field"]=> string(7) "term_id" ["operator"]=> string(2) "IN" ["include_children"]=> bool(true) } } ["relation"]=> string(3) "AND" ["table_aliases":protected]=> array(1) { [0]=> string(21) "wp_term_relationships" } ["queried_terms"]=> array(1) { ["category"]=> array(2) { ["terms"]=> array(1) { [0]=> int(432) } ["field"]=> string(7) "term_id" } } ["primary_table"]=> string(8) "wp_posts" ["primary_id_column"]=> string(2) "ID" } ["meta_query"]=> object(WP_Meta_Query)#11865 (9) { ["queries"]=> array(0) { } ["relation"]=> NULL ["meta_table"]=> NULL ["meta_id_column"]=> NULL ["primary_table"]=> NULL ["primary_id_column"]=> NULL ["table_aliases":protected]=> array(0) { } ["clauses":protected]=> array(0) { } ["has_or_relation":protected]=> bool(false) } ["date_query"]=> bool(false) ["request"]=> string(477) "SELECT SQL_CALC_FOUND_ROWS wp_posts.ID FROM wp_posts LEFT JOIN wp_term_relationships ON (wp_posts.ID = wp_term_relationships.object_id) WHERE 1=1 AND ( wp_term_relationships.term_taxonomy_id IN (433) ) AND wp_posts.post_type = 'post' AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'wc-awaiting-shipment' OR wp_posts.post_status = 'wc-awaiting-document' OR wp_posts.post_status = 'private') GROUP BY wp_posts.ID ORDER BY wp_posts.post_name ASC LIMIT 0, 10"
// ["posts"]=> array(3) { [0]=> object(WP_Post)#11870 (24) { ["ID"]=> int(8356) ["post_author"]=> string(1) "1" ["post_date"]=> string(19) "2019-12-07 20:05:18" ["post_date_gmt"]=> string(19) "2019-12-07 18:05:18" ["post_content"]=> string(140) "מקורות מידע על אלרגיה: נטורופדיה [med_plants]" ["post_title"]=> string(12) "אלרגיה" ["post_excerpt"]=> string(0) "" ["post_status"]=> string(7) "private" ["comment_status"]=> string(4) "open" ["ping_status"]=> string(4) "open" ["post_password"]=> string(0) "" ["post_name"]=> string(36) "%d7%90%d7%9c%d7%a8%d7%92%d7%99%d7%94" ["to_ping"]=> string(0) "" ["pinged"]=> string(0) "" ["post_modified"]=> string(19) "2019-12-07 20:34:47" ["post_modified_gmt"]=> string(19) "2019-12-07 18:34:47" ["post_content_filtered"]=> string(0) "" ["post_parent"]=> int(0) ["guid"]=> string(37) "http://store.im-haadama.co.il/?p=8356" ["menu_order"]=> int(0) ["post_type"]=> string(4) "post" ["post_mime_type"]=> string(0) "" ["comment_count"]=> string(1) "0" ["filter"]=> string(3) "raw" } [1]=> object(WP_Post)#11873 (24) { ["ID"]=> int(8361) ["post_author"]=> string(1) "1" ["post_date"]=> string(19) "2019-12-07 20:12:31" ["post_date_gmt"]=> string(19) "2019-12-07 18:12:31" ["post_content"]=> string(102) "מקורות מידע: ויקיפדיה" ["post_title"]=> string(61) "Astragalus membranaceus - קדד קרומי, אסטרגלוס" ["post_excerpt"]=> string(0) "" ["post_status"]=> string(7) "publish" ["comment_status"]=> string(4) "open" ["ping_status"]=> string(4) "open" ["post_password"]=> string(0) "" ["post_name"]=> string(122) "astragalus-membranaceus-%d7%a7%d7%93%d7%93-%d7%a7%d7%a8%d7%95%d7%9e%d7%99-%d7%90%d7%a1%d7%98%d7%a8%d7%92%d7%9c%d7%95%d7%a1" ["to_ping"]=> string(0) "" ["pinged"]=> string(0) "" ["post_modified"]=> string(19) "2019-12-07 20:12:50" ["post_modified_gmt"]=> string(19) "2019-12-07 18:12:50" ["post_content_filtered"]=> string(0) "" ["post_parent"]=> int(0) ["guid"]=> string(37) "http://store.im-haadama.co.il/?p=8361" ["menu_order"]=> int(0) ["post_type"]=> string(4) "post" ["post_mime_type"]=> string(0) "" ["comment_count"]=> string(1) "0" ["filter"]=> string(3) "raw" } [2]=> object(WP_Post)#11874 (24) { ["ID"]=> int(8359) ["post_author"]=> string(1) "1" ["post_date"]=> string(19) "2019-12-07 20:10:02" ["post_date_gmt"]=> string(19) "2019-12-07 18:10:02" ["post_content"]=> string(107) "מידע: ויקיפדיה" ["post_title"]=> string(70) "Ganoderma lucidum - פטריית ריישי, בהוקית מבריקה" ["post_excerpt"]=> string(0) "" ["post_status"]=> string(7) "publish" ["comment_status"]=> string(4) "open" ["ping_status"]=> string(4) "open" ["post_password"]=> string(0) "" ["post_name"]=> string(159) "ganoderma-lucidum-%d7%a4%d7%98%d7%a8%d7%99%d7%99%d7%aa-%d7%a8%d7%99%d7%99%d7%a9%d7%99-%d7%91%d7%94%d7%95%d7%a7%d7%99%d7%aa-%d7%9e%d7%91%d7%a8%d7%99%d7%a7%d7%94" ["to_ping"]=> string(0) "" ["pinged"]=> string(0) "" ["post_modified"]=> string(19) "2019-12-07 20:10:02" ["post_modified_gmt"]=> string(19) "2019-12-07 18:10:02" ["post_content_filtered"]=> string(0) "" ["post_parent"]=> int(0) ["guid"]=> string(37) "http://store.im-haadama.co.il/?p=8359" ["menu_order"]=> int(0) ["post_type"]=> string(4) "post" ["post_mime_type"]=> string(0) "" ["comment_count"]=> string(1) "0" ["filter"]=> string(3) "raw" } } ["post_count"]=> int(3) ["current_post"]=> int(0) ["in_the_loop"]=> bool(true) ["post"]=> object(WP_Post)#11870 (24) { ["ID"]=> int(8356) ["post_author"]=> string(1) "1" ["post_date"]=> string(19) "2019-12-07 20:05:18" ["post_date_gmt"]=> string(19) "2019-12-07 18:05:18" ["post_content"]=> string(140) "מקורות מידע על אלרגיה: נטורופדיה [med_plants]" ["post_title"]=> string(12) "אלרגיה" ["post_excerpt"]=> string(0) "" ["post_status"]=> string(7) "private" ["comment_status"]=> string(4) "open" ["ping_status"]=> string(4) "open" ["post_password"]=> string(0) "" ["post_name"]=> string(36) "%d7%90%d7%9c%d7%a8%d7%92%d7%99%d7%94" ["to_ping"]=> string(0) "" ["pinged"]=> string(0) "" ["post_modified"]=> string(19) "2019-12-07 20:34:47" ["post_modified_gmt"]=> string(19) "2019-12-07 18:34:47" ["post_content_filtered"]=> string(0) "" ["post_parent"]=> int(0) ["guid"]=> string(37) "http://store.im-haadama.co.il/?p=8356" ["menu_order"]=> int(0) ["post_type"]=> string(4) "post" ["post_mime_type"]=> string(0) "" ["comment_count"]=> string(1) "0" ["filter"]=> string(3) "raw" } ["comment_count"]=> int(0) ["current_comment"]=> int(-1) ["found_posts"]=> string(1) "3" ["max_num_pages"]=> float(1) ["max_num_comment_pages"]=> int(0) ["is_single"]=> bool(false) ["is_preview"]=> bool(false) ["is_page"]=> bool(false) ["is_archive"]=> bool(true) ["is_date"]=> bool(false) ["is_year"]=> bool(false) ["is_month"]=> bool(false) ["is_day"]=> bool(false) ["is_time"]=> bool(false) ["is_author"]=> bool(false) ["is_category"]=> bool(true) ["is_tag"]=> bool(false) ["is_tax"]=> bool(false) ["is_search"]=> bool(false) ["is_feed"]=> bool(false) ["is_comment_feed"]=> bool(false) ["is_trackback"]=> bool(false) ["is_home"]=> bool(false) ["is_privacy_policy"]=> bool(false) ["is_404"]=> bool(false) ["is_embed"]=> bool(false) ["is_paged"]=> bool(false) ["is_admin"]=> bool(false) ["is_attachment"]=> bool(false) ["is_singular"]=> bool(false) ["is_robots"]=> bool(false) ["is_posts_page"]=> bool(false) ["is_post_type_archive"]=> bool(false) ["query_vars_hash":"WP_Query":private]=> string(32) "5f196dc0ea0d078345e128ac389805fb" ["query_vars_changed":"WP_Query":private]=> bool(false) ["thumbnails_cached"]=> bool(false) ["stopwords":"WP_Query":private]=> NULL ["compat_fields":"WP_Query":private]=> array(2) { [0]=> string(15) "query_vars_hash" [1]=> string(18) "query_vars_changed" } ["compat_methods":"WP_Query":private]=> array(2) { [0]=> string(16) "init_query_flags" [1]=> string(15) "parse_tax_query" } }