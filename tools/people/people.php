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
 * @param $user_id
 *
 * @return array|string
 */
function worker_get_companies($user_id)
{
	return sql_query_array_scalar("select company_id from im_working where user_id = " . $user_id);
}

/**
 * @param $user_id
 *
 * @return string
 */
function worker_get_projects($user_id)
{
	return sql_query_array_scalar("select project_id from im_working where user_id = " . $user_id);
}

/**
 * @param $uid
 *
 * @return string
 */
function is_volunteer( $uid ) {
	return sql_query_single_scalar( "SELECT volunteer FROM im_working WHERE user_id = " . $uid );
}


/** if the worker is global company worker, return array of companies
 * @param $user_id
 *
 * @return string
 */
function worker_is_global_company($user_id)
{
	return sql_query_single_scalar("select company_id from im_working where user_id = " . $user_id . " and project_id = 0");
}


// User id == 0: display all users.
/**
 * @param int $user_id
 * @param null $month
 * @param null $year
 * @param null $week
 * @param null $project
 * @param null $sum
 * @param bool $show_salary
 * @param bool $edit
 *
 * @return string
 * @throws Exception
 */
function print_transactions( $user_id = 0, $month = null, $year = null, $week = null, $project = null, &$sum = null, $show_salary = false , $edit = false) {
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

	if ( isset( $week ) ) {
		$sql_month = " and date >= '" . $week . "' and date < '" . date( 'y-m-d', strtotime( $week . "+1 week" ) ) . "'";
		// print $sql_month;
	}

	// var_dump($volunteer);
	// print $user_id;
	$data = "<table id=\"report_" . $user_id ."\" border='1'><tr>";
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
		$data .= "<td>פירוט הוצאות</td>";
	}

	$data .= "</tr>";

	$sql = "SELECT date, start_time, end_time, project_id, user_id, traveling, expense, expense_text, id, dayofweek(date) FROM im_working_hours WHERE 1 ";

	if ( $user_id > 0 ) {
		$sql .= " and user_id = " . $user_id . " ";
	}

	if ( isset( $sql_month ) ) {
		$sql .= $sql_month;
	}

	if ( isset( $project ) ) {
		$sql .= " and project_id = " . $project;
	}

	$sql .= " order by 1 ";
	// print $sql;
	if ( isset( $month ) ) {
		$sql .= "asc";
	} else {
		$sql .= "desc";
	}
	// "  order by 1 desc";
	// print $sql;
	$sql           .= " limit 100";
	$result        = sql_query( $sql );
	$total_sal     = 0;
	$total_travel  = 0;
	$total_expense = 0;

	if ( ! $result )
		return "אין מידע";
	$counters["base"] = $counters["125"] = $counters["150"] = 0;
	while ( $row = mysqli_fetch_row( $result ) ) {
		$user_id = $row[4];
		//print $row[0];
		// print "xxx" . $row[5] . $row[6]. $row[7] . $row[8]. "<br/>";
		$line = "<tr>";

		$line  .= gui_cell( gui_checkbox( "chk" . $row[8], "hours_checkbox" ) );
		$line  .= "<td>" . $row[0] . "</td>";
		$line  .= gui_cell( week_day( $row[9] ) );

		$daily = sql_query_single_scalar( "select day_rate from im_working where user_id = " . $user_id ) > 0;
		$start = new DateTime( $row[1] );
		$end   = new DateTime( $row[2] );

		if ( $end < $start ) {
			$end->add( DateInterval::createFromDateString( "1 day" ) );
		}
		$dur   = $end->diff( $start, true );
		$line  .= "<td>" . $start->format( "G:i" ) . "</td>";
		$line  .= "<td>" . $end->format( "G:i" ) . "</td>";

		$total_dur = $dur->h + $dur->i / 60;
		$dur_base  = min( $total_dur, 25 / 3 );
		$dur_125   = min( 2, $total_dur - $dur_base );
		$dur_150   = $total_dur - $dur_base - $dur_125;

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

		if ( $show_salary )
			$line .= gui_cell( round( $sal, 2 ) );
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
			$sum[ $line_month ] += ( $sal + $travel + $expense );
		}
//		}

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

	if ($edit)
		$data .= gui_button("btn_delete", "delete_line(" . $user_id . ")", "מחק");

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
function gui_select_project( $id, $value, $args)
{
	$edit = GetArg($args, "edit", false);
	if (! $edit)
	{
//		print "v= " . $value . "<br/>";
		return get_project_name($value);
	}
	// Filter by worker if supplied.
	$user_id = GetArg($args, "worker", null);
	$query = null;
	if ( !$user_id ) {
		throw new Exception( __FUNCTION__ .": No worker given" );
	}

	// Check if this user is global company user.
	if ($companies = worker_get_companies($user_id)){
		$query = " where id in (select project_id from im_working where company_id in (" . comma_implode($companies) . "))";
	} else {
		$query = " where id in (" . comma_implode(worker_get_projects($user_id) . ")");
	}

		// print "w=" . $worker;
		// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where user_id = " . $user_id . ")";

	$args["where"] = $query;
	$args["name"] = "project_name";
	$args["selected"] = $value;
	return GuiSelectTable($id, "im_projects", $args);

}

function gui_select_task( $id, $value, $args ) {
	$events = GetArg($args, "events", null);
	$query = GetArg($args, "where", " where status = 0 ");
	$length = GetArg($args, "length", 30);

//	if ( $worker ) {
//		// print "w=" . $worker;
//		// $user_id = sql_query("select user_id from im_working where id = " . $worker);
//		$query = " where id in (select project_id from im_working where worker_id = " . $worker . ")";
//	}

//	return gui_select_table( $id, "im_tasklist", $value, $events, "", "task_description",
//		"where " . $query, true, false );

	$args = array("value" => $value,
	              "events"=>$events,
	              "name"=>"substr(task_description, 1," . $length . ")",
	              "where"=> $query,
	              "include_id" => 1,
	              "datalist" =>1 );
	return GUiSelectTable($id, "im_tasklist", $args);
}

function gui_select_template($id, $value, $args)
{
	// TODO: implement.
	$edit = GetArg($args, "edit", false);

	if (! $edit) return sql_query_single_scalar("select task");

	$query = GetArg($args, "where", " where 1 ");
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

function team_members($team_id)
{
	$sql = "select user_id from im_working where worker_teams(user_id) like '%:" . $team_id . ":%'";
	return sql_query_array_scalar($sql);
}

function team_all_members($user_id)
{
	$teams = sql_query_array_scalar("select id from im_working_teams where manager = " . $user_id);
	if (! $teams) return null;
	$temp_result = array();
	// Change to associative to have each member just once.
	foreach ($teams as $team) {
		$members = team_members($team);
		foreach ($members as $member)
			$temp_result[$member] = 1;
	}
	// Switch to simple array
	$result = array();
	foreach ($temp_result as $member => $x)
		array_push($result, $member);
	return $result;
}

?>