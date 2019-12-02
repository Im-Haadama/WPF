<?php

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
require_once(ROOT_DIR . '/focus/gui.php');

require_once (ROOT_DIR . '/im-config.php');

/**
 * @param bool $mission
 * @param null $new_task_id
 *
 * @return string|void
 * @throws Exception
 */
function focus_new_task($mission = false, $new_task_id = null)
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

	$args["fields"] = array("task_description", "project_id", "priority", "date", "preq", "creator", "team");
	$args['post_file'] = "/focus/focus-post.php";
	$args['form_table'] = 'im_tasklist';

	// Todo: check last update time
	if ($mission and function_exists("gui_select_mission"))
	{
		array_push($args["fields"],"location_name", "location_address", "mission_id");
		$i = new ImMultiSite();
		$i->UpdateFromRemote( "im_missions", "id", 0, null, null );
		$args["selectors"]["mission_id"] = "gui_select_mission";
		$args["header_fields"]["mission_id"] = "Mission";
		$args["mandatory_fields"]["location_name"] = true; $args["mandatory_fields"]["location_address"] = true;
	}

	$args["worker"] = get_user_id();
	$args["companies"] = sql_query_single_scalar("select company_id from im_working where user_id = " . get_user_id());
	$args["hide_cols"] = array("creator" => 1);
	$args["next_page"] = get_url();
	set_args_value($args); // Get values from url.

	$result = ""; $project_tasks = "";
	if ($new_task_id) $result .= im_translate("Task added") . "<br/>";

	if ($new_task_id) {
		$project_args = $args;
		$new_task = new Tasklist($new_task_id);
		$project_id = $new_task->getProject();
		$project_args["title"] = "Project " . get_project_name($project_id);
		$project_args["query"] = "project_id=" . $project_id . " and status < 2";
		$project_args["order"] = "id desc";
		unset($project_args["fields"]);

		$project_tasks = GemTable("im_tasklist", $project_args);

		// Set default value for next task, based on new one.
		$args["values"] = array("project_id" => $project_id, "team" => $new_task->getTeam());
	}

	$result .= GemAddRow("im_tasklist", "New task", $args);
	$result .= $project_tasks;

	return $result;
//	try {
//		if ($debug) { print "<br/>" . __FUNCTION__. ": "; var_dump(GetArg($args, "fields", null)); print "<br/>"; }
//		print NewRow( "im_tasklist", $args );
//	} catch ( Exception $e ) {
//		print $e->getMessage();
//		return;
//	}
//	print gui_button("btn_newtask", "data_save_new('/focus/focus-post.php', 'im_tasklist', show_project)", "צור");
}

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
			print gui_header(1, "Company details");
			print NewRow( "im_company", $args );
		} catch ( Exception $e ) {
			print "Error F1: " . $e->getMessage();
			return false;
		}

		print gui_button( "btn_add", "data_save_new('/focus/focus-post.php?operation=new_company_user', 'im_company', location_reload)", "Add" );

		// print gui_input("company", )
		return false;
	}

	// Check if user has team.
	$team_ids = team_all_teams($user_id);
	if (! count($team_ids)){
		print "uid= $user_id" . gui_br();
		var_dump($team_ids); gui_br();
		die ("Error #F2. Please report");
		// team_add($user_id, im_translate("Personal team") . " " . get_customer_name($user_id));
	}

	$project_ids = worker_get_projects($user_id);
	if (is_null($project_ids) or ! count($project_ids)) {
		project_create($user_id, im_translate("first project"), $company_ids[0]);
	}
	return true;
}


function handle_focus_do($operation)
{
	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates");
	$header_args = [];
	$header_args["scripts"] = array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js", "/focus/gui.php", "/vendor/sorttable.js" );
	$header_args["rtl"] = is_rtl();

	switch ($operation) { // Handle operation that don't need page header.
		///////////////////////////
		// DATA entry and update //
		///////////////////////////
		case "new_sequence":
			if (create_new_sequence())
				print "done";
			else
				print "create failed";
			return;

		case "del_team":
		case "cancel_im_working_teams":
			$team_id = get_param("id", true);
			if (team_delete($team_id)) print "done";
			return;

		case "delete":
			$type = get_param("type");
			switch ($type)
			{
				case "team_members":
					$team_id = get_param("team_id", true);
					$ids = get_param("ids", true);
					if (team_remove_member($team_id, $ids)) print "done";
					print "failed";
					return;
			}

			return;

		case "add_team_member":
			$team_id = get_param("team_id", true);
			$new_member = get_param("new_member", true);
			if (team_add_worker($team_id, $new_member)) print "done";
			return;

		case "end_task":
			$task_id = get_param( "id" );
			task_ended($task_id);
			create_tasks( null, false );
			return;

		case "cancel_task":
			$task_id = get_param( "id" );
			if (task_cancelled($task_id)) print "done";
			create_tasks( null, false );
			return;

		case "create_tasks":
			print create_tasks(null, true);
			return;

		case "postpone_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$r = $T->Postpone();
			create_tasks( null, false );
			if ($r) print "done";
			return;

		case "pri_plus_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$T->setPriority($T->getPriority() + 1);
			create_tasks( null, false );
			print "done";
			return;

		case "pri_minus_task":
			$task_id = get_param( "id" );
			$T       = new Tasklist( $task_id );
			$T->setPriority($T->getPriority() -1);
			create_tasks( null, false );
			print "done";
			return;

		case "save_new":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done." . $result;
			return;

		case "update":
			$table_name = get_param("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			if (update_data($table_name)) {
				if ($table_name == 'im_task_templates') {
					$row_id = intval(get_param("id", true));
					sql_query("update im_task_templates set last_check = null where id = " . $row_id);
				}
				print "done";
			}
			return;

		case "delete_template":
			$user_id = get_user_id();
			$id = get_param("row_id", true);
			if (template_delete($user_id, $id)) print "done";
			return;

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
				print get_url(1) . "?operation=bad_url&id=" . $task_id;
				return;
			}
			if ( strlen( $url ) > 1 ) print $url;
			return;

		case "add_to_company":
			$company_id = get_param("company_id", true);
			$email = get_param("email", true);
			$name = get_param("name", true);
			$project_id = get_param("project_id", true);
			if (company_invite_member($company_id, $email, $name, $project_id))
				print "done";
			return;

		case "save_add_member":
			$member = get_param("member", true);
			$team_id = get_param("team", true);
			$current = get_usermeta($member, "teams");
			if (! $current) $current = ":";
			update_usermeta($member, "teams", ":" . $team_id . $current); // should be :11:22:3:4:
			print "done";
			return;

		case "logout":
			wp_logout();
			$back = get_param( "back", false, get_url( 1 ) );
			header( "location: " . $back );
			return;

		case "cancel_im_projects":
			$id = get_param("id", true);
			if (project_delete($id, get_user_id()))
				print "done";
			return;

		case "search_by_text":
			$text = get_param("text", true);
			return search_by_text($text);
	}
	return "not handled";
}

/**
 * @param $operation
 * @param $args
 *
 * @return void
 * @throws Exception
 */
function handle_focus_show($operation, $args)
{
	if (($done = handle_focus_do($operation, $args)) !== "not handled") return $done;

	// Actions are performed and return to caller.
	// Page are $result .= and displayed in the end. (to handle the header just once);
	$action_url = get_url(1);
	$result = ""; // focus_header($header_args);


	$args["page"] = get_param("page", false, 1);

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	// show/save <obj_type>
	switch ($operation){
		case "show_settings":
			$result .= show_settings(get_user_id());
			break;
		case "focus_main":
			// $new = get_param("new", false, null);
			$result .= focus_main(get_user_id(), $args);
			break;
		case "edit_organization":
			$result .= edit_organization();
			break;
		case "show_worker":
			// $new = get_param("new", false, null);
			$id = get_param("id", true);
			$header_args["view_as"] = $id;
			$result = focus_header($header_args);
			$result .= focus_main($id, $args);
			break;
		case "show_templates":
			$args["table"] = true;
			$args["new"] = get_param("new", false, 0);
			$new = get_param("new", false, null);
			$result .= show_templates($args, null,  $new);
			break;
		case "show_template":
			$id = get_param("id", true);
			$result .= show_templates($args, $id);
			break;
		case "show_task":
			$id = get_param("id", true);
			if ($id) $result .= show_task($id);
			break;
		case "show_project":
			$id = get_param("id", true);
			if ($id) $result .= show_project($id);
			break;
		case "bad_url":
			$id = get_param("id");
			$result .= "Url for task $id is wrong<br/>";
			$template_id = task_template($id);
			$result .=  gui_hyperlink("Edit template $template_id", "?operation=show_template&id=$template_id");
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
			$result .= GemAddRow("im_projects", "Add a project", $args);
			break;
		case "show_edit_project":
			$args = [];
			$project_id = get_param("id", true);
			$args["edit_cols"] = array("project_name" => true, "project_contact" => true, "project_priority" => true);
			$args["post_file"] = get_url(1);
			// TODO: bug fix... $args["selectors"] = array("company" => "company_get_name");
			$result .= GemElement("im_projects", $project_id, $args);
			$args ["query"] = "project_id = $project_id and status < 2";
			$args["page"] = get_param("page", false, null);
			$args["links"] = array("id" => add_to_url(array("operation" => "show_task", "id" => "%s")));
			$result .= GemTable("im_tasklist", $args);
			break;
		case "show_new_team":
			$args = [];
			$args["next_page"] = get_param("next_page", false, null);
			$args["post_file"] = "/niver/data/data-post.php";
			$args["selectors"] = array("manager" => "gui_select_worker");
			$args["mandatory_fields"] = array("manager", "team_name");
			$result .= GemAddRow("im_working_teams", "Add a team", $args);
			break;
		case "show_new_task":
			$mission = get_param("mission", false, null);
			$new = get_param("new", false);
			$result .= focus_new_task($mission, $new); // after the first task, the new tasks belongs to the new tasks' project will be displayed.
			break;
		case "last_entered":
			if (get_user_id() != 1) return;
			$args = array();
			$args["last_entered"] = 1;
			$result .= active_tasks($args);
			break;
		case "show_new_sequence":
			$args = array();
//			$args["selectors"] = $task_selectors;
//			$args["transpose"] = true;
//			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());

			$result .= gui_header(1, "New sequence");
			$result .= gui_label("explain", "Select the project of the sequence, the default priority of all sequence tasks. Enter text for the tasks");
			$args["worker"] = get_user_id(); $args["edit"] = true;
			$table_rows =array(array("project", gui_select_project("project", null, $args)),
				array("priority", GuiInput("priority", null, $args)),
				array("task1", GuiInput("task1")),
				array("task2", GuiInput("task2", null, array("events" => 'onchange="addSequenceTask(2)"')))
			);
			$result .= gui_table_args($table_rows, "sequence_table");

			// $args["debug"] = true;
			// print NewRow("im_tasklist", $args);
			$result .= gui_button("btn_new_sequence", "save_new_sequence()", "Create");
			break;

		case "new_template":
			$result .= gui_header(1, "יצירת תבנית חדשה");
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
			$result .= NewRow("im_task_templates", $args);
			$result .= gui_button("btn_template", "data_save_new('/focus/focus-post.php', 'im_task_templates')", "add");
			break;

		case "show_staff": // Teams that I manage
//			$result .= header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/focus.js" ) );
			$result .= gui_header(1, "Edit staff");
			$result .= show_staff();
			break;

		case "show_edit_projects": // Projects that I manage
//			$result .= header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/focus.js" ) );
			$result .= gui_header(1, "Edit projects");
			$args["worker_id"] = get_user_id();
			$result .= edit_projects($args);
			break;

		case "show_edit_all_projects": // Projects that I manage
			if (! im_user_can("edit_projects")) die("no permissions");
			$result .= gui_header(1, "Edit all projects");
			$args["global"] = true;
			$result .= edit_projects($args);
			break;

		case "new_team":
//			$result .= header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/niver/data/focus.js" ) );
			$result .= gui_header(1, "Add Team");
			$args = array("selectors" => array("manager" => "gui_select_worker"));
			$result .= NewRow("im_working_teams", $args);
			$result .= gui_button("btn_newteam", "save_new('im_working_teams')", "add");
			break;

		case "remove_from_team":
			$team_id = get_param("id", true);
			$worker_id = get_param("user", true);
			team_remove_member($team_id, $worker_id);
			handle_focus_operation("show_team", null);
			break;

		case "show_team":
			$team_id = get_param("id", true);
			print show_team($team_id);
			break;

		case "show_add_member":
			$team_id = get_param("id", true);
			$result .= gui_header(1, "Adding memeber to team" . sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id));
			$result .= gui_select_worker("new_member");
			$result .= gui_label("team_id", $team_id, true);
			$result .= gui_button("btn_add_member", "add_member()", "Add");

			$result .= "<br/>";
			$result .= gui_hyperlink("Invite college to your company", add_to_url(array("operation"=>"show_add_to_company")));
			break;

		case "show_add_to_company":
			$company_id = get_param("id", true);
			$result .=  gui_header(2, "Invite to company") . " " . gui_label("company_id", $company_id);
			$result .=  im_translate("Enter college email address: ");
			$result .=  gui_table_args(array(array("email", GuiInput("email", "", $args)),
				                       array("name", GuiInput("name", "", $args)),
									   array("project", gui_select_project("project_id", null, $args))));
			$result .=  gui_button("btn_add_to_company", "add_to_company()", "Add");
			break;

		case "projects":
			$result .=  header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js" ) );

			if ($id = get_param("project_id")) {
				$result .=  show_project($id);
			}
			$result .= show_projects(get_url(), get_user_id());
			break;

		case "task_types":
			$args = array();
			$args["title"] = "task types";
			$result .=  GemTable("im_task_type", $args);
			break;

		case "new_company_user":
			$company_id = data_save_new("im_company");
		//			$worker_id = worker_get_id(get_user_id());
			$sql = "update im_working set company_id = " . $company_id . " where user_id = " . get_user_id();
			sql_query($sql);

			$result .=  "done";
			break;

		case "show_edit_all_teams": // System manager -> edit all teams in the system.
			if (! im_user_can("edit_teams")) $result .=  "No permissions";
			$args = [];
			$args["post_file"] = "/niver/data/data-post.php";
			$args["selectors"] = array("manager" => "gui_select_worker");
			$args["links"] = array("id" => add_to_url(array("operation" => "show_edit_team", "id" => "%s")));
			$args["page"] = get_param("page");
//			$result .=  "url = " . get_url() . "<br/>";
//			$result .=  add_to_url(array("operation" => "del_team", "id"=>"%s"));

			$args["actions"] = array(array("delete", $action_url . "?operation=del_team&id=%s;action_hide_row"));
			$result .= gui_header(1, "All system teams");
			$result .=  GemTable("im_working_teams", $args);

			unset($args["actions"]);
			$args["mandatory_fields"] = array("manager", "team_name");
			$result .=  GemAddRow("im_working_teams", "Add a team", $args);
			break;

		case "show_edit_team":
			$team_id = get_param("id");
			$result .= show_edit_team($team_id);
			break;

		case "show_tasks":
			$query = data_parse_get("im_tasklist", array("operation"));
			$ids = data_search("im_tasklist", $query);
			$result .= show_tasks($ids);
			// debug_var($query);
			break;

		case "show_edit_company":
			$company_id = get_param("company_id", true);
			$page = get_param("page", false, 1);
			$result .= show_edit_company($company_id, $page);
			break;
		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	print $result;
	return;
}

/**
 * @param $ids
 *
 * @return string|null
 */
function show_tasks($ids)
{
	$args = [];
	$args["query"] = "id in (" . comma_implode($ids) . ")";
	return active_tasks($args);
}

/**
 * @param $team_id
 *
 * @return string
 * @throws Exception
 */
function show_edit_team($team_id)
{
	$result = gui_header(1, "Edit team");
	$args["selectors"] = array("manager" => "gui_select_worker");
	$args["post_file"] = get_url(1) . "?team_id=" . $team_id;
	$result .= GemElement("im_working_teams", $team_id, $args);

	$result .= gui_header(2, "Team members");
	$table = array();
	$table["header"] = array("name");
	foreach (team_all_members($team_id) as $member){
		$table[$member]["name"] = get_user_name($member);
	}

	$args["add_checkbox"] = true;
	$result .= GemArray($table, $args, "team_members");

	$result .= gui_header(1, "add member");
	$result .= gui_select_worker("new_member", null, $args);
	$result .= gui_button("btn_add_member", "add_team_member(" . $team_id . ")", "add");

	$tasks = sql_query_array_scalar("select id from im_tasklist where team = $team_id");
	if ($tasks)
		$result .= show_tasks($tasks);
	else
		$result .= "No tasks";


	return $result;
//			$result = gui_header(1, "edit team" . team_get_name($team_id));
//			$result .=  gui_header(2, "members");
//			foreach (team_all_members($team_id) as $member) $result .= get_user_name($member) . " ";

}

/**
 * @param $company_id
 * @param $page
 *
 * @return string
 * @throws Exception
 */
function show_edit_company($company_id, $page)
{
	$result = gui_header(1, company_get_name($company_id));
	$args = [];
	$args["query"] = "manager = 1";
	$args["links"] = array("id" => add_to_url(array("operation" => "show_edit_team&id=%s")));
	$args["selectors"] = array("team_members" => "gui_show_team");
	$args["page"] = $page;

	$teams = TableData("select id, team_name from im_working_teams where manager in \n" .
	                  "(select user_id from im_working where company_id = $company_id) order by 1", $args);
	foreach ($teams as $key => &$row)
		if ($key == "header") $row [] = im_translate("Team members");
		else $row["team_members"] = comma_implode(team_all_members($row["id"]));
	//GemTable("im_working_teams", $args);
	$result .= GemArray($teams, $args, "company_teams");

	return $result;
}

/**
 * @param $args
 * view_as -
 * developer might add extra info - e.g, log file
 *
 *
 * @return string
 * @throws Exception
 */
function focus_header($args)
{
	$result = "";
	// $args = array("print_logo" => true, "rtl" => is_rtl());
	$args["greeting"] = false;
	if (get_user_id() == 1) $args["greeting_extra_text"] = gui_hyperlink("log", focus_log_file(1));

	$result =  HeaderText($args);
	$result .= load_scripts(GetArg($args, "scripts", null));
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

	return GuiTableContent( "projects", $sql, $args );
}

/**
 * @param $args
 * @param int $template_id
 * @param null $new
 *
 * @return string
 * @throws Exception
 */
function show_templates(&$args, $template_id = 0, $new = null )
{
	$url = get_url(1);

	$result = "";
	$action_url = get_url(1);//  "/focus/focus-post.php";

	$args["worker"] = get_user_id();
	$args["companies"] = worker_get_companies(get_user_id());
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker",
	                           "creator" => "gui_select_worker", "repeat_freq" => "gui_select_repeat_time", "team" => "gui_select_team");
	$args["fields"] = array("id", "task_description", "project_id", "priority", "team", "repeat_freq", "repeat_freq_numbers", "working_hours", "condition_query", "task_url",
		"template_last_task(id)");
	$args["header_fields"] = array("task_description" => "Task description", "project_id" => "Project", "priority" => "Priority",
	                               "team" => "Team", "repeat_freq" => "Repeat Frequency", "repeat_freq_numbers" => "Repeat times", "working_hours" => "Working hours",
		                           "Task site");

	if ($template_id){
		// print gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";
		$args["title"] = "Repeating task";
		$args["post_file"] = $url;

		$result .= GemElement("im_task_templates", $template_id, $args);

		$tasks_args = array("links" => array("id" => get_url(1) . "?operation=show_task&id=%s"));
		$task_args["class"] = "sortable";

		if (get_user_id() == 1){
			$output = "";
			$row = sql_query_single_assoc("SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority, creator, team " .
			       " FROM im_task_templates where id = $template_id");

			create_if_needed($template_id, $row, $output, 1, $verbose_line);
			$result .= $output;
		}

		$table = GuiTableContent("last_tasks", "select * from im_tasklist where task_template = " . $template_id .
		                                       " order by date desc limit 10", $tasks_args);
		if ($table)
		{
			$result .= gui_header(2, "משימות אחרונות");
			$result .= $table;
			return $result;
		}

		return $result;
	}

	if ($page = get_param("page")) { $args["page"] = $page; unset ($_GET["page"]); };

	$query = " 1";
	if (get_param("search", false, false)){
		$ids = data_search("im_task_templates", $args);
		if (! $ids){
			$result .= "No templates found" . gui_br();
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

	$result = gui_hyperlink( "Add repeating task", get_url( true ) . "?operation=new_template" );

	$result .= GemTable("im_task_templates", $args);
	// $result .= GuiTableContent( "projects", $sql, $args );

	return $result;
}

/**
 * @param $row_id
 * @param int $edit
 *
 * @return string
 * @throws Exception
 */
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

	return GemElement($table_name, $row_id, $args);
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
 * @param $id
 * @param $value
 * @param $args
 *
 * @return mixed|string
 */
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

/**
 * @return string
 * @throws Exception
 */
function show_staff() // Edit teams that I manage.
{
	$user = wp_get_current_user();
	$result = gui_header(2, "teams");

	$args = [];
	$args["links"] = array("id" => add_to_url(array("operation" => "show_team", "id" => "%s")));
	$args["selectors"] =  array("manager" => "gui_select_worker");
	$args["edit"] = false;
	$result .= GuiTableContent("working_teams", "select * from im_working_teams where manager = " . $user->id, $args);

	$result .= gui_hyperlink("add", add_to_url("operation", "show_new_team"));
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
	$result = gui_header(2, "Projects");

	$global = GetArg($args, false, "global");
	$args["links"] = array("ID" => add_to_url(array("operation" => "show_edit_project", "id" => "%s")));
	$args["selectors"] =  array("manager" => "gui_select_worker");
	$args["edit"] = false;
	$args["actions"] = array (array("delete", get_url(1) . "?operation=cancel_im_projects&id=%s;action_hide_row"));
	$base_query = "is_active = 1 ";
	$args["fields"] = array("ID", "project_name", "project_contact", "project_priority", "company");
	if ($global) // A global user can see all projects
	{
		$args["query"] = $base_query;
		$result .= GemTable("im_projects", $args); // "select * from im_projects ", $args);
	} else { // Ordinary user can see only projects he's working in.
		$worker_id = GetArg($args,"worker_id", null);
		if (! $worker_id) die ("no worker_id");
		$companies = worker_get_companies(get_user_id());

		foreach ($companies as $company){
			$result .= gui_header(1, company_get_name($company));
			$args["query"] = $base_query . " and company = $company";
			$result .= GemTable("im_projects", $args); //"select * from im_projects where company = $company", $args);
		}
	}

	$result .= gui_hyperlink("add", add_to_url("operation", "show_new_company"));

	return $result;
	// print GuiTableContent("");
}

/**
 * @param null $args
 * @param bool $debug
 * @param bool $time
 *
 * @return string|null
 */
function active_tasks(&$args = null, $debug = false, $time = false)
{
	$args["count"] = 0;
	$args["drill"] = true;
	$args["drill_operation"] = "show_tasks";

	$table_name = "im_tasklist";
	$title = GetArg($args, "title", "");

	$action_url = get_url(1);
	$page_url = get_url(true);

	$active_only = GetArg($args, "active_only", true);
	if ($active_only){
		$title .= " (" . im_translate("active only") . ")";
	}

	if (! isset($args["fields"])) $args["fields"] = array("id", "task_description", "project_id", "priority", "task_template");

	$limit = get_param("limit", false, 10);

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
	// Use drill, instead - $links["project_id"] = $page_url . "?operation=show_project&id=%s";
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

	$sql = "select " . comma_implode($fields) . " from $table_name $query $order ";

	if ($debug)
		print "<br/>" . $sql . "<br/>";

	// print $sql . "<br/>";
	$result = "";
	try {
		if (isset($_GET["debug"])) print "sql = $sql<br/>";
		$args["sql"] = $sql;
		$table = GemTable("im_tasklist", $args);
//		print "CC=" . $args["count"] . "<br/>";
		// $table = GuiTableContent( $table_name, $sql, $args );
		// if (! $args["count"]) return "";
		if ($table) {
			// if (strlen($title)) $result = gui_header(2, $title);
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

/**
 * @param $team_id
 * @param $active_only
 *
 * @return string
 */
function show_team($team_id, $active_only = true)
{
	$result = "";
	$result .=  gui_header(1, "Team " . team_get_name($team_id));
	$result .=  gui_hyperlink("Include non active", add_to_url("active_only", 0));

	// $team_members = team_members($team_id);

//		$result .=  gui_header(2, get_customer_name($user_id) . " " . $user_id);
	$args = array("active_only" => $active_only);
	$args["query"] = " team=" . $team_id;
	$args["fields"] = array("id", "task_description", "project_id", "priority", "task_template", "owner");
	$result .=  active_tasks($args);
	return $result;
}

//// team_add_worker($team_id, team_manager($team_id));
//// $result .= gui_header(1, im_translate("Team") . ": " . team_get_name($team_id));
//$args = array("selectors" => array("ID" => "gui_select_worker"),
//              "edit" => false,
////				array( "cancel", $action_url . "?operation=cancel_task&id=%s;action_hide_row" ),
//
//              "actions" => array(array("remove", add_to_url(array("operation" => "remove_from_team", "user" => "%s")))));
//$result .= GuiTableContent("im_working_teams",
//	'select ID from wp_users where worker_teams(id) like "%:' . $team_id . ':%"', $args);
//$result .= gui_hyperlink("add member", add_to_url("operation" , "show_add_member"));
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
		$result .= gui_hyperlink(team_get_name($team_id), $url . "?team=" . $team_id);
	}
	return $result;
}

/**
 * @param $team_id
 *
 * @return string
 */
function team_get_name($team_id)
{
	return sql_query_single_scalar("select team_name from im_working_teams where id = " . $team_id);
}

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
 * @param $project_id
 * @param null $args
 *
 * @return string|null
 * @throws Exception
 */
function show_project($project_id, $args = null)
{
	$active_only = GetArg($args, "active_only", true);
	$order = GetArg($args, "order", "order by priority desc");
	if (is_null($args)) $args = [];

	$sql = "select * from im_tasklist where project_id = " . $project_id;
	if ($active_only) $sql .= " and status = 0 ";
	$args["sql"] = $sql . $order;
	$args["links"] = array("id" =>  add_to_url(array("operation" => "show_task", "id" => "%s")));
	$args["title"] = im_translate("משימות בפרויקט") . " " . get_project_name($project_id);

//	print $sql;
	return GemTable("im_tasklist", $args);
}

/**
 * @return string
 * @throws Exception
 */
function edit_organization()
{
	$user_id = get_user_id();
	$result = "";
	$result .= gui_hyperlink("Edit organization", add_to_url("operation" , "show_staff")) . " ";

	$result .= gui_hyperlink("My projects", add_to_url("operation", "show_edit_projects")) . " ";
	$my_companies = worker_get_companies($user_id, true);
	if ($my_companies) foreach ($my_companies as $company){
		$result .= gui_hyperlink(company_get_name($company), add_to_url( array("operation" => "show_edit_company", "company_id" => $company))) . " ";
	}

	if (im_user_can("edit_teams")){ // System editor
		$result .= "<br/>" . im_translate("System wide:");
		$result .= gui_hyperlink("All teams", add_to_url("operation" , "show_edit_all_teams")) . " ";
		$result .= gui_hyperlink("All projects", add_to_url("operation", "show_edit_all_projects")) . " ";
	}

	return $result;
}

/**
 * @param $user_id
 *
 * @param $args
 *
 * @return string
 * @throws Exception
 */
function focus_main($user_id, $args)
{
	$debug = 0;
	// $user_id = get_user_id();
	$time_filter = false;

	$result = "";
	$ignore_list = [];
	$args["count"] = 0;

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Links: Templates                                                                       //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////

//	$url = get_url(1);
//	$result .= gui_hyperlink("Repeating tasks", $url . "?operation=show_templates") . " ";

	if ($teams = team_managed_teams($user_id)) {// Team manager
		$result .= gui_hyperlink("Edit organization", get_url() . "?operation=edit_organization");
		$result .= "<br/>";
		$workers = array();
		foreach ($teams as $team) {
			foreach (team_all_members($team) as $worker_id) $workers[$worker_id] = 1;
			$count = 0; // active_task_count("team_id = " . $team);
			$result .= gui_hyperlink( team_get_name($team) . "(" . $count . ")", "?operation=show_team&id=" . $team );
		}
		$result .= "<br/>";
		foreach ($workers as $worker_id => $c) {
			$count = 0;
			$result .= gui_hyperlink(get_user_name($worker_id) . "(" . $count . ")", '?operation=show_worker&id=' . $worker_id) . " ";
		}
		$result .= "<br/>";
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Tasks I need to handle (owner = me)                                                                       //
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$args["title"] = im_translate("Tasks assigned to me");
	$args["query"] = " owner = " . $user_id;
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
	$args["query"] = " creator = " . $user_id . " and (owner != " . $user_id . ' or isnull(owner)) and team not in (' . comma_implode($teams) . ")";
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

/**
 * @throws Exception
 */
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

/**
 * @throws Exception
 */
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

//	$sum     = null;


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


function alerts_pulldown($user_id, $limit = 10)
{
	// TODO: added filtering
	$menu_options = [];

	$events = sql_query_array("select id, started as event_time, 'started' as event_description from im_tasklist where started is not null
union
select id, ended as event_time, 'ended' as event_description from im_tasklist where ended is not null
order by 2 desc
limit $limit
");
	foreach ($events as $event) {
		$id = $event[0];
		$time = $event[1];
		$event_descripton = $event[2];
		$t = new Tasklist($id);
		$text = "task " . $t->getTaskDescription() . " " . $event_descripton . " at " . $time;
		array_push($menu_options, array("link" => link_to_task($id), "text" => $text));
	}

	return GuiPulldown("alerts", "alerts", ["menu_options" => $menu_options] );
}

function link_to_task($id)
{
	return add_to_url(array("operation" => "show_task", "id" => $id));
}

function project_pulldown($user_id)
{
	$projects = worker_get_projects($user_id);
	$menu_options = [];
	foreach ($projects as $project)
		array_push($menu_options,
			array("link" => add_to_url(array("operation"=>"show_project", "id" => $project["project_id"])),
			"text"=>$project['project_name']));
	return GuiPulldown("projects", "projects", ["menu_options" => $menu_options] );
}

function team_pulldown($user_id)
{
	$teams = worker_get_teams($user_id);
	$menu_options = [];
	if (! $teams) return "";
	foreach ($teams as $team)
		array_push($menu_options,
			array("link" => add_to_url(array("operation"=>"show_team", "id" => $team)),
			      "text"=>team_get_name($team)));
	return GuiPulldown("teams", "teams", ["menu_options" => $menu_options] );
}

function search_by_text($text)
{
	$result = [];
	$result = array_merge($result, project_list_search("project_name like " . quote_percent($text)));
	$result = array_merge($result, task_list_search("task_description like " . quote_percent($text)));

	if (count($result) < 2) return "No results";

	return gui_table_args($result);
}

function task_list_search($query)
{
	$tasks = sql_query_array("select id, task_description from im_tasklist where $query");

	$result = [];
	foreach ($tasks as $task)
		array_push($result, GuiHyperlink($task[1], add_to_url(array("operation" => "show_task", "id" => $task[0]))));

	// debug_var($result);
	return $result;
}

function project_list_search($query)
{
	return sql_query_array_scalar("select id from im_projects where $query" );
}

function show_settings($user_id)
{
	$result = gui_header(1, im_translate("Settings for") . " " . get_user_name($user_id));


	return $result;
}