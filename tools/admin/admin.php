<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/08/15
 * Time: 08:19
 */

require_once( '../r-shop_manager.php' );
require_once( "tasklist.php" );
require_once( "../../niver/web.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );
require_once(ROOT_DIR . '/tools/people/people.php');
require_once("../../niver/fund.php");


$this_url           = "admin.php";
$entity_name        = "משימה";
$entity_name_plural = "משימות";
$table_name         = "im_tasklist";

$task_selectors = array("project_id" =>  "gui_select_project", "owner" => "gui_select_creator", "creator" => "gui_select_creator", "preq" => "gui_select_task",
	"mission_id" => "gui_select_mission");
$template_selectors = array("project_id" =>  "gui_select_project", "owner" => "gui_select_creator", "creator" => "gui_select_creator");

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/tools/admin/data.js" ) );

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

$project_id = get_param( "project_id" );
if ( $project_id ) {
	$args = [];
	$args["project"] = $project_id;
	show_active_tasks( $args, $project_id, $user_ID );
	return;
}

$task_template_id = get_param("task_template_id");
if ($task_template_id) { show_templates($task_template_id); return; }

if ($operation) {
	switch ($operation){
		case "templates":
			print gui_hyperlink("הוסף תבנית", "admin.php?operation=new_template");

			show_templates();

			break;

		case "new_task":
			$args = array();
			$args["selectors"] = $task_selectors;
			 $args["transpose"] = true;
			$args["values"] = array("owner" => $user_ID, "creator" => $user_ID);
			// $args["debug"] = true;
			print NewRow("im_tasklist", $args);
			print gui_button("btn_newtask", "save_new('im_tasklist')", "צור");
			break;

		case "new_template":
			print gui_header(1, "יצירת תבנית חדשה");
			$args = array();
			$args["selectors"] = $template_selectors;
			$args["transpose"] = true;
			$args["values"] = array("owner" => $user_ID, "creator" => $user_ID);
			print NewRow("im_task_templates", $args);
			print gui_button("btn_template", "save_new('im_task_templates')", "צור");
			break;
		default:
			print $operation . " not handled <br/>";
			die(1);
	}
	return;
}


$row_id = get_param( "row_id", false );
if ($row_id) { show_task($row_id); return; }

$debug = get_param("debug", false, false);
$time_filter = get_param("time", false, true);

show_active_tasks(null, $debug, $time_filter);

$non_zero = get_param( "non_zero" );

$url = get_url();

print header_text( false, true, true, "/vendor/sorttable.js" );

//print gui_header( 1, "פרויקטים" );
//
//print gui_hyperlink( "רק פרויקטים עם משימות פתוחות", $url . "?non_zero=1" );
//show_projects( $user_ID, $non_zero );
//
//print gui_header( 1, "משימות חוזרות" );
//show_templates();

function show_projects( $owner, $non_zero ) {
	$links = array();

	$links["id"] = "index.php?project_id=%s";
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

	$sum = array();

	print GuiTableContent( "projects", $sql, $args, $sum );
}

function show_templates( $template_id = 0 ) {
global $template_selectors;
	$args              = array();

	$args["selectors"] = $template_selectors;
	$sql         = "select * " .
	               " from im_task_templates ";
	if ($template_id){
		print gui_header(1, "משימה חוזרת מספר " . $template_id) ."<br/>";

		$args["transpose"] = true;
		$args["edit"] = true;
		$args["add_checkbox"] = true;
		$args["events"] = "onchange=\"changed(this)\"";

		print GuiRowContent("im_task_templates", $template_id, $args);
		print gui_button( "btn_save", "save_entity('im_task_templates', " . $template_id . ')', "שמור" );

		$tasks_args = array("links" => array("id" => "admin.php?row_id=%s"));
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
	$args["links"]     = array ("id" => "admin.php?task_template_id=%s" );
	$args["header"]    = true;

	$table = GuiTableContent( "projects", $sql, $args );

	print $table;

}

//function show_tasks( $project_id, $owner ) {
//	$actions     = array( gui_hyperlink( "בוצע", "../tasklist/tasklist-post.php?operation=end&id=%s" ) );
//	$links       = array();
//	$links["id"] = "../tasklist/admin.php?id=%s";
//	$links["project_id"] = "admin.php?project_id=%s";
//
//	$sql = "select * from im_tasklist " .
//	       " where project_id = " . $project_id .
//	       " and status = " . eTasklist::waiting .
//	       " and owner = " . $owner .
//	       " order by 12 desc ";
//
//	$args            = array();
//	$args["actions"] = $actions;
//	$args["class"]   = "sortable";
//	$args["links"]   = $links;
//
//	print GuiTableContent( "tasks", $sql, $args );
//
//}



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

	print gui_header( 1, $entity_name . " " . $row_id );
	$args                 = array();
	$args["edit"]         = $edit;
	$args["selectors"] = $task_selectors;
	$project_id = sql_query_single_scalar("select project_id from im_tasklist where id = " .$row_id);
	if ($project_id > 0)
		$args["where"] = "project_id = " . $project_id;

	print GuiRowContent( $table_name, $row_id, $args );
	print gui_button( "btn_save", "save_entity('$table_name', " . $row_id . ')', "שמור" );

	return;
}

function greeting()
{
	$data = "";

	$user_id = wp_get_current_user()->ID;

	$now = strtotime("now");

	if ($now < strtotime("12pm"))
		$data .= "בוקר טוב";
	else
		$data .= "שלום";

	$data .= " " . get_customer_name($user_id) . "(" . $user_id . ")";

	$data .= Date("G:i", $now );

	$data .= "<br/>";

	return $data;
}

function show_active_tasks($args = null, $debug = false, $time = false)
{
	global $task_selectors;
	global $this_url;
	global $entity_name_plural;
	global $table_name;

	$user_id = wp_get_current_user()->ID;
	$project_id = GetArg($args, "project", null);
	$active_only = $project_id ? false : true;

	if (! get_param("no_limit"))
		$limit = " limit 10 ";

	$links       = array();
	$links["id"] = $this_url . "?row_id=%s";

	print gui_header( 1, "ניהול " . $entity_name_plural );

	print greeting();

	print gui_hyperlink("משימות חוזרות", "admin.php?operation=templates");

	print " ";

	print gui_hyperlink("הוסף משימה", "admin.php?operation=new_task");

	$sum     = null;

	$query   = "where 1 ";

	if ($active_only)
		$query .= "and (status in (0, 1) and (isnull(preq) or task_status(preq) >= 2) and (date is null or date(date) <= Curdate()))";

	if ($project_id)
		$query .= " and project_id = $project_id";

	if ($time) $query .= " and task_active_time(id)";

	 $query .= " and (mission_id is null or mission_id = 0) ";

	$owner_query = $query . " and owner = " . $user_id;
	$creator_query = $query . " and creator = " . $user_id . " and owner != " . $user_id;

	$actions = array(
		array( "התחל", "tasklist.php?operation=start&id=%s" ),
		array( "בוצע", "tasklist.php?operation=end&id=%s" ),
		array( "בטל", "tasklist.php?operation=cancel&id=%s" ),
		array( "דחה", "tasklist.php?operation=postpone&id=%s" )
	);
	$order   = "order by priority desc ";
	$args             = array();

	$links["task_template"] = "admin.php?task_template_id=%s";
	$links["id"] = "admin.php?row_id=%s";
	$links["project_id"] = "admin.php?project_id=%s";
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

	print gui_header(1, "משימות לטיפול");

	print GuiTableContent( $table_name, $sql, $args );

	print gui_header(1, "משימות שיצרתי");

	$sql = "select id, date(date) as date, task_description, task_template, started, project_id,
       priority, preq $more_fields from $table_name $creator_query $order $limit";

	print GuiTableContent( $table_name, $sql, $args );
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
