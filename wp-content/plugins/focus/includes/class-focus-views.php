<?php

require_once (FOCUS_INCLUDES . 'gui.php');

class Focus_Views {
	private $post_file;
	private $version;
	protected static $_instance = null;
	protected $nav_menu_name;

	/**
	 * Focus_Views constructor.
	 *
	 * @param $post_file
	 */
	public function __construct( $post_file ) {
		$this->post_file = $post_file;
		$this->version = "1.0";
		add_action( 'get_header', array( $this, 'create_nav' ) );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "/wp-content/plugins/focus/post.php" ); // Todo: fix this
		}

		return self::$_instance;
	}

	public static function focus_operation() {
		$operation = get_param( "operation", false, "focus_main" );
		if ( get_user_id( true ) ) {
			print Focus::instance()->handle_operation( $operation );
		}
	}

	public function enqueue_scripts() {
		$file = plugin_dir_url( __FILE__ ) . 'org/people/people.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = plugin_dir_url( __FILE__ ) . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

	}

	/**
	 * @param $operation
	 * @param $args
	 *
	 * @return void
	 * @throws Exception
	 */
	static function handle_focus_show( $operation, $args = null ) {
		if ( ( $done = Focus_Views::handle_focus_do( $operation, $args ) ) !== "not handled" ) {
			return $done;
		}

		// Actions are performed and return to caller.
		// Page are $result .= and displayed in the end. (to handle the header just once);
		$action_url = get_url( 1 );
		$result     = ""; // focus_header($header_args);


		$args["page"] = get_param( "page", false, 1 );

		$debug = 0;
		if ( $debug ) {
			print "operation: " . $operation . "<br/>";
		}
		// show/save <obj_type>
		switch ( $operation ) {
			case "show_settings":
				$result .= show_settings( get_user_id() );
				break;
			case "focus_main":
				// $new = get_param("new", false, null);
				$id = get_user_id();
				if ( ! $id > 0 ) {
					$result .= " No user ";
				} else {
					$result .= self::focus_main( $id, $args );
				}
				break;
			case "edit_organization":
				$result .= edit_organization();
				break;
			case "show_worker":
				// $new = get_param("new", false, null);
				$id                     = get_param( "id", true );
				$header_args["view_as"] = $id;
				$result                 .= self::focus_main( $id, $args );
				break;
			case "show_repeating_tasks":
			case "show_templates":
				$args["table"] = true;
				$args["new"]   = get_param( "new", false, 0 );
				$new           = get_param( "new", false, null );
				$freq          = get_param( "freq", false, null );
				$args["query"] = "repeat_freq like '%$freq%'";
				$result        .= show_templates( $args, null, $new );
				break;
			case "show_template":
				$id     = get_param( "id", true );
				$result .= show_templates( $args, $id );
				break;
			case "show_task":
				$id = get_param( "id", true );
				if ( $id ) {
					$result .= self::show_task( $id );
				}
				break;
			case "show_projects":
				$result .= self::show_projects( get_url(), get_user_id() );
				break;

			case "show_project":
				$id           = get_param( "project_id", true );
				$args         = [];
				$args["edit"] = get_param( "edit", false, false );
				if ( $id ) {
					$result .= self::show_project( $id, $args );
				}
				break;
			case "bad_url":
				$id          = get_param( "id" );
				$result      .= "Url for task $id is wrong<br/>";
				$template_id = task_template( $id );
				$result      .= gui_hyperlink( "Edit template $template_id", "?operation=show_template&id=$template_id" );
				break;
			case "show_new_project":
				$args              = [];
				$args["next_page"] = get_param( "next_page", false, null );
				$args["post_file"] = "/core/data/data-post.php";
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
				$result                   .= GemAddRow( "im_projects", "Add a project", $args );
				break;
			case "show_edit_project":
				$args              = [];
				$project_id        = get_param( "id", true );
				$args["edit_cols"] = array(
					"project_name"     => true,
					"project_contact"  => true,
					"project_priority" => true
				);
				$args["post_file"] = get_url( 1 );
				// TODO: bug fix... $args["selectors"] = array("company" => "company_get_name");
				$result         .= GemElement( "im_projects", $project_id, $args );
				$args ["query"] = "project_id = $project_id and status < 2";
				$args["page"]   = get_param( "page", false, null );
				$args["links"]  = array( "id" => add_to_url( array( "operation" => "show_task", "id" => "%s" ) ) );
				$result         .= GemTable( "im_tasklist", $args );
				break;
			case "show_new_team":
				$args                     = [];
				$args["next_page"]        = get_param( "next_page", false, null );
				$args["post_file"]        = "/core/data/data-post.php";
				$args["selectors"]        = array( "manager" => "gui_select_worker" );
				$args["mandatory_fields"] = array( "manager", "team_name" );
				$result                   .= GemAddRow( "im_working_teams", "Add a team", $args );
				break;
			case "show_new_task":
				$mission = get_param( "mission", false, null );
				$new     = get_param( "new", false );
				$result  .= focus_new_task( $mission, $new ); // after the first task, the new tasks belongs to the new tasks' project will be displayed.
				break;
			case "last_entered":
				if ( get_user_id() != 1 ) {
					return;
				}
				$args                 = array();
				$args["last_entered"] = 1;
				$result               .= Focus_Views::active_tasks( $args );
				break;
			case "show_new_sequence":
				$args = array();
//			$args["selectors"] = $task_selectors;
//			$args["transpose"] = true;
//			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());

				$result         .= gui_header( 1, "New sequence" );
				$result         .= gui_label( "explain", "Select the project of the sequence, the default priority of all sequence tasks. Enter text for the tasks" );
				$args["worker"] = get_user_id();
				$args["edit"]   = true;
				$table_rows     = array(
					array( "project", gui_select_project( "project", null, $args ) ),
					array( "priority", GuiInput( "priority", null, $args ) ),
					array( "task1", GuiInput( "task1" ) ),
					array( "task2", GuiInput( "task2", null, array( "events" => 'onchange="addSequenceTask(2)"' ) ) )
				);
				$result         .= gui_table_args( $table_rows, "sequence_table" );

				// $args["debug"] = true;
				// print NewRow("im_tasklist", $args);
				$result .= gui_button( "btn_new_sequence", "save_new_sequence()", "Create" );
				break;

			case "new_template":
				$result                   .= gui_header( 1, "יצירת תבנית חדשה" );
				$args                     = array();
				$args["selectors"]        = array(
					"project_id"  => "gui_select_project",
					"owner"       => "gui_select_worker",
					"creator"     => "gui_select_worker",
					"team"        => "gui_select_team",
					"repeat_freq" => "gui_select_repeat_time"
				);
				$args["transpose"]        = true;
				$args["worker"]           = get_user_id();
				$args["companies"]        = Org_Worker::GetCompanies( get_user_id() );
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
				$result                   .= NewRow( "im_task_templates", $args );
				$result                   .= gui_button( "btn_template", "data_save_new('/focus/focus-post.php', 'im_task_templates')", "add" );
				break;

			case "show_staff": // Teams that I manage
//			$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js", "/core/data/focus.js" ) );
				$result .= gui_header( 1, "Edit staff" );
				$result .= show_staff();
				break;

			case "show_edit_projects": // Projects that I manage
//			$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js", "/core/data/focus.js" ) );
				$result            .= gui_header( 1, "Edit projects" );
				$args["worker_id"] = get_user_id();
				$result            .= edit_projects( $args );
				break;

			case "show_edit_all_projects": // Projects that I manage
				if ( ! im_user_can( "edit_projects" ) ) {
					die( "no permissions" );
				}
				$result         .= gui_header( 1, "Edit all projects" );
				$args["global"] = true;
				$result         .= edit_projects( $args );
				break;

			case "new_team":
//			$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js", "/core/data/focus.js" ) );
				$result .= gui_header( 1, "Add Team" );
				$args   = array( "selectors" => array( "manager" => "gui_select_worker" ) );
				$result .= NewRow( "im_working_teams", $args );
				$result .= gui_button( "btn_newteam", "save_new('im_working_teams')", "add" );
				break;

			case "remove_from_team":
				$team_id   = get_param( "id", true );
				$worker_id = get_param( "user", true );
				team_remove_member( $team_id, $worker_id );
				handle_focus_operation( "show_team", null );
				break;

			case "show_team":
				$team_id = get_param( "team_id", true );
				print self::show_team( $team_id );
				break;

			case "show_add_member":
				$team_id = get_param( "id", true );
				$result  .= gui_header( 1, "Adding memeber to team" . sql_query_single_scalar( "select team_name from im_working_teams where id = " . $team_id ) );
				$result  .= gui_select_worker( "new_member" );
				$result  .= gui_label( "team_id", $team_id, true );
				$result  .= gui_button( "btn_add_member", "add_member()", "Add" );

				$result .= "<br/>";
				$result .= gui_hyperlink( "Invite college to your company", add_to_url( array( "operation" => "show_add_to_company" ) ) );
				break;

			case "show_add_to_company":
				$company_id = get_param( "id", true );
				$result     .= gui_header( 2, "Invite to company" ) . " " . gui_label( "company_id", $company_id );
				$result     .= im_translate( "Enter college email address: " );
				$result     .= gui_table_args( array(
					array( "email", GuiInput( "email", "", $args ) ),
					array( "name", GuiInput( "name", "", $args ) ),
					array( "project", gui_select_project( "project_id", null, $args ) )
				) );
				$result     .= gui_button( "btn_add_to_company", "add_to_company()", "Add" );
				break;

			case "projects":
				$result .= header_text( false, true, true, array( "/core/gui/client_tools.js", "/core/data/data.js" ) );

				if ( $id = get_param( "project_id" ) ) {
					$result .= show_project( $id );
				}
				$result .= show_projects( get_url(), get_user_id() );
				break;

			case "task_types":
				$args          = array();
				$args["title"] = "task types";
				$result        .= GemTable( "im_task_type", $args );
				break;

			case "new_company_user":
				$company_id = data_save_new( "im_company" );
				//			$worker_id = worker_get_id(get_user_id());
				$sql = "update im_working set company_id = " . $company_id . " where user_id = " . get_user_id();
				sql_query( $sql );

				$result .= "done";
				break;

			case "show_edit_all_teams": // System manager -> edit all teams in the system.
				if ( ! im_user_can( "edit_teams" ) ) {
					$result .= "No permissions";
				}
				$args              = [];
				$args["post_file"] = "/core/data/data-post.php";
				$args["selectors"] = array( "manager" => "gui_select_worker" );
				$args["links"]     = array(
					"id" => add_to_url( array(
						"operation" => "show_edit_team",
						"id"        => "%s"
					) )
				);
				$args["page"]      = get_param( "page" );
//			$result .=  "url = " . get_url() . "<br/>";
//			$result .=  add_to_url(array("operation" => "del_team", "id"=>"%s"));

				$args["actions"] = array(
					array(
						"delete",
						$action_url . "?operation=del_team&id=%s;action_hide_row"
					)
				);
				$result          .= gui_header( 1, "All system teams" );
				$result          .= GemTable( "im_working_teams", $args );

				unset( $args["actions"] );
				$args["mandatory_fields"] = array( "manager", "team_name" );
				$result                   .= GemAddRow( "im_working_teams", "Add a team", $args );
				break;

			case "show_edit_team":
				$team_id = get_param( "id" );
				$result  .= show_edit_team( $team_id );
				break;

			case "show_tasks":
				$query  = data_parse_get( "im_tasklist", array( "operation" ) );
				$ids    = data_search( "im_tasklist", $query );
				$result .= self::show_tasks( $ids );
				// debug_var($query);
				break;

			case "show_edit_company":
				$company_id = get_param( "company_id", true );
				$page       = get_param( "page", false, 1 );
				$result     .= show_edit_company( $company_id, $page );
				break;

			default:
				print __FUNCTION__ . ": " . $operation . " not handled <br/>";

				die( 1 );
		}
		print $result;

		return;
	}

	/**
	 * @param $url
	 * @param $owner
	 * @param bool $non_zero
	 *
	 * @return string|null
	 * @throws Exception
	 */
	static function show_projects( $url, $owner, $non_zero = false, $is_active = true ) {
		$links = array();

		$links["id"] = add_to_url( array(
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

			return GemElement( "im_projects", $project_id, $args );
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
		$args["links"] = array( "id" => add_to_url( array( "operation" => "show_task", "id" => "%s" ) ) );
		$args["title"] = im_translate( "משימות בפרויקט" ) . " " . get_project_name( $project_id );

//	print $sql;
		$result = GemTable( "im_tasklist", $args );
		$result .= GuiHyperlink( "Edit project", add_to_url( "edit", 1 ) );

		return $result;
	}


	static function handle_focus_do( $operation ) {
		$allowed_tables         = array( "im_company", "im_tasklist", "im_task_templates", "im_projects", "im_working" );
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
				$team_id = get_param( "id", true );
				if ( team_delete( $team_id ) ) {
					print "done";
				}

				return;

			case "cancel_im_task_templates":
				$id = get_param( "id", true );
				if ( data_delete( "im_task_templates", $id ) ) {
					print "done";
				}

				return;

			case "delete":
				$type = get_param( "type" );
				switch ( $type ) {
					case "team_members":
						$team_id = get_param( "team_id", true );
						$ids     = get_param( "ids", true );
						if ( team_remove_member( $team_id, $ids ) ) {
							print "done";
						}
						print "failed";

						return;
				}

				return;

			case "add_team_member":
				$team_id    = get_param( "team_id", true );
				$new_member = get_param( "new_member", true );
				if ( team_add_worker( $team_id, $new_member ) ) {
					print "done";
				}

				return;

			case "end_task":
				$task_id = get_param( "id" );
				if ($task_id > 0) {
					$t = new Focus_Tasklist($task_id);
					$t->Ended();

					Focus_Tasklist::create_tasks( null, false );
				}
				return;

			case "cancel_task":
				$task_id = get_param( "id" );
				if ( task_cancelled( $task_id ) ) {
					print "done";
				}
				create_tasks( null, false );

				return;

			case "create_tasks":
				print create_tasks( null, true );

				return;

			case "postpone_task":
				$task_id = get_param( "id" );
				$T       = new Focus_Tasklist( $task_id );
				$r       = $T->Postpone();
				create_tasks( null, false );
				if ( $r ) {
					print "done";
				}

				return;

			case "pri_plus_task":
				$task_id = get_param( "id" );
				$T       = new Focus_Tasklist( $task_id );
				$T->setPriority( $T->getPriority() + 1 );
				create_tasks( null, false );
				print "done";

				return;

			case "pri_minus_task":
				$task_id = get_param( "id" );
				$T       = new Focus_Tasklist( $task_id );
				$T->setPriority( $T->getPriority() - 1 );
				create_tasks( null, false );
				print "done";

				return;

			case "save_new":
				$table_name = get_param( "table_name", true );
				if ( ! in_array( $table_name, $allowed_tables ) ) {
					die ( "invalid table operation" );
				}
				$result = data_save_new( $table_name );
				if ( $result > 0 ) {
					print "done." . $result;
				}

				return;

			case "update":
				$table_name = get_param( "table_name", true );
				if ( ! in_array( $table_name, $allowed_tables ) ) {
					die ( "invalid table operation" );
				}
				if ( update_data( $table_name ) ) {
					if ( $table_name == 'im_task_templates' ) {
						$row_id = intval( get_param( "id", true ) );
						sql_query( "update im_task_templates set last_check = null where id = " . $row_id );
					}
					print "done";
				}

				return;

			case "delete_template":
				$user_id = get_user_id();
				$id      = get_param( "row_id", true );
				if ( template_delete( $user_id, $id ) ) {
					print "done";
				}

				return;

			case "start_task":
				// a. set the start time, if not set.
				$task_id = get_param( "id" );
				task_started( $task_id, get_user_id() );

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
				$url = task_url( $task_id );
				if ( ! $url ) {
					$url = get_url( 1 );
				}
				$url_headers = @get_headers( $url );
				if ( ! $url_headers || strstr( $url_headers[0], "404" ) ) {
					print get_url( 1 ) . "?operation=bad_url&id=" . $task_id;

					return;
				}
				if ( strlen( $url ) > 1 ) {
					print $url;
				}

				return;

			case "add_to_company":
				$company_id = get_param( "company_id", true );
				$email      = get_param( "email", true );
				$name       = get_param( "name", true );
				$project_id = get_param( "project_id", true );
				if ( company_invite_member( $company_id, $email, $name, $project_id ) ) {
					print "done";
				}

				return;

			case "save_add_member":
				$member  = get_param( "member", true );
				$team_id = get_param( "team", true );
				$current = get_usermeta( $member, "teams" );
				if ( ! $current ) {
					$current = ":";
				}
				update_usermeta( $member, "teams", ":" . $team_id . $current ); // should be :11:22:3:4:
				print "done";

				return;

			case "logout":
				wp_logout();
				$back = get_param( "back", false, get_url( 1 ) );
				header( "location: " . $back );

				return;

			case "cancel_im_projects":
				$id = get_param( "id", true );
				if ( project_delete( $id, get_user_id() ) ) {
					print "done";
				}

				return;

			case "search_by_text":
				$text = get_param( "text", true );

				return search_by_text( $text );
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
	static function focus_main( $user_id, $args = null ) {
		$o             = new Org_Team(); // To invoke auto_load;
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
			foreach ( $teams as $team ) {
				foreach ( Org_Team::team_all_members( $team ) as $worker_id ) {
					$workers[ $worker_id ] = 1;
				}
				$count  = 0; // active_task_count("team_id = " . $team);
				$result .= gui_hyperlink( Org_Team::team_get_name( $team ) . "(" . $count . ")", "?operation=show_team&id=" . $team );
			}

			$result .= "<br/>";
			if ( $workers ) {
				foreach ( $workers as $worker_id => $c ) {
					$count = 0;

					$result .= GuiHyperlink( get_user_name( $worker_id ) . "(" . $count . ")", '?operation=show_worker&id=' . $worker_id ) . " ";
				}
			}
			$result .= "<br/>";
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I need to handle (owner = me)                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"]       = im_translate( "Tasks assigned to me" );
		$args["query"]       = " owner = " . $user_id;
		$args["limit"]       = get_param( "limit", false, 10 );
		$args["active_only"] = get_param( "active_only", false, true );

		foreach ( $_GET as $param => $value ) {
			if ( ! in_array( $param, $ignore_list ) ) {
				$args[ $param ] = $value;
			}
		}
		$table = self::active_tasks( $args );
		if ( $args["count"] ) {
			$result .= $table;
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks of teams I manage. Not assigned to me                                                               //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = im_translate( "Teams I manage tasks" );
		$teams         = Org_Team::team_managed_teams( $user_id );
		// print "teams: " . comma_implode($teams) . "<br/>";
		$args["fields"][]          = "team";
		$args["selectors"]["team"] = "gui_select_team";
		if ( $teams and count( $teams ) ) {
			$args["query"] = " team in (" . comma_implode( $teams ) . ") and owner != " . $user_id;
			$result        .= Focus_Views::active_tasks( $args );
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks teams I'm a member of (team in my_teams). Not assigned                                              //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = im_translate( "My teams tasks" );
		$teams         = Org_Worker::GetTeams( $user_id );
		// print "teams: " . comma_implode($teams) . "<br/>";
		$args["fields"][]          = "team";
		$args["selectors"]["team"] = "gui_select_team";
		if ( $teams and count( $teams ) ) {
			$args["query"] = " team in (" . comma_implode( $teams ) . ") and owner is null";
			$result        .= Focus_Views::active_tasks( $args );
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I've created. Assigned to some else                                                                 //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"]       = im_translate( "Tasks I've initiated to other teams" );
		$args["query"]       = " creator = " . $user_id . " and (owner != " . $user_id . ' or isnull(owner)) and team not in (' . comma_implode( $teams ) . ")";
		$args["limit"]       = get_param( "limit", false, 10 );
		$args["active_only"] = get_param( "active_only", false, true );
		$result              .= Focus_Views::active_tasks( $args );

		//print "c=" . $args["count"];
		if ( ! $args["count"] ) {
			$result .= im_translate( "No active tasks!" ) . "<br/>";
			$result .= im_translate( "Let's create first one!" ) . " ";
			$result .= gui_hyperlink( "create task", "?operation=show_new_task" ) . "<br/>";
		}

		// if (get_user_id() != 1) return;

		return $result;
	}

	static function active_tasks( &$args = null ) {
		$args["count"]           = 0;
		$args["drill"]           = true;
		$args["drill_operation"] = "show_tasks";

		$table_name = "im_tasklist";
		$title      = GetArg( $args, "title", "" );

		$action_url = get_url( 1 );
		$page_url   = get_url( true );

		$active_only = GetArg( $args, "active_only", true );
		if ( $active_only ) {
			$title .= " (" . im_translate( "active only" ) . ")";
		}

		if ( ! isset( $args["fields"] ) ) {
			$args["fields"] = array( "id", "task_description", "project_id", "priority", "task_template" );
		}

		$limit = get_param( "limit", false, 10 );

		if ( get_param( "offset" ) ) {
			$limit .= " offset " . get_param( "offset" );
		}

		$links = array();

		$query = "where 1 ";
		if ( GetArg( $args, "query", null ) ) {
			$query .= " and " . GetArg( $args, "query", null );
		}

		$project_id = GetArg( $args, "project_id", null );
		if ( $project_id ) {
			$title = im_translate( "Project" ) . " " . get_project_name( $project_id );
			if ( $f = array_search( "project_id", $args["fields"] ) ) {
				unset( $args["fields"][ $f ] );
			}
			$query .= " and project_id = $project_id";
		}

		if ( ! isset( $args["selectors"] ) ) {
			$args["selectors"] = array(
				"project"    => "gui_select_project",
				"project_id" => "gui_select_project",
				"owner"      => "gui_select_worker"
			);
		}

		$query .= " and status < 2 ";
		if ( $active_only ) {
			$query .= " and (isnull(preq) or preq_done(id)) and (date is null or date(date) <= Curdate())";
			$query .= " and (mission_id is null or mission_id = 0) ";
		}

		// New... the first part is action to server. If it replies with done, the second part is executed in the client (usually hiding the row).
		$actions = array(
			array( "start", $action_url . "?operation=start_task&id=%s;load_page" ),
			array( "finished", $action_url . "?operation=end_task&id=%s;action_hide_row" ),
			array( "cancel", $action_url . "?operation=cancel_task&id=%s;action_hide_row" ),
			array( "postpone", $action_url . "?operation=postpone_task&id=%s;action_hide_row" ),
			array( "pri_plus", $action_url . "?operation=pri_plus_task&id=%s" ),
			array( "pri_minus", $action_url . "?operation=pri_minus_task&id=%s;action_hide_row" )

		);
		$order   = "order by priority desc ";

		$links["task_template"] = $page_url . "?operation=show_template&id=%s";
		$links["id"]            = $page_url . "?operation=show_task&id=%s";
		// Use drill, instead - $links["project_id"] = $page_url . "?operation=show_project&id=%s";
		$args["links"] = $links;

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

//	$more_fields = "";

//	if ($debug and ! $time)
//		$more_fields .= ", task_template_time(id) ";

		$sql = "select " . comma_implode( $fields ) . " from $table_name $query $order ";

		$result = "";
		try {
			if ( isset( $_GET["debug"] ) ) {
				print "sql = $sql<br/>";
			}
			$args["sql"] = $sql;
			$table       = GemTable( "im_tasklist", $args );
//		print "CC=" . $args["count"] . "<br/>";
			// $table = GuiTableContent( $table_name, $sql, $args );
			// if (! $args["count"]) return "";
			if ( $table ) {
				// if (strlen($title)) $result = gui_header(2, $title);
				$result .= $table;
			}
		} catch ( Exception $e ) {
			print "can't load tasks." . $e->getMessage();

			return null;
		}
		$count = $args["count"];
		$page  = get_param( "page", false, 1 );
		if ( $count === $page ) {
			// $args["page"] = $page;
			$result .= gui_hyperlink( "More", add_to_url( "page", $page + 1 ) ) . " ";
			$result .= gui_hyperlink( "Not paged", add_to_url( "page", - 1 ) ) . " "; // All pages
		}
		$result .= gui_hyperlink( "Not filtered", add_to_url( "active_only", 0 ) ); // Not filtered

		$result .= " " . gui_hyperlink( "Add task", add_to_url( "operation", "show_new_task" ) );

		$result .= " " . gui_hyperlink( "Add delivery", add_to_url( "operation", "show_new_task&mission=1" ) );

		return $result;
	}

	/**
	 * @param $team_id
	 * @param $active_only
	 *
	 * @return string
	 */
	static function show_team( $team_id, $active_only = true ) {
		$result = "";
		$result .= gui_header( 1, "Team " . Org_Team::team_get_name( $team_id ) );
		$result .= gui_hyperlink( "Include non active", add_to_url( "active_only", 0 ) );

		// $team_members = team_members($team_id);

//		$result .=  gui_header(2, get_customer_name($user_id) . " " . $user_id);
		$args           = array( "active_only" => $active_only );
		$args["query"]  = " team=" . $team_id;
		$args["fields"] = array( "id", "task_description", "project_id", "priority", "task_template", "owner" );
		$result         .= Focus_Views::active_tasks( $args );

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
		$table_name  = "im_tasklist";
		$entity_name = "task";

		// print gui_header( 1, $entity_name . " " . $row_id );
		$args              = array();
		$args["edit"]      = $edit;
		$args["selectors"] = array(
			"project_id" => "gui_select_project",
			"owner"      => "gui_select_worker",
			"creator"    => "gui_select_worker",
			"preq"       => "gui_select_task",
			"mission_id" => "gui_select_mission",
			"team"       => "gui_select_team"
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

		$args["worker"]    = get_user_id();
		$args["companies"] = Org_Worker::GetCompanies( get_user_id() );
		$args["debug"]     = 0; // get_user_id() == 1;
		$args["worker"]    = get_user_id();
		$args["post_file"] = self::instance()->post_file;

		return GemElement( $table_name, $row_id, $args );
	}

	function get_nav_name() {
		return $this->nav_menu_name;
	}

	function create_nav() {
		$user_id = get_user_id();
		if (! $user_id) return;

		$this->nav_menu_name = "management." . $user_id;

		Focus_Nav::instance()->create_nav($this->nav_menu_name, $user_id);
	}

	function get_nav()
	{
		return Focus_Nav::instance()->get_nav();
	}
//if ($menu_nav) $menu_nav_id = $menu_nav->term_id;

}
