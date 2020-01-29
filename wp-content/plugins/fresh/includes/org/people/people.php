<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 21:15
 */
if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( FRESH_INCLUDES . '/org/gui.php' );

//
//

// require_once( TOOLS_DIR . "/account/account.php" );
// require_once( TOOLS_DIR . "/business/business.php" );
// require_once( TOOLS_DIR . "/account/gui.php" );

require_once( FRESH_INCLUDES . '/org/gui.php' );

/**
 * @param $id
 * @param $date
 * @param $start
 * @param $end
 * @param $project_id
 * @param $traveling
 * @param $expense_text
 * @param $expense
 *
 * @return int|string
 */

function people_add_activity_sick( $id, $date, $project_id)
{
	$sql = "INSERT INTO im_working_hours (user_id, date, project_id, start_time, end_time, comment) VALUES (" .
	       $id . ", \"" . $date . '", ' . $project_id . ", 0, 0, \"" . ImTranslate("Sick leave") . "\")";
	// print header_text();
	// print $sql;
	$export = sql_query( $sql );
	if ( ! $export ) {
		die ( 'Invalid query: ' . $sql . mysql_error() );
	}

	return 0; // Success

}

/**
 * @param $id
 * @param $date
 * @param $start
 * @param $end
 * @param $project_id
 * @param $traveling
 * @param $expense_text
 * @param $expense
 *
 * @return int|string
 */

/**
 * @param $id
 * @param $date
 * @param $quantity
 * @param $sender
 */
function driver_add_activity( $id, $date, $quantity, $sender ) {
	MyLog( "driver_add_activity", __FILE__ );
	$sql = "INSERT INTO im_driver_deliveries (user_id, date, quantity, sender) VALUES (" .
	       $id . ", \"" . $date . "\", " . $quantity . ", " . $sender . ")";

	MyLog( "quantity = " . $quantity );
	MyLog( "sender = " . $sender );
	MyLog( $sql );
	$export = mysql_query( $sql );
	if ( ! $export ) {
		MyLog( 'Invalid query: ' . $sql . mysql_error() );
	}
}

/**
 * @param $sender_id
 *
 * @return string
 */
function sender_name( $sender_id ) {
	$sender = "";
	switch ( $sender_id ) {
		case 1:
			$sender = "עם האדמה";
			break;
		case 2:
			$sender = "המכולת";
			break;
	}

	return $sender;
}

/**
 * @param $user_id
 *
 * @return string
 */
function worker_get_id($user_id)
{
	return sql_query_array_scalar("select id from im_working where user_id = " . $user_id);
}

/**
 * show_entry - displays salary entries submitted by workers. No salary information.
 * @param $user_id
 * @param $month
 * @param $year
 *
 * @return string|null
 * @throws Exception
 */
function show_entry($user_id, $month, $year)
{
	$args = [];
	$args["query"] = " user_id = $user_id and month(date)=" . QuoteText($month) . " and year(date)=" . QuoteText($year);
	$args["hide_cols"] = array("ID", "user_id");
	$args["edit"] = false;
	$args["selectors"] = ["project_id" => "gui_select_project"];
	return GemTable("im_working_hours", $args);
}

/**
 * show salary information.
 * @param int $user_id
 * @param null $month
 * @param null $year
 * @param null $args
 *
 * @return string
 * @throws Exception
 */

/**
 * @param $time
 *
 * @return string
 */

/**
 * @param $id
 *
 * @return string
 */
function project_name( $id ) {
	$sql = 'SELECT project_name FROM im_projects '
	       . ' WHERE id = ' . $id;

	return sql_query_single_scalar( $sql );
}

/**
 * @param $project_id
 * @param $user_id
 * @param bool $force
 *
 * @return bool|mysqli_result|null
 * @throws Exception
 */
function project_delete($project_id, $user_id, $force = false)
{
	if (! in_array(project_company($project_id), Org_Worker::GetCompanies($user_id)))
		die ("different company");

	$c = sql_query_single_scalar("select count(*) from im_tasklist where status < 2 and project_id = " . $project_id);
	if ($c and ! $force) {
		print get_project_name($project_id) . "(" . $project_id . ")" . ImTranslate("has") . " " . $c . ImTranslate("active tasks");
		return false;
	}
	// TODO: handle orphan tasks
	return sql_query("update im_projects set is_active=0 where id = " . $project_id);
}

/**
 * @param $user_id
 * @param $project_name
 * @param $company
 * @param string $project_contact
 * @param int $project_priority
 *
 * @return int|string
 */
function project_create($user_id, $project_name, $company, $project_contact = "set later", $project_priority = 5)
{
	sql_query( "insert into im_projects (project_name, project_contact, project_priority) values (" . QuoteText($project_name) .
	           "," . QuoteText($project_contact) . "," . $project_priority . ")");

	$project_id = sql_insert_id();
	
	sql_query ("insert into im_working (user_id, project_id, company_id, rate, report, volunteer, is_active) values " .
	" (" . $user_id . "," . $project_id . ", " . $company . ", 0, 1, 1, 1) ");

	return $project_id;
}

/**
 * @param $project_id
 *
 * @return bool
 */
function project_cancel($project_id)
{
	sql_query("update im_working set is_active = 0 where project_id = " . $project_id);
	return true;
}

/**
 * @param $user_id
 * @param $date
 * @param $start
 * @param $end
 * @param $project_id
 * @param bool $vol
 * @param int $traveling
 * @param string $extra_text
 * @param int $extra
 *
 * @return int|string
 */


// $selector_name( $key, $data, $args)

function handle_people_do($operation)
{
	$allowed_tables = array("im_working");
	switch ($operation)
	{
		case "save_new":
			$table_name = GetParam("table_name", true);
			if (! in_array($table_name, $allowed_tables))
				die ("invalid table operation");
			$result = data_save_new($table_name);
			if ($result > 0) print "done." . $result;
			break;
	}
	return "not handled";
}



/**
 * @param $operation
 *
 * @return bool|string
 * @throws Exception
 */
function handle_people_operation($operation)
{
	$worker = new Org_Worker(get_user_id());
	switch($operation) {
		case "cancel_im_working":
			$id = GetParam( "id", true );
			if ( project_cancel( $id ) ) {
				print "done";
			}
			return true;
	}

	print header_text(true, true, is_rtl(), array("people.js", "/core/gui/client_tools.js", "/core/data/data.js"));

	switch($operation) {
		case "show_edit_worker": // Get worker info by id or worker_project link.
			$row_id = GetParam("row_id", false);
			$worker_id = GetParam("worker_id", false);
			if (! $row_id and ! $worker_id) die ("supply row or worker_id");
			if ($row_id) $worker_id            = sql_query_single_scalar( "select user_id from im_working where id = $row_id" );
			$result               = Core_Html::gui_header( 1, "editing worker info" );
			$args                 = [];
			$args["edit"] = false;
			$args["selectors"]    = array( "project_id" => "gui_select_project" );
			$args["query"]        = "user_id=" . $worker_id . " and is_active = 1";
			$args["add_checkbox"] = true;
			$args["links"]        = array( "id" => AddToUrl( "operation", "show_edit_worker_project" ) );

			$result .= GemTable( "im_working", $args );
			return $result;
			break;
		case "show_edit_worker_project":
			$result = Core_Html::gui_header(1, "Project info for worker");
			$id = GetParam("row_id", true);
			$args = [];
			$result .= GemElement("im_working", $id, $args);
			return $result;
		case "salary_report":
			$edit = GetParam("edit", false, false);
			$args = array("add_checkbox" => true, "edit_lines" => $edit);
			show_all("2019-09", $args);
			break;

		case "edit_workers":
			$companies = $worker->GetCompanies();
			$result = "";
			if (! $companies) {
				return "no managed companies found";
			}
			if (count ($companies) != 1) $result .= "Manage more than one company. showing first one<br/>";

			$company = $companies[0];
			$result .= Core_Html::gui_header(1, "workers for company " . company_get_name($company));
			$args = [];
			$args["selectors"] = array("user_id" => "gui_select_worker", "project_id" => "gui_select_project");
			$args["query"] = "is_active = 1 and company_id = $company";
			$args["edit"] = false;
			$args["page"] = GetParam("page", false, 1);
			$args["links"] = array("id" => AddToUrl(array( "operation" => "show_edit_worker", "row_id" => "%s")),
				                   "project_id" => AddToUrl(array( "operation" => "show_project", "id" => "%s")));
			$result .= GemTable("im_working", $args);

			print $result;
			break;

		case "show_project":
			$project_id = GetParam("id", true);
			print Core_Html::gui_header(1, project_name($project_id));
			$args = [];
			$args["query"] = "project_id = $project_id";
			if (file_exists(FRESH_INCLUDES . '/focus/focus_class.php')){
				require_once( FRESH_INCLUDES . '/focus/focus_class.php' );
				$tasks = Focus_Views::active_tasks($args);
				if ($tasks) print Core_Html::gui_header(1, "Active tasks") . $tasks;
				else print Core_Html::gui_header(1, "No active tasks");
				print Core_Html::GuiButton( "btn_cancel", "cancel_entity('" . GetUrl(1) . "', 'im_working', " . $project_id . ')', "delete" );
			}
			break;
		default:
			$result = "";
			$args = [];
			$args["selectors"] = array("user_id" => "gui_select_user", "project_id" => "gui_select_project");
			if (function_exists($operation)) { $result .= $operation(); break; }
			if (substr($operation, 0, 4) == "show") {
				if (substr($operation, 5,3) == "add") {
					$table_name = substr($operation, 9);
					$result .= GemAddRow($table_name, "Add", $args);
					print $result;
					break;
				}
			}
			return;

	}
}

/**
 * @param $month
 * @param $args
 *
 * @return string
 * @throws Exception
 */

?>