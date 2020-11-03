<?php


class WPF_Organization {
	private static $_instance;
	private $post_file;

	/**
	 * WPF_Organization constructor.
	 *
	 * @param $post_file
	 */
	public function __construct( $post_file ) {
		$this->post_file = $post_file;
	}


	static function init() {
		AddAction('init', __CLASS__ . "::register", 0);
		AddAction("show_edit_worker", __CLASS__ . '::show_edit_worker');
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( Flavor::getPost() );
		}

		return self::$_instance;
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
			'menu_name' => __( 'Companies' ),
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

	static function show_edit_worker() {
		$prefix = GetTablePrefix();
		// Get worker info by id or worker_project link.
//		$row_id    = GetParam( "row_id", false );
		$worker_id = GetParam( "worker_id", false );
		if (  ! $worker_id ) {
			die ( "supply worker_id" );
		}
//		if ( $row_id ) {
//			$worker_id = sql_query_single_scalar( "select user_id from im_working where id = $row_id" );
//		}
		$row_id = SqlQuerySingleScalar("select id from {$prefix}working where user_id = $worker_id");
		$result               = Core_Html::GuiHeader( 1, "editing worker info" );
		$args                 = self::Args();
		$args["edit"]         = false;
		$args["selectors"]    = array( "project_id" => "Focus_Tasks::gui_select_project" );
		$args["query"]        = "user_id=" . $worker_id . " and is_active = 1";
		$args["add_checkbox"] = true;
		$args["links"]        = array( "id" => AddToUrl( "operation", "show_edit_worker_project" ) );

		$result .= Core_HTML::GuiRowContent( "working_rates", $row_id, $args );

		return $result;
	}

	static function Args()
	{
		return array("page" => GetParam("page", false, -1),
		             "post_file" => self::getPost());
	}

	static function getPost()
	{
		return self::instance()->post_file;
	}
}
