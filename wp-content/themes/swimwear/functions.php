<?php
/**
 * Swimwear functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * When using a child theme you can override certain functions (those wrapped
 * in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before
 * the parent theme's file, so the child theme functions would be used.
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @link https://codex.wordpress.org/Child_Themes
 *
 * Functions that are not pluggable (not wrapped in function_exists()) are
 * instead attached to a filter or action hook.
 *
 * For more information on hooks, actions, and filters,
 * @link https://codex.wordpress.org/Plugin_API
 *
 * @package WpOpal
 * @subpackage Swimwear
 * @since Swimwear 1.0
 */
define( 'SWIMWEAR_THEME_VERSION', '1.0' );

/**
 * Set up the content width value based on the theme's design.
 *
 * @see swimwear_fnc_content_width()
 *
 * @since Swimwear 1.0
 */
if ( ! isset( $content_width ) ) {
	$content_width = 474;
}

function swimwear_fnc_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'swimwear_fnc_content_width', 810 );
}
add_action( 'after_setup_theme', 'swimwear_fnc_content_width', 0 );



if ( ! function_exists( 'swimwear_fnc_setup' ) ) :
/**
 * Swimwear setup.
 *
 * Set up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support post thumbnails.
 *
 * @since Swimwear 1.0
 */
function swimwear_fnc_setup() {

	/*
	 * Make Swimwear available for translation.
	 *
	 * Translations can be added to the /languages/ directory.
	 * If you're building a theme based on Swimwear, use a find and
	 * replace to change 'swimwear' to the name of your theme in all
	 * template files.
	 */
	load_theme_textdomain( 'swimwear', get_template_directory() . '/languages' );

	// This theme styles the visual editor to resemble the theme style.
 
	add_editor_style();
	// Add RSS feed links to <head> for posts and comments.
	add_theme_support( 'automatic-feed-links' );

	// Enable support for Post Thumbnails, and declare two sizes.
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 672, 372, true );

	// This theme uses wp_nav_menu() in two locations.
	register_nav_menus( array(
		'primary'   => esc_html__( 'Main menu', 'swimwear' ),
		'secondary' => esc_html__( 'Menu in left sidebar', 'swimwear' ),
		'topmenu'	=> esc_html__( 'Topbar Menu', 'swimwear' )
	) );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
	) );

	/*
	 * Enable support for Post Formats.
	 * See https://codex.wordpress.org/Post_Formats
	 */
	add_theme_support( 'post-formats', array(
		'aside', 'image', 'video', 'audio', 'quote', 'link', 'gallery',
	) );

	// This theme allows users to set a custom background.
	add_theme_support( 'custom-background', apply_filters( 'swimwear_fnc_custom_background_args', array(
		'default-color' => 'f5f5f5',
	) ) );

	// add support for display browser title
	add_theme_support( 'title-tag' );
	// This theme uses its own gallery styles.
	add_filter( 'use_default_gallery_style', '__return_false' );
	

    add_image_size('avatar', 90, 90, true);

}
endif; // swimwear_fnc_setup
add_action( 'after_setup_theme', 'swimwear_fnc_setup' );

/**
 * get theme prefix which will use for own theme setting as page config, customizer
 *
 * @return string text_domain
 */
function swimwear_themer_get_theme_prefix(){
	return 'swimwear_';
}

add_filter( 'wpopal_themer_get_theme_prefix', 'swimwear_themer_get_theme_prefix' );

/**
 * Get Theme Option Value.
 * @param String $name : name of prameters 
 */
function swimwear_fnc_theme_options($name, $default = false) {
  
    // get the meta from the database
    $options = ( get_option( 'wpopal_theme_options' ) ) ? get_option( 'wpopal_theme_options' ) : null;
  
    // return the option if it exists
    if ( isset( $options[$name] ) ) {
        return apply_filters( 'wpopal_theme_options_$name', $options[ $name ] );
    }
    if( get_option( $name ) ){
        return get_option( $name );
    }
    // return default if nothing else
    return apply_filters( 'wpopal_theme_options_$name', $default );
}

/**
 * Require function for including 3rd plugins
 *
 */

if (is_admin()) {
	
	/**
	 * Get plugin icon
	 */
	function swimwear_get_plugin_icon_image($slug)
	{

	    switch ($slug) {
	        case 'revslider':
	            $img = get_template_directory_uri() . '/assets/plugins/logo-rv.png';
	            break;
	        case 'yith-woocommerce-compare':
	        case 'yith-woocommerce-wishlist':
	        case 'yith-woocommerce-quick-view' :
	            $img = 'https://ps.w.org/' . $slug . '/assets/icon-128x128.jpg';
	            break;
	        default:
	            $img = 'https://ps.w.org/' . $slug . '/assets/icon-128x128.png';
	            break;
	    }

	    return '<img src="' . $img . '"/>';
	}

	require get_template_directory() . '/inc/admin/class-menu.php';

	/**
     * Load include plugins using for this project
     */
    require get_template_directory() . '/inc/tgm/class-tgm-plugin-activation.php';

    
	/**
	 * Require function for including 3rd plugins
	 *
	 */
	add_action('tgmpa_register', 'swimwear_fnc_get_load_plugins');

	function swimwear_fnc_get_load_plugins(){

		$plugins[] =(array(
			'name'                     => esc_html__('MetaBox', 'swimwear'),// The plugin name
		    'slug'                     => 'meta-box', // The plugin slug (typically the folder name)
		    'required'                 => true, // If false, the plugin is only 'recommended' instead of required
		));

		$plugins[] =(array(
			'name'                     => esc_html__('WooCommerce','swimwear'), // The plugin name
		    'slug'                     => 'woocommerce', // The plugin slug (typically the folder name)
		    'required'                 => true, // If false, the plugin is only 'recommended' instead of required
		));


		$plugins[] =(array(
			'name'                     => esc_html__('MailChimp', 'swimwear'),// The plugin name
		    'slug'                     => 'mailchimp-for-wp', // The plugin slug (typically the folder name)
		    'required'                 =>  true
		));

		$plugins[] =(array(
			'name'                     => esc_html__('Contact Form 7','swimwear'), // The plugin name
		    'slug'                     => 'contact-form-7', // The plugin slug (typically the folder name)
		    'required'                 => true, // If false, the plugin is only 'recommended' instead of required
		));

		$plugins[] =(array(
			'name'                     => esc_html__('King Composer - Page Builder', 'swimwear'),// The plugin name
		    'slug'                     => 'kingcomposer', // The plugin slug (typically the folder name)
		    'required'                 => true,
		
		));

		$plugins[] =(array(
			'name'                     => esc_html__('Revolution Slider', 'swimwear'),// The plugin name
	        'slug'                     => 'revslider', // The plugin slug (typically the folder name)
	        'required'                 => true ,
	        'source'   					=> 'http://source.wpopal.com/plugins/new/revslider.zip', // The plugin source
		));


		$plugins[] =(array(
			'name'                     => esc_html__('Wpopal Themer For Themes', 'swimwear'),// The plugin name
	        'slug'                     => 'wpopal-themer', // The plugin slug (typically the folder name)
	        'required'                 => true ,
	        'source'				   => esc_url( 'http://www.wpopal.com/_opalfw_/wpopal-themer.zip')
		));

		$plugins[] =(array(
			'name'                     => esc_html__('YITH WooCommerce Wishlist', 'swimwear'),// The plugin name
		    'slug'                     => 'yith-woocommerce-wishlist', // The plugin slug (typically the folder name)
		    'required'                 =>  true
		));

		tgmpa( $plugins );
	}
}
/**
 * Register three Swimwear widget areas.
 *
 * @since Swimwear 1.0
 */
function swimwear_fnc_registart_widgets_sidebars() {
	 
	register_sidebar( 
	array(
		'name'          => esc_html__( 'Sidebar Default', 'swimwear' ),
		'id'            => 'sidebar-default',
		'description'   => esc_html__( 'Appears on posts and pages in the sidebar.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget  clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));

	register_sidebar( 
	array(
		'name'          => esc_html__( 'Newsletter' , 'swimwear'),
		'id'            => 'newsletter',
		'description'   => esc_html__( 'Appears on pages in the sidebar.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));
		

	register_sidebar( 
	array(
		'name'          => esc_html__( 'Left Sidebar' , 'swimwear'),
		'id'            => 'sidebar-left',
		'description'   => esc_html__( 'Appears on posts and pages in the sidebar.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget widget-style  clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));
	register_sidebar(
	array(
		'name'          => esc_html__( 'Right Sidebar' , 'swimwear'),
		'id'            => 'sidebar-right',
		'description'   => esc_html__( 'Appears on posts and pages in the sidebar.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget widget-style clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));

	register_sidebar( 
	array(
		'name'          => esc_html__( 'Blog Left Sidebar' , 'swimwear'),
		'id'            => 'blog-sidebar-left',
		'description'   => esc_html__( 'Appears on posts and pages in the sidebar.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget widget-style clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));

	register_sidebar( 
	array(
		'name'          => esc_html__( 'Blog Right Sidebar', 'swimwear'),
		'id'            => 'blog-sidebar-right',
		'description'   => esc_html__( 'Appears on posts and pages in the sidebar.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget widget-style clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));	
	register_sidebar( 
	array(
		'name'          => esc_html__( 'Footer 1' , 'swimwear'),
		'id'            => 'footer-1',
		'description'   => esc_html__( 'Appears in the footer section of the site.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));
	register_sidebar( 
	array(
		'name'          => esc_html__( 'Footer 2' , 'swimwear'),
		'id'            => 'footer-2',
		'description'   => esc_html__( 'Appears in the footer section of the site.', 'swimwear'),
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	));
		
	//Add Topbar custom sidebar
	register_sidebar(
	array(
		'name'          => esc_html__( 'Topbar custom', 'swimwear'),
		'id'            => 'topbar-custom',
		'description'   => esc_html__( 'Appears in the header custom section of the site.', 'swimwear'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	));
	register_sidebar(
	array(
		'name'          => esc_html__( 'Header social', 'swimwear'),
		'id'            => 'header-social',
		'description'   => esc_html__( 'Appears in the header social section of the site.', 'swimwear'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	));
	
	register_sidebar(
	array(
		'name'          => esc_html__( 'Header support', 'swimwear'),
		'id'            => 'header-support',
		'description'   => esc_html__( 'Appears in the header support section of the site.', 'swimwear'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	));
	register_sidebar(
	array(
		'name'          => esc_html__( 'Copyright link', 'swimwear'),
		'id'            => 'copyright-link',
		'description'   => esc_html__( 'Appears in the header social section of the site.', 'swimwear'),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	));

}
add_action( 'widgets_init', 'swimwear_fnc_registart_widgets_sidebars' );

function consultsolutions_fnc_disable_srcset( $sources ) {
return false;
}
add_filter( 'wp_calculate_image_srcset', 'consultsolutions_fnc_disable_srcset' );
/**
 * Register Lato Google font for Swimwear.
 *
 * @since Swimwear 1.0
 *
 * @return string
 */
function swimwear_fnc_font_url() {
	 
	$fonts_url = '';
 
    /* Translators: If there are characters in your language that are not
    * supported by Lora, translate this to 'off'. Do not translate
    * into your own language.
    */
    $Overpass = _x( 'on', 'Overpass font: on or off', 'swimwear' );
 
    /* Translators: If there are characters in your language that are not
    * supported by Open Sans, translate this to 'off'. Do not translate
    * into your own language.
    */
     
 	$montserrat = _x( 'on', 'Montserrat font: on or off', 'swimwear' );
 	$oswald = _x( 'on', 'Oswald font: on or off', 'swimwear' );

    if ( 'off' !== $Overpass || 'off' !== $open_sans || 'off' !==$montserrat ) {
        $font_families = array();
 
        if ( 'off' !== $Overpass ) {
            $font_families[] = 'Overpass:300,400,600,700,900';
        }
 		if ( 'off' !== $montserrat ) {
            $font_families[] = 'Montserrat:400,500,700,900';
        }
        if ( 'off' !== $oswald ) {
            $font_families[] = 'Oswald:300';
        }
        $query_args = array(
            'family' => ( implode( '|', $font_families ) ),
            'subset' => urlencode( 'latin,latin-ext' ),
        );
 		
 		 
 		$protocol = is_ssl() ? 'https:' : 'http:';
        $fonts_url = add_query_arg( $query_args, $protocol .'//fonts.googleapis.com/css' );
    }
 
    return esc_url_raw( $fonts_url );
}

/**
 * Enqueue scripts and styles for the front end.
 *
 * @since Swimwear 1.0
 */
function swimwear_fnc_scripts() {

//	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	// $file = 'https://hb-swimwear.com/wp-content/plugins/kingcomposer/assets/css/fonts/fontawesome-webfont.woff';
//	$file = 'http://demo1.wpopal.com/swimwear/wp-content/plugins/kingcomposer/assets/css/icons.css?ver=2.6.17';
//	print $file;

	// Agla: load font awesome. http://demo1.wpopal.com/swimwear/wp-content/plugins/kingcomposer/assets/css/fonts/fontawesome-webfont.woff
//	wp_enqueue_style( 'fontawesome-webfont', $file, array(), null );

	// Add Lato font, used in the main stylesheet.
	wp_enqueue_style( 'swimwear-open-sans', swimwear_fnc_font_url(), array(), null );

	// Add Genericons font, used in the main stylesheet.
	wp_enqueue_style( 'swimwear-fa', get_template_directory_uri() . '/css/font-awesome.min.css', array(), '3.0.3' );

	if(isset($_GET['opal-skin']) && $_GET['opal-skin']) {
		$currentSkin = $_GET['opal-skin'];
	}else{
		$currentSkin = str_replace( '.css','', swimwear_fnc_theme_options('skin','default') );
	}
	if( is_rtl() ){
		if( !empty($currentSkin) && $currentSkin != 'default' ){ 
			wp_enqueue_style( 'swimwear-'.$currentSkin.'-style', get_template_directory_uri() . '/css/skins/rtl-'.$currentSkin.'/style.css' );
		}else {
			// Load our main stylesheet.
			wp_enqueue_style( 'swimwear-style', get_template_directory_uri() . '/css/rtl-style.css' );
		}
	}
	else {
		if( !empty($currentSkin) && $currentSkin != 'default' ){ 
			wp_enqueue_style( 'swimwear-'.$currentSkin.'-style', get_template_directory_uri() . '/css/skins/'.$currentSkin.'/style.css' );
		}else {
			// Load our main stylesheet.
			wp_enqueue_style( 'swimwear-style', get_template_directory_uri() . '/css/style.css' );
		}	
	}	

	// Load the Internet Explorer specific stylesheet.
	wp_enqueue_style( 'swimwear-ie', get_template_directory_uri() . '/css/ie.css', array( 'swimwear-style' ), '20131205' );
	wp_style_add_data( 'swimwear-ie', 'conditional', 'lt IE 9' );


	wp_enqueue_script( 'bootstrap-min', get_template_directory_uri() . '/js/bootstrap.min.js', array( 'jquery' ), '20130402' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	if ( is_singular() && wp_attachment_is_image() ) {
		wp_enqueue_script( 'keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array( 'jquery' ), '20130402' );
	}

	
	wp_enqueue_script( 'owl-carousel', get_template_directory_uri() . '/js/owl-carousel/owl.carousel.js', array( 'jquery' ), '20150315', true );
	wp_enqueue_script( 'prettyphoto-js',	get_template_directory_uri().'/js/jquery.prettyPhoto.js');
	wp_enqueue_style ( 'prettyPhoto', get_template_directory_uri() . '/css/prettyPhoto.css');
	
	wp_enqueue_script ( 'allcountries-js', get_template_directory_uri() . '/js/allcountries.js', array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script ( 'swimwear-functions-js', get_template_directory_uri() . '/js/functions.js', array( 'jquery' ), '1.0.1.0', true );
	wp_localize_script( 'swimwear-functions-js', 'swimwearAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

}
add_action( 'wp_enqueue_scripts', 'swimwear_fnc_scripts' );


//Update logo wordpress

function swimwear_fnc_setup_logo(){
    add_theme_support('custom-logo');
}
add_action('after_setup_theme', 'swimwear_fnc_setup_logo');


/**
 * Enqueue Google fonts style to admin screen for custom header display.
 *
 * @since Swimwear 1.0
 */
function swimwear_fnc_admin_fonts() {
	wp_enqueue_style( 'swimwear-lato', swimwear_fnc_font_url(), array(), null );
}
add_action( 'admin_print_scripts-appearance_page_custom-header', 'swimwear_fnc_admin_fonts' );

require_once(  get_template_directory() . '/inc/custom-header.php' );
require_once(  get_template_directory() . '/inc/customizer.php' );
require_once(  get_template_directory() . '/inc/customizer-config.php' );
require_once(  get_template_directory() . '/inc/function-post.php' );
require_once(  get_template_directory() . '/inc/functions-import.php' );
require_once(  get_template_directory() . '/inc/template-tags.php' );
require_once(  get_template_directory() . '/inc/template.php' );
require_once(  get_template_directory() . '/inc/customizer.php' );

/**
 * Check and load to support visual composer
 */
if(  in_array( 'wpopal-themer/wpopal-themer.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ){ 

	if(class_exists('Wpopal_User_Account')){
 		new Wpopal_User_Account();
 	}
}

/**
 * Check and load to support kingcomposer
 */
if(  in_array( 'kingcomposer/kingcomposer.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ){ 
	require_once(  get_template_directory() . '/inc/vendors/kingcomposer/functions.php' );
}

if(  in_array( 'timetable/timetable.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ){ 
	require_once(  get_template_directory() . '/inc/vendors/timetable/timetable.php' );
}

/**
 * Check to support woocommerce
 */
if( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
	add_theme_support( 'woocommerce');
	require_once(  get_template_directory() . '/inc/vendors/woocommerce/functions.php' );
	require_once( get_template_directory() . '/inc/vendors/woocommerce/single-functions.php' );
}

if(  in_array( 'opalservice/opalservice.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ){ 
	require_once(  get_template_directory() . '/inc/customizer/opalservice.php' );
}
