<?php

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR',  dirname( dirname( __FILE__)  ) );
}

require_once( ROOT_DIR . "/core/web.php" );
require_once( ROOT_DIR . '/core/gui/input_data.php' );
require_once( ROOT_DIR . "/core/fund.php" );
require_once( ROOT_DIR . "/core/gui/gem.php" );
require_once( ROOT_DIR . "/core/data/data.php" );
require_once( ROOT_DIR . '/focus/Tasklist.php' );
require_once( ROOT_DIR . '/core/gui/gem.php' );
require_once( ROOT_DIR . '/org/people/people.php' );
require_once( ROOT_DIR . '/focus/gui.php' );

require_once (ROOT_DIR . '/im-config.php');

function handle_medicine_do($operation)
{
// 	$allowed_tables = array("im_company", "im_tasklist", "im_task_templates");
	$header_args = [];
	$header_args["scripts"] = array( "/core/gui/client_tools.js", "/core/data/data.js", "/vendor/sorttable.js" );

	switch ($operation) { // Handle operation that don't need page header.
		///////////////////////////
		// DATA entry and update //
		///////////////////////////

		case "search_by_text":
			$text = GetParam("text", true);
			return search_by_text($text);
		case "add_user":
			$user_id = GetParam("id", true);
			if (update_user_meta($user_id, "medicine", 1))
				print "done";
			return;
	}
	return "not handled";
}

function handle_medicine_show($operation, $args)
{
	// if (($done = handle_focus_do($operation, $args)) !== "not handled") return $done;

	// Actions are performed and return to caller.
	// Page are $result .= and displayed in the end. (to handle the header just once);
	$action_url = GetUrl(1);
	$result = ""; // focus_header($header_args);


	$args["page"] = GetParam("page", false, 1);

	$debug = 0;
	if ($debug)	print "operation: " . $operation . "<br/>";
	// show/save <obj_type>
	switch ($operation){
		case "show_settings":
			$result .= show_settings(get_user_id());
			break;
		case "medicine_main":
			// $new = get_param("new", false, null);
			$result .= medicine_main(get_user_id(), $args);
			break;
		case "data_entry":
			$result .= data_entry();
			break;
		case "show_add_wp_users":
			$result .= show_add_wp_users();
			break;
		default:
			if (function_exists($operation)) { $result .= $operation(); break; }
			if (substr($operation, 0, 4) == "show") {
				if (substr($operation, 5,3) == "add") {
					$table_name = substr($operation, 9);
					$result .= GemAddRow($table_name, "Add", $args);
					break;
				}
			}
			print __FUNCTION__ . ": " . $operation . " not handled <br/>";

			die(1);
	}
	print $result;
	return;
}

function medicine_main()
{
	$result = Core_Html::gui_header(1, "Main medicine");
	$result .= Core_Html::gui_header(2, "Data entry");
//	$result .= GuiHyperlink("plants", add_to_url("operation", "show_plants")) . Core_Html::Br();
//	$result .= GuiHyperlink("symptoms", add_to_url("operation", "show_symptoms")) . Core_Html::Br();
//	$result .= GuiHyperlink("affects", add_to_url("operation", "show_affects")) . Core_Html::Br();
	$result .= GuiHyperlink("clients", AddToUrl("operation", "show_clients")) . Core_Html::Br();

	return $result;
}

function show_plants()
{
	$result = Core_Html::gui_header(1, "Plants");
	$result .= GemTable("pl_plants", $args);

	return $result;
}

function show_clients()
{
	$result = Core_Html::gui_header(1, "Clients");
	$args = [];
	$args["query"] = "ID in (select user_id from wp_usermeta where meta_key = 'medicine')";
	$args["links"] = array("ID" => AddToUrl("operation", "show_user&id=%s"));
	$result .= GemTable("wp_users", $args);

	return $result;
}

function show_add_wp_users()
{
	$result = Core_Html::gui_header(1, "select user");
	$result .= gui_select_user("user_id");
	$result .= Core_Html::GuiButton("btn_add_medicine_user", "add_medicine()", "Add");

	return $result;
}

function show_user($user_id = 0)
{
	if (! $user_id) $user_id = GetParam("id", true);
	$result = "";
	$result .= Core_Html::gui_header(1, GetuserName($user_id));

	$result .= GemTable("me_clients", $args);

	return $result;
}

function show_add_me_clients($user_id = 0)
{
	if (! $user_id) $user_id = GetParam("id", true);

	$result = "";
	$result .= Core_Html::gui_header(1, "Medical info") . " " . GetuserName($user_id);
	$args = [];
	$args["hide_id"] = array("user_id" => 1);
	$args["selectors"] = array("symptoms" => "gui_select_symptom");
	$result .= GemAddRow("me_clients", "Add info", $args);

	return $result;

}