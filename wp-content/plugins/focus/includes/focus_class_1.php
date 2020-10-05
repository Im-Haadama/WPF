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
// incude

//require_once (ROOT_DIR . '/im-config.php');

/**
 * @param bool $mission
 * @param null $new_task_id
 *
 * @return string|void
 * @throws Exception
 */


// Not used
function team_pulldown($user_id)
{
	$teams = worker_get_teams($user_id);
	$menu_options = [];
	if (! $teams) return "";
	foreach ($teams as $team)
		array_push($menu_options,
			array("link" => AddToUrl(array( "operation" =>"show_team", "id" => $team)),
			      "text"=>team_get_name($team)));
	return GuiPulldown("teams", "teams", ["menu_options" => $menu_options] );
}

function project_pulldown($user_id)
{
	$projects = worker_get_projects($user_id);
	$menu_options = [];
	foreach ($projects as $project)
		array_push($menu_options,
			array("link" => AddToUrl(array( "operation" =>"show_project", "id" => $project["project_id"])),
			      "text"=>$project['project_name']));
	return GuiPulldown("projects", "projects", ["menu_options" => $menu_options] );
}

function alerts_pulldown($user_id, $limit = 10)
{
	// TODO: added filtering
	$menu_options = [];

	$events = SqlQueryArray("select id, started as event_time, 'started' as event_description from im_tasklist where started is not null
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

/**
 * @throws Exception
 */
function not_used1() {
	$task_template_id = GetParam( "task_template_id" );
	if ( $task_template_id ) {
		show_templates( GetUrl( 1 ), $task_template_id );

		return;
	}

	if ( GetParam( "templates", false, "none" ) !== "none" ) {
		print header_text( false, true, true, array(
			"/core/gui/client_tools.js",
			"/core/data/data.js",
			"/focus/focus.js"
		) );


		$args = array();

		show_templates( GetUrl( 1 ) );

		return;
	}

	if ( $team_id = GetParam( "team" ) ) {
		global $admin_scripts;
		show_team( $team_id, GetParam( "active_only", false, true ) );

		return;
	}
}

/**
 * @throws Exception
 */
function not_used(){
	$time_filter = GetParam("time", false, true);

	$args["url"] = basename(__FILE__);


	print " ";

	print Core_Html::GuiHyperlink("add tasks", $url . "?operation=show_new_task");

	print " ";

	print Core_Html::GuiHyperlink("add sequence", $url . "?operation=new_sequence");

	print " ";

	print managed_workers(get_user_id(), $_SERVER['REQUEST_URI']) . " ";

	if (im_user_can("edit_task_types"))
		print Core_Html::GuiHyperlink("projects", $url . "?operation=projects") . " ";

	if (im_user_can("edit_projects"))
		print Core_Html::GuiHyperlink("task types", $url . "?operation=task_types") . " ";

//	$sum     = null;


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
	if (get_user_id() == 1) $args["greeting_extra_text"] = Core_Html::GuiHyperlink("log", focus_log_file(1));

	$result =  HeaderText($args);
	$result .= load_scripts(GetArg($args, "scripts", null));
	return $result;
}
