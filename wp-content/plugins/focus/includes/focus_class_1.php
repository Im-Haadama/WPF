<?php

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname( dirname( __FILE__)  ) );
}

require_once( FOCUS_INCLUDES . 'core/web.php' );
require_once( FOCUS_INCLUDES . 'core/gui/input_data.php' );
require_once( FOCUS_INCLUDES . 'core/fund.php' );
require_once( FOCUS_INCLUDES . 'core/gui/gem.php' );
require_once( FOCUS_INCLUDES . 'core/data/data.php' );
require_once( FOCUS_INCLUDES . 'Tasklist.php' );
require_once( FOCUS_INCLUDES . 'core/gui/gem.php' );
require_once( FOCUS_INCLUDES . 'org/people/people.php' );
//require_once( FOCUS_INCLUDES . 'gui.php' );
// incude

//require_once (ROOT_DIR . '/im-config.php');

/**
 * @param bool $mission
 * @param null $new_task_id
 *
 * @return string|void
 * @throws Exception
 */
function focus_new_task($mission = false, $new_task_id = null)
{
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
		$new_task = new Focus_Tasklist($new_task_id);
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
	if (!$team_ids or ! count($team_ids)){
//		print "uid= $user_id" . gui_br();
//		var_dump($team_ids); gui_br();
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
function show_tasks($ids)
{
	$args = [];
	$args["query"] = "id in (" . comma_implode($ids) . ")";
	return Focus_Views::active_tasks($args);
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

		$template = GemElement("im_task_templates", $template_id, $args);
		if (! $template) {
			$result .= "Not found";
			return $result;
		}
		$result .= $template;

		$tasks_args = array("links" => array("id" => get_url(1) . "?operation=show_task&id=%s"));
		$tasks_args["class"] = "sortable";

		if (get_user_id() == 1){
			$output = "";
			$row = sql_query_single_assoc("SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority, creator, team " .
			       " FROM im_task_templates where id = $template_id");

			create_if_needed($template_id, $row, $output, 1, $verbose_line);
			// $result .= $output;
		}

		$sql = "select * from im_tasklist where task_template = " . $template_id;
		$sql .= " order by date desc limit 10";
		print $sql;
		$table = GuiTableContent("last_tasks", $sql, $tasks_args);
		if ($table)
		{
			$result .= gui_header(2, "משימות אחרונות");
			$result .= gui_header(2, "משימות אחרונות");
			$result .= $table;
		}

		return $result;
	}

	if ($page = get_param("page")) { $args["page"] = $page; unset ($_GET["page"]); };

	$query = (isset($args["query"]) ? $args["query"] : " 1");
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
 *
 * @return string|null
 */

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
		$result .= gui_hyperlink(Org_Team::team_get_name($team_id), $url . "?team=" . $team_id);
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
			"/core/gui/client_tools.js",
			"/core/data/data.js",
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
		$t = new Focus_Tasklist($id);
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