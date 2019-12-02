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
require_once( ROOT_DIR . '/org/gui.php' );

// error_reporting( E_ALL );
// ini_set( 'display_errors', 'on' );

// require_once( TOOLS_DIR . "/account/account.php" );
// require_once( TOOLS_DIR . "/business/business.php" );
// require_once( TOOLS_DIR . "/account/gui.php" );

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
	       $id . ", \"" . $date . '", ' . $project_id . ", 0, 0, \"" . im_translate("Sick leave") ."\")";
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
function people_add_activity( $id, $date, $start, $end, $project_id, $traveling, $expense_text, $expense ) {
	if ( strlen( $traveling ) == 0 ) {
		$traveling = 0;
	}
	if ( strlen( $expense ) == 0 ) {
		$expense = 0;
	}
	my_log( "people_add_activity", __FILE__ );
	if ( time() - strtotime( $date ) < 0 ) {
		return "לא ניתן להזין תאריכים עתידיים";
	}
	$sql = "INSERT INTO im_working_hours (user_id, date, start_time, end_time, project_id, traveling,
			expense_text, expense) VALUES (" .
	       $id . ", \"" . $date . "\", \"" . $start . "\", \"" . $end . "\", " . $project_id .
	       "," . $traveling . ", \"" . $expense_text . "\", " . $expense . ")";
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
 * @param $quantity
 * @param $sender
 */
function driver_add_activity( $id, $date, $quantity, $sender ) {
	my_log( "driver_add_activity", __FILE__ );
	$sql = "INSERT INTO im_driver_deliveries (user_id, date, quantity, sender) VALUES (" .
	       $id . ", \"" . $date . "\", " . $quantity . ", " . $sender . ")";

	my_log( "quantity = " . $quantity );
	my_log( "sender = " . $sender );
	my_log( $sql );
	$export = mysql_query( $sql );
	if ( ! $export ) {
		my_log( 'Invalid query: ' . $sql . mysql_error() );
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
	$args["query"] = " user_id = $user_id and month(date)=" . quote_text($month)  . " and year(date)=" . quote_text($year);
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
function print_transactions( $user_id = 0, $month = null, $year = null, &$args = null) // , $week = null, $project = null, &$sum = null, $show_salary = false , $edit = false) {
{
	$sql = "SELECT id, date, dayofweek(date) as weekday, start_time, end_time, project_id, working_rate(user_id, project_id) as rate, traveling, expense, expense_text, comment FROM im_working_hours WHERE 1 ";
	$edit = GetArg($args, "edit_lines", false);
	unset($args["hide_cols"]); // Remove from previous worker.

	$sql_month = null;
	if ( isset( $month ) and $month > 0 ) {
		if ( ! ( $year > 2016 ) ) {
			print " לא נבחרה שנה";

			return "אין מידע";
		}
		// print "מציג נתונים לחודש " . $month . " מזהה " . $user_id . "<br/>";
		$sql_month = " and month(date)=" . $month . " and year(date)=" . $year;
	}
	// $month_sum = array();

	if ( isset( $week ) ) $sql_month = " and date >= '" . $week . "' and date < '" . date( 'y-m-d', strtotime( $week . "+1 week" ) ) . "'";
	if ( $user_id > 0 ) $sql .= " and user_id = " . $user_id . " ";
	if ( isset( $sql_month ) ) $sql .= $sql_month;
	if ( isset( $project ) ) $sql .= " and project_id = " . $project;

	$sql .= " order by 2 ";
	if ( isset( $month ) ) $sql .= "asc";  else $sql .= "desc";
	$sql           .= " limit 100";

	$args["header_fields"] = array("date" => "Date", "weekday" => "Weekday", "start_time" => "Start time", "end_time" => "End time",
	                               "project_id" => "Project", "rate" => "Rate", "traveling" => "Traveling expense", "expense" => "Other expense", "expense_text" => "Expense details", "comment" => "Comment");
	$args["selectors"] = array("project_id" => "gui_select_project");
	$args["skip_id"] = true;
	if ($edit) $args["add_checkbox"] = true;
// 	 $args["hide_cols"] = array("expense" => 1, "expense_text" => 1, "125" => 1, "150" => 1);

	$data = TableData($sql, $args);
	// Add computed rows.
	$total_sal = 0;
	$total_travel = 0;
	$total_expense = 0;
	$counters = ["base"=>0, "125"=>0, "150"=>0];

	$show_125 = false;
	$show_150 = false;
	$show_expense = false;
	$show_comment = false;

	if (! $data) return im_translate( "No data") . gui_br();
	foreach  ($data as $key => &$row)
	{
		if ($key == "header") {
			$row["base"] = im_translate("base");
			$row["dur_125"] = "125%";
			$row["dur_150"] = "150%";
			$row["line_salary"] = im_translate("total");
			continue;
		}
		$row["weekday"] = day_name($row["weekday"] - 1);
		$start = new DateTime( $row["start_time"] );
		$end   = new DateTime( $row["end_time"] );

		if ( $end < $start ) $end->add( DateInterval::createFromDateString( "1 day" ) );
		$dur   = $end->diff( $start, true );

		$total_dur = ($dur->h + $dur->i / 60 );
		$dur_base  = min( $total_dur, 25 / 3 );
		$dur_125   = round(min( 2, $total_dur - $dur_base ), 2);
		$dur_150   = round($total_dur - $dur_base - $dur_125, 2);
		$rate = $row["rate"];

		if ($dur_125 > 0) {	$counters["125"]  += $dur_125; $show_125 = true; }
		if ($dur_150 > 0) { $counters["150"]  += $dur_150; $show_150 = true; }

		$row["base"] = float_to_time($dur_base);
		$row["dur_125"] = float_to_time($dur_125);
		$row["dur_150"] = float_to_time($dur_150);

		$counters["base"] += $dur_base;

		$sal  = round(( $dur_base + $dur_125 * 1.25 + $dur_150 * 1.5 ) * $rate, 2);
		$row["line_salary"] = $sal;

		$total_sal += $sal;

		$travel       = $row["traveling"];
		$total_travel += $travel;

		$expense       = $row["expense"];
		if ($expense > 0 or strlen($row["expense_text"])) $show_expense = true;
		if (strlen($row['comment'])) $show_comment = true;

	}
	if (! $show_150) {
		unset ($data["header"]["dur_150"]);
		$args["hide_cols"]["dur_150"] = 1;
	}
	if (! $show_125) {
		unset ($data["header"]["dur_125"]);
		$args["hide_cols"]["dur_125"] = 1;
	}
	if (! $show_comment){
		unset($data["header"]["comment"]);
		$args["hide_cols"]["comment"] = 1;
	}
	if (! $show_expense){
		unset($data['header']["expense"]);
		unset($data['header']["expense_text"]);
		$args["hide_cols"]["expense"] = 1;
		$args["hide_cols"]["expense_text"] = 1;
	}
	$args["checkbox_class"] = "working_days";
	$data = gui_table_args($data, "working_" . $user_id, $args);

	if ($edit)
		$data .= gui_button("btn_delete_from_report", "delete_lines()", "Delete");

	$data      .= gui_header( 2, "חישוב שכר מקורב" ) . "<br/>";
	$data      .= "שכר שעות " . $total_sal . "<br/>";
	$data      .= "סהכ נסיעה " . $total_travel . "<br/>";
	$data      .= "סהכ הוצאות " . $total_expense . "<br/>";
	$total_sal += $total_travel;
	$total_sal += $total_expense;
	$data      .= "סהכ " . $total_sal . "<br/>";
	if ( $user_id ) {
		$email = get_customer_email( $user_id );
		$r     = "people/people-post.php?operation=get_balance_email&date=" .
		         date( 'Y-m-j', strtotime( "last day of " . $year . "-" . $month ) ) . "&email=" . $email;
		// print $r;
		$b = strip_tags( ImMultiSite::sExecute( $r, 4 ) );
		//print "basket: " . $b . "<br/>";

		if ( $b > 0 ) {
			$data .= " חיובי סלים " . round( $b, 2 );
		}
	}

	return $data;
}

/**
 * @param $time
 *
 * @return string
 */
function float_to_time( $time ) {
	if ( $time > 0 ) {
		$time += 1/120; // 5:20 -> 5.33333333 -> 5:19.
		return sprintf( '%02d:%02d', (int) $time, fmod( $time, 1 ) * 60 );
	}

	return "";
}

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
	if (! in_array(project_company($project_id), worker_get_companies($user_id)))
		die ("different company");

	$c = sql_query_single_scalar("select count(*) from im_tasklist where status < 2 and project_id = " . $project_id);
	if ($c and ! $force) {
		print get_project_name($project_id) . "(" . $project_id . ")" . im_translate("has") . " " . $c . im_translate("active tasks");
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
	sql_query("insert into im_projects (project_name, project_contact, project_priority) values (" . quote_text($project_name) .
	"," . quote_text($project_contact) . "," . $project_priority . ")");

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
function add_activity( $user_id, $date, $start, $end, $project_id, $vol = true, $traveling = 0, $extra_text = "", $extra = 0 ) {
	my_log( "add_activity", __FILE__ );
	$result = people_add_activity( $user_id, $date, $start, $end, $project_id, $traveling, $extra_text, $extra );
	if ( $result ) {
		return $result;
	}
	$fend   = strtotime( $end );
	$fstart = strtotime( $start );
	$amount = - get_rate( $user_id, $project_id ) * ( $fend - $fstart ) / 3600;
	my_log( "add_trans" . $amount, __FILE__ );
	if ( $vol ) {
		account_add_transaction( $user_id, $date, $amount, 1, get_project_name( $project_id ) );
	}
	my_log( "before business" );
	business_add_transaction( $user_id, $date, $amount * 1.1, 0, 0, $project_id );
	my_log( "end add_activity" );
}


// $selector_name( $key, $data, $args)


/**
 * @param $operation
 *
 * @return bool|string
 * @throws Exception
 */
function handle_people_operation($operation)
{
	switch($operation) {
		case "cancel_im_working":
			$id = get_param( "id", true );
			if ( project_cancel( $id ) ) {
				print "done";
			}
			return true;
	}

	print header_text(true, true, is_rtl(), array("people.js", "/niver/gui/client_tools.js", "/niver/data/data.js"));

	switch($operation) {
		case "show_edit_worker": // Get worker info by id or worker_project link.
			$row_id = get_param("row_id", false);
			$worker_id = get_param("worker_id", false);
			if (! $row_id and ! $worker_id) die ("supply row or worker_id");
			if ($row_id) $worker_id            = sql_query_single_scalar( "select user_id from im_working where id = $row_id" );
			$result               = gui_header( 1, "editing worker info" );
			$args                 = [];
			$args["edit"] = false;
			$args["selectors"]    = array( "project_id" => "gui_select_project" );
			$args["query"]        = "user_id=" . $worker_id . " and is_active = 1";
			$args["add_checkbox"] = true;
			$args["links"]        = array( "id" => add_to_url( "operation", "show_edit_worker_project" ) );

			$result .= GemTable( "im_working", $args );
			return $result;
			break;
		case "show_edit_worker_project":
			$result = gui_header(1, "Project info for worker");
			$id = get_param("row_id", true);
			$args = [];
			$result .= GemElement("im_working", $id, $args);
			return $result;
		case "salary_report":
			$edit = get_param("edit", false, false);
			$args = array("add_checkbox" => true, "edit_lines" => $edit);
			show_all("2019-09", $args);
			break;

		case "edit_workers":
			$worker_id = get_user_id();
			$companies = worker_get_companies($worker_id);
			$result = "";
			if (! $companies) {
				return "no managed companies found";
			}
			if (count ($companies) != 1) $result .= "Manage more than one company. showing first one<br/>";

			$company = $companies[0];
			$result .= gui_header(1, "workers for company " . company_get_name($company));
			$args = [];
			$args["selectors"] = array("user_id" => "gui_select_worker", "project_id" => "gui_select_project");
			$args["query"] = "is_active = 1 and company_id = $company";
			$args["edit"] = false;
			$args["page"] = get_param("page", false, 1);
			$args["links"] = array("id" => add_to_url(array("operation" => "show_edit_worker", "row_id" => "%s")),
				                   "project_id" => add_to_url(array("operation" => "show_project", "id" => "%s")));
			$result .= GemTable("im_working", $args);

			print $result;
			break;

		case "show_project":
			$project_id = get_param("id", true);
			print gui_header(1, project_name($project_id));
			$args = [];
			$args["query"] = "project_id = $project_id";
			if (file_exists(ROOT_DIR . '/focus/focus_class.php')){
				require_once (ROOT_DIR . '/focus/focus_class.php');
				$tasks = active_tasks($args);
				if ($tasks) print gui_header(1, "Active tasks") . $tasks;
				else print gui_header(1, "No active tasks");
				print gui_button( "btn_cancel", "cancel_entity('" . get_url(1) . "', 'im_working', " . $project_id . ')', "delete" );
			}
			break;
	}
}

/**
 * @param $month
 * @param $args
 *
 * @return string
 * @throws Exception
 */
function show_all( $month, &$args)
{
	$edit_lines = GetArg($args, "edit_lines", false);

	$output = gui_header(1, im_translate("Salary data for month") . " " . $month);
	$a = explode( "-", $month );
	$y = $a[0];
	$m = $a[1];

	$sql = "select distinct h.user_id, report " .
	       " from im_working_hours h " .
	       " join im_working w " .
	       " where month(date)=" . $m .
	       " and year(date) = " . $y .
	       " and h.user_id = w.user_id ";
	// $output .= $sql;
	$result = sql_query( $sql);
	$has_data = false;

	while ( $row = mysqli_fetch_row( $result ) ) {
		$user_id = $row[0];
		$args["worker"] = $user_id;

		if ( $row[1] ) {
			$output .= gui_header( 1, get_user_name( $user_id ) . " (" . GuiHyperlink("$user_id", "/org/people/people-page.php?operation=show_edit_worker&" .
			"worker_id=" . $user_id) . ")" );

			$output .= "כתובת מייל של העובד/ת: " . get_customer_email( $user_id ) . "<br/>";

//			$output .= print_transactions( 0, $month, $year, null, null, $s, true  );

			$output .= print_transactions( $user_id, $m, $y, $args); // null, null, $s, true, $edit );
			if ($edit_lines){
				$output .= gui_button("btn_delete", "delete_line(" . $user_id . ")", "מחק");
			}
		}
		$has_data = true;
	}
	if (! $has_data) $output .= im_translate("No data entered") . gui_br();
	return $output;
}

?>