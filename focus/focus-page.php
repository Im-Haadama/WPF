<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );
require_once(ROOT_DIR . "/focus/gui.php");
require_once(ROOT_DIR . "/org/gui.php");

if (! get_user_id()) {
	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

	print '<script language="javascript">';
	print "window.location.href = '" . $url . "'";
	print '</script>';
	return;
}

require_once( "focus.php" ); ?>
	<script>
    function success_message(xmlhttp)
    {
	    if (xmlhttp.response === "done")
		    alert("<?php print im_translate("Success"); ?>");
        else
            alert (xmlhttp.response);
    }
</script>
<?php
$debug = get_param("debug", false, false);

print header_text(true, true, is_rtl(), array("/niver/data/data.js", "/niver/gui/client_tools.js"));
// focus_init();

$operation = get_param("operation", false, null);

if ($operation) {
	handle_focus_operation($operation);
	return;
}

$task_template_id = get_param("task_template_id");
if ($task_template_id) { show_templates(get_url(1), $task_template_id); return; }

if (get_param("templates", false,"none") !== "none") {
	print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/focus/focus.js" ) );

	print gui_hyperlink("Add repeating task", get_url(true) . "?operation=new_template");

	$args = array();

	show_templates(get_url(1));
	return;
}

if ($team_id = get_param("team"))
{
	global $admin_scripts;
	show_team($team_id, get_param("active_only", false, true));
	return;
}

$row_id = get_param( "row_id", false );
if ($row_id) { show_task($row_id); 	return; }

$time_filter = get_param("time", false, true);

$args["url"] = basename(__FILE__);

$url = get_url(1);
if (function_exists("greeting")) print greeting();
print gui_hyperlink("Repeating tasks", $url . "?templates");

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

// Tasks I need to handle
$args["title"] = im_translate("Tasks assigned to me");
$args["query"] = " owner = " . get_user_id();
$args["limit"] = get_param("limit", false, 10);
$args["active_only"] = get_param("active_only", false, true);
foreach ($_GET as $param => $value)
{
    if (!in_array($param, $ignore_list)){
        // print "$param = $value<br/>";
	    $args[$param] = $value;
    }
}
print active_tasks($args);
$page = get_param("page", false, 1);
$args["page"] = $page;
print gui_hyperlink("More", add_to_url("page", $page + 1)) . " " ;
print gui_hyperlink("Not paged", add_to_url("page", -1)) . " "; // All pages
print gui_hyperlink("Not filtered", add_to_url("active_only", 0)); // Not filtered
//	if (strlen ($result) < 10) {
//		$result = im_translate( "No active tasks!" ) . "<br/>";
//		$result .= im_translate( "Let's create first one!" ) . " ";
//		$result .= gui_hyperlink( "create task", $page_url . "?operation=new_task" ) . "<br/>";

// if (get_user_id() != 1) return;

// Tasks my teams need to handle.
$members = comma_implode(team_all_members(get_user_id()));
if (strlen($members)) {
	$args["query"] = " team in (" . $members . ") and owner is null";
	$table = active_tasks($args, $debug, $time_filter);
	if (strlen($table) > 100){
        print gui_header(1, "Unassigned team tasks");
        print $table;
    }
}

// Tasks that I created
if (strlen ($members) > 1)
	$args["query"] = " creator = " . get_user_id() . " and owner not in (" . $members . ", " . geT_user_id() . ")";
else
	$args["query"] = " creator = " . get_user_id() . " and owner != " . get_user_id();

$args["limit"] = get_param("limit", false, 10);
$args["active_only"] = get_param("active_only", false, true);
$result =  active_tasks($args);
// print "len=" . strlen($result);
if (strlen($result) > 150){
	print gui_header(1, "Tasks I've created");
	print $result;
}
