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


	function init($loader) {
		$loader->AddAction('init', $this, "register", 0);
		$loader->AddAction("show_edit_worker", $this, 'show_edit_worker');
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( WPF_Flavor::getPost() );
		}

		return self::$_instance;
	}

	function register()
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

	function show_edit_worker() {
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
		$row_id = SqlQuerySingleScalar("select id from {$prefix}working_rates where user_id = $worker_id");
		$result               = Core_Html::GuiHeader( 1, "editing worker info" );
		$args                 = self::Args();
		$args["edit"]         = false;
		$args["selectors"]    = array( "project_id" => "WPF_Organization::gui_select_project" );
		$args["query"]        = "user_id=" . $worker_id . " and is_active = 1";
		$args["add_checkbox"] = true;
		$args["links"]        = array( "id" => AddToUrl( "operation", "show_edit_worker_project" ) );

		$result .= Core_HTML::GuiRowContent( "working_rates", $row_id, $args );

		$result .= Core_Html::GuiHeader(2, "projects");

		$w = new Org_Worker($worker_id);
		$result .= Core_Html::gui_table_args($w->GetAllProjects(true), null, $args);
		$args['worker_id'] = get_user_id();
		$args["edit"] = true;
		$result .= self::gui_select_project("project", "", $args);
		$result .= Core_Html::GuiButton("btn_add", "Add", "worker_add_project('" . self::getPost() . "', $worker_id)");

		print $result;
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

	static function gui_select_project( $id, $project_id, $args ) {
		$edit    = GetArg( $args, "edit", true );
		$new_row = GetArg( $args, "new_row", false );

		if ( ! $edit ) {
			if ( $project_id ) {
				$project = new Org_Project( $project_id );

				return $project->GetName();
			}

			return "no project selected";
		}

		// Filter by worker if supplied.
		$user_id = GetArg( $args, "worker_id" );
		if ( ! $user_id ) {
			throw new Exception( __FUNCTION__ . ": No user " . $user_id );
		}

		$result = "";
		$user   = new Org_Worker( $user_id );

		$form_table = GetArg( $args, "form_table", null );
		$events     = GetArg( $args, "events", null );

		$projects_list = $user->GetAllProjects( "is_active = 1", array( "id", "project_name" ) );
		if ( $projects_list ) {
			$args["values"] = $projects_list;
			$args["id_key"] = "id";
			$args["name"]   = "project_name";
			$result         .= Core_Html::GuiSelect( $id, $project_id, $args );

			// $result .= Core_Html::gui_select( $id, "project_name", $projects_list, $events, $project_id, "project_id" );
			if ( $form_table and $new_row ) { // die(__FUNCTION__ . ":" . " missing form_table");
				$result .= Core_Html::GuiButton( "add_new_project", "New Project", array(
					"action" => "add_element('projects', '" . $form_table . "', '" . GetUrl() . "', 'project_id')",
					"New Project"
				) );
			}
		} else {
			$result .= "No Projects";
		}

		return $result;
	}

}
