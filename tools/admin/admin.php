<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "tasklist.php" );
require_once( "../../niver/web.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );
require_once(ROOT_DIR . '/tools/people/people.php');
require_once("../../niver/fund.php");
require_once("common.php");
require_once("focus.php");

$this_url           = $_SERVER['REQUEST_URI'];
$entity_name        = "משימה";
$entity_name_plural = "משימות";
$table_name         = "im_tasklist";

$task_selectors = array("project_id" =>  "gui_select_project", "owner" => "gui_select_creator", "creator" => "gui_select_creator", "preq" => "gui_select_task",
	"mission_id" => "gui_select_mission");
$template_selectors = array("project_id" =>  "gui_select_project", "owner" => "gui_select_creator", "creator" => "gui_select_creator");

$operation = get_param( "operation", false );
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


if ($operation) {
	switch ($operation){
		case "templates":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

			print gui_hyperlink("הוסף תבנית", $url . "?operation=new_template");

			show_templates();

			break;

		case "new_task":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );
			$args = array();
			$args["selectors"] = $task_selectors;
			 $args["transpose"] = true;
			$args["values"] = array("owner" => $user_ID, "creator" => $user_ID);
			// $args["debug"] = true;
			print NewRow("im_tasklist", $args);
			print gui_button("btn_newtask", "save_new('im_tasklist')", "צור");
			break;

		case "new_sequence":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );
			$args = array();
			$args["selectors"] = $task_selectors;
			$args["transpose"] = true;
			$args["values"] = array("owner" => $user_ID, "creator" => $user_ID);
			// $args["debug"] = true;
			print NewRow("im_tasklist", $args);
			print gui_button("btn_newsequence", "save_new('im_tasklist')", "צור");
			break;

		case "new_template":
			print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

			print gui_header(1, "יצירת תבנית חדשה");
			$args = array();
			$args["selectors"] = $template_selectors;
			$args["transpose"] = true;
			$args["values"] = array("owner" => $user_ID, "creator" => $user_ID);
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
			print gui_hyperlink("add member", $url . "?operation=add_member&id=$team_id");
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

			show_projects($url, $user_ID);
			break;

		default:
			print $operation . " not handled <br/>";
			die(1);
	}
	return;
}


$non_zero = get_param( "non_zero" );

$url = get_url();

print header_text( false, true, true, "/vendor/sorttable.js" );

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
global $template_selectors;
	$args              = array();

	$args["selectors"] = $template_selectors;
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

print header_text( false, true, true, array(
	"/niver/gui/client_tools.js",
	"/tools/admin/data.js",
	"/vendor/sorttable.js"
) );


function show_task($row_id, $edit = 1)
{
	global $entity_name, $table_name, $task_selectors;

	print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = $edit;
	$args["selectors"] = $task_selectors;
	$project_id = sql_query_single_scalar("select project_id from im_tasklist where id = " .$row_id);
	if ($project_id > 0)
		$args["where"] = "project_id = " . $project_id;

	try {
		print GuiRowContent( $table_name, $row_id, $args );
	} catch ( Exception $e ) {
		print "having problem... " . $e->getMessage();
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
function gui_select_repeat_time( $id, $value, $events = "") {
//	print "v=" . $value . "<br/>";

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

function show_active_tasks($args = null, $debug = false, $time = false)
{
	global $task_selectors;
	global $this_url;
	global $entity_name_plural;
	global $table_name;

	$url = GetArg($args, "url", basename(__FILE__));

	print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

	$user_id = GetArg($args, "user_id", wp_get_current_user()->ID);
	$project_id = GetArg($args, "project", null);
	$active_only = GetArg($args, "active_only", true);

	if (! get_param("no_limit"))
		$limit = " limit 10 ";

	$links       = array();
	$links["id"] = $this_url . "?row_id=%s";

	$query   = "where 1 ";
	$title = " Managing " . $entity_name_plural;
	if ($project_id) {
		$query .= " and project_id = $project_id";
		$title .= " project " . get_project_name($project_id);
	}

	print gui_header( 1, $title );

	print greeting();

	print gui_hyperlink("repeating tasks", $url . "?operation=templates");

	print " ";

	print gui_hyperlink("add tasks", $url . "?operation=new_task");

	print " ";

	print managed_workers($user_id, $_SERVER['REQUEST_URI']);

	print " ";

	print gui_hyperlink("projects", $url . "?operation=projects");

	$sum     = null;

	if ($active_only)
		$query .= " and (status in (0, 1) and (isnull(preq) or task_status(preq) >= 2) and (date is null or date(date) <= Curdate()))";

	if ($time) $query .= " and task_active_time(id)";

	$query .= " and (mission_id is null or mission_id = 0) ";

	$owner_query = $query . " and owner = " . $user_id;
	$creator_query = $query . " and creator = " . $user_id . " and owner != " . $user_id;

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

//	print "order=$order<br/>";
	$args             = array();

	$links["task_template"] = $url . "?task_template_id=%s";
	$links["id"] = $url . "?row_id=%s";
	$links["project_id"] = $url . "?project_id=%s";
	$args["links"]    = $links;

	$args["selectors"] = $task_selectors;
	$args["actions"]  = $actions;
	$args["id_field"] = "id";
	$args["edit"] = false;

	$more_fields = "";

	if ($debug and ! $time)
		$more_fields .= ", task_template_time(id) ";

	$sql = "select id, date(date) as date, task_description, task_template, started, project_id,
       priority, preq $more_fields from $table_name $owner_query $order $limit";

	if ($debug)
		print "<br/>" . $sql . "<br/>";

	print gui_header(1, "My Tasks ");

	print GuiTableContent( $table_name, $sql, $args );

	print gui_header(1, "Tasks I've created");

	$sql = "select id, date(date) as date, task_description, task_template, started, project_id,
       priority, preq $more_fields from $table_name $creator_query $order $limit";

	try {
		print GuiTableContent( $table_name, $sql, $args );
	} catch ( Exception $e ) {
		print "configuration problem: " . $e->getMessage();
	}
}


function show_team($team_id, $active_only, $url)
{
	print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js", "/tools/admin/admin.js" ) );

	print gui_header(1, "Showing status of team " . team_get_name($team_id));

	$sql = "select worker_id from im_working where worker_teams(worker_id) like '%:" . $team_id . ":%'";
	$team_members = sql_query_array_scalar($sql);

	foreach ($team_members as $member){
		print gui_header(2, get_customer_name($member));
		$args = array("user_id" => $member, "active_only" => $active_only);
		$args["url"] = $url;
		show_active_tasks($args);
	}
}


function greeting()
{
	$data = "";

	$user_id = wp_get_current_user()->ID;

	$now = strtotime("now");

	if ($now < strtotime("12pm"))
		$data .= "Good morning";
	else
		$data .= "Hello";

	$data .= " " . get_customer_name($user_id) . "(" . $user_id . ")";

	$data .= Date("G:i", $now );

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
