<?php

class Focus_Views {
	private $result_count;
	function init_hooks( Core_Hook_Handler &$loader ) {
		$loader->AddFilter( 'gem_next_page_tasklist', $this );
		$loader->AddFilter( 'gem_next_page_projects', $this );
		$loader->AddFilter( 'search_by_text', $this, 'search_by_text_wrap' );
		$loader->AddFilter( 'data_save_new_projects', $this, 'DataSaveNewDefault', 11, 1 );
		$loader->AddFilter( 'data_save_new_working_teams', $this, 'DataSaveNewTeam', 11, 1 );
		$loader->AddFilter( 'data_save_new_tasklist', $this, 'DataSaveNewTaskList', 11, 1 );
		$loader->AddFilter("data_save_new_after_company", $this, null, 10, 2);

        $loader->AddFilter( 'data_save_new_company', $this, 'DataSaveNewCompany', 11, 1 );
		$loader->AddAction( 'add_worker', $this, 'DoAddCompanyWorker', 11, 3 );
		$loader->AddAction( "tasklist_worker", $this, "show_worker_wrapper", 10, 2 );
		$loader->AddAction('wp_enqueue_scripts', $this, 'enqueue_scripts');
		$loader->AddFilter("gem_next_page_tasklist", $this, "next_page_tasklist");

		$loader->AddAction('focus_edit_project', $this);
		//	Function not found:
		//	$loader->AddFilter("data_update_prepare_tasklist", $this, "data_update_prepare_tasklist", 10, 2);
	}

	private $post_file;
	protected static $_instance = null;
	protected $nav_menu_name;
	private $table_prefix;
	private $options;

	/**
	 * @return mixed
	 */
	public function getOption($option) {
		if (isset($this->options[$option])) return $this->options[$option];
		return false;
	}

	/**
	 * Focus_Views constructor.
	 *
	 * @param $post_file
	 */
	public function __construct( $post_file ) {
//		debug_print_backtrace();
		$this->post_file     = $post_file;
		$this->nav_menu_name = null;
		$this->table_prefix  = GetTablePrefix();
		$this->focus_users   = new Focus_Users_Management();
		if ( TableExists( "missions" ) ) {
			$options["missions"] = true;
		}
	}

	static function OptionEnabled( $option ) {
		return self::instance()->getOption($option);
	}

	public static function instance( $post = null ): ?Focus_Views {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $post );
		}

		return self::$_instance;
	}

	public function admin_scripts() {
		$version = Focus::instance()->version;
		print "<script>let focus_post_url = \"" . self::getPost() . "\"; </script>";

		// Should be loaded by flavor
//		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
//		wp_enqueue_script( 'data', $file, null, $version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $version, false );

		$file = FOCUS_INCLUDES_URL . 'focus.js';
		wp_enqueue_script( 'focus', $file, null, $version, false );

		$file = FOCUS_INCLUDES_URL . '/org/company.js';
		wp_enqueue_script( 'company', $file, null, $version, false );

		// <script src="https://kit.fontawesome.com/f666c528bc.js" crossorigin="anonymous"></script>
		$awesome = "https://kit.fontawesome.com/f666c528bc.js";
		wp_enqueue_script( 'awesome', $awesome, null, $version, false );
	}

	static function focus_main_wrapper() {
		$user_id = get_user_id();
		if ( ! $user_id ) {
			return "unauth";
		}

		$operation  = GetParam( "operation", false, null );
//		// Todo: move all processing to filter.
//		$id = GetParam( "id", false, null );

//		if (strstr($operation, "add"))
//		{
//			$args["edit"] = true;
//			unset_by_value($args["fields"], "created");
//		}
//		$args["operation"] = $operation;
//
//		print "op=$operation";
		if ($operation) {
			$table_name = self::TableFromOperation( $operation );
			$args = self::Args( $table_name );
//			ob_start();
			Core_Hook_Handler::instance()->DoAction($operation, $args);
			return;
//			do_action($operation, $args);
//			return ob_get_clean();
		}

		// If no filter yet, handle the old way.
		return Focus_Views::instance()->focus_main( $user_id );
	}

	static function TableFromOperation( $operation ) {
		strtok( $operation, "_" ); // remove gem
		strtok( "_" ); // remove edit/show/add

		return strtok( null );
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
		$user_id = GetArg( $args, "worker_id", get_user_id() );
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
					"action" => "add_element('project', '" . $form_table . "', '" . GetUrl() . "')",
					"New Project"
				) );
			}
		} else {
			$result .= "No Projects";
		}

		return $result;
	}

	static function gui_select_priority( $id, $priority_id, $args ) {
		$result         = "";
		$priority_list  = array(
			1  => "1-low",
			2  => "2",
			3  => "3",
			4  => "4",
			5  => "5",
			6  => "6",
			7  => "7",
			8  => "8",
			9  => "9",
			10 => "10-high"
		);
		$args["values"] = $priority_list;
		$result         .= Core_Html::GuiSimpleSelect( $id, $priority_id, $args );

		return $result;
	}

	static function Args( $table_name = null, $action = null ) {
		$ignore_list = ["st_main", "page_number"];
		$args        = array(
			"page_number"  => GetParam( "page_number", false, 1 ),
			"post_file"    => self::getPost(),
			"selected_tab" => GetParam( "selected_tab", false, null ),
			"active_only"  => GetParam( "active_only", false, true )
		);
		// Filter by status.
		if ( GetParam( "status", false, false ) ) {
			$args["status"] = GetParam( "status" );
		}
		foreach ( $_GET as $param => $value ) {
			if (! isset($args["drill_fields"])) $args["drill_fields"] = [];
			if (! isset($args["query"])) $args["query"] = "1 ";
			if ( ! in_array( $param, $ignore_list ) ) {
				$args[ $param ] = $value;
				array_push($args["drill_fields"], $param);

				$args["query"] .= " and $param=" . QuoteText($value);
			}
		}

		if ( $table_name )
			switch ( $table_name ) {
				case "task_templates":
					$args["selectors"]     = array(
						"project_id"  => "Focus_Views::gui_select_project",
						"owner"       => "Flavor_Org_Views::gui_select_worker",
						"creator"     => "Flavor_Org_Views::gui_select_worker",
						"repeat_freq" => "gui_select_repeat_time",
						"team"        => "Focus_Views::gui_select_team"
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
						"task_url"
					);
					$args["header_fields"] = array(
						"task_description"    => "Task description",
						"project_id"          => "Project",
						"priority"            => "Priority",
						"team"                => "Team",
						"repeat_freq"         => "Repeat Frequency",
						"repeat_freq_numbers" => "Repeat times",
						"working_hours"       => "Working hours",
						"task_url"            => "Task Url",
						"condition_query"     => "Condition query"
					);
					$args["check_active"] = true;

					break;

				case "tasklist":
					$args["rows_per_page"] = 20;
					$args["selectors"] = array(
						"project_id" => "Focus_Views::gui_select_project",
						"owner"      => "Flavor_Org_Views::gui_select_worker",
						"creator"    => "Flavor_Org_Views::gui_select_worker",
						"preq"       => "Focus_Views::gui_select_task",
						"team"       => "Focus_Views::gui_select_team",
						"priority"   => "Focus_Views::gui_select_priority",
						"created" => "Core_Html::GuiShowDynamicDateTime",
					);
					if ( self::OptionEnabled( "missions" ) ) {
						$args["selectors"]["mission_id"] = "Flavor_Mission::gui_select_mission";
					}

					$args["header_fields"] = array(
						"task_title"       => "Task Title",
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
						"team"             => "Team"
					);
					$args["fields"]        = array(
						"id",
						"task_title",
						"task_description", // Needed for task title
						"team",
						"project_id",
						"priority"
					);
					$args["links"]         = array( "id" => self::get_link( "task", "%d" ),
						"task_template"=>self::get_link("template", "%d"));
					if ($action == "show")
						$args["fields"][] = "creator";
					break;

				case "working_teams":
					$args["fields"]        = array( "team_name" );
					$args["header_fields"] = array( "team_name" => "Team name" );
					break;

				case "projects":
					// Todo: if col is hidden, set default.
					$args["links"]            = array( "ID" => AddToUrl( array( "operation" => "focus_edit_project&id=%s" ) ) );
					$args["fields"]           = array(
						"ID",
						"project_name",
						"project_contact",
						"project_contact_email",
						"project_priority"
					);
					$args["mandatory_fields"] = array( "project_name" );
					$args["selectors"]        = array( "project_priority" => "Focus_Views::gui_select_priority" );
					//$args["values"] = array("manager" => get_user_id());
					$args["header_fields"] = array(
						"project_name"          => "Project name",
						"project_contact"       => "Project contact (client)",
						"project_contact_email" => "Project contact email",
						"project_priority"      => "Priority"

					);
					//$args["hide_cols"] = array("is_active" => 1, "manager" => 1);
					$args["check_active"] = true;
					break;
			}

		if ( $action == 'new' ) {
			$args["fields"]["last_create"]        = "template_last_task(id) as last_create";
			$args["header_fields"]["last_create"] = "Last created";
		}

		return $args;
	}

	static function getPost() {
		return self::instance()->post_file;
	}

	/**
	 * @param $operation
	 * @param $user_id
	 *
	 * @return string
	 * @throws Exception
	 */
	function focus_main_old( $operation, $user_id )
	{
		if ( ! $operation ) {
			$operation = "default";
		}
		$db_prefix = GetTablePrefix();
		// Actions are performed and return to caller.
		// Page are $result .= and displayed in the end. (to handle the header just once);
		$result = ""; // focus_header($header_args);

		$me = self::instance();

		switch ( $operation ) {
			case "default":
				return $me->default( $user_id );

			case "show_settings":
				return self::show_settings( get_user_id() );
			case "edit_organization":
				return edit_organization();
			case "show_repeating_tasks":
			case "show_templates":
				$args          = self::Args();
				$args["table"] = true;
				$args["new"]   = GetParam( "new", false, 0 );
				// $new           = GetParam( "new", false, null );
				$freq          = GetParam( "freq", false, null );
				$args["query"] = "repeat_freq like '%$freq%'";

				return self::show_templates( $args );
			case "show_template":
				$args = self::Args();
				$id   = GetParam( "id", true );

				return $me->show_templates( $args, $id );
			case "show_task":
				$id = GetParam( "id", true );
				if ( $id ) {
					return self::show_task( $id );
				}
				break;

			case "show_project":
				die("Is this called");
				$args = self::Args( "projects" );

				$id           = GetParam( "project_id", true );
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
				$args              = self::Args( "projects" );
				$args["next_page"] = GetParam( "next_page", false, null );
				$args["post_file"] = self::getPost();
//			$user_id = get_user_id();
//			$args["user_id"] = $user_id;
				//$args["hide_cols"] = array("is_active" => 1, "manager" => 13);
//			$args["values"] = array("company_id" => worker_get_companies($user_id)[0]);

				$args["fields"]           = array( "project_name", "project_contact", "project_priority" );
				$args["mandatory_fields"] = array( "project_name" );
				$args["header_fields"]    = array(
					"project_name"     => "Project name",
					"project_contact"  => "Project contact (client)",
					"project_priority" => "Priority"

				);

				return Core_Gem::GemAddRow( "projects", "Add a project", $args );

			case "last_entered":
				if ( get_user_id() != 1 ) {
					return false;
				}
				$args                 = self::Args();
				$args["last_entered"] = 1;

				return $this->Taskslist( $args );
			case "show_new_sequence":
				$args = self::Args();

				$result         .= Core_Html::GuiHeader( 1, "New sequence" );
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

				$result .= Core_Html::GuiButton( "btn_new_sequence", "save_new_sequence()", "Create" );

				return $result;


			case "show_staff": // Teams that I manage
				$result .= Core_Html::GuiHeader( 1, "Edit staff" );
				$result .= show_staff();

				return $result;

			case "show_edit_projects": // Projects that I manage
				$args              = self::Args( "projects" );
				$result            .= Core_Html::GuiHeader( 1, "Edit projects" );
				$args["worker_id"] = get_user_id();
				$result            .= edit_projects( $args );

				return $result;

			case "show_edit_all_projects": // Projects that I manage
				$args = self::Args( "projects" );

				if ( ! im_user_can( "edit_projects" ) ) {
					die( "no permissions " . __FUNCTION__ );
				}
				$result         .= Core_Html::GuiHeader( 1, "Edit all projects" );
				$args["global"] = true;
				$result         .= edit_projects( $args );

				return $result;

			case "new_team":
				$args   = self::Args( "teams" );
				$result .= Core_Html::GuiHeader( 1, "Add Team" );
				$args   = array( "selectors" => array( "manager" => "Flavor_Org_Views::gui_select_worker" ) );
				$result .= Core_Html::NewRow( "working_teams", $args );
				$result .= Core_Html::GuiButton( "btn_newteam", "save_new('working_teams')", "add" );

				return $result;


			case "show_team":
				$team_id = GetParam( "team_id", true );

				return self::show_team( $team_id );

			case "show_add_to_company":
				$args       = self::Args();
				$company_id = GetParam( "id", true );
				$result     .= Core_Html::GuiHeader( 2, "Invite to company" ) . " " . gui_label( "company_id", $company_id );
				$result     .= ETranslate( "Enter college email address: " );
				$result     .= Core_Html::gui_table_args( array(
					array( "email", GuiInput( "email", "", $args ) ),
					array( "name", GuiInput( "name", "", $args ) ),
					array( "project", gui_select_project( "project_id", null, $args ) )
				) );
				$result     .= Core_Html::GuiButton( "btn_add_to_company", "add_to_company()", "Add" );

				return $result;

			case "projects":
				if ( $id = GetParam( "project_id" ) ) {
					$result .= show_project( $id );
				}
				$result .= show_projects( GetUrl(), get_user_id() );

				return $result;

			case "task_types":
				$args          = self::Args( "task_type" );
				$args["title"] = "task types";

				return Core_Gem::GemTable( "task_type", $args );

			case "new_company_user":
				$company_id = data_save_new( "company" );
				//			$worker_id = worker_get_id(get_user_id());
				$sql = "update ${db_prefix}working set company_id = " . $company_id . " where user_id = " . get_user_id();
				SqlQuery( $sql );

				return SqlInsertId();

			case "show_teams": // System manager -> edit all teams in the system.
				return self::show_teams();
				break;

			case "show_tasks":
				die( 1 ); // 1/10/2020 Not sure this code is needed. If needed write the scenario.
				$query_array = Core_Data::data_parse_get( "tasklist", array(
					"search",
					"operation",
					"table_name",
					"id",
					"dummy"
				) );
				// Todo: handle default values
				$query_array["tasklist"]["status"] = array( 0, false );
				$args["query_array"]               = $query_array;
				$ids                               = Core_Data::data_search( "tasklist", $args );

				return self::show_tasks( $ids );

			case "show_edit_company":
				$company_id = GetParam( "company_id", true );
				$page       = GetParam( "page", false, 1 );

				return show_edit_company( $company_id, $page );

			case "new_template":
				return self::show_new_template();

			default:
				return false;
		}
	}

	static function show_new_team() {
		$args              = self::Args();
		$args["selectors"] = array( "manager" => __CLASS__ . "::gui_select_worker" );

		return Core_Gem::GemAddRow( "working_teams", "New team", $args );
	}

	// Called from the shortcode [focus_project]
	static function show_project_wrapper() {
		$db_prefix = GetTablePrefix();
		// Are we here after creating a task?
		$new = GetParam( "new", false, false, true ); // Called after add a task. Ned is the id of the new task.
		if ( false !== $new ) {
			if ($new) {
				$project = Focus_Project::create_from_task( $new );
				if ( ! $project ) {
					return "project not found";
				}
				$project_id = $project->getId();
			} else {
				$project_id = SqlQuerySingleScalar("select project_id from ${db_prefix}tasklist where id = " . SqlQuerySingleScalar("Select max(id) from ${db_prefix}tasklist where creator = " . get_user_id()));
			}
		} else {
			// Otherwise get from the URL.
			$project_id = GetParam("id", true, null, true);
		}
		$args = self::Args( "tasklist" );
		$args["order"] = " id desc";

		return self::show_project($project_id, $args );
	}

	static function show_teams() {
		$action_url = Focus::getPost();
		$result     = "";
		if ( ! im_user_can( "edit_teams" ) ) {
			$result .= "No permissions";
		}
		$args              = [];
		$args["post_file"] = Focus::getPost();
		$args["selectors"] = array( "manager" => "Flavor_Org_Views::gui_select_worker" );
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
		$result          .= Core_Html::GuiHeader( 1, "All system teams" );
		$result          .= Core_Gem::GemTable( "working_teams", $args );

		unset( $args["actions"] );
		$args["mandatory_fields"] = array( "manager", "team_name" );
		$result                   .= Core_Gem::GemAddRow( "working_teams", "Add a team", $args );

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
		$db_prefix = GetTablePrefix();
		$links     = array();

		$links["id"] = AddToUrl( array(
			"operation" => "show_project",
			"id"        => "%s"
		) ); // add_param_to_url($url, "project_id", "%s");
		$sql         = "select id, project_name, project_priority, project_count(id, " . $owner . ") as open_count " .
		               " from ${db_prefix}projects where 1 ";
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
		if ( ! ( $project_id > 0 ) ) {
			return "bad project id $project_id";
		}

		$P = new Org_Project( $project_id );
		if ( is_null( $args ) ) {
			$args = self::Args( "tasklist" );
		}

		$args["query"] = (isset($args["query"]) ? $args["query"] : 1) . " and project_id = $project_id and (status < 2 or status = 6)";
		$args["title"]        = __( "Tasks in project" ) . " " . $P->getName();
		$action_url = GetUrl();

		$args["actions"] =array(array( "", $action_url . "?operation=gem_add_tasklist&preq=%d;", "fas fa-plus-square", "add_followup" ));

		unset_by_value( $args["fields"], "project_id" );

		$result = Core_Gem::GemTable( "tasklist", $args );
		$result .= Core_Html::GuiHyperlink( " Edit project ", AddToUrl( "edit", 1 ) );
		$result .= '<br/>';
		$result .= Core_Html::GuiHyperlink( " main page ", "/focus/", null );

		return $result;
	}

	function handle_focus_do( $operation ) {
		die ("No to be called"); // 2020-11-24 agla
		if ( strpos( $operation, "data_" ) === 0 ) {
			return handle_data_operation( $operation );
		}

		$allowed_tables         = array(
			"company",
			"tasklist",
			"task_templates",
			"projects",
			"working_rates"
		);
		$header_args            = [];
		$header_args["scripts"] = array(
			"/core/gui/client_tools.js",
			"/core/data/data.js",
			"/focus/focus.js?version=" . Focus::instance()->get_version(),
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
					print "failed";
				}

				return;

			case "del_team":
			case "cancel_working_teams":
				$team_id = GetParam( "id", true );
				if ( team_delete( $team_id ) ) {
					print "done";
				}

				return;

			case "cancel_task_templates":
				$id = GetParam( "id", true );
				if ( data_delete( "${table_prefix}task_templates", $id ) ) {
					print "done";
				}

				return;


			case "add_team_member":
				$team_id    = GetParam( "team_id", true );
				$new_member = GetParam( "new_member", true );
				// $worker = new Org_Worker($new_member);
				$team = new Org_Team( $team_id );

				return $team->AddWorker( $new_member );

			case "save_new":
				$table_name = GetParam( "table_name", true );
				if ( ! in_array( $table_name, $allowed_tables ) ) {
					die ( "invalid table operation" );
				}
				$result = data_save_new( $table_name );
				if ( $result > 0 ) {
					print $result;
				}

				return;

			case "update":
				$table_name = GetParam( "table_name", true );
				if ( ! in_array( $table_name, $allowed_tables ) ) {
					die ( "invalid table operation" );
				}
				if ( Core_Data::data_update( $table_name ) ) {
					if ( $table_name == "{$this->table_prefix}task_templates" ) {
						$row_id = intval( GetParam( "id", true ) );

						return ( SqlQuery( "update {$this->table_prefix}task_templates set last_check = null where id = " . $row_id ) );
					}
				}

				return "not handled";

			case "delete_template":
				$user_id = get_user_id();
				$id      = GetParam( "row_id", true );

				return Focus_Views::template_delete( $user_id, $id );

				$url = $task->task_url();
				if ( ! $url ) {
					return true;
				}
				$url_headers = @get_headers( $url );
				if ( ! $url_headers || strstr( $url_headers[0], "404" ) ) {
					return GetUrl( 1 ) . "?operation=bad_url&id=" . $task_id;
				}
				if ( strlen( $url ) > 1 ) {
					return $url;
				}

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

			case "cancel_projects":
				$id = GetParam( "id", true );

				return ( project_delete( $id, get_user_id() ) );
		}

		return false;
	}

	static function search_by_text_wrap() {
		$text = GetParam( "text", true );

		 print self::search_by_text( get_user_id(), $text );
	}

	/**
	 * @param $user_id
	 *
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	function focus_main( $user_id ) {
		unset ($_GET["operation"]);
//		return __FUNCTION__;
//		print self::CompanySettings(new Org_Company(1));
//		die (1);
		if ( ! self::focus_check_user() ) {
			return null;
		}

		if ( ! ( $user_id > 0 ) ) {
			return "'$user_id' is not valid user";
		}

		$table_args = array("border"=>0);
		$result = Core_Html::gui_table_args(array(array(greeting( null, false ), self::search_box())), null, $table_args);
		$result .= Core_Html::GuiDiv( "search_result" );

		$worker = new Org_Worker( $user_id );
		$args   = self::Args( "tasklist" );

		$selected_tab = GetParam( "st_main", false, "my_work" );
		$tabs = array(
			array( "my_work", "My tasks", null ),
			array( "my_team_work", "Team's tasks", null ),
			array( "i_want", "I want", null ),
			array( "my_teams", "My Teams", null ),
			array( "my_projects", "My projects", null ),
			array( "repeating_tasks", "Repeating tasks", null )
		);

		if ( $companies = $worker->GetCompanies( true ) ) {
			foreach ( $companies as $company_id ) {
				if ( ! is_integer( $company_id ) ) {
					die ( "company $company_id is not int" );
				}
				$company = new Org_Company( $company_id );
				array_push( $tabs, array(
					"company_settings&company_id=$company_id",
					"{$company->getName()} Settings",
					null
				) );
			}
		}

//		print "st=$selected_tab<br/>";

		switch ( $selected_tab ) {
			case "my_work":
				$tabs[0][2] = self::user_work( $args, "Active tasks assigned to me", false, $user_id );
				if (! $this->result_count) $tabs[0][2] = self::user_work( $args, "Active tasks assigned to my teams", true, $user_id );
				break;
			case "my_team_work":
				$tabs[1][2] = self::user_work( $args, "Active tasks assigned to my teams", true, $user_id );
				break;
			case "i_want":
				$tabs[2][2] = self::i_want( $args, $user_id );
				break;
			case "my_teams":
				$tabs[3][2] = self::my_teams( $args, $user_id );
				break;
			case "my_projects":
				$tabs[4][2] = self::my_projects( $args, $user_id );
				break;
			case "repeating_tasks":
				$tabs[5][2] = self::show_templates( $args );
				break;
			case "company_settings":
				$company_id = GetParam( "company_id", true );
				$company    = new Org_Company( $company_id );
				$tabs[6][2] = Flavor_Org_Views::CompanySettings( $company );
				break;
			default:
				$result .= "$selected_tab not handled!";
		}

		$args["tabs_load_all"] = false;
		$args["st_main"] = $selected_tab;

		$result .= Core_Html::GuiTabs( "main", $tabs, $args );

		return $result;
	}

	static function search_box() {
		$result = "";
		$result .= Core_Html::GuiInput( "search_text", "(search here)",
			array( "events" => "onfocus=\"search_by_text()\" onkeyup=\"search_by_text()\" onfocusout=\"search_box_reset()\"" ) );

		return $result;
	}

	function user_work( $args, $title, $include_team, $user_id )
	{
		$result = "";

		if ( ! ( $user_id > 0 ) ) {
			print debug_trace();
			die ( "bad user id $user_id" );
		}

		$worker = new Org_Worker( $user_id );
		$status = GetArg( $args, "status", null );

		// Build the title
		$args["title"] = __( $title );
		foreach ($_GET as $query_key => $query_value) {
			if ( isset( $args["selectors"][ $query_key ] ) ) {
				$args["title"] .= " " . $args["header_fields"][ $query_key ] . " " . $args["selectors"][ $query_key ]("title", $query_value, ["edit"=>false]) . ", ";
			}
		}
		$args["title"] = rtrim($args["title"], ", ");

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I need to handle (owner = me)                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if (! isset($args["query"])) $args["query"] = 1;
		$args["query"] .= " and " . $worker->myWorkQuery( $include_team, $status ); ///self::ActiveQuery( );
		FocusLog( $args["query"] );
		if ( isset( $args["period"] ) ) {
			$period        = $args["period"];
			$args["query"] .= " and (ended >= curdate() - INTERVAL $period )";
		}

		$args["rows_per_page"] = 9; // GetParam( "limit", false, 10 );
		array_push($args["fields"], "created");

		$table = $this->Taskslist( $args );
		if ( $this->result_count ) {
			$result .= $table;
		} else {
			if ( ! $include_team ) {
				return self::user_work( $args, "Active tasks assigned to my teams", true, $user_id );
			}
			$result .= "Nothing found.";
		}
		$result .= self::task_filters();

		return $result;
	}

	static function task_filters() {
		return "<div>Filter by status: " .
		       Core_Html::GuiHyperlink( "Waiting", AddToUrl( "status", 0 ) ) . " " .
		       Core_Html::GuiHyperlink( "Started", AddToUrl( "status", 1 ) ) . " " .
		       Core_Html::GuiHyperlink( "Completed", AddToUrl( "status", 2 ) ) . " " .
		       Core_Html::GuiHyperlink( "Cancelled", AddToUrl( "status", 3 ) ) .
		       "</div>";

	}

	static function my_teams( $args, $user_id ) {
		$worker = new Org_Worker( $user_id );
		$result = "";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks teams I'm a member of (team in my_teams). Not assigned                                              //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = ETranslate( "My teams tasks" );

		$teams = $worker->GetAllTeams();
		if ( ! $teams ) {
			return "No teams";
		}
		$workers = $worker->GetAllWorkers();
		if ( ! $workers ) {
			return null;
		}

		// Todo: if more than 6 workers need to organize differently.
		$data = array( "header" => array( "Worker", "Ready tasks", "Non ready tasks", "done (1 week)" ) );
		foreach ( $workers as $worker_id ) {
			$args["worker_id"] = $worker_id;
			$w                 = new Org_Worker( $worker_id );
			array_push( $data, array(
				$w->getName(),
				Core_Html::GuiHyperlink( $w->tasksCount( 1 ), self::get_link( "worker" ) . "?worker_id=$worker_id&active_only=1" ),
				Core_Html::GuiHyperlink( $w->tasksCount( 0 ), self::get_link( "worker" ) . "?worker_id=$worker_id&active_only=0" ),
				Core_Html::GuiHyperlink( $w->doneTask( "7 day" ), self::get_link( "worker" ) . "?worker_id=$worker_id&active_only=2&period=" . urldecode( "7 day" ) )
			) );
//			array_push ($tab_data, array( $w->getName(), $w->getName(), self::user_work($args, $worker_id)));
		}
//		$args["class"] = "team";
//		$result .= Core_Html::GuiTabs($tab_data, $args);
		// print "teams: " . CommaImplode($teams) . "<br/>";
//		$args["extra_fields"]      = array( "team" );
//		$args["selectors"]["team"] = "Focus_Views::gui_select_team";
//		if ( $teams and count( $teams ) ) {
//			$result .= Focus_Views::Taskslist( $args );
//		}
		$result .= Core_Html::gui_table_args( $data );

		return $result;
	}

	static function my_projects( $args, $user_id ) {
		$args   = self::Args( "projects" );
		$worker = new Org_Worker( $user_id );
		$result = "";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks projects I'm a member of (team in my_projects). Not assigned                                        //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = ETranslate( "My projects" );
		// DebugVar(CommaImplode($worker->AllProjects()));
		$projects = $worker->GetAllProjects();
		if ( ! $projects ) {
			return "no projects for user " . $worker->getName();
		}

		$args["query"] = " id in (" . CommaImplode( $projects ) . ")";
		$args["actions"] = array(array("Open tasks", "/project?id=%d"));

		$result .= Core_Gem::GemTable( "projects", $args );

		return $result;
	}

	static function my_tasks( $args, $user_id ) {
		$result        = "";
		$ignore_list   = [];
		$args["count"] = 0;

		if ( ! $user_id > 0 ) {
			print debug_trace();
			die ( "bad user id $user_id" );
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Links: Templates                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		if ( $teams = Org_Team::team_managed_teams( $user_id ) ) {// Team manager
			$workers = array();
			foreach ( $teams as $team_id ) {
				$team = new Org_Team( $team_id );
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

					$result .= Core_Html::GuiHyperlink( GetUserName( $worker_id ) . "(" . $count . ")", '?operation=show_worker&id=' . $worker_id ) . " ";
				}
			}
			$result .= "<br/>";
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I need to handle (owner = me)                                                                       //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"]       = ETranslate( "Tasks assigned to me" );
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

	function i_want( $args, $user_id ) {
		$result = "";
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I've created. Assigned to some else                                                                 //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = ETranslate( "Tasks I've initiated to other teams" );
		$args["query"] = " creator = $user_id and (status < 2 or status = 6)"; // . " and (owner != " . $user_id . ' or isnull(owner)) ' . ($teams ? ' and team not in (' . CommaImplode( $teams ) . ")" : '');
		$args["class"] = "sortable";
		$args["fields"][] = "status";
		$args["selectors"]["status"] = "Focus_Tasklist::get_task_status";
		$table         = $this->Taskslist( $args );
		if ( $this->result_count ) {
			$result .= $table;
		} else {
			$result .= "<br/>" . ETranslate( "No active tasks!" );
			$result .= ETranslate( "Let's create first one!" ) . " ";
		}

		$result .= Core_Html::GuiHyperlink( "create task", "?selected_tab=i_want&operation=gem_add_tasklist" ) . "<br/>";

		return $result;
	}

	function Taskslist( $args = null ) {
		$args["count"]           = 0;
		$args["drill"]           = true;
		$action_url = Focus::getPost();

		if ( ! isset( $args["fields"] ) ) {
			$args["fields"] = array(
				"id",
				"task_description",
				"task_title",
				"project_id",
				"date",
				"priority",
				"task_template"
			);
		}
		if ( isset( $args["extra_fields"] ) ) {
			$args["fields"] = array_merge( $args["fields"], $args["extra_fields"] );
		}

		$args["hide_cols"] = array( "task_description" => 1 );

		$links = array();

		if ( ! isset( $args["selectors"] ) ) {
			$args["selectors"] = array(
				"project"    => "Focus_Views::gui_select_project",
				"project_id" => "Focus_Views::gui_select_project",
				"owner"      => "Flavor_Org_Views::gui_select_worker"
			);
		}

		// The first part is action to server. If it replies with done, the second part is executed in the client (usually hiding the row).
		$actions = array(
			//    text, action,                                                class,               tooltip
			array( "", $action_url . "?operation=task_start&id=%s;load_page", "fas fa-play-circle", "start" ),
			array( "", $action_url . "?operation=task_end&id=%s;action_hide_row", "fas fa-stop-circle", "finished" ),
			array( "", $action_url . "?operation=task_cancel&id=%s;action_hide_row", "fas fa-window-close", "cancel" ),
			array( "", $action_url . "?operation=task_postpone&id=%s;action_hide_row", "fas fa-clock", "tomorrow" ),
			array(
				"",
				$action_url . "?operation=task_pri_plus&id=%s;location_reload",
				"fas fa-arrow-alt-circle-up",
				"increase priority"
			),
			array(
				"",
				$action_url . "?operation=task_pri_minus&id=%s;location_reload",
				"fas fa-arrow-alt-circle-down",
				"decrease priority"
			)

		);
		$args["order"]   = " priority desc ";

		$links["task_template"] = self::get_link( "template", "%s" );
		$links["id"]            = self::get_link( "task", "%s" );
		$args["links"]     = $links;
		$args["post_file"] = self::getPost();
		$args["actions"]   = $actions;
		$args["id_field"]  = "id";
		$args["edit"]      = false;
		$result      = "";

		$args["col_width"]    = array( "task_description" => '30%' );
		$args["prepare_plug"] = __CLASS__ . "::prepare_row";

		$args["post_action"]  = self::getPost() .
		                        $table = Core_Gem::GemTable( "tasklist", $args );

		if ( $table ) {
			$result .= $table;
			$this->result_count = $args["count"];
		}

		$result .= " " . Core_Html::GuiHyperlink( "Add delivery", AddToUrl( "operation", "show_new_task&mission=1" ) );

		return $result;
	}

	static function prepare_row( $task_row ) {
		$id = $task_row['id'];
		$t  = new Focus_Tasklist( $id );
		if ( ! $t->working_time() ) {
			return null;
		}
		$max_len = 60;
		if ( ! isset( $task_row["task_title"] ) or ! strlen( $task_row['task_title'] ) ) {
			$description = explode( " ", $task_row["task_description"] );
			// Start with the first word and add until max_len is reached.
			$title = $description[0];
			unset( $description[0] );

			foreach ( $description as $word ) {
				if ( strlen( $title ) + strlen( $word ) > $max_len ) {
					break;
				}
				$title .= " " . $word;
			}
			$task_row["task_title"] = $title;
		}

		return $task_row;
	}

	function show_tasks( $ids ) {
		$args          = [];
		$args["query"] = "id in (" . CommaImplode( $ids ) . ")";

		return $this->Taskslist( $args );
	}

	static function show_team( $team_id, $active_only = true ) {
		$team   = new Org_Team( $team_id );
		$result = "";
		$result .= Core_Html::GuiHeader( 1, "Team " . $team->getName() );
		$result .= Core_Html::GuiHyperlink( "Include non active", AddToUrl( "active_only", 0 ) );

		// $team_members = team_members($team_id);

//		$result .=  Core_Html::GuiHeader(2, get_customer_name($user_id) . " " . $user_id);
		$args           = array( "active_only" => $active_only );
		$args["query"]  = " team=" . $team_id;
		$args["fields"] = array( "id", "task_description", "project_id", "priority", "task_template", "owner" );
		$result         .= Focus_Views::tasks_list( $args );

		return $result;
	}

	static function show_template_wrapper() {
		$template_id = GetParam( "id", false );
		if ( ! $template_id ) {
			return self::show_new_template();
		}
		if ($operation = GetParam("operation", false, null)){
			$args = array("id"=> GetParam("id", true),
			"table"=>"task_templates");
			do_action($operation, $args);
		}

		return self::instance()->show_templates( $not_used, $template_id );
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
//		$result                   .= Core_Html::GuiHeader( 1, "יצירת תבנית חדשה" );
		$worker                   = new Org_Worker( get_user_id() );
		$args                     = array();
		$args["selectors"]        = array(
			"project_id"  => "Focus_Views::gui_select_project",
			"owner"       => "Flavor_Org_Views::gui_select_worker",
			"creator"     => "Flavor_Org_Views::gui_select_worker",
			"team"        => "Focus_Views::gui_select_team",
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
		$result                   .= Core_Html::NewRow( "task_templates", $args );
		$result                   .= Core_Html::GuiButton( "btn_template", "add",
			array( "action" => "data_save_new('" . self::getPost() . "', 'task_templates')" ) );

		$args["style"] = "display:none";

//		return  Core_Html::GuiButton( "btn_new_template", "Add repeating",
//			array( "action" => "new_task_template.style.display = 'block'; btn_new_template.style.display ='none'; btn_new_task.style.display = 'none'; btn_cancel.style.display='block';" ) ) .

		return Core_Html::GuiDiv( "new_task_template", $result, $args );
	}

	static function show_task( $row_id, $edit = 1 ) {
		if ( ! $row_id ) {
			return self::show_new_task();
		}

		$t = new Focus_Tasklist($row_id);
		$table_name  = "tasklist";
		$args              = self::Args("tasklist", $edit ? "show" : "create");
		if ($t->task_template()) {
			$args["fields"][] = "task_template";
		}
		$args["fields"][] = "preq";
		$args["fields"][] = "date";

		$args["edit_cols"] = array(
			"date"             => true,
			"task_title"       => true,
			"task_description" => true,
			"task_template"    => true,
			"status"           => true,
			"started"          => true,
			"ended"            => true,
			"location_name"    => true,
			"location_address" => true,
			"preq"             => true,
			"task_type"        => true,
			"is_active"        => true
		);
//		$args["selectors"] = array(
//			"project_id" => "Focus_Views::gui_select_project",
//			"owner"      => "Flavor_Org_Views::gui_select_worker",
//			"creator"    => "Flavor_Org_Views::gui_select_worker",
//			"preq"       => "Focus_Views::gui_select_task",
//			"team"       => "Focus_Views::gui_select_team",
//			"mission_id" => "Flavor_Mission::gui_select_mission"
//		);
//		if ( self::OptionEnabled( "missions" ) ) {
//			$args["selectors"]["mission_id"] = "Flavor_Mission::gui_select_mission";
//		}
//
//		$args["title"]         = $entity_name;
//		$args["hide_cols"]     = array(
//			"status"           => true,
//			"preq"             => true,
//			"task_type"        => true,
//			"mission_id"       => true,
//			"mission_id"       => true,
//			"location_name"    => true,
//			"location_address" => true,
//			"is_active"        => true
//		);
//		$args["header_fields"] = array(
//			"date"             => "Start date",
//			"task_title"       => "Title",
//			"task_description" => "Task description",
//			"task_template"    => "Repeating task",
//			"status"           => "Status",
//			"started"          => "Started date",
//			"ended"            => "Ended date",
//			"project_id"       => "Project",
//			"location_name"    => "Location",
//			"location_address" => "Address",
//			"priority"         => "Priority",
//			"preq"             => "Prerequisite",
//			"owner"            => "Assigned to",
//			"creator"          => "Creator",
//			"task_type"        => "Task type",
//			"mission_id"       => "Mission",
//            "team"             => "Team",
//            "created"          => "Created"
//		);
//
//		//$new_task = new Focus_Tasklist($row_id);
//		//$creator = new Org_Worker( $new_task->getCreator() );
//		//print "creator = " .$creator->getName();
//		//$args["creator"] = $creator->getName();
//		$args["companies"] = $worker->GetCompanies();
//		$args["debug"]     = 0; // get_user_id() == 1;
//		$args["post_file"] = self::instance()->post_file;
//		$args["v_checkbox"] =true;

		return Core_Gem::GemElement( $table_name, $row_id, $args );
	}

	function focus_check_user() {

		// Check if user has company. if not create new one.
		$user_id     = get_user_id();
		$worker      = new Org_Worker( $user_id );
		$company_ids = $worker->GetCompanies();

		if ( ! $company_ids or ! count( $company_ids ) ) {
			print "We need some information to get started! <br/> please enter a name to represent your company<br/>";
            $args              = self::Args( "company" );
            $args["fields"]           = array("name");
            $args["post_file"] = Focus::getPost();
            $args["mandatory_fields"] = array( "name" );
            //$args["links"]     = array( "id" => AddToUrl( array( "operation" => "show_edit_company", "id" => "%s" ) ) );
            print Core_Gem::GemAddRow( "company", "Add New Company", $args );

            /*
			$args                  = array( "values" => array( "admin" => get_user_id() ) );
			$args["hide_cols"]     = array( "admin" => true );
			$args["header_fields"] = array( "name" => "Company name" );
			try {
				print Core_Html::GuiHeader( 1, "Company details" );
				print Core_Html::NewRow( "company", $args );
			} catch ( Exception $e ) {
				print "Error F1: " . $e->getMessage();
				return false;
			}
			print Core_Html::GuiButton( "btn_add", "Add", array( "action" => "data_save_new('" . Focus::getPost() . "','company', location_reload)" ) );
*/
			return null;
		}

		// Check if user has team. creat a new personal team if not.
		$team_ids       = $worker->GetAllTeams();
		$found_personal = false;
		$prefix         = __( "Personal team" );
		foreach ( $team_ids as $team_id ) {
			$t = new Org_Team( $team_id );
			if ( strstr( $t->getName(), $prefix ) ) {
				$found_personal = true;
			}
		}
		if (! $found_personal) {
			Org_Team::Create($user_id, $prefix . " " . $worker->getName());
		}

		// Check if user has at list one project. create the first one if not.
		if ( ! ( $worker->GetAllProjects() ) ) {
			print "It seems you have no projects. let's create your first project.<br/>";
			$args              = self::Args( "projects" );
			$args["post_file"] = Focus::getPost();
			try {
				print Core_Gem::GemAddRow( "projects", "Please enter details for you first project", $args );

				return null;
			} catch ( Exception $e ) {
				print "Error F1: " . $e->getMessage();

				return false;
			}

		}

//		$project_ids = worker_get_projects( $user_id );
//		if ( is_null( $project_ids ) or ! count( $project_ids ) ) {
//			project_create( $user_id, ImTranslate( "first project" ), $company_ids[0] );
//		}

		return true;
	}

	function show_templates( &$not_sure_about_this_args, $template_id = 0 ) {
		$db_prefix = GetTablePrefix();
		$url       = GetUrl( 1 );

		$result                     = "";
		$action_url                 = Focus::getPost();
		$worker                     = new Org_Worker( get_user_id() );
		$template_args              = self::Args( "task_templates" );
		$template_args["worker"]    = $worker->getId();
		$template_args["companies"] = $worker->GetCompanies();

		if ( $template_id ) {
//			$template_args["title"]     = "Repeating task"; 2020-11-29. agla: Title should be set in pages
			$template_args["post_file"] = $action_url;

			$template = Core_Gem::GemElement( "task_templates", $template_id, $template_args );
			if ( ! $template ) {
				$result .= "Not found";

				return $result;
			}
			$result .= $template;

			$tasks_args         = self::Args( "tasklist" );
			$tasks_args["edit"] = false;
			// $tasks_args["prepare_plug"] = "Focus_Tasklist::prepare";

			$tasks_args["class"] = "sortable";

			$sql   = "select * from ${db_prefix}tasklist where task_template = " . $template_id;
			$sql   .= " order by date desc limit 10";
			$table = Core_Html::GuiTableContent( "last_tasks", $sql, $tasks_args );

			if ( $table ) {
				$result .= Core_Html::GuiHeader( 2, "משימות אחרונות" );
				$result .= $table;
			} else {
				$result .= "<br/>Not tasks created for this template";
			}

			return $result;
		}

		if ( $page = GetParam( "page" ) ) {
			$template_args["page"] = $page;
			unset ( $_GET["page"] );
		};

		$query = ( isset( $template_args["query"] ) ? $template_args["query"] : " 1" );
		if ( GetParam( "search", false, false ) ) {
			$ids = Core_Data::data_search( "task_templates", $template_args );
			if ( ! $ids ) {
				$result .= "No templates found" . Core_Html::Br();

				return $result;
			}
			$query .= " and id in (" . CommaImplode( $ids ) . ")";
		}

		$template_args["class"]         = "sortable";
		$template_args["rows_per_page"] = 10;
		$template_args["links"]         = array( "id" => $url . "?operation=show_template&id=%s" );
		$template_args["header"]        = true;
		$template_args["drill"]         = true;
		$template_args["edit"]          = false;
		$template_args["actions"]       = array(
			array(
				"delete",
				$action_url . "?operation=delete_template&row_id=%s;action_hide_row"
			)
		);
		$template_args["query"]         = $query;
		$new                            = GetArg( $template_args, "new", false );
		$template_args["order"]         = " id " . ( $new ? "desc" : "asc" );

//		$result = Core_Html::GuiHyperlink( "Add repeating task", GetUrl( true ) . "?operation=new_template" );

		$result .= Core_Gem::GemTable( "task_templates", $template_args );

		// $result .= GuiTableContent( "projects", $sql, $args );

		return $result;
	}

	function show_staff() // Edit teams that I manage.
	{
		$db_prefix = GetTablePrefix();
		$user      = wp_get_current_user();
		$result    = Core_Html::GuiHeader( 2, "teams" );

		$args              = [];
		$args["links"]     = array( "id" => AddToUrl( array( "operation" => "show_team", "id" => "%s" ) ) );
		$args["selectors"] = array( "manager" => "Flavor_Org_Views::gui_select_worker" );
		$args["edit"]      = false;
		$result            .= GuiTableContent( "working_teams", "select * from {$db_prefix}working_teams where manager = " . $user->id, $args );

		$result .= Core_Html::GuiHyperlink( "add", AddToUrl( "operation", "show_new_team" ) );

		// print GuiTableContent("");

		return $result;
	}

	function edit_projects( $args ) // Edit projects that I manage.
	{
		$result = Core_Html::GuiHeader( 2, "Projects" );

		$global            = GetArg( $args, false, "global" );
		$args["links"]     = array( "ID" => AddToUrl( array( "operation" => "show_edit_project", "id" => "%s" ) ) );
		$args["selectors"] = array( "manager" => "Flavor_Org_Views::gui_select_worker" );
		$args["edit"]      = false;
		$args["actions"]   = array(
			array(
				"delete",
				GetUrl( 1 ) . "?operation=cancel_projects&id=%s;action_hide_row"
			)
		);
		$base_query        = "is_active = 1 ";
		$args["fields"]    = array( "ID", "project_name", "project_contact", "project_priority", "company" );
		if ( $global ) // A global user can see all projects
		{
			$args["query"] = $base_query;
			$result        .= Core_Gem::GemTable( "projects", $args );
		} else { // Ordinary user can see only projects he's working in.
			$worker_id = GetArg( $args, "worker_id", null );
			if ( ! $worker_id ) {
				die ( "no worker_id" );
			}
			$companies = worker_get_companies( get_user_id() );

			foreach ( $companies as $company ) {
				$result        .= Core_Html::GuiHeader( 1, company_get_name( $company ) );
				$args["query"] = $base_query . " and company = $company";
				$result        .= Core_Gem::GemTable( "projects", $args );
			}
		}

		$result .= Core_Html::GuiHyperlink( "add", AddToUrl( "operation", "show_new_company" ) );

		return $result;
		// print GuiTableContent("");
	}

	function managed_workers( $manager_id, $url ) {
		$db_prefix = GetTablePrefix();
		$teams     = SqlQueryArrayScalar( "select id from ${db_prefix}working_teams where manager = " . $manager_id );

		if ( ! $teams ) {
			return "";
		}

		$result = "";

		foreach ( $teams as $team_id ) {
			$team   = new Org_Team( $team_id );
			$result .= Core_Html::GuiHyperlink( $team->getName(), $url . "?team=" . $team_id );
		}

		return $result;
	}

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

	function task_new( $user_id, $project, $priority, $description, $preq = null ) {
		$db_prefix = GetTablePrefix();
		$creator   = $user_id;
		$owner     = $user_id; // For now
		is_numeric( $priority ) or die( "bad project id" );
		is_numeric( $priority ) or die ( "bad priority" );
		strlen( $description ) > 2 or die ( "short description" );

		$sql = "insert into ${db_prefix}tasklist (task_description, project_id, priority";

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

		SqlQuery( $sql );

		return SqlInsertId();
	}

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
			$result .= "<br/>" . ETranslate( "System wide:" );
			$result .= Core_Html::GuiHyperlink( "All teams", AddToUrl( "operation", "show_edit_all_teams" ) ) . " ";
			$result .= Core_Html::GuiHyperlink( "All projects", AddToUrl( "operation", "show_edit_all_projects" ) ) . " ";
		}

		return $result;
	}

	function template_creator( $template_id ) {
		return SqlQuerySingleScalar( "select creator from {$this->table_prefix}task_templates where id = " . $template_id );
	}

	function template_delete( $user_id, $template_id ) {
		$db_prefix = GetTablePrefix();

		$creator_id = self::template_creator( $template_id );
		if ( get_user_id() != 1 and ( $creator_id != $user_id ) ) {
			print "not creator c=$creator_id u=$user_id<br/>";

			return false;
		}
		if ( $template_id > 0 ) {
			$sql = "delete from ${db_prefix}task_templates where id = " . $template_id;

			return SqlQuery( $sql );
		}

		return false;
	}

	function link_to_task( $id ) {
		return AddToUrl( array( "operation" => "show_task", "id" => $id ) );
	}

	static function search_by_text( $user_id, $text ) {
		$result = [];
		$result = array_merge( $result, self::project_list_search( $user_id, $text ) );
		$result = array_merge( $result, self::task_list_search( "(status < 2 or status = 6) and (task_description like " . QuotePercent( $text ) . " or task_title like " . QuotePercent( $text ) . ")" ) );
		$result = array_merge( $result, self::template_list_search( " is_active = 1 and task_description like " . QuotePercent( $text ) ) );

		if ( count( $result ) < 1 ) {
			return "No results";
		}

		return Core_Html::gui_table_args( $result );
	}

	static function task_list_search( $query ) {
		$db_prefix = GetTablePrefix();
		$sql = "select id, CONCAT(IFNULL(task_title, substring(task_description, 1, 20))) from ${db_prefix}tasklist where $query";
		$tasks     = SqlQueryArray($sql);

		$result = [];
		foreach ( $tasks as $task ) {
			array_push( $result, Core_Html::GuiHyperlink( $task[1], self::get_link( "task", $task[0] ) ) );
		}

		// debug_var($result);
		return $result;
	}

	static function template_list_search( $query ) {
		$db_prefix = GetTablePrefix();
		$tasks     = SqlQueryArray( "select id, task_description from ${db_prefix}task_templates where $query" );

		$result = [];
		foreach ( $tasks as $task ) {
			array_push( $result, Core_Html::GuiHyperlink( $task[1], self::get_link( "template", $task[0] ) ) );
		}

		// debug_var($result);
		return $result;
	}

	static function project_list_search( $user_id, $query ) {
		$result = [];
		$user   = new Org_Worker( $user_id );

		$projects = $user->GetAllProjects( "is_active = 1", array( "id", "project_name" ) );
		if ( $projects ) {
			foreach ( $projects as $project ) {
//				var_dump($project);
				// print $project['project_name'] . " " . $query . " " . strpos( $project['project_name'], $query )."<br/>";
				if ( strpos( $project['project_name'], $query ) !== false ) {
					array_push( $result, "Project " . Core_Html::GuiHyperlink( $project["project_name"], self::get_link( "project", $project["id"] ) ) );
				}
			}
		}

		return $result;
	}

	static function show_settings( $user_id ) {
		$result = Core_Html::GuiHeader( 1, ETranslate( "Settings for" ) . " " . GetUserName( $user_id ) );

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

		return $result;
	}

	static function gui_select_team( $id, $selected = null, $args = null ) {
		$db_prefix = GetTablePrefix();
		$edit      = GetArg( $args, "edit", false );

		// Just view - fetch the team name and return.
		if ( ! $edit ) {
			return ( $selected > 0 ) ? SqlQuerySingleScalar( "select team_name from ${db_prefix}working_teams where id = " . $selected ) : "";
		}

		//
		$worker = new Org_Worker( get_user_id() );

		// The user is the manager of the company.
		$companies       = $worker->GetCompanies( true );
		$teams = array();
		foreach ( $companies as $company_id ) {
			$company_id    = new Org_Company( $company_id );
			$company_teams = $company_id->getTeams(); //get teams in company
			foreach ( $company_teams as $company_team ) {
				if ( ! in_array( $company_team, $teams ) ) // check if a team is already exist
				{
					array_push( $teams, $company_team );
				}
			}
		}

		// Specific given sender permission
		$can_send_to = $worker->CanSendTasks();
//		var_dump($can_send_to);
//		print "<br/>";
//		var_dump($can_send_to);
		foreach ($can_send_to as $team) {
//			print $team . "<br/>";
			if (! in_array($team, $teams))
			array_push($teams, $team);
		}

		$data = array();
		foreach ($teams as $team) {
			$t = new Org_Team($team);
			if ($t->getName())
				$data[] = array("id"=>$team, "name"=>$t->getName());
		}
		//teams return all the teams in the user's company.
		//$teams = SqlQueryArrayScalar( "select team_name from ${db_prefix}working_teams where manager = " . $user_id );
		$debug            = false; // (get_user_id() == 1);
		$args["values"]   = $data;
		$args["debug"]    = $debug;
//		$args["name"]     = "team_name";
//		$arg["id_key"]    = "id";
		$args["selected"] = $selected;

		// collision between query of the container and the selector.
		$args["query"] = ( isset( $args["query_team"] ) ? $args["query_team"] : null );

		$form_table = "working_teams"; // GetArg( $args, "form_table", null );

		$gui = Core_Html::GuiSelect( $id, $selected, $args );
		//$gui = Core_Html::GuiSelectTable( $id, "working_teams", $args );
		$gui .= Core_Html::GuiButton( "add_new_team", "New Team", array(
			"action" => "add_element('team', '" . $form_table . "', '" . GetUrl() . "')",
			"New Team"
		) );

		return $gui;
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

			case "worker":
				return "/focus_worker";


		}
	}

	function getShortcodes() {
		//             code                           function                  capablity (not checked, for now).
		return ( array(
			'focus_main'            => array( 'Focus_Views::focus_main', 'show_tasks' ),
			'focus_task'            => array( 'Focus_Views::show_task', 'show_tasks' ),
			'focus_template'        => array( 'Focus_Views::show_template', 'show_tasks' ),
			'focus_repeating_task'  => array( 'Focus_Views::show_repeating_task', 'show_tasks' ),
			'focus_team'            => array( 'Focus_Views::show_team', 'show_teams' ),
			'focus_project'         => array( 'Focus_Views::show_project', null ), // 'edit_projects' ),
			'focus_project_tasks'   => array( 'Focus_Views::show_project_tasks', 'show_tasks' ),
			'focus_worker'          => array( 'Focus_Views::show_worker', 'show_tasks' ),
			'focus_sign_up'         =>  array( __CLASS__ . '::sign_up' )
		) );
	}

	static function sign_up() {
		if ( ! class_exists( "Subscription_Manager" ) ) {
			die( "install error. Install Subscript_Manager" );
		}
		return self::instance()->focus_users->register();
	}

	function init() {
		Core_Gem::getInstance()->AddTable( "task_templates" );

		// Project related actions.
		Core_Gem::getInstance()->AddTable( "projects" ); // add + edit

		// Tasklist
		Core_Gem::getInstance()->AddTable( "tasklist" ); // add + edit

		// Company
	}

    static function DataSaveNewCompany( $row ) {
        //when new company is open the user that open the company is the admin of the company.
        if ( ! isset( $row["admin"] ) ) {
            $row["admin"] = get_user_id();
        }
        //check if the team already exist
        $admin   = $row["admin"];
        $company_name = $row["name"];
        $db_prefix = GetTablePrefix( "company" );
        $count     = SqlQuerySingleScalar( "select count(*) from ${db_prefix}company where name = '" . $company_name . "' and admin =" . $admin );
        if ( $count > 0 ) {
            print __( "Duplicate value ", "e-fresh" );

            return null;
        }
        //add the company to the admin-user
//        $company_id = GetParam( "company_id" );
//        $U = new Org_Worker( $admin );
//        $U->AddCompany( $company_id );

        return $row;
    }

	static function DataSaveNewDefault( $row ) {
		//when new project is open the user that open the project is the manager of the project.
		if ( ! isset( $row["manager"] ) ) {
			$row["manager"] = get_user_id();
		}

		//$row["hide_cols"] = array("manager" => true);
		return $row;
	}

	static function DataSaveNewTeam( $row ) {
		//when new team is open the user that open the team is the manager of the team.
		if ( ! isset( $row["manager"] ) ) {
			$row["manager"] = get_user_id();
		}
		//check if the team already exist
		$manager   = $row["manager"];
		$team_name = $row["team_name"];
		$db_prefix = GetTablePrefix( "working_teams" );
		$count     = SqlQuerySingleScalar( "select count(*) from ${db_prefix}working_teams where team_name = '" . $team_name . "' and manager =" . $manager );
		if ( $count > 0 ) {
			print __( "Duplicate value ", "e-fresh" );

			return null;
		}

		return $row;
	}

    static function DataSaveNewTaskList($row){
        if(!isset($row["creator"])){
            $row["creator"] = get_user_id();
        }
        if(!isset($row["created"])){
            $row["created"] = date("Y/m/d G:i" );
            FocusLog($row["created"]);
        }
        return $row;
    }

	function data_save_new_after_company($row, $new_company_id)
	{
		$worker = $row['admin'];
		$w = new Org_Worker($worker);
		$w->AddCompany($new_company_id);
//		var_dump($new_copany_id);
//		var_dump($row);
	}

	static function DoAddCompanyWorker() {
		$user_id = get_user_id();
		if ( ! $user_id ) {
			print "need to connect first";

			return false;
		}
		$User = new Org_Worker( $user_id );

		$company_id = GetParam( "company_id" );
		if ( ! in_array( $company_id, $User->GetCompanies() ) ) {
			print "not your company!";

			return false;
		}

		$worker_email = GetParam( "worker_email" );
		$new_user     = get_user_by( 'email', $worker_email );
		if ( ! $new_user ) {
			$name        = strtok( $worker_email, "@" );
			$new_user_id = wp_create_user( $name, $name, $worker_email );
			if ( ! ( $new_user > 0 ) ) {
				var_dump( $new_user );

				return false;
			}
		} else {
			$new_user_id = $new_user->ID;
		}

		$U = new Org_Worker( $new_user_id );
		$U->AddCompany( $company_id );

		$message = GetParam( "message" );
		$company = new Org_Company( $company_id );

		return mail( $worker_email, "Welcome to Focus!, company " . $company->getName(), $message );
	}

	static function show_worker_wrapper() {
		$user_id = get_user_id();
		if ( ! $user_id ) {
			return "unauth";
		}

		$worker_id = GetParam( "worker_id", false, null );

		$user = new Org_Worker( $user_id );
		if ( ! in_array( $worker_id, $user->GetAllWorkers() ) ) {
			return "not privileged";
		}

		// Todo: move all processing to filter.
		$args = self::Args( "tasklist" );

		// If no filter yet, handle the old way.
		return self::instance()->user_work( $args, "Worker's tasks", true, $user_id );
	}

	static function ProjectAddMember() {
		$project_id = GetParam( "project_id", true );
		$worker_id  = GetParam( "user", true );
		$project    = new Org_Project( $project_id );

		return $project->addWorker( $worker_id );
	}

	static function AddCompanyWorker( $operation ) {
		$args       = [];
		$company_id = GetParam( "company", true );
		$result     = "";
		$result     .= Core_Html::GuiHeader( 1, "New worker!" );
		$result     .= Core_Html::GuiHeader( 2, "Enter worker email address" );
		$result     .= Core_Html::GuiInput( "worker_email", null, $args ) . "<br/>";
		$message    = SqlQuerySingleScalar( "select post_content from wp_posts where post_title = 'welcome_message'" );
		if ( ! $message ) {
			$message = "Welcome to work with me in Focus management tool!\n" .
			           GetUserName( get_user_id() );
			$result  .= "You can create default message as a private post with title welcome_message" . "\n" .
			            Core_Html::GuiHyperlink( "here", "/wp-admin/post-new.php" );
		} else {
			$message = strip_tags( $message );
			$post_id = SqlQuerySingleScalar( "select id from wp_posts where post_title = 'welcome_message'" );
			$result  .= Core_Html::GuiHyperlink( "Edit this message here", "/wp-admin/post.php?post=$post_id&action=edit" );
		}
		$result .= Core_Html::gui_textarea( "welcome_message", $message );
		$result .= Core_Html::GuiButton( "btn_add_worker", "Add",
			array( "action" => 'company_add_worker(' . QuoteText( self::getPost() ) . "," . $company_id . ')' ) );

		return $result;
	}

	static function ActiveQuery() {
		return " (isnull(preq) or preq_done(id)) and (date is null or date(date) <= Curdate())"
		       . " and (mission_id is null or mission_id = 0) and status < 2";
	}

	static public function AddProjectMember( $i, $project_id, $args ) {
		return $i;

		$args           = self::Args();
		$project        = new Org_Project( $project_id );
		$result         = $i . Core_Html::GuiHeader( 1, $project->getName() );
		$result         .= self::gui_select_worker( "new_worker", null, $args );
		$args["action"] = "project_add_worker('" . self::getPost() . "', " . $project_id . ")";

		$result .= Core_Html::GuiButton( "btn_add_worker", "Add", $args );
//		$table = array();
//		foreach ($project->all_members() as $member) {
//			$w = new Org_Worker($member);
//			$table[$member] = array("name" => $w->getName());
//		}
//		$result .= Core_Gem::GemArray($table, $args, "project_members");

		return $result;
	}

	static function show_new_task( $mission = false, $new_task_id = null ) {
		die (1); // Suspected as not in use.
		$db_prefix    = GetTablePrefix();
		$table_prefix = GetTablePrefix();

		$args = self::Args( "tasklist" );
//		$args["selectors"]        = array(
//			"project_id" => "Focus_Views::gui_select_project",
//			"owner"      => "Focus_Views::gui_select_worker",
//			"creator"    => "Focus_Views::gui_select_worker",
//			"preq"       => "gui_select_task",
//			"team"       => "Focus_Views::gui_select_team"
//		);
		$args["values"] = array( "owner" => get_user_id(), "creator" => get_user_id() );
		$args["header"] = true;
//		$args["header_fields"]    = array(
//			"date"             => "Start after",
//			"task_description" => "Task description",
//			"project_id"       => "Project",
//			"location_address" => "Address",
//			"location_name"    => "Location name",
//			"priority"         => "Priority",
//			"preq"             => "Prerequisite",
//			"creator"          => "Creator"
//		);
		$args["mandatory_fields"] = array( "project_id", "priority", "team", "task_description" );

//		$args["fields"]     = array( "task_description", "project_id", "priority", "date", "preq", "creator", "team" );
		$args['post_file']  = Focus::getPost();
		$args['form_table'] = 'tasklist';

		// Todo: check last update time
		if ( $mission and function_exists( "Flavor_Mission::gui_select_mission" ) ) {
			array_push( $args["fields"], "location_name", "location_address", "mission_id" );
			$i = new Core_Db_MultiSite();
			$i->UpdateFromRemote( "missions", "id", 0, null, null );
			$args["selectors"]["mission_id"]              = "Flavor_Mission::gui_select_mission";
			$args["header_fields"]["mission_id"]          = "Mission";
			$args["mandatory_fields"]["location_name"]    = true;
			$args["mandatory_fields"]["location_address"] = true;
		}

		$args["worker"] = get_user_id();
		$user           = new Org_Worker( get_user_id() );
		$result         = "";

		$args["companies"] = $user->
		// SqlQuerySingleScalar( "select company_id from ${db_prefix}working where user_id = " . get_user_id() );
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

			$project_tasks = Core_Gem::GemTable( "${table_prefix}tasklist", $project_args );

			// Set default value for next task, based on new one.
			$args["values"] = array( "project_id" => $project_id, "team" => $new_task->getTeam() );
		}

		$result .= Core_Gem::GemAddRow( "tasklist", "New Task", $args );
		// $result .= Core_Gem::GemAddRow( "tasklist", "New task", $args );
		$result .= $project_tasks;

		return $result;
	}

	static function gui_select_task( $id, $value, $args ) {
		if ( $value > 0 ) {
			$t        = new Focus_Tasklist( $value );
			$selected = $value . ")" . $t->getTaskDescription();
		} else {
			$selected = $value;
		}

		$args["selected"] = $selected;
		$args["name"]     = "task_description";
		$args["query"]    = GetArg( $args, "query", " status = 0 " );
		//	              "include_id" => 1,
		//	              "datalist" =>1,
		$args["multiple_inline"] = 1;

		return Core_Html::GuiAutoList( $id, "tasks", $args );
	}

	function enqueue_scripts()
	{
		$version = Focus::instance()->version;

		$file = FLAVOR_INCLUDES_URL . 'js/sorttable.js';
		wp_enqueue_script( 'sorttable', $file, null, $version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $version, false );

		$file = FOCUS_INCLUDES_URL . 'focus.js';
		wp_enqueue_script( 'focus', $file, null, $version, false );

	}

	function next_page_tasklist()
	{
		return "/project";
	}

	function gem_next_page_projects()
	{
		return "/focus";
	}

	function focus_edit_project()
	{
		$id = GetParam("id");
		$post_file = Flavor::getPost();
		$result = "";
		$p = new Org_Project($id);
		$args = self::Args("projects");
		$result .= Core_Html::GuiHeader(1, ETranslate("Editing project") . " " . $p->getName());
		$result .= Core_Html::GuiHeader(2, ETranslate("General"));
		$result .= Core_Gem::GemElement("projects", $id, $args);

		$result .= Core_Html::GuiHeader(2, ETranslate("Members"));
		$args = [];
		$args["add_checkbox"] = true;
		$args["checkbox_class"] = "workers";
		$result .= Core_Html::gui_table_args($p->AllWorkers(true), null, $args);
		$result .= Core_Html::GuiButton("btn_project_remove", "Remove", "project_remove_member('$post_file', $id)");
		$result .= Flavor_Org_Views::gui_select_worker("worker", null, $args);
		$result .= Core_Html::GuiButton("btn_add", "Add", "project_add_member('$post_file', $id)");

		print $result;
	}
}