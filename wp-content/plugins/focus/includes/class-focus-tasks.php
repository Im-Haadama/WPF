<?php

require_once( FOCUS_INCLUDES . 'gui.php' );

class Focus_Tasks {
	private $post_file;
	private $version;
	protected static $_instance = null;
	protected $nav_menu_name;

	/**
	 * Focus_Tasks constructor.
	 *
	 * @param $post_file
	 */
	public function __construct( $post_file ) {
//		debug_print_backtrace();

		$this->post_file     = $post_file;
		$this->version       = "1.0";
		$this->nav_menu_name = null;
	}

	public static function instance( $post = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $post );
		}

		return self::$_instance;
	}

	public static function focus_operation() {
		$operation = GetParam( "operation", false, "focus_main" );
		if ( get_user_id( true ) ) {
			print Focus::instance()->handle_operation( $operation );
		}
	}

	public function enqueue_scripts() {
		print "<script>let focus_post_url = \"" . self::getPost() . "\"; </script>";

		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

		$file = FOCUS_INCLUDES_URL . 'focus.js';
		wp_enqueue_script( 'focus', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gem.js';
		wp_enqueue_script( 'gem', $file, null, $this->version, false );

	}

	static function show_main_wrapper() {
		$user_id = get_user_id();
		if ( ! $user_id ) {
			return "unauth";
		}

		$operation  = GetParam( "operation", false, "default" );
		$table_name = substr( $operation, 8 );

		// Todo: move all processing to filter.
		$id = GetParam("id", false, null);
		$result = apply_filters( $operation, $operation, $id, self::Args( $table_name ) );
		if ( $result != $operation ) {
			return $result;
		}

		// If no filter yet, handle the old way.
		return self::handle_focus_show( $operation, $user_id );
	}

	static function show_repeating_task() {
		return "repeating";
	}

	static function show_project_tasks() {
		return __FUNCTION__;
	}

	static function gui_select_worker( $id, $selected, $args ) {
		// $events = GetArg($args, "events", null);
		$edit      = GetArg( $args, "edit", true );
		$worker    = new Org_Worker( get_user_id() );
		$companies = $worker->GetCompanies();

		$debug            = false; // (get_user_id() == 1);
		$args["debug"]    = $debug;
		$args["name"]     = "client_displayname(user_id)";
		$args["where"]    = "where is_active=1 and company_id in (" . CommaImplode( $companies ) . ")";
		$args["id_key"]   = "user_id";
		$args["selected"] = $selected;
		$args["query"]    = ( isset( $args["query_team"] ) ? $args["query_worker"] : null );

		if ( $edit ) {
			$gui = Core_Html::GuiSelectTable( $id, "im_working", $args );

			return $gui;
		} else {
			return ( $selected > 0 ) ? sql_query_single_scalar( "select client_displayname(user_id) from im_working where user_id = " . $selected ) :
				"";
		}
	}

	static function gui_select_project( $id, $project_id, $args ) {
		$edit    = GetArg( $args, "edit", true );
		$new_row = GetArg( $args, "new_row", false );

		if ( ! $edit ) {
			if ($project_id) {
				$project = new Org_Project($project_id);
				return $project->GetName();
			}
			return "no project selected";
		}

		// Filter by worker if supplied.
		$user_id = GetArg( $args, "worker_id", get_user_id() );
		if ( ! $user_id ) {
			throw new Exception( __FUNCTION__ . ": No user " . $user_id );
		}

		$result = "";
		$user = new Org_Worker($user_id);

		$form_table = GetArg( $args, "form_table", null );
		$events     = GetArg( $args, "events", null );

		$projects      = $user->AllProjects( );
		if ($projects) {
			$projects_list = [];
			foreach ( $projects as $p_id ) {
				$p = new Org_Project($p_id);
				$projects_list[] = array( "project_id" => $p_id, "project_name" => $p->getName() );
			}

			$args["values"] = $projects_list;
			$args["id_key"] = "project_id";
			$args["name"] = "project_name";
			$result .= Core_Html::GuiSelect($id, $project_id, $args);

			// $result .= Core_Html::gui_select( $id, "project_name", $projects_list, $events, $project_id, "project_id" );
			if ( $form_table and $new_row ) { // die(__FUNCTION__ . ":" . " missing form_table");
				$result .= Core_Html::GuiButton( "add_new_project", "New Project", array(
					"action" => "add_element('project', '" . $form_table . "', '" . GetUrl() . "')",
					"New Project"
				) );
			}
		} else
			$result .= "No Projects";

		return $result;
	}

	static function Args( $table_name = null, $action = null ) {
		$ignore_list = [];
		$args        = array(
			"page"      => GetParam( "page", false, - 1 ),
			"post_file" => self::getPost()
		);
		if ( GetParam( "non_active", false, false ) ) {
			$args["non_active"] = 1;
		}
		foreach ( $_GET as $param => $value ) {
			if ( ! in_array( $param, $ignore_list ) ) {
				$args[ $param ] = $value;
			}
		}

		if ( $table_name )
			switch ( $table_name ) {
				case "im_task_templates":
					$args["selectors"]     = array(
						"project_id"  => "Focus_Tasks::gui_select_project",
						"owner"       => "Focus_Tasks::gui_select_worker",
						"creator"     => "Focus_Tasks::gui_select_worker",
						"repeat_freq" => "gui_select_repeat_time",
						"team"        => "Focus_Tasks::gui_select_team"
					);
					$args["fields"]        = array(
						"id",
						"task_description",
						"project_id",
						"priority",
						"team",
						"repeat_freq",
						"repeat_freq_numbers",
						"working_hours",
						"condition_query",
						"task_url",
						"template_last_task(id)"
					);
					$args["header_fields"] = array(
						"task_description" => "Task description",
						"project_id"       => "Project",
						"priority"         => "Priority",
						"team"                => "Team",
						"repeat_freq"         => "Repeat Frequency",
						"repeat_freq_numbers" => "Repeat times",
						"working_hours"       => "Working hours",
						"Task site"
					);
					break;

				case "im_tasklist":
					$args["selectors"] = array(
						"project_id" => "Focus_Tasks::gui_select_project",
						"owner"      => "Focus_Tasks::gui_select_worker",
						"creator"    => "Focus_Tasks::gui_select_worker",
						"preq"       => "gui_select_task",
						"mission_id" => "gui_select_mission",
						"team"       => "Focus_Tasks::gui_select_team"
					);

					$args["header_fields"] = array(
						"date"             => "Date",
						"task_description" => "Task description",
						"task_template"    => "Repeating task",
						"project_id"       => "Project",
						"location_name"    => "Location",
						"location_address" => "Address",
						"priority"         => "Priority",
						"preq"             => "Prerequisite",
						"owner"            => "Assigned to",
						"creator"          => "Creator",
						"task_type"        => "Task type",
						"mission_id"       => "Mission",
						"status"           => "Status",
						"started"          => "Started",
						"ended"            => "Ended",
						"team"=>"Team"
					);
					$args["fields"] = array(
						"task_description",
						"team",
						"project_id",
						"priority"
					);


//				$args["fields"] = ;
				case "im_working_teams":
					break;


				case "im_projects":
					$args["hide_cols"] = array("is_active"=>1, "manager"=>1);
					$args["links"]     = array( "ID" => AddToUrl( array( "operation" => "gem_edit_im_projects&id=%s" ) ) );

					break;
			}

		return $args;
	}

	static function ActiveQuery( $args ) {
		if ( GetArg( $args, "non_active", false ) ) {
			return " 1 ";
		}

		return " (isnull(preq) or preq_done(id)) and (date is null or date(date) <= Curdate())"
		       . " and (mission_id is null or mission_id = 0) ";
	}

	static function getPost() {
		return self::instance()->post_file;
	}

	/**
	 * @param $operation
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function handle_focus_show( $operation, $user_id ) {
		$args = self::Args();

		// Actions are performed and return to caller.
		// Page are $result .= and displayed in the end. (to handle the header just once);
		$result = ""; // focus_header($header_args);

		switch ( $operation ) {
			case "default":
				return self::main( $user_id, $args );

			case "show_settings":
				return self::show_settings( get_user_id() );
			case "edit_organization":
				return edit_organization();
			case "show_worker":
				// $new = GetParam("new", false, null);
				$id                     = GetParam( "id", true );
				$header_args["view_as"] = $id;

				return self::focus_main( $id, $args );
			case "show_repeating_tasks":
			case "show_templates":
				$args["table"] = true;
				$args["new"]   = GetParam( "new", false, 0 );
				$new           = GetParam( "new", false, null );
				$freq          = GetParam( "freq", false, null );
				$args["query"] = "repeat_freq like '%$freq%'";

				return self::show_templates( $args, null, $new );
			case "show_template":
				$id = GetParam( "id", true );

				return self::show_templates( $args, $id );
			case "show_task":
				$id = GetParam( "id", true );
				if ( $id ) {
					return self::show_task( $id );
				}
				break;

			case "show_project":
				$id           = GetParam( "project_id", true );
				$args         = [];
				$args["edit"] = GetParam( "edit", false, false );
				if ( $id ) {
					return self::show_project( $id, $args );
				}
				break;
			case "bad_url":
				$id          = GetParam( "id" );
				$result      .= "Url for task $id is wrong<br/>";
				$template_id = task_template( $id );

				return Core_Html::GuiHyperlink( "Edit template $template_id", self::get_link( "template", $template_id ) );
			case "show_new_project":
				$args              = [];
				$args["next_page"] = GetParam( "next_page", false, null );
				$args["post_file"] = self::getPost();
//			$user_id = get_user_id();
//			$args["user_id"] = $user_id;
//			$args["hide_cols"] = array("user_id" => 1, "company_id" => 1);
//			$args["values"] = array("company_id" => worker_get_companies($user_id)[0]);
				$args["fields"]           = array( "project_name", "project_contact", "project_priority" );
				$args["mandatory_fields"] = array( "project_name" );
				$args["header_fields"]    = array(
					"project_name"     => "Project name",
					"project_contact"  => "Project contact (client)",
					"project_priority" => "Priority"
				);

				return Core_Gem::GemAddRow( "im_projects", "Add a project", $args );

			case "last_entered":
				if ( get_user_id() != 1 ) {
					return false;
				}
				$args                 = array();
				$args["last_entered"] = 1;

				return Focus_Tasks::Taskslist( $args );
			case "show_new_sequence":
				$args = array();
//			$args["selectors"] = $task_selectors;
//			$args["transpose"] = true;
//			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());

				$result         .= Core_Html::gui_header( 1, "New sequence" );
				$result         .= gui_label( "explain", "Select the project of the sequence, the default priority of all sequence tasks. Enter text for the tasks" );
				$args["worker"] = get_user_id();
				$args["edit"]   = true;
				$table_rows     = array(
					array( "project", gui_select_project( "project", null, $args ) ),
					array( "priority", GuiInput( "priority", null, $args ) ),
					array( "task1", GuiInput( "task1" ) ),
					array( "task2", GuiInput( "task2", null, array( "events" => 'onchange="addSequenceTask(2)"' ) ) )
				);
				$result         .= Core_Html::gui_table_args( $table_rows, "sequence_table" );

				// $args["debug"] = true;
				// print NewRow("im_tasklist", $args);
				$result .= Core_Html::GuiButton( "btn_new_sequence", "save_new_sequence()", "Create" );

				return $result;


			case "show_staff": // Teams that I manage
//			$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js", "/core/data/focus.js" ) );
				$result .= Core_Html::gui_header( 1, "Edit staff" );
				$result .= show_staff();

				return $result;

			case "show_edit_projects": // Projects that I manage
//			$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js", "/core/data/focus.js" ) );
				$result            .= Core_Html::gui_header( 1, "Edit projects" );
				$args["worker_id"] = get_user_id();
				$result            .= edit_projects( $args );

				return $result;

			case "show_edit_all_projects": // Projects that I manage
				if ( ! im_user_can( "edit_projects" ) ) {
					die( "no permissions " . __FUNCTION__ );
				}
				$result         .= Core_Html::gui_header( 1, "Edit all projects" );
				$args["global"] = true;
				$result         .= edit_projects( $args );

				return $result;

			case "new_team":
//			$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js", "/core/data/focus.js" ) );
				$result .= Core_Html::gui_header( 1, "Add Team" );
				$args   = array( "selectors" => array( "manager" => "Focus_Tasks::gui_select_worker" ) );
				$result .= NewRow( "im_working_teams", $args );
				$result .= Core_Html::GuiButton( "btn_newteam", "save_new('im_working_teams')", "add" );

				return $result;

			case "remove_from_team":
				$team_id   = GetParam( "id", true );
				$worker_id = GetParam( "user", true );
				team_remove_member( $team_id, $worker_id );
				handle_focus_operation( "show_team", null );

				return $result;

			case "show_team":
				$team_id = GetParam( "team_id", true );

				return self::show_team( $team_id );

			case "show_add_member":
				$team_id = GetParam( "id", true );
				$result  .= Core_Html::gui_header( 1, "Adding member to team" . sql_query_single_scalar( "select team_name from im_working_teams where id = " . $team_id ) );
				$result  .= gui_select_worker( "new_member" );
				$result  .= gui_label( "team_id", $team_id, true );
				$result  .= Core_Html::GuiButton( "btn_add_member", "add_member()", "Add" );

				$result .= "<br/>";
				$result .= Core_Html::GuiHyperlink( "Invite college to your company", AddToUrl( array( "operation" => "show_add_to_company" ) ) );

				return $result;

			case "show_add_to_company":
				$company_id = GetParam( "id", true );
				$result     .= Core_Html::gui_header( 2, "Invite to company" ) . " " . gui_label( "company_id", $company_id );
				$result     .= im_translate( "Enter college email address: " );
				$result     .= Core_Html::gui_table_args( array(
					array( "email", GuiInput( "email", "", $args ) ),
					array( "name", GuiInput( "name", "", $args ) ),
					array( "project", gui_select_project( "project_id", null, $args ) )
				) );
				$result     .= Core_Html::GuiButton( "btn_add_to_company", "add_to_company()", "Add" );

				return $result;

			case "projects":
//				$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js" ) );

				if ( $id = GetParam( "project_id" ) ) {
					$result .= show_project( $id );
				}
				$result .= show_projects( GetUrl(), get_user_id() );

				return $result;

			case "task_types":
				$args          = array();
				$args["title"] = "task types";

				return Core_Gem::GemTable( "im_task_type", $args );

			case "new_company_user":
				$company_id = data_save_new( "im_company" );
				//			$worker_id = worker_get_id(get_user_id());
				$sql = "update im_working set company_id = " . $company_id . " where user_id = " . get_user_id();
				sql_query( $sql );

				return sql_insert_id();

			case "show_teams": // System manager -> edit all teams in the system.
				return self::show_teams();
				break;


			case "show_tasks":
				$query = Core_Data::data_parse_get( "im_tasklist", array( "operation" ) );
				$ids   = Core_Data::data_search( "im_tasklist", $query );

				return self::show_tasks( $ids );

			case "show_edit_company":
				$company_id = GetParam( "company_id", true );
				$page       = GetParam( "page", false, 1 );

				return show_edit_company( $company_id, $page );

			case "new_template":
				return self::show_new_template();

			case "show_add_company_teams":
				return self::show_new_team();

			default:
				return false;
		}
	}

	static function show_new_team() {
		$args              = self::Args();
		$args["selectors"] = array( "manager" => __CLASS__ . "::gui_select_worker" );

		return Core_Gem::GemAddRow( "im_working_teams", "New team", $args );
	}

	static function show_project_wrapper() {
		$new = GetParam( "new" );
		if ( $new ) {
			$project = Focus_Project::create_from_task( $new );

			return $project->getId();
		}

		return self::show_project( get_user_id() );
	}

	static function show_edit_team($page, $team_id, $args ) {
//		$args = self::Args("im_working_teams");
		$args["post_file"] .= "?team_id=" . $team_id;

		$result            = Core_Html::gui_header( 1, "Edit team" );
		$args["selectors"] = array( "manager" => "Focus_Tasks::gui_select_worker" );
		// $args["post_file"] = GetUrl( 1 ) . "?team_id=" . $team_id;
		$result            .= Core_Gem::GemElement( "im_working_teams", $team_id, $args );

		$result          .= Core_Html::gui_header( 2, "Team members" );
		$table           = array();
		$table["header"] = array( "name" );
		$team = new Org_Team($team_id);
		foreach ( $team->AllMembers() as $member ) {
			$table[ $member ]["name"] = get_user_name( $member );
		}

		$args["add_checkbox"] = true;
		$args["edit"] = true;
		$result               .= Core_Gem::GemArray( $table, $args, "team_members" );

		$result .= Core_Html::gui_header( 1, "add member" );
		$result .= gui_select_worker( "new_member", null, $args );
		$result .= Core_Html::GuiButton( "btn_add_member", "add", array("action" => "add_team_member(" . $team_id . ")") );

		return $result;
	}

	static function show_teams() {
		$action_url = "/wp-content/plugins/focus/post.php";
		$result     = "";
		if ( ! im_user_can( "edit_teams" ) ) {
			$result .= "No permissions";
		}
		$args              = [];
		$args["post_file"] = "/wp-content/plugins/focus/post.php";
		$args["selectors"] = array( "manager" => "Focus_Tasks::gui_select_worker" );
		$args["links"]     = array(
			"id" => AddToUrl( array(
				"operation" => "show_edit_team",
				"id"        => "%s"
			) )
		);
		$args["page"]      = GetParam( "page" );
//			$result .=  "url = " . GetUrl() . "<br/>";
//			$result .=  add_to_url(array("operation" => "del_team", "id"=>"%s"));

		$args["actions"] = array(
			array(
				"delete",
				$action_url . "?operation=del_team&id=%s;action_hide_row"
			)
		);
		$result          .= Core_Html::gui_header( 1, "All system teams" );
		$result          .= Core_Gem::GemTable( "im_working_teams", $args );

		unset( $args["actions"] );
		$args["mandatory_fields"] = array( "manager", "team_name" );
		$result                   .= Core_Gem::GemAddRow( "im_working_teams", "Add a team", $args );

		return $result;
	}

	/**
	 * @param $url
	 * @param $owner
	 * @param bool $non_zero
	 *
	 * @return string|null
	 * @throws Exception
	 */
	static function show_projects( $owner, $non_zero = false, $is_active = true ) {
		$links = array();

		$links["id"] = AddToUrl( array(
			"operation" => "show_project",
			"id"        => "%s"
		) ); // add_param_to_url($url, "project_id", "%s");
		$sql         = "select id, project_name, project_priority, project_count(id, " . $owner . ") as open_count " .
		               " from im_projects where 1 ";
		if ( $non_zero ) {
			$sql .= " and project_count(id, " . $owner . ") > 0 ";
		}
		if ( $is_active ) {
			$sql .= " and is_active = 1";
		}
		$sql .= " order by 3 desc";

		$args           = array();
		$args["class"]  = "sortable";
		$args["links"]  = $links;
		$args["header"] = true;

		return GuiTableContent( "projects", $sql, $args );
	}

	/**
	 * @param $project_id
	 * @param null $args
	 *
	 * @return string|null
	 * @throws Exception
	 */
	static function show_project( $project_id, $args = null ) {
		$edit = GetArg( $args, "edit", false );
		if ( $edit ) {
			$args["post_file"] = self::instance()->post_file;

			return Core_Gem::GemElement( "im_projects", $project_id, $args );
		}
		$active_only = GetArg( $args, "active_only", true );
		$order       = GetArg( $args, "order", "order by priority desc" );
		if ( is_null( $args ) ) {
			$args = [];
		}

		$sql = "select * from im_tasklist where project_id = " . $project_id;
		if ( $active_only ) {
			$sql .= " and status = 0 ";
		}
		$args["sql"]   = $sql . $order;
		$args["links"] = array( "id" => self::task_link( "%s" ) );
		$args["title"] = im_translate( "משימות בפרויקט" ) . " " . Org_Project::GetName( $project_id );

//	print $sql;
		$result = Core_Gem::GemTable( "im_tasklist", $args );
		$result .= GuiHyperlink( "Edit project", AddToUrl( "edit", 1 ) );

		return $result;
	}

	static function handle_focus_do( $operation ) {
		if ( strpos( $operation, "data_" ) === 0 ) {
			return handle_data_operation( $operation );
		}

		$allowed_tables         = array(
			"im_company",
			"im_tasklist",
			"im_task_templates",
			"im_projects",
			"im_working"
		);
		$header_args            = [];
		$header_args["scripts"] = array(
			"/core/gui/client_tools.js",
			"/core/data/data.js",
			"/focus/focus.js",
			"/focus/gui.php",
			"/vendor/sorttable.js"
		);

		switch ( $operation ) { // Handle operation that don't need page header.
			///////////////////////////
			// DATA entry and update //
			///////////////////////////
			case "new_sequence":
				if ( create_new_sequence() ) {
					print "done";
				} else {
					print "create failed";
				}

				return;

			case "del_team":
			case "cancel_im_working_teams":
				$team_id = GetParam( "id", true );
				if ( team_delete( $team_id ) ) {
					print "done";
				}

				return;

			case "cancel_im_task_templates":
				$id = GetParam( "id", true );
				if ( data_delete( "im_task_templates", $id ) ) {
					print "done";
				}

				return;


			case "add_team_member":
				$team_id    = GetParam( "team_id", true );
				$new_member = GetParam( "new_member", true );
				// $worker = new Org_Worker($new_member);
				$team = new Org_Team($team_id);
				return $team->AddWorker($new_member);

			case "end_task":
				$task_id = GetParam( "id" );
				if ( $task_id > 0 ) {
					$t = new Focus_Tasklist( $task_id );
					return  $t->Ended();
				}

				return false;

			case "cancel_task":
				$task_id = GetParam( "id" );
				return  Focus_Tasklist::task_cancelled( $task_id );

			case "postpone_task":
				$task_id = GetParam( "id" );
				$T       = new Focus_Tasklist( $task_id );
				return        $T->Postpone();

			case "pri_plus_task":
				$task_id = GetParam( "id" );
				$T       = new Focus_Tasklist( $task_id );
				return   $T->setPriority( $T->getPriority() + 1 );

			case "pri_minus_task":
				$task_id = GetParam( "id" );
				$T       = new Focus_Tasklist( $task_id );
				return $T->setPriority( $T->getPriority() - 1 );

			case "save_new":
				$table_name = GetParam( "table_name", true );
				if ( ! in_array( $table_name, $allowed_tables ) ) {
					die ( "invalid table operation" );
				}
				$result = data_save_new( $table_name );
				if ( $result > 0 ) {
					print "done." . $result;
				}

				return;

			case "update":
				$table_name = GetParam( "table_name", true );
				if ( ! in_array( $table_name, $allowed_tables ) ) {
					die ( "invalid table operation" );
				}
				if ( Core_Data::update_data( $table_name ) ) {
					if ( $table_name == 'im_task_templates' ) {
						$row_id = intval( GetParam( "id", true ) );
						if ( sql_query( "update im_task_templates set last_check = null where id = " . $row_id ) ) {
							return "done";
						}
					}
				}

				return "not handled";

			case "delete_template":
				$user_id = get_user_id();
				$id      = GetParam( "row_id", true );
				return template_delete( $user_id, $id );

			case "start_task":
				// a. set the start time, if not set.
				$task_id = GetParam( "id" );
				$task = new Focus_Tasklist($task_id);
				$task->task_started(get_user_id());

				// If the was query we want to show the result.
				// And the move to the task_url if exists.
//			if ($query = task_query($task_id))
//			{
////				print im_file_get_html($query);
//				$url = task_url($task_id);
//				if (strlen($url)) {
//					print '<script language="javascript">';
//					print "window.location.href = '" . $url . "'";
//					print '</script>';
//				}
//				return;
//			}
				$url = $task->task_url( );
				if ( ! $url ) return true;
				$url_headers = @get_headers( $url );
				if ( ! $url_headers || strstr( $url_headers[0], "404" ) ) {
					print GetUrl( 1 ) . "?operation=bad_url&id=" . $task_id;

					return false;
				}
				if ( strlen( $url ) > 1 ) print $url;

				return true;

			case "add_to_company":
				$company_id = GetParam( "company_id", true );
				$email      = GetParam( "email", true );
				$name       = GetParam( "name", true );
				$project_id = GetParam( "project_id", true );
				return company_invite_member( $company_id, $email, $name, $project_id );

			case "save_add_member":
				$member  = GetParam( "member", true );
				$team_id = GetParam( "team", true );
				$current = get_usermeta( $member, "teams" );
				if ( ! $current ) {
					$current = ":";
				}
				update_usermeta( $member, "teams", ":" . $team_id . $current ); // should be :11:22:3:4:
				return true;

			case "logout":
				wp_logout();
				$back = GetParam( "back", false, GetUrl( 1 ) );
				header( "location: " . $back );

				return;

			case "cancel_im_projects":
				$id = GetParam( "id", true );
				if ( project_delete( $id, get_user_id() ) ) {
					print "done";
				}

				return;

			case "search_by_text":
				$text = GetParam( "text", true );

				return self::search_by_text( $text );
		}

		return "not handled";
	}

	/**
	 * @param $user_id
	 *
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function main( $user_id, $args = null ) {
		if ( ! $args ) {
			die( "no args " . __FUNCTION__ );
		}

		$worker = new Org_Worker( $user_id );
		$tabs   = array();

		$result = "";

		$result .= self::search_box();
		$result .= self::new_task();

		// My work queue
		$mine = self::user_work( $args, $user_id );
		if ( $mine ) {
			array_push( $tabs, array( "my_work", "My work", $mine ) );
		}

		// tasks I initiated.
		$i_want = self::i_want( $args, $user_id );
		if ( $i_want ) {
			array_push( $tabs, array( "i_want", "I want", $i_want ) );
		}

		// Tasks that belong to my teams.
		$my_teams = self::my_teams( $args, $user_id );
		if ( $my_teams ) {
			array_push( $tabs, array( "my teams", "My Teams", $my_teams ) );
		}

		$my_projects = self::my_projects( $args, $user_id );
		if ( $my_projects ) {
			array_push( $tabs, array( "my projects", "My projects", $my_projects ) );
		}


		$repeating = self::show_templates( $args ); // Todo: limit to what user can see
		if ( $repeating ) {
			array_push( $tabs, array( "repeating tasks", "Repeating tasks", $repeating ) );
		}

		if ( $companies = $worker->GetCompanies( true ) ) {
			$args = self::Args();
			foreach ( $companies as $company ) {
				array_push( $tabs, array(
					"company_settings",
					"Company settings",
					self::CompanySettings($company)
				) );
			}
		}

//		$my_teams = self::teams($args, $user_id);
//		if ($my_teams) array_push($tabs, array("my teams", "My Teams", $my_teams));

		$result .= Core_Html::GuiTabs( $tabs );

		return $result;
	}

	static function CompanySettings($company)
	{
		$args = self::Args();
		$tabs = [];

		array_push( $tabs, array(
			"teams",
			"Teams",
			self::company_teams( $company, $args )
		) );

		array_push( $tabs, array(
			"workers",
			"Workers",
			self::company_workers( $company, $args )
		) );

		$args["class"] = "company_tabs";
		return Core_Html::GuiTabs($tabs, $args);
	}

	static function search_box() {
		$result = "";
		$result .= Core_Html::GuiInput( "search_text", "(search here)",
			array( "events" => "onfocus=\"search_by_text()\" onkeyup=\"search_by_text()\" onfocusout=\"search_box_reset()\"" ) );

		$result .= Core_Html::GuiDiv( "search_result" );

		return $result;
	}

	static function new_task() {
		$result = "";
		$result .= Core_Html::GuiButton( "btn_new_task", "Add",
			array( "action" => "new_task.style.display = 'block';" ) );

		$args          = self::Args( "im_tasklist", "new" );
		$args["style"] = "display:none";
		$result        .= Core_Html::GuiDiv( "new_task", Core_Gem::GemAddRow( "im_tasklist", __( "Add" ), $args ), $args );

		return $result;
	}

	static function user_work( $args, $user_id ) {
		$result = "";

		if ( ! ( $user_id > 0 ) ) {
			print sql_trace();
			die ( "bad user id $user_id" );
		}

		$worker = new Org_Worker( $user_id );

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I need to handle (owner = me)                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["count"] = 0;
		$args["title"] = im_translate( "Active tasks assigned to me or my teams" );
		$teams         = $worker->AllTeams();
		$args["query"] = " (owner = " . $user_id . ( $teams ? " or team in (" . CommaImplode( $teams ) . ")" : "" ) . ")";
		if ( GetArg( $args, "active_only", true ) ) {
			$args["query"] .= " and " . self::ActiveQuery( $args );
		}
		$args["limit"] = GetParam( "limit", false, 10 );

		$table = self::Taskslist( $args );
		if ( $args["count"] ) {
			$result .= $table;
		} else {
			$result .= "Nothing found. Try non active list: ";
			$result .= Core_Html::GuiHyperlink( "non active", AddToUrl( "non_active", 1 ) );
		}

		return $result;
	}

	static function my_teams( $args, $user_id ) {
		$worker = new Org_Worker( $user_id );
		$result = "";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks teams I'm a member of (team in my_teams). Not assigned                                              //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = im_translate( "My teams tasks" );
		$teams         = $worker->AllTeams();

		// print "teams: " . CommaImplode($teams) . "<br/>";
		$args["extra_fields"]      = array( "team" );
		$args["selectors"]["team"] = "Focus_Tasks::gui_select_team";
		if ( $teams and count( $teams ) ) {
			$result .= Focus_Tasks::Taskslist( $args );
		}

		return $result;
	}

	static function my_projects( $args, $user_id ) {
		$args = self::Args("im_projects");
		$worker = new Org_Worker( $user_id );
		$result = "";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks projects I'm a member of (team in my_projects). Not assigned                                              //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = im_translate( "My projects tasks" );
		$args["where"] = " id in (" . CommaImplode($worker->AllProjects()) . ")";

		$result .= Core_Gem::GemTable("im_projects", $args);

		return $result;
	}

	static function my_tasks( $args, $user_id ) {
		$result        = "";
		$ignore_list   = [];
		$args["count"] = 0;

		if ( ! $user_id > 0 ) {
			print sql_trace();
			die ( "bad user id $user_id" );
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Links: Templates                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if ( $teams = Org_Team::team_managed_teams( $user_id ) ) {// Team manager
			$workers = array();
			foreach ( $teams as $team_id ) {
				$team = new Org_Team($team_id);
				foreach ( $team->AllMembers( $team ) as $worker_id ) {
					$workers[ $worker_id ] = 1;
				}
				$count  = 0; // active_task_count("team_id = " . $team);
				$result .= Core_Html::GuiHyperlink( $team->getName() . "(" . $count . ")", "?operation=show_team&id=" . $team_id );
			}

			$result .= "<br/>";
			if ( $workers ) {
				foreach ( $workers as $worker_id => $c ) {
					$count = 0;

					$result .= Core_Html::GuiHyperlink( get_user_name( $worker_id ) . "(" . $count . ")", '?operation=show_worker&id=' . $worker_id ) . " ";
				}
			}
			$result .= "<br/>";
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I need to handle (owner = me)                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"]       = im_translate( "Tasks assigned to me" );
		$args["query"]       = " owner = " . $user_id;
		$args["limit"]       = GetParam( "limit", false, 10 );
		$args["active_only"] = GetParam( "active_only", false, true );

		foreach ( $_GET as $param => $value ) {
			if ( ! in_array( $param, $ignore_list ) ) {
				$args[ $param ] = $value;
			}
		}
		$table = self::Taskslist( $args );
		if ( $args["count"] ) {
			$result .= $table;
		}

		return $result;
	}

	static function i_want( $args, $user_id ) {
		$result = "";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I've created. Assigned to some else                                                                 //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = im_translate( "Tasks I've initiated to other teams" );
		$args["query"] = " creator = " . $user_id; // . " and (owner != " . $user_id . ' or isnull(owner)) ' . ($teams ? ' and team not in (' . CommaImplode( $teams ) . ")" : '');
//		$args["limit"]       = GetParam( "limit", false, 10 );
//		$args["active_only"] = GetParam( "active_only", false, true );
		$result .= Focus_Tasks::Taskslist( $args );

		//print "c=" . $args["count"];
		if ( ! $args["count"] ) {
			$result .= im_translate( "No active tasks!" ) . "<br/>";
			$result .= im_translate( "Let's create first one!" ) . " ";
			$result .= Core_Html::GuiHyperlink( "create task", "?operation=show_new_task" ) . "<br/>";
		}

		return $result;
	}

	static function Taskslist( &$args = null ) {
		$args["count"]           = 0;
		$args["drill"]           = true;
		$args["drill_operation"] = "show_tasks";

		$table_name = "im_tasklist";
		$title      = GetArg( $args, "title", "" );

		$action_url = "/wp-content/plugins/focus/post.php";
		$page_url   = GetUrl( true );

		$active_only = GetArg( $args, "active_only", true );
		if ( $active_only ) {
			$title .= " (" . im_translate( "active only" ) . ")";
		}

		if ( ! isset( $args["fields"] ) ) {
			$args["fields"] = array( "id", "task_description", "project_id", "priority", "task_template" );
		}
		if ( isset( $args["extra_fields"] ) ) {
			$args["fields"] = array_merge( $args["fields"], $args["extra_fields"] );
		}

		$limit = GetParam( "limit", false, 10 );

		if ( GetParam( "offset" ) ) {
			$limit .= " offset " . GetParam( "offset" );
		}

		$links = array();

		$query = "where 1 ";
		if ( GetArg( $args, "query", null ) ) {
			$query .= " and " . GetArg( $args, "query", null );
		}

		$project_id = GetArg( $args, "project_id", null );
		if ( $project_id ) {
			$title = im_translate( "Project" ) . " " . Org_Project::GetName( $project_id );
			if ( $f = array_search( "project_id", $args["fields"] ) ) {
				unset( $args["fields"][ $f ] );
			}
			$query .= " and project_id = $project_id";
		}

		if ( ! isset( $args["selectors"] ) ) {
			$args["selectors"] = array(
				"project"    => "Focus_Tasks::gui_select_project",
				"project_id" => "Focus_Tasks::gui_select_project",
				"owner"      => "Focus_Tasks::gui_select_worker"
			);
		}

		$query .= " and status < 2 ";

		// The first part is action to server. If it replies with done, the second part is executed in the client (usually hiding the row).
		$actions = array(
			array( "start", $action_url . "?operation=start_task&id=%s;load_page" ),
			array( "finished", $action_url . "?operation=end_task&id=%s;action_hide_row" ),
			array( "cancel", $action_url . "?operation=cancel_task&id=%s;action_hide_row" ),
			array( "postpone", $action_url . "?operation=postpone_task&id=%s;action_hide_row" ),
			array( "pri_plus", $action_url . "?operation=pri_plus_task&id=%s" ),
			array( "pri_minus", $action_url . "?operation=pri_minus_task&id=%s;action_hide_row" )

		);
		$order   = "order by priority desc ";

		$links["task_template"] = self::get_link( "template", "%s" );
		$links["id"]            = self::get_link( "task", "%s" );
		// Use drill, instead - $links["project_id"] = $page_url . "?operation=show_project&id=%s";
		$args["links"]         = $links;
		$args["post_file"]     = self::getPost();
		$args["actions"]       = $actions;
		$args["id_field"]      = "id";
		$args["edit"]          = false;
		$args["header_fields"] = array(
			"task_description" => "Task description",
			"task_template"    => "Repeating task id",
			"project_id"       => "Project Id",
			"id"               => "Id",
			"priority"         => "Priority",
			"start"            => "Start",
			"finish"           => "Finished",
			"cancel"           => "Cancel",
			"postpone"         => "Postpone"
		);
		$fields                = $args["fields"];

		$sql = "select " . CommaImplode( $fields ) . " from $table_name $query $order ";

		$result      = "";
		$args["sql"] = $sql;

		$args["col_width"] = array( "task_description" => '30%' );
		$table             = Core_Gem::GemTable( "im_tasklist", $args );
		if ( $table ) {
			// if (strlen($title)) $result = Core_Html::gui_header(2, $title);
			$result .= $table;
		}

		$count = $args["count"];
		$page  = GetParam( "page", false, 1 );
		if ( $count === $page ) {
			// $args["page"] = $page;
			$result .= Core_Html::GuiHyperlink( "More", AddToUrl( "page", $page + 1 ) ) . " ";
			$result .= Core_Html::GuiHyperlink( "Not paged", AddToUrl( "page", - 1 ) ) . " "; // All pages
		}
		$result .= Core_Html::GuiHyperlink( "Not filtered", AddToUrl( "active_only", 0 ) ); // Not filtered

		$result .= " " . Core_Html::GuiHyperlink( "Add task", self::get_link( "task" ) ); // id == 0 -> new

		$result .= " " . Core_Html::GuiHyperlink( "Add delivery", AddToUrl( "operation", "show_new_task&mission=1" ) );

		return $result;
	}

	static function show_tasks( $ids ) {
		$args          = [];
		$args["query"] = "id in (" . CommaImplode( $ids ) . ")";

		return Focus_Tasks::Taskslist( $args );
	}

	/**
	 * @param $team_id
	 * @param $active_only
	 *
	 * @return string
	 */
	static function show_team( $team_id, $active_only = true ) {
		$team = new Org_Team($team_id);
		$result = "";
		$result .= Core_Html::gui_header( 1, "Team " . $team->getName( ) );
		$result .= Core_Html::GuiHyperlink( "Include non active", AddToUrl( "active_only", 0 ) );

		// $team_members = team_members($team_id);

//		$result .=  Core_Html::gui_header(2, get_customer_name($user_id) . " " . $user_id);
		$args           = array( "active_only" => $active_only );
		$args["query"]  = " team=" . $team_id;
		$args["fields"] = array( "id", "task_description", "project_id", "priority", "task_template", "owner" );
		$result         .= Focus_Tasks::tasks_list( $args );

		return $result;
	}

	static function show_template_wrapper() {
		$row_id = GetParam( "id", false );
		if ( ! $row_id ) {
			return self::show_new_template();
		}

		return self::show_templates( $args, $row_id );
	}

	static function show_task_wrapper() {
		$row_id = GetParam( "id", false );
		if ( ! $row_id ) {
			return self::show_new_task();
		}

		return self::show_task( $row_id );
	}

	static function show_new_template() {
		$result = "";
//		$result                   .= Core_Html::gui_header( 1, "יצירת תבנית חדשה" );
		$worker                   = new Org_Worker( get_user_id() );
		$args                     = array();
		$args["selectors"]        = array(
			"project_id"  => "Focus_Tasks::gui_select_project",
			"owner"       => "Focus_Tasks::gui_select_worker",
			"creator"     => "Focus_Tasks::gui_select_worker",
			"team"        => "Focus_Tasks::gui_select_team",
			"repeat_freq" => "gui_select_repeat_time"
		);
		$args["transpose"]        = true;
		$args["worker"]           = get_user_id();
		$args["companies"]        = $worker->GetCompanies();
		$args["values"]           = array( "owner" => get_user_id(), "creator" => get_user_id() );
		$args["fields"]           = array(
			"task_description",
			"task_url",
			"repeat_freq_numbers",
			"project_id",
			"repeat_freq",
			"condition_query",
			"priority",
			"working_hours",
			"path_code",
			"creator",
			"team"
		);
		$args["mandatory_fields"] = array(
			"task_description",
			"repeat_freq_numbers",
			"repeat_freq",
			"project"
		);
		$result                   .= Core_Html::NewRow( "im_task_templates", $args );
		$result                   .= Core_Html::GuiButton( "btn_template", "add",
			array( "action" => "data_save_new('" . self::getPost() . "', 'im_task_templates')" ) );

		return $result;
	}

	/**
	 * @param $row_id
	 * @param int $edit
	 *
	 * @return string
	 * @throws Exception
	 */
	static function show_task( $row_id, $edit = 1 ) {
		if ( ! $row_id ) {
			return self::show_new_task();
		}
		$worker = new Org_Worker( get_user_id() );

		$table_name  = "im_tasklist";
		$entity_name = "task";

		// print Core_Html::gui_header( 1, $entity_name . " " . $row_id );
		$args              = array();
		$args["edit"]      = $edit;
		$args["selectors"] = array(
			"project_id" => "Focus_Tasks::gui_select_project",
			"owner"      => "Focus_Tasks::gui_select_worker",
			"creator"    => "Focus_Tasks::gui_select_worker",
			"preq"       => "gui_select_task",
			"mission_id" => "gui_select_mission",
			"team"       => "Focus_Tasks::gui_select_team"
		);
		$args["title"]     = $entity_name;

		$args["header_fields"] = array(
			"date"             => "Date",
			"task_description" => "Task description",
			"task_template"    => "Repeating task",
			"status"           => "Status",
			"started"          => "Started",
			"ended"            => "Ended",
			"project_id"       => "Project",
			"location_name"    => "Location",
			"location_address" => "Address",
			"priority"         => "Priority",
			"preq"             => "Prerequisite",
			"owner"            => "Assigned to",
			"creator"          => "Creator",
			"task_type"        => "Task type",
			"mission_id"       => "Mission"
		);

		$args["worker"]    = $worker->getId();
		$args["companies"] = $worker->GetCompanies();
		$args["debug"]     = 0; // get_user_id() == 1;
		$args["post_file"] = self::instance()->post_file;

		// if (get_user_id() == 1) var_dump(self::instance()->post_file);

		return Core_Gem::GemElement( $table_name, $row_id, $args );
	}

//	function get_nav_name() {
//		if ($this->nav_menu_name) return $this->nav_menu_name;
//
//		if ($user_id = get_user_id(true)) {
//			$this->nav_menu_name = "management." . $user_id;
//		}
//		return $this->nav_menu_name;
//	}
//
//	function create_nav() {
//		$user_id = get_user_id(true);
////		Focus_Nav::instance()->create_nav($this->get_nav_name(), $user_id);
//	}
//
//	function get_nav()
//	{
//		return Focus_Nav::instance()->get_nav();
//	}
//if ($menu_nav) $menu_nav_id = $menu_nav->term_id;

	/**
	 * @return bool
	 * @throws Exception
	 */
	function focus_check_user() {
		// Check if user has company
		$user_id     = get_user_id();
		$worker      = new Org_Worker( $user_id );
		$company_ids = $worker->GetCompanies();
		if ( ! count( $company_ids ) ) {
			print "Ne need some information to get started!<br/>";
			$args = array( "values" => array( "admin" => get_user_id() ) );
			try {
				print Core_Html::gui_header( 1, "Company details" );
				print NewRow( "im_company", $args );
			} catch ( Exception $e ) {
				print "Error F1: " . $e->getMessage();

				return false;
			}

			print Core_Html::GuiButton( "btn_add", "Add", array( "action" => "data_save_new('/focus/focus-post.php?operation=new_company_user', 'im_company', location_reload)" ) );

			// print gui_input("company", )
			return false;
		}

		// Check if user has team.
		$team_ids = $worker->AllTeams();
		if ( ! $team_ids or ! count( $team_ids ) ) {
//		print "uid= $user_id" . Core_Html::Br();
//		var_dump($team_ids); Core_Html::Br();
//		die ("Error #F2. Please report");
			Org_Team::Create($user_id, im_translate( "Personal team" ) . " " . get_customer_name( $user_id ) );
		}

		$project_ids = worker_get_projects( $user_id );
		if ( is_null( $project_ids ) or ! count( $project_ids ) ) {
			project_create( $user_id, im_translate( "first project" ), $company_ids[0] );
		}

		return true;
	}

	/**
	 * @param $ids
	 *
	 * @return string|null
	 */

	/**
	 * @param $team_id
	 *
	 * @return string
	 * @throws Exception
	 */

	/**
	 * @param $company_id
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function company_teams( $company_id, $args ) {
		$c                 = new WPF_Company( $company_id );
		$result            = Core_Html::gui_header( 1, $c->getName() );
		$args["query"]     = "manager = 1";
		$args["links"]     = array( "id" => AddToUrl( array( "operation" => "show_edit_team&id=%s" ) ) );
		$args["selectors"] = array( "team_members" => __CLASS__ . "::gui_show_team" );
		//$args["post_file"] .= "operation=company_teams";

		$teams = Core_Data::TableData( "select id, team_name from im_working_teams where manager in \n" .
		                               "(select user_id from im_working where company_id = $company_id) order by 1", $args );
		if ( $teams ) {
			foreach ( $teams as $key => &$row ) {
				if ( $key == "header" ) {
					$row [] = im_translate( "Team members" );
				} else {
					$team = new Org_Team($row["id"]);
					$all = $team->AllMembers();
					if ($all)
						$row["team_members"] = CommaImplode( $all);
					else
						continue;
					// Temp:
//					foreach ($all as $worker){
//						$w = new Org_Worker($worker);
//						$w->AddCompany($company_id);
//					}
				}
			}
		}
		//GemTable("im_working_teams", $args);
		$result .= Core_Gem::GemArray( $teams, $args, "company_teams" );

		return $result;
	}

	static function company_workers( $company_id, $args ) {
		$c                 = new WPF_Company( $company_id );
		$result            = Core_Html::gui_header( 1, $c->getName() );
		$args["query"]     = "manager = 1";
		$args["links"]     = array( "id" => AddToUrl( array( "operation" => "show_edit_worker&id=%s" ) ) );
//		$args["selectors"] = array( "team_members" => __CLASS__ . "::gui_show_team" );
		//$args["post_file"] .= "operation=company_teams";

		$worker_ids = $c->GetWorkers(); // Should return the admin at least.
		if (! $worker_ids) return null;

		$workers = Core_Data::TableData("select id, client_displayname(id) from wp_users where id in ("
		. CommaImplode($worker_ids) . ")");
		$args["post_file"] .= "?company=" . $company_id;
//		if ( $workers ) {
//			foreach ( $workers as $key => &$row ) {
//				if ( $key == "header" ) {
//					// Add col to header, if needed later
//				} else {
//					$team = new Org_Team($row["id"]);
//					$row["team_members"] = CommaImplode( $team->AllMembers());
//				}
//			}
//		} else {
//			return null;
//		}
//		if (get_user_id() == 1) var_dump($workers);
		//GemTable("im_working_teams", $args);
		$args["add_button"] = false;
		$result .= Core_Gem::GemArray( $workers, $args, "company_workers" );
		$result .= Core_Html::GuiHyperlink("Add", AddToUrl(array("operation"=>"show_add_company_worker", "company" => $company_id)));

		return $result;
	}


	/**
	 * @param $args
	 * @param int $template_id
	 * @param null $new
	 *
	 * @return string
	 * @throws Exception
	 */
	static function show_templates( &$args, $template_id = 0, $new = null ) {
		$url = GetUrl( 1 );

		$result     = "";
		$action_url = "/wp-content/plugins/focus/post.php"; // GetUrl(1);//  "/focus/focus-post.php";

		$worker                = new Org_Worker( get_user_id() );
		$args["worker"]        = $worker->getId();
		$args["companies"]     = $worker->GetCompanies();
		$args["selectors"]     = array(
			"project_id"  => "Focus_Tasks::gui_select_project",
			"owner"       => "Focus_Tasks::gui_select_worker",
			"creator"     => "Focus_Tasks::gui_select_worker",
			"repeat_freq" => "gui_select_repeat_time",
			"team"        => "Focus_Tasks::gui_select_team"
		);
		$args["fields"]        = array(
			"id",
			"task_description",
			"project_id",
			"priority",
			"team",
			"repeat_freq",
			"repeat_freq_numbers",
			"timezone",
			"working_hours",
			"condition_query",
			"task_url",
			"template_last_task(id)"
		);
		$args["header_fields"] = array(
			"task_description"    => "Task description",
			"project_id"          => "Project",
			"priority"            => "Priority",
			"team"                => "Team",
			"repeat_freq"         => "Repeat Frequency",
			"repeat_freq_numbers" => "Repeat times",
			"working_hours"       => "Working hours",
			"Task site"
		);

		if ( $template_id ) {
			// print Core_Html::gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";
			$args["title"]     = "Repeating task";
			$args["post_file"] = $action_url;

			$template = Core_Gem::GemElement( "im_task_templates", $template_id, $args );
			if ( ! $template ) {
				$result .= "Not found";

				return $result;
			}
			$result .= $template;

			$tasks_args          = array( "links" => array( "template_id" => self::get_link( "task", "%s" ) ) );
			$tasks_args["class"] = "sortable";

//			if (get_user_id() == 1){
//				$output = "";
//				$row = sql_query_single_assoc("SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority, creator, team " .
//				                              " FROM im_task_templates where id = $template_id");
//
//				Focus_Tasklist::create_if_needed($template_id, $row, $output, 1, $verbose_line);
//				// $result .= $output;
//			}

			$sql   = "select * from im_tasklist where task_template = " . $template_id;
			$sql   .= " order by date desc limit 10";
			$table = Core_Html::GuiTableContent( "last_tasks", $sql, $tasks_args );
			if ( $table ) {
				$result .= Core_Html::gui_header( 2, "משימות אחרונות" );
				$result .= $table;
			}

			return $result;
		}

		if ( $page = GetParam( "page" ) ) {
			$args["page"] = $page;
			unset ( $_GET["page"] );
		};

		$query = ( isset( $args["query"] ) ? $args["query"] : " 1" );
		if ( GetParam( "search", false, false ) ) {
			$ids = Core_Data::data_search( "im_task_templates", $args );
			if ( ! $ids ) {
				$result .= "No templates found" . Core_Html::Br();

				return $result;
			}
			$query .= " and id in (" . CommaImplode( $ids ) . ")";
		}

		$args["class"]   = "sortable";
		$args["links"]   = array( "id" => $url . "?operation=show_template&id=%s" );
		$args["header"]  = true;
		$args["drill"]   = true;
		$args["edit"]    = false;
		$args["actions"] = array(
			array(
				"delete",
				$action_url . "?operation=delete_template&row_id=%s;action_hide_row"
			)
		);
		$args["query"]   = $query;
		$args["order"]   = " id " . ( $new ? "desc" : "asc" );

//		$result = Core_Html::GuiHyperlink( "Add repeating task", GetUrl( true ) . "?operation=new_template" );

		$result .= Core_Gem::GemTable( "im_task_templates", $args );

		// $result .= GuiTableContent( "projects", $sql, $args );

		return $result;
	}

	/**
	 * @param $id
	 * @param $value
	 * @param $args
	 *
	 * @return string
	 */
// $selector_name( $input_name, $orig_data, $args)

	/**
	 * @return string
	 * @throws Exception
	 */
	function show_staff() // Edit teams that I manage.
	{
		$user   = wp_get_current_user();
		$result = Core_Html::gui_header( 2, "teams" );

		$args              = [];
		$args["links"]     = array( "id" => AddToUrl( array( "operation" => "show_team", "id" => "%s" ) ) );
		$args["selectors"] = array( "manager" => "Focus_Tasks::gui_select_worker" );
		$args["edit"]      = false;
		$result            .= GuiTableContent( "working_teams", "select * from im_working_teams where manager = " . $user->id, $args );

		$result .= Core_Html::GuiHyperlink( "add", AddToUrl( "operation", "show_new_team" ) );

		// print GuiTableContent("");

		return $result;
	}

	/**
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	function edit_projects( $args ) // Edit projects that I manage.
	{
		$result = Core_Html::gui_header( 2, "Projects" );

		$global            = GetArg( $args, false, "global" );
		$args["links"]     = array( "ID" => AddToUrl( array( "operation" => "show_edit_project", "id" => "%s" ) ) );
		$args["selectors"] = array( "manager" => "Focus_Tasks::gui_select_worker" );
		$args["edit"]      = false;
		$args["actions"]   = array(
			array(
				"delete",
				GetUrl( 1 ) . "?operation=cancel_im_projects&id=%s;action_hide_row"
			)
		);
		$base_query        = "is_active = 1 ";
		$args["fields"]    = array( "ID", "project_name", "project_contact", "project_priority", "company" );
		if ( $global ) // A global user can see all projects
		{
			$args["query"] = $base_query;
			$result        .= Core_Gem::GemTable( "im_projects", $args ); // "select * from im_projects ", $args);
		} else { // Ordinary user can see only projects he's working in.
			$worker_id = GetArg( $args, "worker_id", null );
			if ( ! $worker_id ) {
				die ( "no worker_id" );
			}
			$companies = worker_get_companies( get_user_id() );

			foreach ( $companies as $company ) {
				$result        .= Core_Html::gui_header( 1, company_get_name( $company ) );
				$args["query"] = $base_query . " and company = $company";
				$result        .= Core_Gem::GemTable( "im_projects", $args ); //"select * from im_projects where company = $company", $args);
			}
		}

		$result .= Core_Html::GuiHyperlink( "add", AddToUrl( "operation", "show_new_company" ) );

		return $result;
		// print GuiTableContent("");
	}

	/**
	 * @param null $args
	 *
	 * @return string|null
	 */


	/**
	 * @param $manager_id
	 * @param $url
	 *
	 * @return string
	 * @throws Exception
	 */
	function managed_workers( $manager_id, $url ) {
		$teams = sql_query_array_scalar( "select id from im_working_teams where manager = " . $manager_id );

		if ( ! $teams ) {
			return "";
		}

		$result = "";

		foreach ( $teams as $team_id ) {
			$team = new Org_Team($team_id);
			$result .= Core_Html::GuiHyperlink( $team->getName(), $url . "?team=" . $team_id );
		}

		return $result;
	}

	/**
	 * @param $team_id
	 *
	 * @return string
	 */

	/**
	 * @return bool
	 */
	function create_new_sequence() {
		$user_id = get_user_id();
		$project = GetParam( "project", true );
		$priorty = GetParam( "priority", true );

		$i           = 1;
		$description = null;
		$preq        = null;
		while ( isset( $_GET[ "task" . $i ] ) ) {
			$description = GetParam( "task" . $i );
			$preq        = task_new( $user_id, $project, $priorty, $description, $preq );
			$i ++;
		}

		return true;
	}

	/**
	 * @param $user_id
	 * @param $project
	 * @param $priority
	 * @param $description
	 * @param null $preq
	 *
	 * @return int|string
	 */
	function task_new( $user_id, $project, $priority, $description, $preq = null ) {
		$creator = $user_id;
		$owner   = $user_id; // For now
		is_numeric( $priority ) or die( "bad project id" );
		is_numeric( $priority ) or die ( "bad priority" );
		strlen( $description ) > 2 or die ( "short description" );

		$sql = "insert into im_tasklist (task_description, project_id, priority";

		if ( $preq ) {
			$sql .= ", preq";
		}

		$sql .= ", creator, owner) values (" .
		        quote_text( $description ) . "," .
		        $project . "," .
		        $priority . ",";
		if ( $preq ) {
			$sql .= $preq . ",";
		}
		$sql .= $user_id . "," . $owner . ")";

		sql_query( $sql );

		return sql_insert_id();
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	function edit_organization() {
		$user_id = get_user_id();
		$result  = "";
		$result  .= Core_Html::GuiHyperlink( "Edit organization", AddToUrl( "operation", "show_staff" ) ) . " ";

		$result       .= Core_Html::GuiHyperlink( "My projects", AddToUrl( "operation", "show_edit_projects" ) ) . " ";
		$my_companies = worker_get_companies( $user_id, true );
		if ( $my_companies ) {
			foreach ( $my_companies as $company ) {
				$result .= Core_Html::GuiHyperlink( company_get_name( $company ), AddToUrl( array(
						"operation"  => "show_edit_company",
						"company_id" => $company
					) ) ) . " ";
			}
		}

		if ( im_user_can( "edit_teams" ) ) { // System editor
			$result .= "<br/>" . im_translate( "System wide:" );
			$result .= Core_Html::GuiHyperlink( "All teams", AddToUrl( "operation", "show_edit_all_teams" ) ) . " ";
			$result .= Core_Html::GuiHyperlink( "All projects", AddToUrl( "operation", "show_edit_all_projects" ) ) . " ";
		}

		return $result;
	}

	/**
	 * @param $template_id
	 *
	 * @return string
	 */
	function template_creator( $template_id ) {
		return sql_query_single_scalar( "select creator from im_task_templates where id = " . $template_id );
	}

	/**
	 * @param $user_id
	 * @param $template_id
	 *
	 * @return bool|mysqli_result|null
	 */
	function template_delete( $user_id, $template_id ) {
		$creator_id = template_creator( $template_id );
		if ( $creator_id != $user_id ) {
			print "not creator c=$creator_id u=$user_id<br/>";

			return false;
		}
		if ( $template_id > 0 ) {
			$sql = "delete from im_task_templates where id = " . $template_id;

			return sql_query( $sql );
		}

		return false;
	}

	/**
	 * @param $id
	 * @param $selected
	 * @param $args
	 *
	 * @return string
	 */
	static function gui_show_team( $id, $selected, $args ) {
		$members = explode( ",", $selected );
		$result  = "";
		foreach ( $members as $member ) {
			$result .= get_user_name( $member ) . ", ";
		}

		return rtrim( $result, ", " );
	}

	function link_to_task( $id ) {
		return AddToUrl( array( "operation" => "show_task", "id" => $id ) );
	}

	static function search_by_text( $text ) {
		$result = [];
		$result = array_merge( $result, self::project_list_search( "project_name like " . QuotePercent( $text ) ) );
		$result = array_merge( $result, self::task_list_search( "status < 2 and task_description like " . QuotePercent( $text ) ) );

		if ( count( $result ) < 1 ) {
			return "No results";
		}

		return Core_Html::gui_table_args( $result );
	}

	static function task_list_search( $query ) {
		$tasks = sql_query_array( "select id, task_description from im_tasklist where $query" );

		$result = [];
		foreach ( $tasks as $task ) {
			array_push( $result, Core_Html::GuiHyperlink( $task[1], self::get_link( "task", $task[0] ) ) );
		}

		// debug_var($result);
		return $result;
	}

	static function project_list_search( $query ) {
		return sql_query_array_scalar( "select id from im_projects where $query" );
	}

	static function show_settings( $user_id ) {
		$result = Core_Html::gui_header( 1, im_translate( "Settings for" ) . " " . get_user_name( $user_id ) );

		return $result;
	}

	static function gui_select_company( $id, $value, $args ) {
		$edit    = GetArg( $args, "edit", true );
		$new_row = GetArg( $args, "new_row", false );

		if ( ! $edit ) {
			return Org_Company::GetName( $value );
		}
		// Filter by worker if supplied.
		$user_id = GetArg( $args, "worker_id", get_user_id() );
		if ( ! $user_id ) {
			throw new Exception( __FUNCTION__ . ": No user " . $user_id );
		}

		$form_table = GetArg( $args, "form_table", null );
		$events     = GetArg( $args, "events", null );

		$companies      = array( 1 ); // Org_Company::GetCompanies($user_id);
		$companies_list = [];
		foreach ( $companies as $company_id => $company_name ) {
			$companies_list[] = array( "company_id" => $company_id, "company_name" => $company_name );
		}
		$result = Core_Html::gui_select( $id, "company_name", $companies_list, $events, $value, "company_id" );
//	if ($form_table and $new_row) { // die(__FUNCTION__ . ":" . " missing form_table");
//		$result .= Core_Html::GuiButton( "add_new_project", "add_element('project', '" . $form_table . "', '" . GetUrl() . "')", "New Project" );
//	}

		return $result;
	}

	static function gui_select_user( $id = null, $selected = null, $args = null ) {
		// $events = GetArg($args, "events", null);
		$edit = GetArg( $args, "edit", true );

		$args["name"]     = "client_displayname(id)";
		$args["id_key"]   = "id";
		$args["selected"] = $selected;

		if ( $edit ) {
			$gui = GuiAutoList( $id, "users", $args );

			return $gui;
		} else {
			return ( $selected > 0 ) ? sql_query_single_scalar( "select client_displayname(user_id) from wp_users where id = " . $selected ) :
				"";
		}
	}

	static function gui_select_team( $id, $selected = null, $args = null ) {
		$edit             = GetArg( $args, "edit", true );
		$worker           = new Org_Worker( get_user_id() );
		$companies        = $worker->GetCompanies();
		$debug            = false; // (get_user_id() == 1);
		$args["debug"]    = $debug;
		$args["name"]     = "team_name";
		$args["selected"] = $selected;

		// collision between query of the container and the selector.
		$args["query"] = ( isset( $args["query_team"] ) ? $args["query_team"] : null );

		$form_table = GetArg( $args, "form_table", null );

		if ( $edit ) {
			$gui = Core_Html::GuiSelectTable( $id, "im_working_teams", $args );
			$gui .= Core_Html::GuiButton( "add_new_team", "New Team", array(
				"action" => "add_element('team', '" . $form_table . "', '" . GetUrl() . "')",
				"New Team"
			) );

			return $gui;
		} else {
			return ( $selected > 0 ) ? sql_query_single_scalar( "select team_name from im_working_teams where id = " . $selected ) : "";
		}

	}

	static function get_link( $type, $id = 0 ) {
		switch ( $type ) {
			case "task":
				return "/task?id=$id";

			case "project":
				return "/project?new_task=$id";

			case "template":
				return "/template?id=$id";

			case "new_task":
				return AddToUrl( "operation", "show_new_template" );

		}
	}

	function getShortcodes() {
		//             code                           function                  capablity (not checked, for now).
		return ( array(
			'focus_main'           => array( 'Focus_Tasks::show_main', 'show_tasks' ),
			'focus_task'           => array( 'Focus_tasks::show_task', 'show_tasks' ),
			'focus_template'       => array( 'Focus_tasks::show_template', 'show_tasks' ),
			'focus_repeating_task' => array( 'Focus_tasks::show_repeating_task', 'show_tasks' ),
			'focus_team'           => array( 'Focus_tasks::show_team', 'show_teams' ),
			'focus_project'        => array( 'Focus_tasks::show_project', 'show_projects' ),
			'focus_project_tasks'  => array( 'Focus_tasks::show_project_tasks', 'show_projects' )
		) );

	}

	function init() {
		Core_Gem::AddTable( "im_task_templates" );
		AddAction("gem_add_team_members", array(__CLASS__, 'show_edit_team'), 10, 3);
		AddAction("show_edit_team", array(__CLASS__, 'show_edit_team'), 10, 3);

		// Project related actions.
		Core_Gem::AddTable( "im_projects" ); // add + edit
		AddAction("gem_edit_im_projects", array(__CLASS__, 'ShowProjectMembers'), 11, 3);
		AddAction("gem_add_project_members", array(__CLASS__, 'AddProjectMember'), 11, 3);
		AddAction("project_add_member", array(__CLASS__, 'ProjectAddMember'), 11, 3);

		// Company
		AddAction("show_add_company_worker", array(__CLASS__, 'AddCompanyWorker'), 11, 3);
		AddAction("add_worker", array(__CLASS__, 'doAddCompanyWorker'), 11, 3);
	}

	static function DoAddCompanyWorker()
	{
		$user_id = get_user_id();
		if (! $user_id) {
			print "need to connect first";
			return false;
		}
		$User = new Org_Worker($user_id);

		$company_id = GetParam("company_id");
//		var_dump($User->GetCompanies());
//		print "<br/>co=$company_id";
		if (!in_array($company_id, $User->GetCompanies())){
			print "not your company!";
			return false;
		}

		$worker_email = GetParam("worker_email");
		$new_user = get_user_by('email', $worker_email);
		if (! $new_user) {
			$name = strtok($worker_email, "@");
			$new_user_id = wp_create_user($name, $name, $worker_email);
			if (! ($new_user > 0)) {
				var_dump($new_user);
				return false;
			}
		} else
			$new_user_id = $new_user->ID;

		$U = new Org_Worker($new_user_id);
		$U->AddCompany($company_id);

		$message = GetParam("message");
		$company = new Org_Company($company_id);
		return mail($worker_email, "Welcome to Focus!, company " . $company->getName(), $message);
	}

	static function ProjectAddMember()
	{
		$project_id = GetParam("project_id", true);
		$worker_id = GetParam("user", true);
		$project = new Org_Project($project_id);

		return $project->addWorker($worker_id);
	}

	static function AddCompanyWorker($operation)
	{
		$args = [];
		$company_id = GetParam("company", true);
		$result = "";
		$result .= Core_Html::gui_header(1, "New worker!");
		$result .= Core_Html::gui_header(2, "Enter worker email address");
		$result .= Core_Html::GuiInput("worker_email", null, $args) . "<br/>";
		$message = sql_query_single_scalar("select post_content from wp_posts where post_title = 'welcome_message'");
		if (! $message) {
			$message = "Welcome to work with me in Focus management tool!\n" .
			           get_user_name( get_user_id() );
			$result .= "You can create default message as a private post with title welcome_message" . "\n" .
			           Core_Html::GuiHyperlink( "here", "/wp-admin/post-new.php" );
		}
		else {
			$message =strip_tags($message);
			$post_id = sql_query_single_scalar("select id from wp_posts where post_title = 'welcome_message'");
			$result .= Core_Html::GuiHyperlink("Edit this message here","/wp-admin/post.php?post=$post_id&action=edit");
		}
		$result .= Core_Html::gui_textarea("welcome_message", $message);
		$result .= Core_Html::GuiButton("btn_add_worker", "Add",
			array("action" => 'company_add_worker(' . QuoteText(self::getPost()) . "," . $company_id . ')'));

		return $result;
	}

	static function ShowProjectMembers($i, $id, $args)
	{
		$u = new Org_Project($id);
		$result = Core_Html::gui_header(1, $u->getName());
		$result .= self::doShowProjectMembers($id);
		$result .= Core_html::gui_header(2, "Add member");
		$result .= gui_select_worker("new_worker", null, $args);
		$result .= Core_Html::GuiButton("btn_add_worker", "Add",
			array("action" => 'project_add_worker(' . QuoteText(self::getPost()) . "," . $id . ')'));
		return $result;
	}

//	static public function AddTeamMember($i, $team_id, $args)
//	{
//		$args = self::Args();
//		$team = new Org_Team($team_id);
//		$result = Core_Html::gui_header(1, $team->getName());
//		$result .= self::gui_select_worker("new_worker", null, $args);
//		$args["action"] = "team_add_worker('" . self::getPost() ."', " .$team_id . ")";
//
//		 $result .= Core_Html::GuiButton("btn_add_worker", "Add", $args);
////		$table = array();
////		foreach ($project->all_members() as $member) {
////			$w = new Org_Worker($member);
////			$table[$member] = array("name" => $w->getName());
////		}
////		$result .= Core_Gem::GemArray($table, $args, "project_members");
//
//		return $result;
//	}

	static public function AddProjectMember($i, $project_id, $args)
	{
		$args = self::Args();
		$project = new Org_Project($project_id);
		$result = Core_Html::gui_header(1, $project->getName());
		$result .= self::gui_select_worker("new_worker", null, $args);
		$args["action"] = "project_add_worker('" . self::getPost() ."', " .$project_id . ")";

		$result .= Core_Html::GuiButton("btn_add_worker", "Add", $args);
//		$table = array();
//		foreach ($project->all_members() as $member) {
//			$w = new Org_Worker($member);
//			$table[$member] = array("name" => $w->getName());
//		}
//		$result .= Core_Gem::GemArray($table, $args, "project_members");

		return $result;
	}

	static public function doShowProjectMembers($project_id)
	{
		$args = self::Args("im_");
		$args["post_file"] .= "?team_id=" . $project_id;
		$project = new Org_Project($project_id);

		$result = "";
//		$result            = Core_Html::gui_header( 1, "Edit project" );
//		$args["selectors"] = array( "manager" => "Focus_Tasks::gui_select_worker" );
		// $args["post_file"] = GetUrl( 1 ) . "?team_id=" . $team_id;
//		$result            .= Core_Gem::GemElement( "im_working_teams", $project_id, $args );
//
		$result          .= Core_Html::gui_header( 2, "Project members" );
		$table           = array();
		$table["header"] = array( "name" );
		$members = $project->all_members();
		foreach ($members as $member)
		{
			$table[ $member ]["name"] = get_user_name( $member );
		}

		$args["add_checkbox"] = true;
		$args["edit"] = true;
		$result               .= Core_Gem::GemArray( $table, $args, "project_members" );
//
//		$result .= Core_Html::gui_header( 1, "add member" );
//		$result .= gui_select_worker( "new_member", null, $args );
//		$result .= Core_Html::GuiButton( "btn_add_member", "add_project_member(" . $project_id . ")", "add" );
//
//		$args = self::Args();

		//GemTable("im_");

		return $result;

	}
}

/**
 * TODO: change action to be array(class_name, method_name);
 * till then using functions and not methods.
 *
 * @param $id
 * @param $value
 * @param $args
 *
 * @return mixed|string
 */
if ( ! function_exists( 'gui_select_repeat_time' ) ) {
	function gui_select_repeat_time( $id, $value, $args ) {
//	print "v=" . $value . "<br/>";

		$edit   = GetArg( $args, "edit", false );
		$events = GetArg( $args, "events", null );
		$values = array( "w - weekly", "j - monthly", "z - annual", "c - continuous" );

		$selected = 1;
		for ( $i = 0; $i < count( $values ); $i ++ ) {
			if ( substr( $values[ $i ], 0, 1 ) == substr( $value, 0, 1 ) ) {
				$selected = $i;
			}
		}

		// return gui_select( $id, null, $values, $events, $selected );
		if ( $edit ) {
			return Core_Html::gui_simple_select( $id, $values, $events, $selected );
		} else {
			return $values[ $selected ];
		}
	}

}

// Allow later users to set page name.
// For now just the default.

function gui_select_worker( $id = null, $selected = null, $args = null ) {
	return Focus_Tasks::gui_select_worker( $id, $selected, $args );
}

function gui_select_project( $id, $value, $args ) {
	return Focus_Tasks::gui_select_project( $id, $value, $args );
}

/*
 * 	static function show_new_task( $mission = false, $new_task_id = null ) {
		die(1);
		$args                     = array();
		$args["selectors"]        = array(
			"project_id" => "Focus_Tasks::gui_select_project",
			"owner"      => "Focus_Tasks::gui_select_worker",
			"creator"    => "Focus_Tasks::gui_select_worker",
			"preq"       => "gui_select_task",
			"team"       => "Focus_Tasks::gui_select_team"
		);
		$args["values"]           = array( "owner" => get_user_id(), "creator" => get_user_id() );
		$args["header"]           = true;
		$args["header_fields"]    = array(
			"date"             => "Start after",
			"task_description" => "Task description",
			"project_id"       => "Project",
			"location_address" => "Address",
			"location_name"    => "Location name",
			"priority"         => "Priority",
			"preq"             => "Prerequisite",
			"creator"          => "Creator"
		);
		$args["mandatory_fields"] = array( "project_id", "priority", "team", "task_description" );

		$args["fields"]     = array( "task_description", "project_id", "priority", "date", "preq", "creator", "team" );
		$args['post_file']  = "/wp-content/plugins/focus/post.php";
		$args['form_table'] = 'im_tasklist';

		// Todo: check last update time
		if ( $mission and function_exists( "gui_select_mission" ) ) {
			array_push( $args["fields"], "location_name", "location_address", "mission_id" );
			$i = new Core_Db_MultiSite();
			$i->UpdateFromRemote( "im_missions", "id", 0, null, null );
			$args["selectors"]["mission_id"]              = "gui_select_mission";
			$args["header_fields"]["mission_id"]          = "Mission";
			$args["mandatory_fields"]["location_name"]    = true;
			$args["mandatory_fields"]["location_address"] = true;
		}

		$args["worker"]    = get_user_id();
		$result        = "";
		$result .= "w=" . $args["worker"];
		$args["companies"] = sql_query_single_scalar( "select company_id from im_working where user_id = " . get_user_id() );
		$args["hide_cols"] = array( "creator" => 1 );
		$args["next_page"] = self::get_link( "project" );
		Core_Data::set_args_value( $args ); // Get values from url.

		$project_tasks = "";
		if ( $new_task_id ) {
			$result .= im_translate( "Task added" ) . "<br/>";
		}

		if ( $new_task_id ) {
			$project_args          = $args;
			$new_task              = new Focus_Tasklist( $new_task_id );
			$project_id            = $new_task->getProject();
			$project_args["title"] = "Project " . Org_Project::GetName( $project_id );
			$project_args["query"] = "project_id=" . $project_id . " and status < 2";
			$project_args["order"] = "id desc";
			unset( $project_args["fields"] );
			$project_args["page"] = 1;

			$project_tasks = Core_Gem::GemTable( "im_tasklist", $project_args );

			// Set default value for next task, based on new one.
			$args["values"] = array( "project_id" => $project_id, "team" => $new_task->getTeam() );
		}

		$result .= Core_Gem::GemAddRow( "im_tasklist", "New task", $args );
		$result .= $project_tasks;

		return $result;
	}

			case "show_new_team":
				$args                     = [];
				$args["next_page"]        = GetParam( "next_page", false, null );
				$args["post_file"]        = "/wp-content/plugins/focus/post.php";
				$args["selectors"]        = array( "manager" => "Focus_Tasks::gui_select_worker" );
				$args["mandatory_fields"] = array( "manager", "team_name" );

				return Core_Gem::GemAddRow( "im_working_teams", "Add a team", $args );

			case "show_new_task":
				$mission = GetParam( "mission", false, null );
				$new     = GetParam( "new", false );

				return self::show_new_task( $mission, $new ); // after the first task, the new tasks belongs to the new tasks' project will be displayed.

 */