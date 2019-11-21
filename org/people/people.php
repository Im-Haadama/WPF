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




// User id == 0: display all users.
/**
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
	$show_salary = GetArg($args, "show_salary", false);
	$edit = GetArg($args, "edit", false);

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
	if ( isset( $month ) ) {
		$sql .= "asc";
	} else {
		$sql .= "desc";
	}
	// "  order by 1 desc";
	// print $sql;
	$sql           .= " limit 100";

	$args["header_fields"] = array("date" => "Date", "weekday" => "Weekday", "start_time" => "Start time", "end_time" => "End time", "rate" => "Rate",
	                               "project_id" => "Project", "traveling" => "Traveling expense", "expense" => "Other expense", "expense_text" => "Expense details", "Comment");
	$args["selectors"] = array("project_id" => "gui_select_project");
	$args["skip_id"] = true;
	$args["hide_cols"] = array("expense" => 1, "expense_text" => 1, "125" => 1, "150" => 1);
	if (! $show_salary) $args["hide_cols"]["rate"] = 1;
	// $args["acc"] = array("", "", )
	// $args["headers"] = array("Date", "Day of week", "Start time", "End time", "Rate", "Project", "Traveling", "Expense", "Expense details", "Comments");

	// $data .= GuiTableContent("im_working_hours", $sql, $args);
	// Add computed rows.
	$data = TableData($sql, $args);
	$total_sal = 0;
	$total_travel = 0;
	$total_expense = 0;
	$counters = ["base"=>0, "125"=>0, "150"=>0];

	foreach  ($data as $key => &$row)
	{
		if ($key == "header") {
			$row["base"] = im_translate("base");
			$row["125"] = "125%";
			$row["150"] = "150%";
			$row["line_salary"] = im_translate("total");
			continue;
		}
		$start = new DateTime( $row["start_time"] );
		$end   = new DateTime( $row["end_time"] );

		if ( $end < $start ) {
			$end->add( DateInterval::createFromDateString( "1 day" ) );
		}
		$dur   = $end->diff( $start, true );

		$total_dur = $dur->h + $dur->i / 60;
		$dur_base  = min( $total_dur, 25 / 3 );
		$dur_125   = min( 2, $total_dur - $dur_base );
		$dur_150   = $total_dur - $dur_base - $dur_125;
		$rate = $row["rate"];

		$row["base"] = float_to_time($dur_base);
		$row["dur_125"] = float_to_time($dur_125);
		$row["dur_150"] = float_to_time($dur_150);

		$counters["base"] += $dur_base;
		if ($dur_125 > 0) {	$counters["125"]  += $dur_125; $args["hide_cols"]["125"] = 0; }
		if ($dur_150 > 0) { $counters["150"]  += $dur_150; $args["hide_cols"]["125"] = 0; }

		$sal  = ( $dur_base + $dur_125 * 1.25 + $dur_150 * 1.5 ) * $rate;

		if ( $show_salary ) $row["line_salary"] = $sal;

		// print $sal . " " . $total_sal . "<br/>";
		$total_sal += $sal;
		// var_dump($total_sal);

		$travel       = $row["traveling"];
		$total_travel += $travel;

		$expense       = $row["expense"];
		if ($expense > 0) {
			$args["hide_cols"]["expense"] = 0;
			$args["hide_cols"]["expense_text"] = 0;
			$total_expense += $expense;
		}

		if (0) {
//		if ( isset( $sum ) and is_array( $sum ) ) {
//			$line_month = date( 'y-m', strtotime( $row[0] ) );
//			// print $row[0]. " " . $line_month . "<br/>";
//			if (! isset ($sum[$line_month])) $sum[$line_month] = 0;
//			$sum[ $line_month ] += ( $sal + $travel + $expense );
//		}
////		}
//
//		$line .= gui_cell($comment);
//
//		$line .= "</tr>";
//
//		$data .= $line;

//	$data .= gui_row( array(
//		"",
//		"סהכ",
//		"",
//		"",
//		"",
//		float_to_time( $counters["base"], 2 ),
//		float_to_time( $counters["125"], 2 ),
//		float_to_time( $counters["150"], 2 ),
//		"",
//		"",
//		"",
//		$show_salary ? $total_sal : "",
//		$show_salary ? $total_expense : "",
//		$show_salary ? $total_travel : ""
//	) );
//
//	$data      .= "</table>";
//
//
//	$total_sal = round( $total_sal, 1 );
//
//	// print "total_sal " . $total_sal ;
//	if ( $show_salary and $total_sal > 0 and $month ) {
//		$data      .= gui_header( 2, "חישוב שכר מקורב" ) . "<br/>";
//		$data      .= "שכר שעות " . $total_sal . "<br/>";
//		$data      .= "סהכ נסיעה " . $total_travel . "<br/>";
//		$data      .= "סהכ הוצאות " . $total_expense . "<br/>";
//		$total_sal += $total_travel;
//		$total_sal += $total_expense;
//		$data      .= "סהכ " . $total_sal . "<br/>";
//		if ( $user_id ) {
//			$email = get_customer_email( $user_id );
//			$r     = "people/people-post.php?operation=get_balance_email&date=" .
//			         date( 'Y-m-j', strtotime( "last day of " . $year . "-" . $month ) ) . "&email=" . $email;
//			// print $r;
//			$b = strip_tags( ImMultiSite::sExecute( $r, 4 ) );
//			//print "basket: " . $b . "<br/>";
//
//			if ( $b > 0 ) {
//				$data .= " חיובי סלים " . round( $b, 2 );
//			}
//		}
//	}
//
//	if ( ! is_null( $sum ) and ( ! is_array( $sum ) ) ) {
//		$sum = $total_sal;
//	}
//	}
		}
	}
	$args["checkbox_class"] = "working_days";
	$data = gui_table_args($data, "working_" . $user_id, $args);

	if ($edit)
		$data .= gui_button("btn_delete_from_report", "delete_lines()", "Delete");

	if ( $show_salary and $total_sal > 0 and $month ) {
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
	}

	return $data;

	if (0) {
		//	print "ss=" . $show_salary . "<br/>";
		// print "uid=" . $user_id . "<br/>";

		$counters  = array();
		$volunteer = false;
		if ( $user_id > 0 and is_volunteer( $user_id ) ) {
			$volunteer = true;
		}
//	if ( ! $user_id  ) {
//		$user_id = get_user_id();
//	}

		// var_dump($volunteer);
		// print $user_id;
		$data = "<table id=\"report_" . $user_id . "\" border='1'><tr>";
		$data .= gui_cell( "בחר" );
		$data .= "<td>תאריך</td><td>יום בשבוע</td><td>משעה</td><td>עד שעה</td><td>שעות</td><td>125%</td><td>150%</td>";
		$data .= "<td>פרויקט</td>";
		if ( $user_id == 0 ) {
			$data .= "<td>עובד</td>";
		}

		if ( $show_salary ) {
			$data .= "<td>תעריף</td>";
			$data .= "<td>סהכ</td>";
		}
		$data .= "<td>הוצאות</td>";

		if ( ! $volunteer ) {
			$data .= "<td>נסיעה</td>";
			$data .= gui_cell( "Comments" );
		}

		$data .= "</tr>";

		$sql = "SELECT date, start_time, end_time, project_id, user_id, traveling, expense, expense_text, id, dayofweek(date), comment FROM im_working_hours WHERE 1 ";

		$result        = sql_query( $sql );
		$total_sal     = 0;
		$total_travel  = 0;
		$total_expense = 0;

		if ( ! $result ) {
			return "אין מידע";
		}
		$counters["base"] = $counters["125"] = $counters["150"] = 0;
		while ( $row = mysqli_fetch_row( $result ) ) {
			$user_id = $row[4];
			//print $row[0];
			// print "xxx" . $row[5] . $row[6]. $row[7] . $row[8]. "<br/>";
			$line = "<tr>";

			$line .= gui_cell( gui_checkbox( "chk" . $row[8], "hours_checkbox" ) );
			$line .= "<td>" . $row[0] . "</td>";
			$line .= gui_cell( week_day( $row[9] ) );

			$daily = sql_query_single_scalar( "select day_rate from im_working where user_id = " . $user_id ) > 0;
			$start = new DateTime( $row[1] );
			$end   = new DateTime( $row[2] );

			if ( $end < $start ) {
				$end->add( DateInterval::createFromDateString( "1 day" ) );
			}
			$dur  = $end->diff( $start, true );
			$line .= "<td>" . $start->format( "G:i" ) . "</td>";
			$line .= "<td>" . $end->format( "G:i" ) . "</td>";

			$total_dur = $dur->h + $dur->i / 60;
			$dur_base  = min( $total_dur, 25 / 3 );
			$dur_125   = min( 2, $total_dur - $dur_base );
			$dur_150   = $total_dur - $dur_base - $dur_125;
			$comment   = $row[10];

			$line             .= "<td>" . float_to_time( $dur_base ) . "</td>";
			$line             .= "<td>" . float_to_time( $dur_125 ) . "</td>";
			$line             .= "<td>" . float_to_time( $dur_150 ) . "</td>";
			$counters["base"] += $dur_base;
			$counters["125"]  += $dur_125;
			$counters["150"]  += $dur_150;

			$project_id = $row[3];
			$line       .= "<td>" . project_name( $project_id ) . "</td>";
			if ( $user_id == 0 ) {
				$line .= "<td>" . get_customer_name( $user_id ) . "</td>";
			}
//		else{
			if ( $daily ) {
				$rate = get_daily_rate( $user_id );
				$sal  = $rate;
			} else {
				$rate = get_rate( $user_id, $project_id );
				$sal  = ( $dur_base + $dur_125 * 1.25 + $dur_150 * 1.5 ) * $rate;
			}

			if ( $show_salary ) {
				$line .= gui_cell( $rate );
			}
			// var_dump($dur);

			// print $sal . " " . $total_sal . "<br/>";
			$total_sal += $sal;
			// var_dump($total_sal);

			if ( $show_salary ) {
				$line .= gui_cell( round( $sal, 2 ) );
			}
			$travel       = $row[5];
			$total_travel += $travel;

			$expense       = $row[6];
			$total_expense += $expense;

			$line .= gui_cell( $expense );
			$line .= gui_cell( $travel );
			$line .= gui_cell( $row[7] );

			if ( isset( $sum ) and is_array( $sum ) ) {
				$line_month = date( 'y-m', strtotime( $row[0] ) );
				// print $row[0]. " " . $line_month . "<br/>";
				if ( ! isset ( $sum[ $line_month ] ) ) {
					$sum[ $line_month ] = 0;
				}
				$sum[ $line_month ] += ( $sal + $travel + $expense );
			}
//		}

			$line .= gui_cell( $comment );

			$line .= "</tr>";

			$data .= $line;
		}
		$data .= gui_row( array(
			"",
			"סהכ",
			"",
			"",
			"",
			float_to_time( $counters["base"], 2 ),
			float_to_time( $counters["125"], 2 ),
			float_to_time( $counters["150"], 2 ),
			"",
			"",
			"",
			$show_salary ? $total_sal : "",
			$show_salary ? $total_expense : "",
			$show_salary ? $total_travel : ""
		) );

		$data      .= "</table>";
		$total_sal = round( $total_sal, 1 );

		// print "total_sal " . $total_sal ;
		if ( $show_salary and $total_sal > 0 and $month ) {
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
		}

		if ( ! is_null( $sum ) and ( ! is_array( $sum ) ) ) {
			$sum = $total_sal;
		}

		return $data;
	}
}

function float_to_time( $time ) {
	if ( $time > 0 ) {
		return sprintf( '%02d:%02d', (int) $time, fmod( $time, 1 ) * 60 );
	}

	return "";
}

function project_name( $id ) {
	$sql = 'SELECT project_name FROM im_projects '
	       . ' WHERE id = ' . $id;

	return sql_query_single_scalar( $sql );
}

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
function project_create($user_id, $project_name, $company, $project_contact = "set later", $project_priority = 5)
{
	sql_query("insert into im_projects (project_name, project_contact, project_priority) values (" . quote_text($project_name) .
	"," . quote_text($project_contact) . "," . $project_priority . ")");

	$project_id = sql_insert_id();
	
	sql_query ("insert into im_working (user_id, project_id, company_id, rate, report, volunteer, is_active) values " .
	" (" . $user_id . "," . $project_id . ", " . $company . ", 0, 1, 1, 1) ");

	return $project_id;
}

function project_cancel($project_id)
{
	sql_query("update im_working set is_active = 0 where project_id = " . $project_id);
	return true;
}

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


function gui_select_template($id, $value, $args)
{
	// TODO: implement.
	$edit = GetArg($args, "edit", false);

	if (! $edit) return sql_query_single_scalar("select task");

	$length = GetArg($args, "length", 30);

	print "value = $value<br/>";

//	if ( $worker ) {
//		// print "w=" . $worker;
//		// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where worker_id = " . $worker . ")";
//	}

//	return gui_select_table( $id, "im_tasklist", $value, $events, "", "task_description",
//		"where " . $query, true, false );

	$args["value"] = $value;
	$args["name"] ="substr(task_description, 1," . $length . ")";
	$args["include_id"] = 1;
	$args["datalist"] = 1;
	return GUiSelectTable($id, "im_task_templates", $args);

}

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
		case "show_edit_worker":
			$id = get_param("id", true);
			print gui_header(1, "editing worker info");
			$args = [];

			print GemElement("im_working", $id, $args);
			break;
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
			$args["links"] = array("id" => add_to_url(array("operation" => "show_edit_worker", "id" => "%s")),
				                   "project_id" => add_to_url(array("operation" => "show_project", "id" => "%s")));
			$result .= GemTable("im_working", $args);

			print $result;
			break;

		case "show_project":
			$project_id = get_param("id", true);
			print gui_header(1, project_name($project_id));
			$args = [];
			$args["query"] = "project_id = $project_id";
			if (file_exists(ROOT_DIR . '/focus/focus.php')){
				require_once (ROOT_DIR . '/focus/focus.php');
				$tasks = active_tasks($args);
				if ($tasks) print gui_header(1, "Active tasks") . $tasks;
				else print gui_header(1, "No active tasks");
				print gui_button( "btn_cancel", "cancel_entity('" . get_url(1) . "', 'im_working', " . $project_id . ')', "delete" );
			}
			break;


	}
}

function show_all( $month, &$args) {
	$edit_lines = GetArg($args, "edit_lines", false);

	$a = explode( "-", $month );
	$y = $a[0];
	$m = $a[1];

	$sql = "select distinct h.user_id, report " .
	       " from im_working_hours h " .
	       " join im_working w " .
	       " where month(date)=" . $m .
	       " and year(date) = " . $y .
	       " and h.user_id = w.user_id ";
	// print $sql;
	$result = sql_query( $sql);

	while ( $row = mysqli_fetch_row( $result ) ) {
		$user_id = $row[0];
		$args["worker"] = $user_id;

		if ( $row[1] ) {
			print gui_header( 1, get_user_name( $user_id ) . "(" . gui_hyperlink($user_id, add_to_url(array("operation"=>"edit_worker", "id" => $user_id))) . ")");
			print "כתובת מייל של העובד/ת: " . get_customer_email( $user_id ) . "<br/>";

//			print print_transactions( 0, $month, $year, null, null, $s, true  );

			print print_transactions( $user_id, $m, $y, $args); // null, null, $s, true, $edit );
			if ($edit_lines){
				print gui_button("btn_delete", "delete_line(" . $user_id . ")", "מחק");
			}
		}
	}
}

?>