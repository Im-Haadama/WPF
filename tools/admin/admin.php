<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */


require_once( "tasklist.php" );
require_once( ROOT_DIR . "/niver/web.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );
require_once(ROOT_DIR . '/tools/people/people.php');
require_once(ROOT_DIR . "/niver/fund.php");
require_once(TOOLS_DIR . "/account/gui.php");
require_once(TOOLS_DIR . '/people/people.php');
require_once(TOOLS_DIR . "/gui.php");

require_once("common.php");
require_once("data.php");

//$this_url           = $_SERVER['REQUEST_URI'];
//$entity_name        = "משימה";
//$entity_name_plural = "משימות";
//$table_name         = "im_tasklist";

//
//
//$operation = get_param( "operation", false );
//if ( $operation ) {
//	switch ( $operation ) {
////		case "add":
////			$args = array();
////			foreach ( $_GET as $key => $data ) {
////				if ( ! in_array( $key, array( "operation", "table_name" ) ) ) {
////					if ( ! isset( $args["fields"] ) ) {
////						$args["fields"] = array();
////					}
////				}
////				$args["fields"][ $key ] = $data;
////			}
////			$args["edit"] = true;
////			print NewRow( "im_business_info", $args, true );
////			print gui_button( "btn_add", "save_new('im_business_info')", "הוסף" );
////			break;
//
//		case "templates":
//			show_templates();
//			break;
//		default:
//			die( "$operation not handled" );
//	}
//
//	return;
//}

// Selection:
global $user_ID; // by wordpress.

$admin_scripts = array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" );

function handle_admin_operation($operation)
{
	global $admin_scripts;
	switch ($operation){
		case "new_task":
			im_init($admin_scripts);
			admin_new_tasks();
			break;

		case "new_sequence":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );
			$args = array();
			$args["selectors"] = $task_selectors;
			$args["transpose"] = true;
			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
			// $args["debug"] = true;
			print NewRow("im_tasklist", $args);
			print gui_button("btn_newsequence", "save_new('im_tasklist')", "צור");
			break;

		case "new_template":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

			print gui_header(1, "יצירת תבנית חדשה");
			$args = array();
			$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker");
			$args["transpose"] = true;
			$args["worker"] = get_user_id();
			$args["companies"] = worker_get_companies(get_user_id());
			$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
			print NewRow("im_task_templates", $args);
			print gui_button("btn_template", "save_new('im_task_templates')", "צור");
			break;

		case "edit_staff":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );
			print gui_header(1, "Edit staff");
			edit_staff();
			break;

		case "new_team":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );
			print gui_header(1, "Add Team");
			$args = array("selectors" => array("manager" => "gui_select_worker"));
			print NewRow("im_working_teams", $args);
			print gui_button("btn_newteam", "save_new('im_working_teams')", "add");
			break;

		case "edit_team":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );
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
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

			show_projects(get_url(), get_user_id());
			break;

		case "new_company_user":
			$company_id = data_save_new("im_company");
			$worker_id = worker_get_id(get_user_id());
			$sql = "update im_working set company_id = " . $company_id . " where id = " . $worker_id;
//			print $sql;
			sql_query($sql);

			print "done";
			break;
		case "start":
			$task_id = get_param( "id" );
			$sql     = "UPDATE im_tasklist SET started = now(), status = " . eTasklist::started .
			           " WHERE id = " . $task_id;
			sql_query( $sql );

			$sql = "SELECT task_url FROM im_task_templates WHERE id = "
			       . " (SELECT task_template FROM im_tasklist WHERE id = " . $task_id . ")";
			$url = sql_query_single_scalar( $sql );
			if ( strlen( $url ) > 1 ) // print $url;
			{
				header( "Location: " . $url );
			} else {
				redirect_back();
			}
			break;

		default:
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	return;
}


$non_zero = get_param( "non_zero" );

$url = get_url();

// print header_text( false, true, true, "/vendor/sorttable.js" );

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
	               " from im_task_templates ";
	if ($template_id){
		print gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";

		$args["transpose"] = true;
		$args["edit"] = true;
	//	$args["add_checkbox"] = true;
		$args["events"] = "onchange=\"changed(this)\"";

		print GuiRowContent("im_task_templates", $template_id, $args);
		print gui_button( "btn_save", "save_entity('im_task_templates', " . $template_id . ')', "שמור" );

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

	$sql .= " order by 3 desc";

	$args["class"]     = "sortable";
	$args["links"]     = array ("id" => $url . "?task_template_id=%s" );
	$args["header"]    = true;

	$table = GuiTableContent( "projects", $sql, $args );

	print $table;
}

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

//print header_text( false, true, true, array(
//	"/niver/gui/client_tools.js",
//	"/tools/admin/data.js",
//	"/vendor/sorttable.js"
//) );

function show_task($row_id, $edit = 1)
{
	$table_name = "im_tasklist";
	$entity_name = "task";
	// global $entity_name, $task_selectors;

	// print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = $edit;
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker", "preq1" => "gui_select_task",
	                                                   "mission_id" => "gui_select_mission");

	$args["header_fields"] = array("Date", "Task description", "Repeating task", "Status", "Started", "Ended", "Project", "Mission", "Location", "Address", "Priority", "Prerequisite", "Assigned to",
	"Creator");

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
	print gui_button( "btn_save", "save_entity('$table_name', " . $row_id . ')', "שמור" );

	return;
}

/**
 * @param $id
 * @param $value
 * @param string $events
 *
 * @return string
 */
// $selector_name( $input_name, $orig_data, $args)
function gui_select_repeat_time( $id, $value, $args) {
//	print "v=" . $value . "<br/>";

	$events = GetArg($args, "events", null);
	$values = array( "w - שבועי", "j - חודשי", "z - שנתי");

	$selected = 1;
	for ( $i = 0; $i < count( $values ); $i ++ ) {
		if ( substr( $values[ $i ], 0, 1 ) == substr($value, 0, 1) ) {
			$selected = $i;
		}
	}

	// return gui_select( $id, null, $values, $events, $selected );
	return gui_simple_select( $id, $values, $events, $selected );
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
	global $this_url;
	$table_name = "im_tasklist";

	$url = get_url(true);
	$active_only = GetArg($args, "active_only", true);

	if (get_param("limit"))
		$limit = "limit " . get_param("limit");
	else
		$limit = "limit 10";

	$links       = array();
	$links["id"] = $this_url . "?row_id=%s";

	$query   = "where 1 ";
	if (GetArg($args, "query", null))
		$query .= " and " . GetArg($args, "query", null);

	$project_id = GetArg($args, "project", null);
	if ($project_id) {
		$query .= " and project_id = $project_id";
	}

	$args["selectors"] = array("project" => "gui_select_project", "project_id" => "gui_select_project");

	if ($active_only)
		$query .= " and (status in (0, 1) and (isnull(preq) or task_status(preq) >= 2) and (date is null or date(date) <= Curdate()))";

	if ($time) $query .= " and task_active_time(id)";

	$query .= " and (mission_id is null or mission_id = 0) ";

	$actions = array(
		array( "התחל", $url . "?operation=start&id=%s" ),
		array( "בוצע", $url . "?operation=end&id=%s" ),
		array( "בטל", $url . "?operation=cancel&id=%s" ),
		array( "דחה", $url . "?operation=postpone&id=%s" )
	);
	if (! $active_only)
		$order = "order by id desc ";
	else
		$order   = "order by priority desc ";

	$links["task_template"] = $url . "?task_template_id=%s";
	$links["id"] = $url . "?row_id=%s";
	$links["project_id"] = $url . "?project_id=%s";
	$args["links"]    = $links;

	$args["actions"]  = $actions;
	$args["id_field"] = "id";
	$args["edit"] = false;
	$args["header_fields"] = array("Id", "Start after", "Task description", "Repeating task id", "Started", "Project Id", "Priority", "Prerequisite", "Start", "Finished", "Cancel", "Postpone");

	$more_fields = "";

	if ($debug and ! $time)
		$more_fields .= ", task_template_time(id) ";

	$sql = "select id, date(date) as date, task_description, task_template, started, project_id,
       priority, preq $more_fields from $table_name $query $order $limit";

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
		$result .= gui_hyperlink( "create task", $url . "?operation=new_task" ) . "<br/>";
	}

	return $result;
}

function show_team($team_id, $active_only)
{
//	print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

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

function greeting()
{
	$data = "";

	$user_id = wp_get_current_user()->ID;

	if (! $user_id) {
		$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

		print '<script language="javascript">';
		print "window.location.href = '" . $url . "'";
		print '</script>';
		die (1);
	}

	$now = strtotime("now");

	if ($now < strtotime("12pm"))
		$data .= im_translate("Good morning");
	else
		$data .= im_translate("Hello");

	$data .= " " . gui_div("user_id", get_customer_name($user_id), false, $user_id);

	$data .=  ". " . im_translate("the time is:") . Date("G:i", $now ) . ".";

	$data .= "<br/>";

	return $data;
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

function admin_new_tasks()
{
	if (! admin_check_setup())
		return;
	// print im_translate("Project"). " " . im_translate("Good morning") . _("Hello") . $locale;
	$args = array();
	$args["selectors"] = array("project_id" =>  "gui_select_project", "owner" => "gui_select_worker", "creator" => "gui_select_worker", "preq" => "gui_select_task",
	                           "mission_id" => "gui_select_mission");;
	$args["transpose"] = true;
	$args["values"] = array("owner" => get_user_id(), "creator" => get_user_id());
	// $args["debug"] = true;
	$args["header"] = true;
	$args["header_fields"] = array("Start after", "Task description", "Project", "Mission", "Location name", "Priority",
		"Creator", "Prerequisite", "Assigned to");

	$args["fields"] = array("date", "task_description", "project_id", "mission_id", "location_name", "priority",
		"creator", "preq", "owner");

	$args["worker"] = get_user_id();
	$args["companies"] = sql_query_single_scalar("select company_id from im_working where user_id = " . get_user_id());

	$args["debug"] = (get_current_user() == 369);
	$allowed_actions = array();

	if (count($allowed_actions)) $args["actions"] = $allowed_actions;
	// $args["actions"] = array();"project_id" => "add");
	try {
		print NewRow( "im_tasklist", $args );
	} catch ( Exception $e ) {
		print $e->getMessage();
		return;
	}
	print gui_button("btn_newtask", "save_new('im_tasklist')", "צור");
}

function admin_check_setup()
{
	$worker_id = sql_query_single_scalar("select id from im_working where user_id = " . get_user_id());

	if (! $worker_id) {
		sql_query( "insert into im_working (user_id, project_id, rate, report, volunteer, day_rate, is_active, company_id) " .
		           " values ( " . get_user_id() . ", 0, 0, 0, 0, 0, 1, 0)" );
		$worker_id = sql_insert_id();
		if ( ! $worker_id ) {
			print "can't insert worker info";

			return false;
		}
	}
//	print "worker id: " . $worker_id . "<br/>";

	$company_id = sql_query_single_scalar("select company_id from im_working where id = " . $worker_id);

//	print "company id: " . $company_id . "<br/>";

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

		print gui_button( "btn_add", "save_new_custom('/tools/admin/admin-post.php?operation=new_company_user', 'im_company', location_reload)", "Add" );

		// print gui_input("company", )
		return false;
	}
	return true;
}


//	print gui_header( 1, $title );

//	print greeting();

//	print gui_hyperlink("repeating tasks", $url . "?operation=templates");

//	print " ";

//	print gui_hyperlink("add tasks", $url . "?operation=new_task");

//	print " ";

//	print managed_workers($user_id, $_SERVER['REQUEST_URI']);

//	print " ";

//	print gui_hyperlink("projects", $url . "?operation=projects");

//	$sum     = null;
