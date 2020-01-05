<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/11/16
 * Time: 18:22
 */


function add_stylesheet_to_head() {
//    require_once( ABSPATH . 'wp-content/plugins/fresh/includes/core/fund.php' );

    if (class_exists("Flavor") and Flavor::isManagementPage()){
    	print '<link rel="stylesheet" type="text/css" href="' .  get_template_directory_uri() . '/css/management.css'.  '">'; // Hides logo, search and white area contains them.
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
require_once( ROOT_DIR . '/core/data/sql.php' );
require_once( ROOT_DIR . '/core/wp.php' );
require_once(ROOT_DIR . '/fresh/pricing.php' );
require_once( ROOT_DIR . '/core/gui/inputs.php' );

