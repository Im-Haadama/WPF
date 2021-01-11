<?php


class Focus_Actions
{
	function init_hooks(&$loader)
	{
		$loader->AddAction("task_start", $this, "Start", 10, 2);
		$loader->AddAction("task_end", $this, "End", 10, 2);
		$loader->AddAction("task_cancel", $this, "Cancel", 10, 2);
		$loader->AddAction("task_postpone", $this, "Postpone", 10, 2);
		$loader->AddAction("task_pri_plus", $this, "PriPlus", 10, 2);
		$loader->AddAction("task_pri_minus", $this, "PriMinus", 10, 2);
		$loader->AddAction("team_remove_member", $this, "team_remove_member");
		$loader->AddAction("team_add_member", $this, "team_add_member");
		$loader->AddAction("team_add_sender", $this, "team_add_sender");
		$loader->AddAction( 'company_add_worker', $this, 'company_add_worker' );
		$loader->AddAction( 'company_remove_worker', $this, 'company_remove_worker' );

	}

	// Use WRAP function to output data to be used by the jabascript.
	function Start_wrap($args)
	{
		if (is_string($url = self::Start($args)))
			print $url;
		return true;
	}

	// The internal function can be called from the code - no output should be displayed.
	function Start($args) {
		$task_id = GetArg( $args, "id", 0 );
		if ( ! ( $task_id > 0 ) ) {
			return false;
		}

		$task = new Focus_Tasklist( $task_id );

		if ($task->task_started( get_user_id() )) {
			$url = $task->task_url();
			if ($url) return $url;
			return true;
		}

		return false;
	}

	function End($args) {
		$task_id = GetArg( $args, "id", 0 );
		if ( ! ( $task_id > 0 ) ) {
			print "no id";
			return false;
		}

		$t = new Focus_Tasklist( $task_id );

		return $t->Ended(get_user_id());
	}

	static function Cancel($args) {
		$task_id = GetArg( $args, "id", 0 );
		if ( ! ( $task_id > 0 ) ) {
			return false;
		}

		return Focus_Tasklist::task_cancelled( $task_id );
	}

	static function Postpone($args) {
		$task_id = GetArg( $args, "id", 0 );
		if ( ! ( $task_id > 0 ) ) {
			return false;
		}
		$T = new Focus_Tasklist( $task_id );

		return $T->Postpone();
	}

	static function PriPlus($args)
	{
		$task_id=GetArg($args, "id", 0);
		if (!($task_id > 0)) return false;

		$T       = new Focus_Tasklist( $task_id );
        if($T->getPriority() == 10) return false;
		return   $T->setPriority( $T->getPriority() + 1 );
	}

	static function PriMinus($args)
	{
		$task_id=GetArg($args, "id", 0);
		if (!($task_id > 0)) return false;

		$T       = new Focus_Tasklist( $task_id );
		if($T->getPriority() == 1) return false;
		return $T->setPriority( $T->getPriority() - 1 );
	}

	function team_remove_member() {
		$team_id   = GetParam( "team_id", true );
		$ids = GetParam( "ids", true );
		$team = new Org_Team($team_id);
		return $team->RemoveMember($ids);
	}

	static function team_add_member()
	{
		//let operation = post_file + "?operation=team_add_member&team_id=" + team_id + "&new_member=" + new_member;
		$team_id   = GetParam( "team_id", true );
		$new = GetParam( "new_member", true );
		$team = new Org_Team($team_id);
		return $team->AddWorker($new);
	}

	static function team_add_sender()
	{
		//let operation = post_file + "?operation=team_add_member&team_id=" + team_id + "&new_member=" + new_member;
		$team_id   = GetParam( "team_id", true );
		$new = GetParam( "new_member", true );
		$team = new Org_Team($team_id);
		return $team->AddSender($new);
	}
	function company_remove_worker()
	{
		$workers = GetParamArray("users", true);
		$company = GetParam("company", true);
		$C = new Org_Company($company);

		foreach ($workers as $worker) {
			print "removing $worker from $company<Br/>";
			$C->RemoveWorker( $worker );
		}

		return true;
	}

	function company_add_worker()
	{
		$worker_email = GetParam("worker_email", true);
		$company = GetParam("company", true);
		$C = new Org_Company($company);
		$C->AddWorker($worker_email);

		return true;
	}

}

/**
 * TODO: change action to be array(class_name, method_name);
 * till then using functions and not methods.
 *
 * @param $id
 * @param $value
 * @param $args
 *
 * @return mixed|string
 */
if ( ! function_exists( 'gui_select_repeat_time' ) ) {
	function gui_select_repeat_time( $id, $value, $args ) {
//	print "v=" . $value . "<br/>";

		$edit   = GetArg( $args, "edit", false );
		$events = GetArg( $args, "events", null );
		$values = array( "w - weekly", "j - monthly", "z - annual", "c - continuous" );

		$selected = 1;
		for ( $i = 0; $i < count( $values ); $i ++ ) {
			if ( substr( $values[ $i ], 0, 1 ) == substr( $value, 0, 1 ) ) {
				$selected = $i;
			}
		}

		// return gui_select( $id, null, $values, $events, $selected );
		if ( $edit ) {
			return Core_Html::gui_simple_select( $id, $values, $events, $selected );
		} else {
			return $values[ $selected ];
		}
	}
}

// Allow later users to set page name.
// For now just the default.

// Conflicts with 2.8.4.1
// 0-==-=--=-=-=-=-=-==-=-=\


//function gui_select_worker( $id = null, $selected = null, $args = null ) {
//	return Flavor_Org_Views::gui_select_worker( $id, $selected, $args );
//}

//function gui_select_project( $id, $value, $args ) {
//	return Focus_Views::gui_select_project( $id, $value, $args );
//}


/*
			case "show_new_team":
				$args                     = [];
				$args["next_page"]        = GetParam( "next_page", false, null );
				$args["post_file"]        = "/wp-content/plugins/focus/post.php";
				$args["selectors"]        = array( "manager" => "Flavor_Org_Views::gui_select_worker" );
				$args["mandatory_fields"] = array( "manager", "team_name" );

				return Core_Gem::GemAddRow( "working_teams", "Add a team", $args );

			case "show_new_task":
				$mission = GetParam( "mission", false, null );
				$new     = GetParam( "new", false );

				return self::show_new_task( $mission, $new ); // after the first task, the new tasks belongs to the new tasks' project will be displayed.
//		$args["selectors"]     = array(
//			"project_id"  => "Focus_Views::gui_select_project",
//			"owner"       => "Flavor_Org_Views::gui_select_worker",
//			"creator"     => "Flavor_Org_Views::gui_select_worker",
//			"repeat_freq" => "gui_select_repeat_time",
//			"team"        => "Focus_Views::gui_select_team"
//		);
//		$args["fields"]        = array(
//			"id",
//			"task_description",
//			"project_id",
//			"priority",
//			"team",
//			"repeat_freq",
//			"repeat_freq_numbers",
//			"timezone",
//			"working_hours",
//			"condition_query",
//			"task_url",
//			"template_last_task(id)"
//		);
//		$args["header_fields"] = array(
//			"task_description"    => "Task description",
//			"project_id"          => "Project",
//			"priority"            => "Priority",
//			"team"                => "Team",
//			"repeat_freq"         => "Repeat Frequency",
//			"repeat_freq_numbers" => "Repeat times",
//			"working_hours"       => "Working hours",
//			"Task site"
//		);

 */
