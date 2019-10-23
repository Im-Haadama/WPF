<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname( dirname( __FILE__)  ) );
}

require_once( ROOT_DIR . "/niver/web.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );
require_once(ROOT_DIR . "/niver/fund.php");
// require_once(ROOT_DIR . "/focus/gui.php");
require_once(ROOT_DIR . "/niver/gui/gem.php");
require_once(ROOT_DIR . "/niver/data/data.php");
require_once(ROOT_DIR . '/org/gui.php');
require_once(ROOT_DIR . '/focus/Tasklist.php');
require_once(ROOT_DIR . '/niver/gui/gem.php');
require_once(ROOT_DIR . '/org/people/people.php');

require_once (ROOT_DIR . '/im-config.php');

//function focus_init($script_files = null)
//{
////	print "uid=" . get_user_id() . "<br/>";
//	// Singleton connection for the application.
//	$debug = 0;
//
//	$conn = get_sql_conn();
//
//	if (! $conn){
//		if ($debug) print "connecting...";
//		if (! defined("DB_HOST")) throw new Exception("DB configuration error = host");
//		if (! defined ("DB_USER")) throw new Exception("DB configuration error = user");
//		if (! defined ("DB_PASSWORD")) throw new Exception("DB configuration error = password");
//		if (! defined ("DB_NAME")) throw new Exception("DB configuration error = name");
//		// print "connecting" . __LINE__ . "<br/>";
//
//		$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
//		get_sql_conn($conn);
//		sql_set_time_offset();
//		if (! mysqli_set_charset( $conn, IM_CHARSET )){
//			my_log("encoding setting failed");
//			die("encoding setting failed");
//		}
//		if ($debug) print "done<br/>";
//		// Local and international staff...
//		// Todo: get it from user profile
//		date_default_timezone_set( "Asia/Jerusalem" );
//		sql_query("SET collation_connection = utf8_general_ci");
//
//		if ($debug) print "loading translation<br/>";
//		$locale = get_locale();
//		if ($locale !== 'en_US') {
//			$mofile = ROOT_DIR . '/wp-content/languages/plugins/im_haadama-' . $locale . '.mo';
//			if (! load_textdomain('im-haadama', $mofile))
//				print "load translation failed . $locale";
//		}
//		$style_file = "tools/im.css";
//		if ($debug) print "loading styles $style_file";
//		if (file_exists($style_file)) print load_style($style_file);
//		else if ($debug) print "style not found.";
//
//		// $admin_scripts = array( "/niver/gui/client_tools.js", "/niver/data/data.js");
//	}
//
//	if ($script_files)
//		print load_scripts( $script_files );
//
//	if ($debug){
//		print "getting...<Br/>";
//		$c = get_sql_conn();
//		var_dump($c);
//	}
//	return $conn;
//}

function focus_new_task()
{
	if (! focus_check_user()) return;
	$args = array();
	$args["selectors"] = array("project_id" =>  "gui_select_project",
	                           "owner" => "gui_select_worker",
	                           "creator" => "gui_select_worker",
	                           "preq" => "gui_select_task",
							   "team" => "gui_select_team"	);
	$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
	$args["header"] = true;
	$args["header_fields"] = array("date"=>"Start after",
	                               "task_description" => "Task description",
	                               "project_id" => "Project",
	                               "location_name" => "Location name",
	                               "priority" => "Priority",
	                               "preq" => "Prerequisite",
	                               "creator" => "Creator");
	$args["mandatory_fields"] = array("project_id", "priority", "team", "task_description") ;

	$args["fields"] = array("task_description", "project_id", "location_name", "priority", "date", "preq", "creator", "team");
	$args['post_file'] = "/focus/focus-post.php";

	// TODO: if missions are available:
	if (0 and function_exists("gui_select_mission"))
	{
		$args["selectors"]["mission_id"] = "gui_select_mission";
		$args["header_fields"]["mission_id"] = "Mission";
		array_push($args["fields"], "mission_id");
	}

	$args["worker"] = get_user_id();
	$args["companies"] = sql_query_single_scalar("select company_id from im_working where user_id = " . get_user_id());
	$args["hide_cols"] = array("creator" => 1);
	set_args_value($args); // Get values from url.

	print GemAddRow("im_tasklist", "New task", $args);
//	try {
//		if ($debug) { print "<br/>" . __FUNCTION__. ": "; var_dump(GetArg($args, "fields", null)); print "<br/>"; }
//		print NewRow( "im_tasklist", $args );
//	} catch ( Exception $e ) {
//		print $e->getMessage();
//		return;
//	}
//	print gui_button("btn_newtask", "data_save_new('/focus/focus-post.php', 'im_tasklist', show_project)", "צור");
}

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
			print gui_header(1, "Company details");
			print NewRow( "im_company", $args );
		} catch ( Exception $e ) {
			print "Error: " . $e->getMessage();
			return false;
		}

		print gui_button( "btn_add", "data_save_new('/focus/focus-post.php?operation=new_company_user', 'im_company', location_reload)", "Add" );

		// print gui_input("company", )
		return false;
	}

	// Check if user has team.
	$team_ids = team_all_members($user_id);
//	var_dump($team_ids);
	if (! count($team_ids)){
		team_add($user_id, "Personal team");
	}

	$project_ids = worker_get_projects($user_id);
	if (! count($project_ids)) {
		project_create($user_id, im_translate("first project"), $company_ids[0]);
	}
	return true;
}

function handle_focus_operation($operation)
{
	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates");

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	switch ($operation){
		case "show_new_task":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js" ) );
			focus_new_task();
			break;
		case "last_entered":
			if (get_user_id() != 1) return;
			$args = array();
			$args["last_entered"] = 1;
			print active_tasks($args);
			break;

		case "show_new_sequence":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js" ) );
			$args = array();
//			$args["selectors"] = $task_selectors;
//			$args["transpose"] = true;
//			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());

			print gui_header(1, "New sequence");
			print gui_label("explain", "Select the project of the sequence, the default priority of all sequence tasks. Enter text for the tasks");
			$args["worker"] = get_user_id(); $args["edit"] = true;
			$table_rows =array(array("project", gui_select_project("project", null, $args)),
				array("priority", GuiInput("priority", null, $args)),
				array("task1", GuiInput("task1")),
				array("task2", GuiInput("task2", null, array("events" => 'onchange="addSequenceTask(2)"')))
			);
			print gui_table_args($table_rows, "sequence_table");

			// $args["debug"] = true;
			// print NewRow("im_tasklist", $args);
			print gui_button("btn_new_sequence", "save_new_sequence()", "Create");
			break;

		case "new_sequence":
			if (create_new_sequence())
				print "done";
			else
				print "create failed";
			break;

		case "new_template":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/fresh/admin/focus.js" ) );

			print gui_header(1, "יצירת תבנית חדשה");
			$args = array();
			$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker");
			$args["transpose"] = true;
			$args["worker"] = get_user_id();
			$args["companies"] = worker_get_companies(get_user_id());
			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
			print NewRow("im_task_templates", $args);
			print gui_button("btn_template", "data_save_new('/focus/focus-post.php', 'im_task_templates')", "add");
			break;

		case "edit_staff":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/focus.js" ) );
			print gui_header(1, "Edit staff");
			edit_staff();
			break;

		case "new_team":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/focus.js" ) );
			print gui_header(1, "Add Team");
			$args = array("selectors" => array("manager" => "gui_select_worker"));
			print NewRow("im_working_teams", $args);
			print gui_button("btn_newteam", "save_new('im_working_teams')", "add");
			break;

		case "edit_team":
			// temporary - for existing teams.
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/focus.js" ) );
			$team_id = get_param("id", true);
			team_add_worker($team_id, team_manager($team_id));
			print gui_header(1, "Edit Team" . team_get_name($team_id));
			$args = array("selectors" => array("id" => "gui_select_worker"), "edit" => false);
			print GuiTableContent("im_working_teams",
				'select id from wp_users where worker_teams(id) like "%:' . $team_id . ':%"', $args);
			print gui_hyperlink("add member", get_url() . "?operation=add_member&id=$team_id");
			break;

		case "add_member":
			$team_id = get_param("id", true);
			print gui_header(1, "Adding memeber to team" . sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id));
			print gui_select_worker("new_member");
			print gui_label("team_id", $team_id, true);
			print gui_button("btn_add_member", "add_member()", "Add");
			break;

		case "do_add_member":
			$member = get_param("member", true);
			$team_id = get_param("team", true);
			$current = get_usermeta($member, "teans");
			if (! $current) $current = ":";
			update_usermeta($member, "teams", ":" . $team_id . $current); // should be :11:22:3:4:
			print "done";
			break;

		case "projects":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js" ) );

			if ($id = get_param("project_id")) {
				print show_project($id);
				return;
			}
			show_projects(get_url(), get_user_id());
			break;

		case "task_types":
			$args = array();
			$args["title"] = "task types";
			print GemTable("im_task_type", $args);
			break;

		case "new_company_user":
			$company_id = data_save_new("im_company");
		//			$worker_id = worker_get_id(get_user_id());
			$sql = "update im_working set company_id = " . $company_id . " where user_id = " . get_user_id();
			sql_query($sql);

			print "done";
			break;

		case "start_task":
			$task_id = get_param( "id" );
			if (task_started($task_id, get_user_id()) != true){
				print "started";
				return;
			};
			// Started task...
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
			$url = task_url($task_id);
			if ( strlen( $url ) > 1 ) // print $url;
			{
				header( "Location: " . $url );
				return;
			}
			print "done";
			break;

		case "end_task":
			$task_id = get_param( "id" );
			task_ended($task_id);
			break;

		case "cancel_task":
			$task_id = get_param( "id" );
			if (task_cancelled($task_id)) print "done";
			break;

		case "postpone_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$r = $T->Postpone();
			create_tasks( null, false );
			if ($r) print "done";
			break;


		///////////////////////////
			// DATA entry and update //
			///////////////////////////

		case "save_new":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done";
			break;

		case "update":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			if (update_data($table_name))
				print "done";
			break;

		case "cancel":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			if (cancel_data($table_name))
				print "done";
			break;

		case "edit_teams":
			$args = [];
			$args["post_file"] = "/niver/data/data-post.php";
			$args["selectors"] = array("manager" => "gui_select_worker");
			$args["links"] = array("id" => add_to_url(array("operation" => "edit_team", "id" => "%s")));
			print GemTable("im_working_teams", $args);
			print GemAddRow("im_working_teams", "Add a team", $args);
			break;

		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	return;
}

function show_projects( $url, $owner, $non_zero = true) {
	$links = array();

	$links["id"] = add_param_to_url($url, "project_id", "%s");
	$sql         = "select id, project_name, project_priority, project_count(id, " . $owner . ") as open_count " .
	               " from im_projects where 1 ";
	if ( $non_zero ) {
		$sql .= " and project_count(id, " . $owner . ") > 0 ";
	}
	$sql .= " order by 3 desc";

	$args           = array();
	$args["class"]  = "sortable";
	$args["links"]  = $links;
	$args["header"] = true;

	print GuiTableContent( "projects", $sql, $args );
}

function show_templates($url,  $template_id = 0 ) {
	$args              = array();

	$args["worker"] = get_user_id();
	$args["companies"] = worker_get_companies(get_user_id());
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker",
	                           "creator" => "gui_select_worker", "repeat_freq" => "gui_select_repeat_time", "team" => "gui_select_team");
	$args["fields"] = array("id", "task_description", "project_id", "priority", "team", "repeat_freq", "repeat_freq_numbers", "working_hours", "task_url");
	$args["header_fields"] = array("task_description" => "Task description", "project_id" => "Project", "priority" => "Priority",
	                                      "team" => "Team", "repeat_freq" => "Repeat Frequency", "repeat_freq_numbers" => "Repeat times", "working_hours" => "Working hours",
		"Task site");

	$sql         = "select * " .
	               " from im_task_templates where 1 ";
	if ($template_id){
		// print gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";
		$args["title"] = "Repeating task";

//		$args["transpose"] = true;
//		$args["edit"] = true;
//		//	$args["add_checkbox"] = true;
//		$args["events"] = "onchange=\"changed(this)\"";

		print GemElement("im_task_templates", $template_id, $args);

		print gui_button( "btn_save", "data_save_entity('/focus/focus-post.php', 'im_task_templates', " . $template_id . ')', "save" );
		print gui_button( "btn_cancel", "cancel_entity('/focus/focus-post.php', 'im_task_templates', " . $template_id . ')', "cancel" );

		// show last active
		$tasks_args = array("links" => array("id" => $url . "?row_id=%s"));
		$table = GuiTableContent("last_tasks", "select * from im_tasklist where task_template = " . $template_id .
		                                       " order by date desc limit 10", $tasks_args);
		if ($table)
		{
			print gui_header(2, "משימות אחרונות");
			print $table;
		}

		return;
	}

	foreach ($_GET as $key => $data){
		if (! in_array($key, array("templates")))
			$sql .= "where " . $key . '=' . quote_text($data);
	}

	// print $sql;
	$sql .= " order by 3 desc";

	$args["class"]     = "sortable";
	$args["links"]     = array ("id" => $url . "?task_template_id=%s" );
	$args["header"]    = true;
	$args["drill"] = true;
	$args["edit"] = false;

	$table = GuiTableContent( "projects", $sql, $args );

	print $table;
}

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

function show_task($row_id, $edit = 1)
{
	$table_name = "im_tasklist";
	$entity_name = "task";

	// print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = $edit;
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker", "preq" => "gui_select_task",
	                           "mission_id" => "gui_select_mission",
	                           "team" => "gui_select_team");
	$args["title"] = $entity_name;

	$args["header_fields"] = array( "date" => "Date", "task_description" => "Task description", "task_template" =>"Repeating task",
		"status" => "Status", "started" => "Started", "ended" => "Ended", "project_id" => "Project", "location_name" => "Location",
		"location_address" => "Address", "priority" => "Priority", "preq" => "Prerequisite", "owner" => "Assigned to",
		"creator" => "Creator", "task_type" => "Task type", "mission_id" => "Mission");

	$args["worker"] = get_user_id();
	$args["companies"] = worker_get_companies(get_user_id());
	$args["debug"] = 0; // get_user_id() == 1;
	$args["worker"] = get_user_id();

	print GemElement($table_name, $row_id, $args);

	return;
}

/**
 * @param $id
 * @param $value
 * @param $args
 *
 * @return string
 */
// $selector_name( $input_name, $orig_data, $args)
function gui_select_repeat_time( $id, $value, $args) {
//	print "v=" . $value . "<br/>";

	$edit = GetArg($args, "edit", false);
	$events = GetArg($args, "events", null);
	$values = array( "w - weekly", "j - monthly", "z - annual", "c - continuous");

	$selected = 1;
	for ( $i = 0; $i < count( $values ); $i ++ ) {
		if ( substr( $values[ $i ], 0, 1 ) == substr($value, 0, 1) ) {
			$selected = $i;
		}
	}

	// return gui_select( $id, null, $values, $events, $selected );
	if ($edit)
		return gui_simple_select( $id, $values, $events, $selected );
	else
		return $values[$selected];
}

function edit_staff($url)
{
	$user = wp_get_current_user();
	print gui_header(2, "teams");

	$args = array("selectors" => array("manager" => "gui_select_worker"),
	              "links" => array("id" => $url . "?operation=edit_team&id=%s"));
	print GuiTableContent("working_teams", "select * from im_working_teams where manager = " . $user->id, $args);

	print gui_hyperlink("add", $url . "?operation=new_team");
	// print GuiTableContent("");
}

function active_tasks($args = null, $debug = false, $time = false)
{
	$table_name = "im_tasklist";
	$title = "";

	$action_url = "/focus/focus-post.php";
	$page_url = get_url(true);

	$active_only = GetArg($args, "active_only", true);
	if ($active_only){
		$title .= im_translate("active only");
	}

	$page = GetArg($args, "page", 1);
	$rows_per_page = GetArg($args, "rows_per_page", 10);
	$offset = ($page - 1) * $rows_per_page;

	$limit = (($page > -1) ? "limit $rows_per_page offset $offset" : "");
	if (! isset($args["fields"])) $args["fields"] = array("id", "task_description", "project_id", "priority", "task_template");

//	if (get_param("limit"))
//		$limit = "limit " . get_param("limit");
//	else
//		$limit = "limit 10";

//	if (get_param("offset"))
//		$limit .= " offset " . get_param("offset");

	$links       = array();

	$query   = "where 1 ";
	if (GetArg($args, "query", null))
		$query .= " and " . GetArg($args, "query", null);

	$project_id = GetArg($args, "project_id", null);
	if ($project_id) {
		$title = im_translate("Project") . " " . get_project_name($project_id);
		if ($f = array_search("project_id", $args["fields"])) {
			unset($args["fields"][$f]);
//			print "removed";
		}
//		var_dump($args["fields"]);
		$query .= " and project_id = $project_id";
	}

	$args["selectors"] = array("project" => "gui_select_project", "project_id" => "gui_select_project");

	$query .= " and status < 2 ";
	if ($active_only) {
		$query .= " and (isnull(preq) or preq_done(id)) and (date is null or date(date) <= Curdate())";
		$query .= " and (mission_id is null or mission_id = 0) ";
	}

	// New... the first part is action to server. If it replies with done, the second part is executed in the client (usually hiding the row).
	$actions = array(
		array( "start", $action_url . "?operation=start_task&id=%s;location_reload" ),
		array( "finished", $action_url . "?operation=end_task&id=%s;action_hide_row" ),
		array( "cancel", $action_url . "?operation=cancel_task&id=%s;action_hide_row" ),
		array( "postpone", $action_url . "?operation=postpone_task&id=%s;action_hide_row" )
	);
	$order   = "order by priority desc ";

	$links["task_template"] = $page_url . "?task_template_id=%s";
	$links["id"] = $page_url . "?row_id=%s";
	$links["project_id"] = $page_url . "?project_id=%s";
	$args["links"]    = $links;

	$args["actions"]  = $actions;
	$args["id_field"] = "id";
	$args["edit"] = false;
	$args["header_fields"] = array("task_description" => "Task description", "task_template" => "Repeating task id", "project_id" => "Project Id", "id" => "Id",
		"priority" => "Priority", "start" => "Start", "finish" => "Finished", "cancel" => "Cancel", "postpone" => "Postpone");
	$fields = $args["fields"];

//	$more_fields = "";

//	if ($debug and ! $time)
//		$more_fields .= ", task_template_time(id) ";

	$sql = "select " . comma_implode($fields) . " from $table_name $query $order $limit";

	if ($debug)
		print "<br/>" . $sql . "<br/>";

	$result = "";
	try {
		if (isset($_GET["debug"])) print "sql = $sql<br/>";
		$table = GuiTableContent( $table_name, $sql, $args );
		if ($table) {
			if (strlen($title)) $result = gui_header(2, $title);
			$result .= $table;
		}
	} catch ( Exception $e ) {
		print "can't load tasks." . $e->getMessage();
		return null;
	}

	return $result;
}

function show_team($team_id, $active_only)
{
	print gui_header(1, "Showing status of team " . team_get_name($team_id));
	print gui_hyperlink("Include non active", add_to_url("active_only", 0));

	$team_members = team_members($team_id);

	foreach ($team_members as $user_id){
		print gui_header(2, get_customer_name($user_id) . " " . $user_id);
		$args = array("user_id" => $user_id, "active_only" => $active_only, "owner" => $user_id);
		$args["query"] = " owner=" . $user_id;
		print active_tasks($args);
	}
}

function managed_workers($manager_id, $url)
{
	$teams = sql_query_array_scalar("select id from im_working_teams where manager = " . $manager_id);

	if (!$teams) return "";

	$result = "";

	foreach ($teams as $team_id)
	{
		$result .= gui_hyperlink(team_get_name($team_id), $url . "?team=" . $team_id);
	}
	return $result;
}

function team_get_name($team_id)
{
	return sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id);
}

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

function show_project($project_id, $args = null)
{
	$active_only = GetArg($args, "active_only", true);
	$order = GetArg($args, "order", "order by priority desc");
	if (is_null($args)) $args = [];

	$sql = "select * from im_tasklist where project_id = " . $project_id;
	if ($active_only) $sql .= " and status = 0 ";
	$args["sql"] = $sql . $order;
	$args["links"] = array("id" => add_param_to_url(get_url(), "id", "%s"));

//	print $sql;
	return GemTable("im_tasklist", $args);
}
