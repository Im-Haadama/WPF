<?php


class WPF_Organization {
	static function init() {
		add_action('init', __CLASS__ . "::register", 0);
	}

	static function register()
	{
		// Add new taxonomy, make it hierarchical like categories
		// first do the translations part for GUI
		$labels = array(
			'name' => _x( 'Companies', 'company_taxonomy' ),
			'singular_name' => _x( 'Topic', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search workers' ),
			'all_items' => __( 'All Topics' ),
			'parent_item' => __( 'Parent Topic' ),
			'parent_item_colon' => __( 'Parent Topic:' ),
			'edit_item' => __( 'Edit Topic' ),
			'update_item' => __( 'Update Topic' ),
			'add_new_item' => __( 'Add New Topic' ),
			'new_item_name' => __( 'New Topic Name' ),
			'menu_name' => __( 'Topics' ),
		);

		register_taxonomy('company_taxonomy',array('post'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'company' ),
		));

	}
}
