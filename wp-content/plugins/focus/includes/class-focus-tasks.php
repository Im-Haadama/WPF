<?php

require_once (FOCUS_INCLUDES . 'gui.php');

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
		$this->post_file = $post_file;
		$this->version = "1.0";
		$this->nav_menu_name = null;

//		add_action( 'get_header', array( $this, 'create_nav' ) );
	}

	public static function instance($post = null) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $post );
		}

		return self::$_instance;
	}

	public static function focus_operation() {
		$operation = get_param( "operation", false, "focus_main" );
//		print $operation;
		if ( get_user_id( true ) ) {
			print Focus::instance()->handle_operation( $operation );
		}
	}

	public function enqueue_scripts() {
		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'data', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );

		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );
	}

	static function show_main_wrapper()
	{
		$user_id = get_user_id(true);
		$args = self::Args();
		if ($user_id) return self::main($user_id, $args);
	}

	static function show_repeating_task()
	{
		return "repeating";
	}

	static function show_project_tasks()
	{
		return __FUNCTION__;
	}

	static function gui_select_worker($id, $selected, $args){
		// $events = GetArg($args, "events", null);
		$edit = GetArg($args, "edit", true);
		$companies = Org_Worker::GetCompanies(get_user_id());

		$debug = false; // (get_user_id() == 1);
		$args["debug"] = $debug;
		$args["name"] = "client_displayname(user_id)";
		$args["where"] = "where is_active=1 and company_id in (" . comma_implode($companies) . ")";
		$args["id_key"] = "user_id";
		$args["selected"] = $selected;
		$args["query"] = (isset($args["query_team"]) ? $args["query_worker"] : null);

		if ($edit) {
			$gui = Core_Html::GuiSelectTable($id, "im_working", $args);
			return $gui;
		} else
			return ($selected > 0) ? sql_query_single_scalar("select client_displayname(user_id) from im_working where user_id = " . $selected) :
				"";
	}
	static function gui_select_project($id, $value, $args)
	{
		$edit = GetArg($args, "edit", true);
		$new_row = GetArg($args, "new_row", false);

		if (! $edit)
		{
			return Org_Project::GetName($value);
		}
		// Filter by worker if supplied.
		$user_id = GetArg($args, "worker_id", get_user_id());
		if ( !$user_id ) {
			throw new Exception( __FUNCTION__ .": No user " . $user_id);
		}

		$form_table = GetArg($args, "form_table", null);
		$events = GetArg($args,"events", null);

		$projects = Org_Project::GetProjects($user_id);
		$projects_list = [];
		foreach($projects as $project_id => $project_name) $projects_list[] = array("project_id" => $project_id, "project_name" => $project_name);
		$result = Core_Html::gui_select( $id, "project_name", $projects_list, $events, $value, "project_id" );
		if ($form_table and $new_row) { // die(__FUNCTION__ . ":" . " missing form_table");
			$result .= Core_Html::GuiButton( "add_new_project", "New Project", array("action" => "add_element('project', '" . $form_table . "', '" . get_url() . "')", "New Project" ));
		}

		return $result;
	}

	static function Args()
	{
		return array("page" => get_param("page", false, -1),
			"post_file" => self::getPost());
	}

	static function getPost()
	{
		return self::instance()->post_file;
	}
	/**
	 * @param $operation
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function handle_focus_show( $operation) {
		$args = [];
		print __FUNCTION__ . ':' . $operation ."<br/>";
//		if ( ( $done = Focus_Tasks::handle_focus_do( $operation, $args ) ) !== "not handled" ) {
//			return $done;
//		}

		// Actions are performed and return to caller.
		// Page are $result .= and displayed in the end. (to handle the header just once);
		$action_url = get_url( 1 );
		$result     = ""; // focus_header($header_args);

		$args["page"] = get_param( "_page", false, 1 );

		$debug = 0;
		if ( $debug ) {
			print "operation: " . $operation . "<br/>";
		}
		// show/save <obj_type>
		switch ( $operation ) {
			case "show_settings":
				return self::show_settings( get_user_id() );
			case "focus_main":
				// $new = get_param("new", false, null);
				if ( ! ($id = get_user_id(true)) > 0 ) return " No user ";
				return self::focus_main( $id, $args );
				break;
			case "edit_organization":
				return edit_organization();
			case "show_worker":
				// $new = get_param("new", false, null);
				$id                     = get_param( "id", true );
				$header_args["view_as"] = $id;
				return self::focus_main( $id, $args );
			case "show_repeating_tasks":
			case "show_templates":
				$args["table"] = true;
				$args["new"]   = get_param( "new", false, 0 );
				$new           = get_param( "new", false, null );
				$freq          = get_param( "freq", false, null );
				$args["query"] = "repeat_freq like '%$freq%'";
				return self::show_templates( $args, null, $new );
			case "show_template":
				$id     = get_param( "id", true );
				return self::show_templates( $args, $id );
			case "show_task":
				$id = get_param( "id", true );
				if ( $id ) {
					return self::show_task( $id );
				}
				break;

			case "show_project":
				$id           = get_param( "project_id", true );
				$args         = [];
				$args["edit"] = get_param( "edit", false, false );
				if ( $id ) return self::show_project( $id, $args );
				break;
			case "bad_url":
				$id          = get_param( "id" );
				$result      .= "Url for task $id is wrong<br/>";
				$template_id = task_template( $id );
				return Core_Html::GuiHyperlink( "Edit template $template_id", self::get_link("template", $template_id));
			case "show_new_project":
				$args              = [];
				$args["next_page"] = get_param( "next_page", false, null );
				$args["post_file"] = getPost();
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
				$result         .= Core_Gem::GemElement( "im_projects", $project_id, $args );
				$args ["query"] = "project_id = $project_id and status < 2";
				$args["page"]   = get_param( "page", false, null );
				$args["links"]  = array( "id" => get_page_name("focus_task") . "?task_id=%s" );
				return Core_Gem::GemTable( "im_tasklist", $args );
			case "show_new_team":
				$args                     = [];
				$args["next_page"]        = get_param( "next_page", false, null );
				$args["post_file"]        = "/wp-content/plugins/focus/post.php";
				$args["selectors"]        = array( "manager" => "Focus_Tasks::gui_select_worker" );
				$args["mandatory_fields"] = array( "manager", "team_name" );
				return Core_Gem::GemAddRow( "im_working_teams", "Add a team", $args );
			case "show_new_task":
				$mission = get_param( "mission", false, null );
				$new     = get_param( "new", false );
				return self::show_new_task( $mission, $new ); // after the first task, the new tasks belongs to the new tasks' project will be displayed.
			case "last_entered":
				if ( get_user_id() != 1 ) {
					return false;
				}
				$args                 = array();
				$args["last_entered"] = 1;
				return Focus_Tasks::active_tasks( $args );
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
					die( "no permissions "  . __FUNCTION__);
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
				$team_id   = get_param( "id", true );
				$worker_id = get_param( "user", true );
				team_remove_member( $team_id, $worker_id );
				handle_focus_operation( "show_team", null );
				return $result;

			case "show_team":
				$team_id = get_param( "team_id", true );
				return self::show_team( $team_id );

			case "show_add_member":
				$team_id = get_param( "id", true );
				$result  .= Core_Html::gui_header( 1, "Adding member to team" . sql_query_single_scalar( "select team_name from im_working_teams where id = " . $team_id ) );
				$result  .= gui_select_worker( "new_member" );
				$result  .= gui_label( "team_id", $team_id, true );
				$result  .= Core_Html::GuiButton( "btn_add_member", "add_member()", "Add" );

				$result .= "<br/>";
				$result .= Core_Html::GuiHyperlink( "Invite college to your company", add_to_url( array( "operation" => "show_add_to_company" ) ) );
				return $result;

			case "show_add_to_company":
				$company_id = get_param( "id", true );
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

				if ( $id = get_param( "project_id" ) ) {
					$result .= show_project( $id );
				}
				$result .= show_projects( get_url(), get_user_id() );
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

			case "show_edit_team":
				$team_id = get_param( "id" );
				return self::show_edit_team( $team_id );

			case "show_tasks":
				$query  = Core_Data::data_parse_get( "im_tasklist", array( "operation" ) );
				$ids    = data_search( "im_tasklist", $query );
				return self::show_tasks( $ids );

			case "show_edit_company":
				$company_id = get_param( "company_id", true );
				$page       = get_param( "page", false, 1 );
				return show_edit_company( $company_id, $page );

			default:
				return false;
		}
	}

	static function show_project_wrapper()
	{
		$new = get_param("new");
		if ($new) {
			$project = Focus_Project::create_from_task($new);
			return $project->getId();

		}

		return self::show_project( get_user_id() );
	}

	static function show_edit_team($team_id)
	{
		$result = Core_Html::gui_header(1, "Edit team");
		$args["selectors"] = array("manager" => "Focus_Tasks::gui_select_worker");
		$args["post_file"] = get_url(1) . "?team_id=" . $team_id;
		$result .= Core_Gem::GemElement("im_working_teams", $team_id, $args);

		$result .= Core_Html::gui_header(2, "Team members");
		$table = array();
		$table["header"] = array("name");
		foreach (Org_Team::team_all_members($team_id) as $member){
			$table[$member]["name"] = get_user_name($member);
		}

		$args["add_checkbox"] = true;
		$result .= Core_Gem::GemArray($table, $args, "team_members");

		$result .= Core_Html::gui_header(1, "add member");
		$result .= gui_select_worker("new_member", null, $args);
		$result .= Core_Html::GuiButton("btn_add_member", "add_team_member(" . $team_id . ")", "add");

		$tasks = sql_query_array_scalar("select id from im_tasklist where team = $team_id");
		if ($tasks)
			$result .= self::show_tasks($tasks);
		else
			$result .= "No tasks";


		return $result;
//			$result = Core_Html::gui_header(1, "edit team" . team_get_name($team_id));
//			$result .=  Core_Html::gui_header(2, "members");
//			foreach (team_all_members($team_id) as $member) $result .= get_user_name($member) . " ";

	}

	static function show_teams()
	{
		$action_url = "/wp-content/plugins/focus/post.php";
		$result = "";
		if ( ! im_user_can( "edit_teams" ) ) {
			$result .= "No permissions";
		}
		$args              = [];
		$args["post_file"] = "/wp-content/plugins/focus/post.php";
		$args["selectors"] = array( "manager" => "Focus_Tasks::gui_select_worker" );
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
		$result          .= Core_Html::gui_header( 1, "All system teams" );
		$result          .= Core_Gem::GemTable( "im_working_teams", $args );

		unset( $args["actions"] );
		$args["mandatory_fields"] = array( "manager", "team_name" );
		$result                   .= Core_Gem::GemAddRow( "im_working_teams", "Add a team", $args );

		return $result;
	}

	static function show_new_task($mission = false, $new_task_id = null)
	{
		$args = array();
		$args["selectors"] = array("project_id" =>  "Focus_Tasks::gui_select_project",
		                           "owner" => "Focus_Tasks::gui_select_worker",
		                           "creator" => "Focus_Tasks::gui_select_worker",
		                           "preq" => "gui_select_task",
		                           "team" => "Focus_Tasks::gui_select_team"	);
		$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
		$args["header"] = true;
		$args["header_fields"] = array("date"=>"Start after",
		                               "task_description" => "Task description",
		                               "project_id" => "Project",
		                               "location_address" => "Address",
		                               "location_name" => "Location name",
		                               "priority" => "Priority",
		                               "preq" => "Prerequisite",
		                               "creator" => "Creator");
		$args["mandatory_fields"] = array("project_id", "priority", "team", "task_description") ;

		$args["fields"] = array("task_description", "project_id", "priority", "date", "preq", "creator", "team");
		$args['post_file'] = "/wp-content/plugins/focus/post.php";
		$args['form_table'] = 'im_tasklist';

		// Todo: check last update time
		if ($mission and function_exists("gui_select_mission"))
		{
			array_push($args["fields"],"location_name", "location_address", "mission_id");
			$i = new Core_Db_MultiSite();
			$i->UpdateFromRemote( "im_missions", "id", 0, null, null );
			$args["selectors"]["mission_id"] = "gui_select_mission";
			$args["header_fields"]["mission_id"] = "Mission";
			$args["mandatory_fields"]["location_name"] = true; $args["mandatory_fields"]["location_address"] = true;
		}

		$args["worker"] = get_user_id();
		$args["companies"] = sql_query_single_scalar("select company_id from im_working where user_id = " . get_user_id());
		$args["hide_cols"] = array("creator" => 1);
		$args["next_page"] = self::get_link("project");
		Core_Data::set_args_value($args); // Get values from url.

		$result = ""; $project_tasks = "";
		if ($new_task_id) $result .= im_translate("Task added") . "<br/>";

		if ($new_task_id) {
			$project_args = $args;
			$new_task = new Focus_Tasklist($new_task_id);
			$project_id = $new_task->getProject();
			$project_args["title"] = "Project " . Org_Project::GetName($project_id);
			$project_args["query"] = "project_id=" . $project_id . " and status < 2";
			$project_args["order"] = "id desc";
			unset($project_args["fields"]);
			$project_args["page"] = 1;

			$project_tasks = Core_Gem::GemTable("im_tasklist", $project_args);

			// Set default value for next task, based on new one.
			$args["values"] = array("project_id" => $project_id, "team" => $new_task->getTeam());
		}

		$result .= Core_Gem::GemAddRow("im_tasklist", "New task", $args);
		$result .= $project_tasks;

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
	static function show_projects($owner, $non_zero = false, $is_active = true ) {
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
		$args["links"] = array( "id" => self::task_link("%s") );
		$args["title"] = im_translate( "משימות בפרויקט" ) . " " . Org_Project::GetName( $project_id );

//	print $sql;
		$result = Core_Gem::GemTable( "im_tasklist", $args );
		$result .= GuiHyperlink( "Edit project", add_to_url( "edit", 1 ) );

		return $result;
	}


	static function handle_focus_do( $operation ) {
//		print "focus: $operation";

		if (strpos($operation, "data_") === 0)
			return handle_data_operation($operation);

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
						if ( Org_Team::team_remove_member( $team_id, $ids ) ) {
							print "done";
							return;
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
					if ($t->Ended()) {
						Focus_Tasklist::create_tasks( null, false );
						return true;
					}
				}
				return;

			case "cancel_task":
				$task_id = get_param( "id" );
				if ( Focus_Tasklist::task_cancelled( $task_id ) ) {
					print "done";
				}
				Focus_Tasklist::create_tasks( null, false );

				return;

			case "create_tasks":
				print Focus_Tasklist::create_tasks( null, true );

				return;

			case "postpone_task":
				$task_id = get_param( "id" );
				$T       = new Focus_Tasklist( $task_id );
				$r       = $T->Postpone();
				Focus_Tasklist::create_tasks( null, false );

				return $r;

			case "pri_plus_task":
				$task_id = get_param( "id" );
				$T       = new Focus_Tasklist( $task_id );
				$T->setPriority( $T->getPriority() + 1 );
				Focus_Tasklist::create_tasks( null, false );
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
				if ( Core_Data::update_data( $table_name ) ) {
					if ( $table_name == 'im_task_templates' ) {
						$row_id = intval( get_param( "id", true ) );
						if (sql_query( "update im_task_templates set last_check = null where id = " . $row_id ))
							return "done";
					}
				}

				return "not handled";

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
				Focus_Tasklist::task_started( $task_id, get_user_id() );

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
				$url = Focus_Tasklist::task_url( $task_id );
				if ( ! $url ) return true;
				$url_headers = @get_headers( $url );
				if ( ! $url_headers || strstr( $url_headers[0], "404" ) ) {
					print get_url( 1 ) . "?operation=bad_url&id=" . $task_id;

					return false;
				}
				if ( strlen( $url ) > 1 ) {
					print $url;
				}

				return true;

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
	static function main( $user_id, $args = null ) {
		if (! $args) $args = [];

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
				$result .= Core_Html::GuiHyperlink( Org_Team::team_get_name( $team ) . "(" . $count . ")", "?operation=show_team&id=" . $team );
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
		$args["selectors"]["team"] = "Focus_Tasks::gui_select_team";
		if ( $teams and count( $teams ) ) {
			$args["query"] = " team in (" . comma_implode( $teams ) . ") and owner != " . $user_id;
			$result        .= Focus_Tasks::active_tasks( $args );
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks teams I'm a member of (team in my_teams). Not assigned                                              //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"] = im_translate( "My teams tasks" );
		$teams         = Org_Worker::GetTeams( $user_id );
		// print "teams: " . comma_implode($teams) . "<br/>";
		$args["fields"][]          = "team";
		$args["selectors"]["team"] = "Focus_Tasks::gui_select_team";
		if ( $teams and count( $teams ) ) {
			$args["query"] = " team in (" . comma_implode( $teams ) . ") and owner is null";
			$result        .= Focus_Tasks::active_tasks( $args );
		}

		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Tasks I've created. Assigned to some else                                                                 //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		$args["title"]       = im_translate( "Tasks I've initiated to other teams" );
		$args["query"]       = " creator = " . $user_id . " and (owner != " . $user_id . ' or isnull(owner)) ' . ($teams ? ' and team not in (' . comma_implode( $teams ) . ")" : '');
		$args["limit"]       = get_param( "limit", false, 10 );
		$args["active_only"] = get_param( "active_only", false, true );
		$result              .= Focus_Tasks::active_tasks( $args );

		//print "c=" . $args["count"];
		if ( ! $args["count"] ) {
			$result .= im_translate( "No active tasks!" ) . "<br/>";
			$result .= im_translate( "Let's create first one!" ) . " ";
			$result .= Core_Html::GuiHyperlink( "create task", "?operation=show_new_task" ) . "<br/>";
		}

		// if (get_user_id() != 1) return;
		print $result;
		return $result;
	}

	static function active_tasks( &$args = null ) {
		$args["count"]           = 0;
		$args["drill"]           = true;
		$args["drill_operation"] = "show_tasks";

		$table_name = "im_tasklist";
		$title      = GetArg( $args, "title", "" );

		$action_url = "/wp-content/plugins/focus/post.php";
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

		$links["task_template"] = self::get_link("template", "%s");
		$links["id"]            = self::get_link("task", "%s");
		// Use drill, instead - $links["project_id"] = $page_url . "?operation=show_project&id=%s";
		$args["links"] = $links;
		$args["post_file"] = self::getPost();
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

		$sql = "select " . comma_implode( $fields ) . " from $table_name $query $order ";

		$result = "";
		try {
			if ( isset( $_GET["debug"] ) ) {
				print "sql = $sql<br/>";
			}
			$args["sql"] = $sql;
			$table       = Core_Gem::GemTable("im_tasklist", $args );
//		print "CC=" . $args["count"] . "<br/>";
			// $table = GuiTableContent( $table_name, $sql, $args );
			// if (! $args["count"]) return "";
			if ( $table ) {
				// if (strlen($title)) $result = Core_Html::gui_header(2, $title);
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
			$result .= Core_Html::GuiHyperlink( "More", add_to_url( "page", $page + 1 ) ) . " ";
			$result .= Core_Html::GuiHyperlink( "Not paged", add_to_url( "page", - 1 ) ) . " "; // All pages
		}
		$result .= Core_Html::GuiHyperlink( "Not filtered", add_to_url( "active_only", 0 ) ); // Not filtered

		$result .= " " . Core_Html::GuiHyperlink( "Add task", self::get_link("task") ); // id == 0 -> new

		$result .= " " . Core_Html::GuiHyperlink( "Add delivery", add_to_url( "operation", "show_new_task&mission=1" ) );

		return $result;
	}

	static function show_tasks($ids)
	{
		$args = [];
		$args["query"] = "id in (" . comma_implode($ids) . ")";
		return Focus_Tasks::active_tasks($args);
	}

	/**
	 * @param $team_id
	 * @param $active_only
	 *
	 * @return string
	 */
	static function show_team( $team_id, $active_only = true ) {
		$result = "";
		$result .= Core_Html::gui_header( 1, "Team " . Org_Team::team_get_name( $team_id ) );
		$result .= Core_Html::GuiHyperlink( "Include non active", add_to_url( "active_only", 0 ) );

		// $team_members = team_members($team_id);

//		$result .=  Core_Html::gui_header(2, get_customer_name($user_id) . " " . $user_id);
		$args           = array( "active_only" => $active_only );
		$args["query"]  = " team=" . $team_id;
		$args["fields"] = array( "id", "task_description", "project_id", "priority", "task_template", "owner" );
		$result         .= Focus_Tasks::active_tasks( $args );

		return $result;
	}

	static function show_template_wrapper()
	{
		$row_id = get_param("id", false);
		if (! $row_id) return self::show_new_template();
		return self::show_templates($args, $row_id);
	}

	static function show_task_wrapper()
	{
		$row_id = get_param("id", false);
		if (! $row_id) return self::show_new_task();
		return self::show_task($row_id);
	}

	static function show_new_template()
	{
		$result = "";
//		$result                   .= Core_Html::gui_header( 1, "יצירת תבנית חדשה" );
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
		$result                   .= Core_Html::NewRow( "im_task_templates", $args );
		$result                   .= Core_Html::GuiButton( "btn_template", "add", array("action" => "data_save_new('/focus/focus-post.php', 'im_task_templates')"));
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
		if (! $row_id) return self::show_new_task();
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

		$args["worker"]    = get_user_id();
		$args["companies"] = Org_Worker::GetCompanies( get_user_id() );
		$args["debug"]     = 0; // get_user_id() == 1;
		$args["worker"]    = get_user_id();
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
	function focus_check_user()
	{
//	$worker_id = sql_query_single_scalar("select id from im_working where user_id = " . get_user_id());
//
//	print "worker_id=$worker_id<br/>";
//
//	if (! $worker_id) {
//		sql_query( "insert into im_working (user_id, project_id, rate, report, volunteer, day_rate, is_active, company_id) " .
//		           " values ( " . get_user_id() . ", 0, 0, 0, 0, 0, 1, 0)" );
//		$worker_id = sql_insert_id();
//		if ( ! $worker_id ) {
//			print "can't insert worker info";
//
//			return false;
//		}
//	}
//	print "worker id: " . $worker_id . "<br/>";

		// Check if user has company
		$user_id = get_user_id();
		$company_ids = worker_get_companies($user_id);
		if (! count($company_ids)){
			print "Ne need some information to get started!<br/>";
			$args = array("values" => array("admin" => get_user_id()));
			try {
				print Core_Html::gui_header(1, "Company details");
				print NewRow( "im_company", $args );
			} catch ( Exception $e ) {
				print "Error F1: " . $e->getMessage();
				return false;
			}

			print Core_Html::GuiButton( "btn_add", "Add", array("action" => "data_save_new('/focus/focus-post.php?operation=new_company_user', 'im_company', location_reload)"));

			// print gui_input("company", )
			return false;
		}

		// Check if user has team.
		$team_ids =  Org_Worker::GetTeams($user_id);
		if (!$team_ids or ! count($team_ids)){
//		print "uid= $user_id" . Core_Html::Br();
//		var_dump($team_ids); Core_Html::Br();
//		die ("Error #F2. Please report");
			team_add($user_id, im_translate("Personal team") . " " . get_customer_name($user_id));
		}

		$project_ids = worker_get_projects($user_id);
		if (is_null($project_ids) or ! count($project_ids)) {
			project_create($user_id, im_translate("first project"), $company_ids[0]);
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
	 * @param $page
	 *
	 * @return string
	 * @throws Exception
	 */
	function show_edit_company($company_id, $page)
	{
		$result = Core_Html::gui_header(1, company_get_name($company_id));
		$args = [];
		$args["query"] = "manager = 1";
		$args["links"] = array("id" => add_to_url(array("operation" => "show_edit_team&id=%s")));
		$args["selectors"] = array("team_members" => "gui_show_team");
		$args["page"] = $page;

		$teams = Core_Data::TableData("select id, team_name from im_working_teams where manager in \n" .
		                   "(select user_id from im_working where company_id = $company_id) order by 1", $args);
		foreach ($teams as $key => &$row)
			if ($key == "header") $row [] = im_translate("Team members");
			else $row["team_members"] = comma_implode(team_all_members($row["id"]));
		//GemTable("im_working_teams", $args);
		$result .= Core_Gem::GemArray($teams, $args, "company_teams");

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
	static function show_templates(&$args, $template_id = 0, $new = null )
	{
		$url = get_url(1);

		$result = "";
		$action_url = "/wp-content/plugins/focus/post.php"; // get_url(1);//  "/focus/focus-post.php";

		$args["worker"] = get_user_id();
		$args["companies"] =  Org_Worker::GetCompanies(get_user_id());
		$args["selectors"] = array("project_id" =>  "Focus_Tasks::gui_select_project", "owner" => "Focus_Tasks::gui_select_worker",
		                           "creator" => "Focus_Tasks::gui_select_worker", "repeat_freq" => "gui_select_repeat_time", "team" => "Focus_Tasks::gui_select_team");
		$args["fields"] = array("id", "task_description", "project_id", "priority", "team", "repeat_freq", "repeat_freq_numbers", "working_hours", "condition_query", "task_url",
			"template_last_task(id)");
		$args["header_fields"] = array("task_description" => "Task description", "project_id" => "Project", "priority" => "Priority",
		                               "team" => "Team", "repeat_freq" => "Repeat Frequency", "repeat_freq_numbers" => "Repeat times", "working_hours" => "Working hours",
			"Task site");

		if ($template_id){
			// print Core_Html::gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";
			$args["title"] = "Repeating task";
			$args["post_file"] = $action_url;

			$template = Core_Gem::GemElement("im_task_templates", $template_id, $args);
			if (! $template) {
				$result .= "Not found";
				return $result;
			}
			$result .= $template;

			$tasks_args = array("links" => array("template_id" => self::get_link("task", "%s")));
			$tasks_args["class"] = "sortable";

			if (get_user_id() == 1){
				$output = "";
				$row = sql_query_single_assoc("SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority, creator, team " .
				                              " FROM im_task_templates where id = $template_id");

				Focus_Tasklist::create_if_needed($template_id, $row, $output, 1, $verbose_line);
				// $result .= $output;
			}

			$sql = "select * from im_tasklist where task_template = " . $template_id;
			$sql .= " order by date desc limit 10";
//			print $sql;
			$table = Core_Html::GuiTableContent("last_tasks", $sql, $tasks_args);
			if ($table)
			{
				$result .= Core_Html::gui_header(2, "משימות אחרונות");
				$result .= Core_Html::gui_header(2, "משימות אחרונות");
				$result .= $table;
			}

			return $result;
		}

		if ($page = get_param("page")) { $args["page"] = $page; unset ($_GET["page"]); };

		$query = (isset($args["query"]) ? $args["query"] : " 1");
		if (get_param("search", false, false)){
			$ids = data_search("im_task_templates", $args);
			if (! $ids){
				$result .= "No templates found" . Core_Html::Br();
				return $result;
			}
			$query .= " and id in (" . comma_implode($ids) . ")";
		}

		$args["class"]     = "sortable";
		$args["links"]     = array ("id" => $url . "?operation=show_template&id=%s" );
		$args["header"]    = true;
		$args["drill"] = true;
		$args["edit"] = false;
		$args["actions"] = array(array("delete", $action_url . "?operation=delete_template&row_id=%s;action_hide_row"));
		$args["query"] = $query;
		$args["order"] = " id " . ($new ? "desc" : "asc");

		$result = Core_Html::GuiHyperlink( "Add repeating task", get_url( true ) . "?operation=new_template" );

		$result .= Core_Gem::GemTable("im_task_templates", $args);
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
		$user = wp_get_current_user();
		$result = Core_Html::gui_header(2, "teams");

		$args = [];
		$args["links"] = array("id" => add_to_url(array("operation" => "show_team", "id" => "%s")));
		$args["selectors"] =  array("manager" => "Focus_Tasks::gui_select_worker");
		$args["edit"] = false;
		$result .= GuiTableContent("working_teams", "select * from im_working_teams where manager = " . $user->id, $args);

		$result .= Core_Html::GuiHyperlink("add", add_to_url("operation", "show_new_team"));
		// print GuiTableContent("");

		return $result;
	}

	/**
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	function edit_projects($args) // Edit projects that I manage.
	{
		$result = Core_Html::gui_header(2, "Projects");

		$global = GetArg($args, false, "global");
		$args["links"] = array("ID" => add_to_url(array("operation" => "show_edit_project", "id" => "%s")));
		$args["selectors"] =  array("manager" => "Focus_Tasks::gui_select_worker");
		$args["edit"] = false;
		$args["actions"] = array (array("delete", get_url(1) . "?operation=cancel_im_projects&id=%s;action_hide_row"));
		$base_query = "is_active = 1 ";
		$args["fields"] = array("ID", "project_name", "project_contact", "project_priority", "company");
		if ($global) // A global user can see all projects
		{
			$args["query"] = $base_query;
			$result .= Core_Gem::GemTable("im_projects", $args); // "select * from im_projects ", $args);
		} else { // Ordinary user can see only projects he's working in.
			$worker_id = GetArg($args,"worker_id", null);
			if (! $worker_id) die ("no worker_id");
			$companies = worker_get_companies(get_user_id());

			foreach ($companies as $company){
				$result .= Core_Html::gui_header(1, company_get_name($company));
				$args["query"] = $base_query . " and company = $company";
				$result .= Core_Gem::GemTable("im_projects", $args); //"select * from im_projects where company = $company", $args);
			}
		}

		$result .= Core_Html::GuiHyperlink("add", add_to_url("operation", "show_new_company"));

		return $result;
		// print GuiTableContent("");
	}

	/**
	 * @param null $args
	 *
	 * @return string|null
	 */

//// team_add_worker($team_id, team_manager($team_id));
//// $result .= Core_Html::gui_header(1, im_translate("Team") . ": " . team_get_name($team_id));
//$args = array("selectors" => array("ID" => "Focus_Tasks::gui_select_worker"),
//              "edit" => false,
////				array( "cancel", $action_url . "?operation=cancel_task&id=%s;action_hide_row" ),
//
//              "actions" => array(array("remove", add_to_url(array("operation" => "remove_from_team", "user" => "%s")))));
//$result .= GuiTableContent("im_working_teams",
//	'select ID from wp_users where worker_teams(id) like "%:' . $team_id . ':%"', $args);
//$result .= Core_Html::GuiHyperlink("add member", add_to_url("operation" , "show_add_member"));
//
//$args = [];
//$args["page"] = get_param("page", false, 1);
//$args["query"] = " team = $team_id";
//$result .= GemTable("im_tasklist", $args);

	/**
	 * @param $manager_id
	 * @param $url
	 *
	 * @return string
	 * @throws Exception
	 */
	function managed_workers($manager_id, $url)
	{
		$teams = sql_query_array_scalar("select id from im_working_teams where manager = " . $manager_id);

		if (!$teams) return "";

		$result = "";

		foreach ($teams as $team_id)
		{
			$result .= Core_Html::GuiHyperlink(Org_Team::team_get_name($team_id), $url . "?team=" . $team_id);
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
	function create_new_sequence()
	{
		$user_id = get_user_id();
		$project = get_param("project", true);
		$priorty = get_param("priority", true);

		$i = 1;
		$description = null;
		$preq = null;
		while (isset($_GET["task" . $i]))
		{
			$description = get_param("task" . $i);
			$preq = task_new($user_id, $project, $priorty, $description, $preq);
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
	function task_new($user_id, $project, $priority, $description, $preq = null)
	{
		$creator = $user_id;
		$owner = $user_id; // For now
		is_numeric($priority) or die("bad project id");
		is_numeric($priority) or die ("bad priority");
		strlen($description) > 2 or die ("short description");

		$sql = "insert into im_tasklist (task_description, project_id, priority";

		if ($preq) $sql .= ", preq";

		$sql .= ", creator, owner) values (" .
		        quote_text($description) . "," .
		        $project . "," .
		        $priority . ",";
		if ($preq) $sql .=  $preq . ",";
		$sql .= $user_id . "," . $owner .")";

		sql_query($sql);
		return sql_insert_id();
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	function edit_organization()
	{
		$user_id = get_user_id();
		$result = "";
		$result .= Core_Html::GuiHyperlink("Edit organization", add_to_url("operation" , "show_staff")) . " ";

		$result .= Core_Html::GuiHyperlink("My projects", add_to_url("operation", "show_edit_projects")) . " ";
		$my_companies = worker_get_companies($user_id, true);
		if ($my_companies) foreach ($my_companies as $company){
			$result .= Core_Html::GuiHyperlink(company_get_name($company), add_to_url( array("operation" => "show_edit_company", "company_id" => $company))) . " ";
		}

		if (im_user_can("edit_teams")){ // System editor
			$result .= "<br/>" . im_translate("System wide:");
			$result .= Core_Html::GuiHyperlink("All teams", add_to_url("operation" , "show_edit_all_teams")) . " ";
			$result .= Core_Html::GuiHyperlink("All projects", add_to_url("operation", "show_edit_all_projects")) . " ";
		}

		return $result;
	}

	/**
	 * @param $template_id
	 *
	 * @return string
	 */
	function template_creator($template_id)
	{
		return sql_query_single_scalar("select creator from im_task_templates where id = " . $template_id);
	}

	/**
	 * @param $user_id
	 * @param $template_id
	 *
	 * @return bool|mysqli_result|null
	 */
	function template_delete($user_id, $template_id)
	{
		$creator_id = template_creator($template_id);
		if ($creator_id != $user_id) {
			print "not creator c=$creator_id u=$user_id<br/>";
			return false;
		}
		if ($template_id > 0) {
			$sql = "delete from im_task_templates where id = " . $template_id;

			return sql_query($sql);
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
	function gui_show_team($id, $selected, $args)
	{
		$members = explode(",", $selected);
		$result = "";
		foreach ($members as $member) $result .= get_user_name($member) . ", ";
		return rtrim($result, ", ");
	}

	function link_to_task($id)
	{
		return add_to_url(array("operation" => "show_task", "id" => $id));
	}

	function search_by_text($text)
	{
		$result = [];
		$result = array_merge($result, project_list_search("project_name like " . quote_percent($text)));
		$result = array_merge($result, task_list_search("task_description like " . quote_percent($text)));

		if (count($result) < 2) return "No results";

		return Core_Html::gui_table_args($result);
	}

	function task_list_search($query)
	{
		$tasks = sql_query_array("select id, task_description from im_tasklist where $query");

		$result = [];
		foreach ($tasks as $task)
			array_push($result, GuiHyperlink($task[1], self::get_link("task", $task[0])));

		// debug_var($result);
		return $result;
	}

	function project_list_search($query)
	{
		return sql_query_array_scalar("select id from im_projects where $query" );
	}

	static function show_settings($user_id)
	{
		$result = Core_Html::gui_header(1, im_translate("Settings for") . " " . get_user_name($user_id));

		return $result;
	}

	static function gui_select_company($id, $value, $args)
	{
		$edit = GetArg($args, "edit", true);
		$new_row = GetArg($args, "new_row", false);

		if (! $edit)
		{
			return Org_Company::GetName($value);
		}
		// Filter by worker if supplied.
		$user_id = GetArg($args, "worker_id", get_user_id());
		if ( !$user_id ) {
			throw new Exception( __FUNCTION__ .": No user " . $user_id);
		}

		$form_table = GetArg($args, "form_table", null);
		$events = GetArg($args,"events", null);

		$companies = array(1); // Org_Company::GetCompanies($user_id);
		$companies_list = [];
		foreach($companies as $company_id => $company_name) $companies_list[] = array("company_id" => $company_id, "company_name" => $company_name);
		$result =  Core_Html::gui_select( $id, "company_name", $companies_list, $events, $value, "company_id" );
//	if ($form_table and $new_row) { // die(__FUNCTION__ . ":" . " missing form_table");
//		$result .= Core_Html::GuiButton( "add_new_project", "add_element('project', '" . $form_table . "', '" . get_url() . "')", "New Project" );
//	}

		return $result;
	}

	static function gui_select_user( $id = null, $selected = null, $args = null )
	{
		// $events = GetArg($args, "events", null);
		$edit = GetArg($args, "edit", true);

		$args["name"] = "client_displayname(id)";
		$args["id_key"] = "id";
		$args["selected"] = $selected;

		if ($edit) {
			$gui = GuiAutoList($id, "users", $args);
			return $gui;
		} else
			return ($selected > 0) ? sql_query_single_scalar("select client_displayname(user_id) from wp_users where id = " . $selected) :
				"";
	}

	static function gui_select_team($id, $selected = null, $args = null)
	{
		$edit = GetArg($args, "edit", true);
		$companies = Org_Worker::GetCompanies(get_user_id());
		$debug = false; // (get_user_id() == 1);
		$args["debug"] = $debug;
		$args["name"] = "team_name";
		$args["selected"] = $selected;

		// collision between query of the container and the selector.
		$args["query"] = (isset($args["query_team"]) ? $args["query_team"] : null);

		$form_table = GetArg($args, "form_table", null);

		if ($edit) {
			$gui = Core_Html::GuiSelectTable($id, "im_working_teams", $args);
			$gui .= Core_Html::GuiButton("add_new_team", "New Team", array("action" => "add_element('team', '" . $form_table . "', '" .get_url() . "')", "New Team"));
			return $gui;
		}
		else
			return ($selected > 0) ? sql_query_single_scalar("select team_name from im_working_teams where id = " . $selected) : "";

	}

	static function get_link($type, $id = 0)
	{
		switch ($type)
        {
			case "task":
				return "/task?id=$id";

			case "project":
				return "/project?new_task=$id";

			case "template":
				return "/template?id=$id";

		}
	}

	function getShortcodes()
	{
		//             code                           function                  capablity (not checked, for now).
		return (array( 'focus_main'           => array('Focus_Tasks::show_main', 'show_tasks'),
                       'focus_task'           => array('Focus_tasks::show_task', 'show_tasks'),
                       'focus_template'       => array('Focus_tasks::show_template', 'show_tasks'),
	                   'focus_repeating_task' => array('Focus_tasks::show_repeating_task', 'show_tasks'),
	                   'focus_team'           => array('Focus_tasks::show_team', 'show_teams'),
	                   'focus_project'        => array('Focus_tasks::show_project', 'show_projects'),
	                   'focus_project_tasks'  => array('Focus_tasks::show_project_tasks', 'show_projects')));

	}
}

/**
 * TODO: change action to be array(class_name, method_name);
 * till then using functions and not methods.
 * @param $id
 * @param $value
 * @param $args
 *
 * @return mixed|string
 */
if (!function_exists('gui_select_repeat_time')) {
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

function gui_select_worker( $id = null, $selected = null, $args = null )
{
	return Focus_Tasks::gui_select_worker($id, $selected, $args);
}

function gui_select_project($id, $value, $args)
{
	return Focus_Tasks::gui_select_project($id, $value, $args);
}
