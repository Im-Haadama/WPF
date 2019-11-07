<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname( dirname( __FILE__)  ) );
}

require_once(ROOT_DIR . "/niver/web.php" );
require_once(ROOT_DIR . '/niver/gui/inputs.php' );
require_once(ROOT_DIR . '/niver/gui/input_data.php' );
require_once(ROOT_DIR . "/niver/fund.php");
require_once(ROOT_DIR . "/niver/gui/gem.php");
require_once(ROOT_DIR . "/niver/data/data.php");
require_once(ROOT_DIR . '/focus/Tasklist.php');
require_once(ROOT_DIR . '/niver/gui/gem.php');
require_once(ROOT_DIR . '/org/people/people.php');

require_once (ROOT_DIR . '/im-config.php');

function focus_new_task($mission = false)
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
	                               "location_address" => "Address",
	                               "location_name" => "Location name",
	                               "priority" => "Priority",
	                               "preq" => "Prerequisite",
	                               "creator" => "Creator");
	$args["mandatory_fields"] = array("project_id", "priority", "team", "task_description") ;

	$args["fields"] = array("task_description", "project_id", "location_name", "location_address", "priority", "date", "preq", "creator", "team");
	$args['post_file'] = "/focus/focus-post.php";
	$args['form_table'] = 'im_tasklist';

	if ($mission and function_exists("gui_select_mission"))
	{
		$i = new ImMultiSite();
		$i->UpdateFromRemote( "im_missions", "id", 0, null, null );
		$args["selectors"]["mission_id"] = "gui_select_mission";
		$args["header_fields"]["mission_id"] = "Mission";
		$args["mandatory_fields"]["location_name"] = true; $args["mandatory_fields"]["location_address"] = true;
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
	if (is_null($project_ids) or ! count($project_ids)) {
		project_create($user_id, im_translate("first project"), $company_ids[0]);
	}
	return true;
}

function handle_focus_operation($operation)
{
	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates");

	$action_url = "/focus/focus-post.php";

	$handled = true;
	switch ($operation) { // Handle operation that don't need page header.
		///////////////////////////
		// DATA entry and update //
		///////////////////////////
		case "del_team":
			$team_id = get_param("id", true);
			if (team_delete($team_id)) print "done";
			break;

		case "end_task":
			$task_id = get_param( "id" );
			task_ended($task_id);
			create_tasks( null, false );
			break;

		case "cancel_task":
			$task_id = get_param( "id" );
			if (task_cancelled($task_id)) print "done";
			create_tasks( null, false );
			break;

		case "postpone_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$r = $T->Postpone();
			create_tasks( null, false );
			if ($r) print "done";
			break;

		case "pri_plus_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$T->setPriority($T->getPriority() + 1);
			create_tasks( null, false );
			print "done";
			break;

		case "pri_minus_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$T->setPriority($T->getPriority() -1);
			create_tasks( null, false );
			print "done";
			break;

		case "save_new":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done.table=" . $table_name . '&new=' . $result;
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

		case "delete_template":
			$user_id = get_user_id();
			$id = get_param("row_id", true);
			if (template_delete($user_id, $id)) print "done";
			break;
		case "start_task":
			// a. set the start time, if not set.
			$task_id = get_param( "id" );
			task_started($task_id, get_user_id());

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
			$url_headers = @get_headers($url);
			if (! $url_headers || strstr($url_headers[0], "404")) {
				print "/focus/focus-post.php?operation=bad_url&id=" . $task_id;
				return;
			}
			if ( strlen( $url ) > 1 ) print $url;
			break;

		default:
			$handled = false;
			// Don't warn. Will be handled in the second switch

	}
	if ($handled) return;
	print_focus_header();

	$args["page"] = get_param("page", false, 1);

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	// show/save <obj_type>
	switch ($operation){
		case "focus_main":
			print focus_main();
			break;
		case "show_templates":
			$args["table"] = true;
			$args["new"] = get_param("new", false, 0);
			$new = get_param("new", false, null);
			print show_templates($args, null,  $new);
			break;
		case "show_template":
			$id = get_param("id", true);
			print show_templates($args, $id);
			break;
		case "show_task":
			$id = get_param("id", true);
			if ($id) show_task($id);
			return;
		case "show_project":
			$id = get_param("id", true);
			if ($id) print show_project($id);
			return;
		case "bad_url":
			$id = get_param("id");
			print "Url for task $id is wrong<br/>";
			$template_id = task_template($id);
			print  gui_hyperlink("Edit template $template_id", "?operation=show_template&id=$template_id");
			break;
		case "show_new_project":
			$args = [];
			$args["next_page"] = get_param("next_page", false, null);
			$args["post_file"] = "/niver/data/data-post.php";
//			$user_id = get_user_id();
//			$args["user_id"] = $user_id;
//			$args["hide_cols"] = array("user_id" => 1, "company_id" => 1);
//			$args["values"] = array("company_id" => worker_get_companies($user_id)[0]);
			$args["fields"] = array("project_name", "project_contact", "project_priority");
			$args["mandatory_fields"] = array("project_name");
			$args["header_fields"] = array("project_name" => "Project name", "project_contact" => "Project contact (client)", "project_priority"=> "Priority");
			print GemAddRow("im_projects", "Add a project", $args);
			break;
		case "show_new_team":
			$args = [];
			$args["next_page"] = get_param("next_page", false, null);
			$args["post_file"] = "/niver/data/data-post.php";
			print GemAddRow("im_working_teams", "Add a team", $args);
			break;
		case "show_new_task":
			// print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js" ) );
			$mission = get_param("mission", false, null);
			focus_new_task($mission);
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
			print gui_header(1, "יצירת תבנית חדשה");
			$args = array();
			$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker",
			                           "creator" => "gui_select_worker", "team" => "gui_select_team", "repeat_freq" => "gui_select_repeat_time" );
			$args["transpose"] = true;
			$args["worker"] = get_user_id();
			$args["companies"] = worker_get_companies(get_user_id());
			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
			$args["fields"] = array("task_description", "task_url", "repeat_freq_numbers", "project_id", "repeat_freq",
				"condition_query", "priority", "working_hours", "path_code", "creator", "team");
			$args["mandatory_fields"] = array("task_description", "repeat_freq_numbers", "repeat_freq", "project");
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
			print gui_hyperlink("add member", get_url() . "?operation=show_add_member&id=$team_id");
			break;

		case "show_add_member":
			$team_id = get_param("id", true);
			print gui_header(1, "Adding memeber to team" . sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id));
			print gui_select_worker("new_member");
			print gui_label("team_id", $team_id, true);
			print gui_button("btn_add_member", "add_member()", "Add");
			break;

		case "save_add_member":
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

		case "edit_teams":
			$args = [];
			$args["post_file"] = "/niver/data/data-post.php";
			$args["selectors"] = array("manager" => "gui_select_worker");
			$args["links"] = array("id" => add_to_url(array("operation" => "edit_team", "id" => "%s")));
//			print "url = " . get_url() . "<br/>";
//			print add_to_url(array("operation" => "del_team", "id"=>"%s"));

			$args["actions"] = array(array("delete", $action_url . "?operation=del_team&id=%s;location_reload"));
			print GemTable("im_working_teams", $args);

			unset($args["actions"]);
			print GemAddRow("im_working_teams", "Add a team", $args);
			break;


		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	return;
}

function print_focus_header()
{
	$args = arraY("print_logo" => true, "rtl" => is_rtl());
	print HeaderText($args);
	print load_scripts(array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js" ));
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

function show_templates(&$args, $template_id = 0, $new = null ) {
	$url = get_url(1);

	$action_url = "/focus/focus-post.php";

	$args["worker"] = get_user_id();
	$args["companies"] = worker_get_companies(get_user_id());
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker",
	                           "creator" => "gui_select_worker", "repeat_freq" => "gui_select_repeat_time", "team" => "gui_select_team");
	$args["fields"] = array("id", "task_description", "project_id", "priority", "team", "repeat_freq", "repeat_freq_numbers", "working_hours", "condition_query", "task_url");
	$args["header_fields"] = array("task_description" => "Task description", "project_id" => "Project", "priority" => "Priority",
	                               "team" => "Team", "repeat_freq" => "Repeat Frequency", "repeat_freq_numbers" => "Repeat times", "working_hours" => "Working hours",
		                           "Task site");

	if ($template_id){
		// print gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";
		$args["title"] = "Repeating task";

		print GemElement("im_task_templates", $template_id, $args);

		$tasks_args = array("links" => array("id" => $url . "?row_id=%s"));
		$table = GuiTableContent("last_tasks", "select * from im_tasklist where task_template = " . $template_id .
		                                       " order by date desc limit 10", $tasks_args);
		if ($table)
		{
			$result = gui_header(2, "משימות אחרונות");
			$result .= $table;
			return $result;
		}

		return null;
	}

	if ($page = get_param("page")) { $args["page"] = $page; unset ($_GET["page"]); };

	$query = " 1";
	foreach ($_GET as $key => $data){
		if (! in_array($key, array("operation", "table_name", "new")))
			$query .= " and " . $key . '=' . quote_text($data);
	}

	$args["class"]     = "sortable";
	$args["links"]     = array ("id" => $url . "?operation=show_template&id=%s" );
	$args["header"]    = true;
	$args["drill"] = true;
	$args["edit"] = false;
	$args["actions"] = array(array("delete", $action_url . "?operation=delete_template&row_id=%s;action_hide_row"));
	$args["query"] = $query;
	$args["order"] = " id " . ($new ? "desc" : "asc");

	$result = gui_hyperlink( "Add repeating task", get_url( true ) . "?operation=new_template" );

	$result .= GemTable("im_task_templates", $args);
	// $result .= GuiTableContent( "projects", $sql, $args );

	return $result;
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

function active_tasks(&$args = null, $debug = false, $time = false)
{
	$args["count"] = 0;

	$table_name = "im_tasklist";
	$title = GetArg($args, "title", "");

	$action_url = "/focus/focus-post.php";
	$page_url = get_url(true);

	$active_only = GetArg($args, "active_only", true);
	if ($active_only){
		$title .= " (" . im_translate("active only") . ")";
	}

	if (! isset($args["fields"])) $args["fields"] = array("id", "task_description", "project_id", "priority", "task_template");

	if (get_param("limit"))
		$limit = "limit " . get_param("limit");
	else
		$limit = "limit 10";

	if (get_param("offset"))
		$limit .= " offset " . get_param("offset");

	$links       = array();

	$query   = "where 1 ";
	if (GetArg($args, "query", null))
		$query .= " and " . GetArg($args, "query", null);

	$project_id = GetArg($args, "project_id", null);
	if ($project_id) {
		$title = im_translate("Project") . " " . get_project_name($project_id);
		if ($f = array_search("project_id", $args["fields"])) {
			unset($args["fields"][$f]);
		}
		$query .= " and project_id = $project_id";
	}

	if (! isset($args["selectors"]))
		$args["selectors"] = array("project" => "gui_select_project",
		                           "project_id" => "gui_select_project",
			"owner" => "gui_select_worker");

	$query .= " and status < 2 ";
	if ($active_only) {
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
	$links["id"] = $page_url . "?operation=show_task&id=%s";
	$links["project_id"] = $page_url . "?operation=show_project&id=%s";
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

	// print $sql . "<br/>";
	$result = "";
	try {
		if (isset($_GET["debug"])) print "sql = $sql<br/>";
		$table = GuiTableContent( $table_name, $sql, $args );
		if (! $args["count"]) return "";
		if ($table) {
			if (strlen($title)) $result = gui_header(2, $title);
			$result .= $table;
		}
	} catch ( Exception $e ) {
		print "can't load tasks." . $e->getMessage();
		return null;
	}
	$count = $args["count"];
	$page = get_param("page", false, 1);
	if ($count === $page){
	// $args["page"] = $page;
		$result .= gui_hyperlink("More", add_to_url("page", $page + 1)) . " " ;
		$result .= gui_hyperlink("Not paged", add_to_url("page", -1)) . " "; // All pages
	}
	$result .= gui_hyperlink("Not filtered", add_to_url("active_only", 0)); // Not filtered

	$result .= " " . gui_hyperlink("Add task", add_to_url("operation", "show_new_task"));

	$result .= " " . gui_hyperlink("Add delivery", add_to_url("operation", "show_new_task&mission=1"));

	return $result;
}

function show_team($team_id, $active_only)
{
	print gui_header(1, "Showing status of team " . team_get_name($team_id));
	print gui_hyperlink("Include non active", add_to_url("active_only", 0));

	// $team_members = team_members($team_id);

//		print gui_header(2, get_customer_name($user_id) . " " . $user_id);
	$args = array("active_only" => $active_only);
	$args["query"] = " team=" . $team_id;
	$args["fields"] = array("id", "task_description", "project_id", "priority", "task_template", "owner");
	print active_tasks($args);
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
	$args["title"] = im_translate("משימות בפרויקט") . " " . get_project_name($project_id);

//	print $sql;
	return GemTable("im_tasklist", $args);
}

function focus_main()
{
	$debug = 0;
	$user_id = get_user_id();
	$time_filter = false;

	$result = "";
	$ignore_list = [];
	$args = [];
	$args["count"] = 0;

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Links: Templates                                                                       //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$url = get_url(1);
	$result .= gui_hyperlink("Repeating tasks", $url . "?operation=show_templates");

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tasks I need to handle (owner = me)                                                                       //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$args["title"] = im_translate("Tasks assigned to me");
	$args["query"] = " owner = " . get_user_id();
	$args["limit"] = get_param("limit", false, 10);
	$args["active_only"] = get_param("active_only", false, true);

	foreach ($_GET as $param => $value)  if (!in_array($param, $ignore_list))  $args[$param] = $value;
	$table = active_tasks($args);
	if ($args["count"]){
		$result .= $table;
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tasks of teams I manage. Not assigned to me                                                               //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$args["title"] = im_translate("Teams I manage tasks");
	$teams = team_managed_teams($user_id);
	// print "teams: " . comma_implode($teams) . "<br/>";
	$args["fields"][] = "team";
	$args["selectors"]["team"] = "gui_select_team";
	if ($teams and count($teams)) {
		$args["query"] = " team in (" . comma_implode($teams) . ") and owner != " . $user_id;
		$result .= active_tasks($args, $debug, $time_filter);
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tasks teams I'm a member of (team in my_teams). Not assigned                                              //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$args["title"] = im_translate("My teams tasks");
	$teams = team_all_teams($user_id);
	// print "teams: " . comma_implode($teams) . "<br/>";
	$args["fields"][] = "team";
	$args["selectors"]["team"] = "gui_select_team";
	if ($teams and count($teams)) {
		$args["query"] = " team in (" . comma_implode($teams) . ") and owner is null";
		$result .= active_tasks($args, $debug, $time_filter);
	}

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tasks I've created. Assigned to some else                                                                 //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$args["title"] = im_translate("Tasks I've initiated to other teams");
	$args["query"] = " creator = " . get_user_id() . " and (owner != " . $user_id . ' or isnull(owner)) and team not in (' . comma_implode($teams) . ")";
	$args["limit"] = get_param("limit", false, 10);
	$args["active_only"] = get_param("active_only", false, true);
	$result .= active_tasks($args);

	//print "c=" . $args["count"];
	if (! $args["count"]) {
		$result = im_translate( "No active tasks!" ) . "<br/>";
		$result .= im_translate( "Let's create first one!" ) . " ";
		$result .= gui_hyperlink( "create task", "?operation=show_new_task" ) . "<br/>";
	}
	// if (get_user_id() != 1) return;
	
	return $result;
}

function not_used1() {
	$task_template_id = get_param( "task_template_id" );
	if ( $task_template_id ) {
		show_templates( get_url( 1 ), $task_template_id );

		return;
	}

	if ( get_param( "templates", false, "none" ) !== "none" ) {
		print header_text( false, true, true, array(
			"/niver/gui/client_tools.js",
			"/niver/data/data.js",
			"/focus/focus.js"
		) );


		$args = array();

		show_templates( get_url( 1 ) );

		return;
	}

	if ( $team_id = get_param( "team" ) ) {
		global $admin_scripts;
		show_team( $team_id, get_param( "active_only", false, true ) );

		return;
	}
}

function not_used(){
$time_filter = get_param("time", false, true);

$args["url"] = basename(__FILE__);


print " ";

print gui_hyperlink("add tasks", $url . "?operation=show_new_task");

print " ";

print gui_hyperlink("add sequence", $url . "?operation=new_sequence");

print " ";

print managed_workers(get_user_id(), $_SERVER['REQUEST_URI']) . " ";

if (im_user_can("edit_task_types"))
	print gui_hyperlink("projects", $url . "?operation=projects") . " ";

if (im_user_can("edit_projects"))
	print gui_hyperlink("task types", $url . "?operation=task_types") . " ";

if (im_user_can("edit_teams"))
	print gui_hyperlink("edit teams", $url . "?operation=edit_teams") . " ";
//	$sum     = null;


}

function template_creator($template_id)
{
	return sql_query_single_scalar("select creator from im_task_templates where id = " . $template_id);
}

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