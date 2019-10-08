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
require_once(ROOT_DIR . "/focus/gui.php");
require_once(ROOT_DIR . "/niver/gui/gem.php");
require_once(ROOT_DIR . "/niver/data/data.php");
require_once(ROOT_DIR . '/org/gui.php');
require_once(ROOT_DIR . '/focus/Tasklist.php');


require_once (ROOT_DIR . '/im-config.php');

function focus_init($script_files = null)
{
//	print "uid=" . get_user_id() . "<br/>";
	// Singleton connection for the application.
	$debug = 0;

	$conn = get_sql_conn();

	if (! $conn){
		if ($debug) print "connecting...";
		if (! defined("DB_HOST")) throw new Exception("DB configuration error = host");
		if (! defined ("DB_USER")) throw new Exception("DB configuration error = user");
		if (! defined ("DB_PASSWORD")) throw new Exception("DB configuration error = password");
		if (! defined ("DB_NAME")) throw new Exception("DB configuration error = name");
		// print "connecting" . __LINE__ . "<br/>";

		$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
		get_sql_conn($conn);
		sql_set_time_offset();
		if (! mysqli_set_charset( $conn, IM_CHARSET )){
			my_log("encoding setting failed");
			die("encoding setting failed");
		}
		if ($debug) print "done<br/>";
		// Local and international staff...
		// Todo: get it from user profile
		date_default_timezone_set( "Asia/Jerusalem" );
		sql_query("SET collation_connection = utf8_general_ci");

		if ($debug) print "loading translation<br/>";
		$locale = get_locale();
		if ($locale !== 'en_US') {
			$mofile = ROOT_DIR . '/wp-content/languages/plugins/im_haadama-' . $locale . '.mo';
			if (! load_textdomain('im-haadama', $mofile))
				print "load translation failed . $locale";
		}
		$style_file = "tools/im.css";
		if ($debug) print "loading styles $style_file";
		if (file_exists($style_file)) print load_style($style_file);
		else if ($debug) print "style not found.";

		// $admin_scripts = array( "/niver/gui/client_tools.js", "/niver/data/data.js");
	}

	if ($script_files)
		print load_scripts( $script_files );

	if ($debug){
		print "getting...<Br/>";
		$c = get_sql_conn();
		var_dump($c);
	}
	return $conn;
}

function focus_new_task()
{
	$debug = 0;
	if (! focus_check_user())
		return;
	// print im_translate("Project"). " " . im_translate("Good morning") . _("Hello") . $locale;
	$args = array();
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker", "preq" => "gui_select_task");
	if (function_exists("gui_select_mission"))
		$args["selectors"]["mission_id"] = "gui_select_mission";
	// $args["transpose"] = true;
	$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
	// $args["debug"] = true;
	$args["header"] = true;
	$args["header_fields"] = array("Start after", "Task description", "Project", "Mission", "Location name", "Priority",
		"Prerequisite", "Creator", "Assigned to");

	$args["fields"] = array("date", "task_description", "project_id", "mission_id", "location_name", "priority", "preq", "creator", "owner");

	$args["worker"] = get_user_id();
	$args["companies"] = sql_query_single_scalar("select company_id from im_working where user_id = " . get_user_id());
	$args["hide_cols"] = array("creator" => 0);

	// $args["debug"] = (get_current_user() == 369);
	$allowed_actions = array();

	if (count($allowed_actions)) $args["actions"] = $allowed_actions;
	// $args["actions"] = array();"project_id" => "add");
	try {
		if ($debug) { print "<br/>" . __FUNCTION__. ": "; var_dump(GetArg($args, "fields", null)); print "<br/>"; }
		print NewRow( "im_tasklist", $args );
	} catch ( Exception $e ) {
		print $e->getMessage();
		return;
	}
	print gui_button("btn_newtask", "save_new_custom('/focus/focus-post.php', 'im_tasklist')", "צור");
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

	$sql = "select company_id from im_working where user_id = " . get_user_id();
	$company_id = sql_query_single_scalar($sql);

	// print "company id: " . $company_id . "<br/>";

	if (! $company_id){
		print "Ne need some information to get started!<br/>";
		$args = array("values" => array("admin" => get_user_id()));
		try {
			print gui_header(1, "Company details");
			print NewRow( "im_company", $args );
		} catch ( Exception $e ) {
			print "Error: " . $e->getMessage();
			return false;
		}

		print gui_button( "btn_add", "save_new_custom('/focus/focus-post.php?operation=new_company_user', 'im_company', location_reload)", "Add" );

		// print gui_input("company", )
		return false;
	}
	return true;
}

function handle_focus_operation($operation)
{
	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates");

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	switch ($operation){
		case "new_task":
			focus_new_task();
			break;
		case "last_entered":
			if (get_user_id() != 1) return;
			$args = array();
			$args["last_entered"] = 1;
			print active_tasks($args);
			break;

		case "new_sequence":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/admin.js" ) );
			$args = array();
			$args["selectors"] = $task_selectors;
			$args["transpose"] = true;
			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
			// $args["debug"] = true;
			print NewRow("im_tasklist", $args);
			print gui_button("btn_newsequence", "save_new('im_tasklist')", "צור");
			break;

		case "new_template":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/fresh/admin/admin.js" ) );

			print gui_header(1, "יצירת תבנית חדשה");
			$args = array();
			$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker");
			$args["transpose"] = true;
			$args["worker"] = get_user_id();
			$args["companies"] = worker_get_companies(get_user_id());
			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
			print NewRow("im_task_templates", $args);
			print gui_button("btn_template", "save_new_custom('/focus/focus-post.php', 'im_task_templates')", "add");
			break;

		case "edit_staff":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/admin.js" ) );
			print gui_header(1, "Edit staff");
			edit_staff();
			break;

		case "new_team":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/admin.js" ) );
			print gui_header(1, "Add Team");
			$args = array("selectors" => array("manager" => "gui_select_worker"));
			print NewRow("im_working_teams", $args);
			print gui_button("btn_newteam", "save_new('im_working_teams')", "add");
			break;

		case "edit_team":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/admin.js" ) );
			$team_id = get_param("id", true);
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
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/admin.js" ) );

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
			task_started($task_id);
			// Started task...
			// If the was query we want to show the result.
			// And the move to the task_url if exists.
			if ($query = task_query($task_id))
			{
//				print im_file_get_html($query);
				$url = task_url($task_id);
				print '<script language="javascript">';
				print "window.location.href = '" . $url . "'";
				print '</script>';
				return;
			}
			$url = task_url($task_id);
			if ( strlen( $url ) > 1 ) // print $url;
			{
				header( "Location: " . $url );
			} else {
				redirect_back();
			}
			break;

		case "end_task":
			$task_id = get_param( "id" );
			task_ended($task_id);
			break;

		case "cancel_task":
			$task_id = get_param( "id" );
			task_cancelled($task_id);
			break;

		case "postpone_task":
			$task_id = get_param( "id" );
			$t = new Tasklist($task_id);
			if ($t->Postpone()) {
				print "done";
				return;
			}
			print "update failed";
			break;


		///////////////////////////
			// DATA entry and update //
			///////////////////////////

		case "save_new":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0)
				print "done";
			print $result;
			break;

		case "update":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			if (update_data($table_name))
				print "done";
			break;

		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	return;
}

function show_projects( $owner, $url, $non_zero = true) {
	$links = array();

	$links["id"] = $url . "?project_id=%s";
	$sql         = "select id, project_name, project_priority, project_count(id, " . $owner . ") as open_count " .
	               " from im_projects ";
	if ( $non_zero ) {
		$sql .= " where project_count(id, " . $owner . ") > 0 ";
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
	                           "creator" => "gui_select_worker", "repeat_freq" => "gui_select_repeat_time");
	$sql         = "select * " .
	               " from im_task_templates where 1 ";
	if ($template_id){
		print gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";

		$args["transpose"] = true;
		$args["edit"] = true;
		//	$args["add_checkbox"] = true;
		$args["events"] = "onchange=\"changed(this)\"";

		print GuiRowContent("im_task_templates", $template_id, $args);

		print gui_button( "btn_save", "save_entity('/focus/focus-post.php', 'im_task_templates', " . $template_id . ')', "שמור" );

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

	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = $edit;
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker", "preq" => "gui_select_task",
	                           "mission_id" => "gui_select_mission");

	$args["header_fields"] = array("Id", "Date", "Task description", "Repeating task", "Status", "Started", "Ended", "Project", "Mission", "Location", "Address", "Priority", "Prerequisite", "Assigned to",
		"Creator", "Task type");

//	 $args["fields"] = array("id", "task_description");
//
	$args["worker"] = get_user_id();
	$args["companies"] = worker_get_companies(get_user_id());
	$args["debug"] = 0; // get_user_id() == 1;

// 	print "d=" . $args["debug"] . " " . get_current_user();
//	$project_id = sql_query_single_scalar("select project_id from im_tasklist where id = " .$row_id);
//	if ($project_id > 0)
//		$args["where"] = " project_id = " . $project_id;

	try {
		$task_table = GuiRowContent( $table_name, $row_id, $args );
		// print "<br/>" . str_replace("<", "$", $task_table) . "<br/>";
		print $task_table;
	} catch ( Exception $e ) {
		print "Error: " . $e->getMessage();
		return;
	}
	print gui_button( "btn_save", "save_entity('focus-post.php', '$table_name', " . $row_id . ')', "שמור" );

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

	$action_url = "focus-post.php";
	$page_url = get_url(true);

	$last_entered = GetArg($args, "last_entered", null);
	if ($last_entered) print "showing newest tasks<br/>";

	$active_only = GetArg($args, "active_only", true);

	$page = get_param("page", false, 1);
	$rows_per_page = 10;
	$offset = ($page - 1) * $rows_per_page;

	$limit = "limit $rows_per_page offset $offset";

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

	$project_id = GetArg($args, "project", null);
	if ($project_id) {
		$query .= " and project_id = $project_id";
	}

	$args["selectors"] = array("project" => "gui_select_project", "project_id" => "gui_select_project");

	if (! $last_entered) {
		if ($active_only)
			$query .= " and (status in (0, 1) and (isnull(preq) or task_status(preq) >= 2) and (date is null or date(date) <= Curdate()))";

		if ($time) $query .= " and task_active_time(id)";
	}
	$query .= " and (mission_id is null or mission_id = 0) ";

	// New... the first part is action to server. If it replies with done, the second part is executed in the client (usually hiding the row).
	$actions = array(
		array( "start", $action_url . "?operation=start_task&id=%s" ),
		array( "finished", $action_url . "?operation=end_task&id=%s;action_hide_row" ),
		array( "cancel", $action_url . "?operation=cancel_task&id=%s;action_hide_row" ),
		array( "postpone", $action_url . "?operation=postpone_task&id=%s;action_hide_row" )
	);
	if (! $active_only or $last_entered)
		$order = "order by id desc ";
	else
		$order   = "order by priority desc ";

	$links["task_template"] = $page_url . "?task_template_id=%s";
	$links["id"] = $page_url . "?row_id=%s";
	$links["project_id"] = $page_url . "?project_id=%s";
	$args["links"]    = $links;

	$args["actions"]  = $actions;
	$args["id_field"] = "id";
	$args["edit"] = false;
	$args["header_fields"] = array("Id", "Task description", "Repeating task id", "Project Id", "Priority", "Start", "Finished", "Cancel", "Postpone");

	$more_fields = "";

	if ($debug and ! $time)
		$more_fields .= ", task_template_time(id) ";

	$sql = "select id, task_description, task_template, project_id, priority $more_fields from $table_name $query $order $limit";

	if ($debug)
		print "<br/>" . $sql . "<br/>";

	try {
		$result = GuiTableContent( $table_name, $sql, $args );
	} catch ( Exception $e ) {
		print "can't load tasks." . $e->getMessage();
		return null;
	}

	if (strlen ($result) < 10) {
		$result = im_translate( "No active tasks!" ) . "<br/>";
		$result .= im_translate( "Let's create first one!" ) . " ";
		$result .= gui_hyperlink( "create task", $page_url . "?operation=new_task" ) . "<br/>";
	} else
		$result .= gui_hyperlink("Older", add_to_url("page", $page + 1));


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
