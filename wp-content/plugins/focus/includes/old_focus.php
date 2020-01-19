<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR',  dirname( __FILE__ ) ) ;
}
if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH',  dirname(dirname( dirname(ROOT_DIR)) ) . '/');
}


require_once(ABSPATH . '/wp-config.php');
require_once(ABSPATH . '/im-config.php');

require_once( ROOT_DIR . "/core/gui/gem.php" );

require_once( "focus/focus_class.php" );
$operation = GetParam("operation", false, "focus_main");

require_once( ROOT_DIR . "/init.php" );

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if (! get_user_id(true)) return;

if (handle_focus_do($operation) !== "not handled") return;

$themes = [];
$height = "80px";
$dark_brown = "231f20";
$light_green = "98c64a";
$redish = "6a241a";

$themes["dark"] = array("logo_image" => "http://store.im-haadama.co.il/wp-content/uploads/2019/11/logo-red.png",
                        "css" => "/im-dark.css");

//$themes["dark"] =
//	array("header_logo_url" => "http://store.im-haadama.co.il/wp-content/uploads/2019/11/imadama-logo.png",
//	      "text_color" => "98c64a",
//	      "background_color" => "231f20",
//	      "logo_height" => "80px",
//        "bgcolor" => "231f20");

//$theme["light"] =
//	array("header_logo_url" => "http://store.im-haadama.co.il/wp-content/uploads/2019/11/imadama-logo.png",
//	      "text_color" => "98c64a",
//	      "background_color" => "231f20",
//	      "logo_height" => "80px");

$args = $themes["dark"];
$args["print_logo"] = false;
$args["script_files"] = array( "/core/gui/client_tools.js", "/core/data/data.js", "/focus/focus.js" );
$user_id = get_user_id();
$projects = project_pulldown($user_id);
$logo = GuiHyperlink(GuiImage(GetArg($args, "logo_image", null), GetArg($args, "height", 80)), "/focus.php");
$search = GuiInput("search_text", "(search here)", array("events" => "onfocus=\"search_by_text()\" onkeyup=\"search_by_text()\" onfocusout=\"search_box_reset()\""));
//$greeting = greeting();
$alerts = alerts_pulldown($user_id);
// $setting = GuiHyperlink("settings", add_to_url("operation", "show_settings"), array ("class" => "light_hyperlink"));
$setting = GuiPulldown("settings", "settings", array("menu_options" => array(array("text" => "Edit organization", "link" => AddToUrl("operation", "edit_organization")))));
$repeating_tasks = GuiPulldown("repeating_tasks", "Repeating tasks", array("menu_options" => array(array("text" => "all", "link" => GetUrl(1) . "?operation=show_repeating_tasks"),
	array("text" => "weekly", "link" => GetUrl(1) . "?operation=show_repeating_tasks&freq=w"),
	array("text" => "monthly", "link" => GetUrl(1) . "?operation=show_repeating_tasks&freq=j"),
	array("text" => "annual", "link" => GetUrl(1) . "?operation=show_repeating_tasks&freq=z"))));

$teams = team_pulldown($user_id);
//$circle = '<div id="avatar_circle"
$header_elements = array($logo, $search, get_avatar(get_user_id(), 40), $repeating_tasks, $alerts, $projects, $teams, $setting);
$args["header_elements"] = $header_elements;
print GemHeader("header_div", "header_div", $args); // Opens the body with style definitions and menus.

print '<div id="search_result"></div>';

print "<br/>"; // The space of header.

if (! focus_check_user()) return;

if ($operation) {
	if (GetParam("page", false, null)) $args ["page"] = GetParam("page");
	handle_focus_show($operation, $args);
	return;
}
?>
