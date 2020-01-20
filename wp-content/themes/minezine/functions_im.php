<?php

// Theme functions:
// 1) If it is a management page, load management.css - to remove unneeded elements.
// 2) Add nav to office.
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/11/16
 * Time: 18:22
 */

function add_stylesheet_to_head() {
    if (class_exists("Flavor") and Flavor::isManagementPage()){
    	print '<link rel="stylesheet" type="text/css" href="' .  get_template_directory_uri() . '/css/management.css'.  '">'; // Hides logo, search and white area contains them.
    }
}

add_action( 'wp_head', 'add_stylesheet_to_head' );


add_filter( 'wp_get_nav_menu_items', 'custom_nav_menu_items', 20, 2 );
function wpf_custom_nav_menu_item( $title, $url, $order, $parent = 0 ){
	$item = new stdClass();
	$item->ID = 1000000 + $order + $parent;
	$item->db_id = $item->ID;
	$item->title = $title;
	$item->url = $url;
	$item->menu_order = $order;
	$item->menu_item_parent = $parent;
	$item->type = '';
	$item->object = '';
	$item->object_id = '';
	$item->classes = array();
	$item->target = '';
	$item->attr_title = '';
	$item->description = '';
	$item->xfn = '';
	$item->status = '';
	return $item;
}

function custom_nav_menu_items( $items, $menu ){
//	print "ms=" . $menu->slug . "<br/>";
	// only add item to a specific menu
	if ( $menu->slug == 'info_site' || $menu->slug == 'main' ){
		// only add profile link if user is logged in, and user is a staff member.
//	add_filter( 'wp_nav_menu_items', 'wpf_nav', 10, 2 );
		if ( get_current_user_id() ){
			$current_user = wp_get_current_user();
				if (in_array('staff', $current_user->roles))
					$items[] = wpf_custom_nav_menu_item( 'Office', "/focus_main", 0 );
		}
	}

	return $items;
}