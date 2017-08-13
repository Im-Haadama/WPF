<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 21:15
 */
require_once( "../account/account.php" );

function people_add_activity( $id, $date, $start, $end, $project_id, $traveling, $expense_text, $expense ) {
	if ( strlen( $traveling ) == 0 ) {
		$traveling = 0;
	}
	if ( strlen( $expense ) == 0 ) {
		$expense = 0;
	}
	my_log( "people_add_activity", __FILE__ );
	$sql = "INSERT INTO im_working_hours (user_id, date, start_time, end_time, project_id, traveling,
			expense_text, expense) VALUES (" .
	       $id . ", \"" . $date . "\", \"" . $start . "\", \"" . $end . "\", " . $project_id .
	       "," . $traveling . ", \"" . $expense_text . "\", " . $expense . ")";
	print header_text();
	// print $sql;
	$export = mysql_query( $sql );
	if ( ! $export ) {
		die ( 'Invalid query: ' . $sql . mysql_error() );
	}

}

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

function get_project_name( $project_id ) {
	global $conn;

	$sql = "SELECT project_name FROM im_projects WHERE id = " . $project_id;

	$result = mysqli_query( $conn, $sql );
	$row    = mysqli_fetch_assoc( $result );

	return $row["project_name"];
}

function is_volunteer( $uid ) {
	return sql_query_single_scalar( "SELECT volunteer FROM im_working WHERE worker_id = " . $uid );
}

// User id == 0: display all users.
function print_transactions( $user_id = 0, $month = null, $year = null, $week = null, $project = null, &$sum = null ) {
	$counters  = array();
	$volunteer = false;
	if ( $user_id > 0 and is_volunteer( $user_id ) ) {
		$volunteer = true;
	}
	$sql_month = null;
	if ( isset( $month ) and $month > 0 ) {
		print "מציג נתונים לחודש " . $month . " מזהה " . $user_id . "<br/>";
		$sql_month = " and month(date)=" . $month . " and year(date)=" . $year;
	}
	// $month_sum = array();

	if ( isset( $week ) ) {
		$sql_month = " and date >= '" . $week . "' and date < '" . date( 'y-m-d', strtotime( $week . "+1 week" ) ) . "'";
		// print $sql_month;
	}

	// var_dump($volunteer);
	// print $user_id;
	$data = "<table border='1'><tr>";
	$data .= gui_cell( "בחר" );
	$data .= "<td>תאריך</td><td>יום בשבוע</td><td>משעה</td><td>עד שעה</td><td>שעות</td><td>125%</td><td>150%</td>";
	$data .= "<td>פרויקט</td>";
	if ( $user_id == 0 ) {
		$data .= "<td>עובד</td>";
	}

	$data .= "<td>תעריף</td>";
	$data .= "<td>סהכ</td>";
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
	$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );
	$total_sal     = 0;
	$total_travel  = 0;
	$total_expense = 0;

	while ( $row = mysql_fetch_row( $export ) ) {

		// print "xxx" . $row[5] . $row[6]. $row[7] . $row[8]. "<br/>";
		$line = "<tr>";

		$line  .= gui_cell( gui_checkbox( "chk" . $row[8], "hours_checkbox" ) );
		$line  .= "<td>" . $row[0] . "</td>";
		$line  .= gui_cell( week_day( $row[9] ) );
		$start = new DateTime( $row[1] );
		$end   = new DateTime( $row[2] );

		if ( $end < $start ) {
			$end->add( DateInterval::createFromDateString( "1 day" ) );
		}
		$dur   = $end->diff( $start, true );
		$line  .= "<td>" . $start->format( "G:i" ) . "</td>";
		$line  .= "<td>" . $end->format( "G:i" ) . "</td>";

		$total_dur        = $dur->h + $dur->i / 60;
		$dur_base         = min( $total_dur, 8.5 );
		$dur_125          = min( 2, $total_dur - $dur_base );
		$dur_150          = $total_dur - $dur_base - $dur_125;
		$line             .= "<td>" . float_to_time( $dur_base ) . "</td>";
		$line             .= "<td>" . float_to_time( $dur_125 ) . "</td>";
		$line             .= "<td>" . float_to_time( $dur_150 ) . "</td>";
		$counters["base"] += $dur_base;
		$counters["125"]  += $dur_125;
		$counters["150"]  += $dur_150;

		$project_id = $row[3];
		$line       .= "<td>" . project_name( $project_id ) . "</td>";
		if ( $user_id == 0 ) {
			$line .= "<td>" . get_customer_name( $row[4] ) . "</td>";
		}
//		else{
		$rate = get_rate( $row[4], $project_id );

		$line .= gui_cell( $rate );
		// var_dump($dur);
		$sal = ( $dur_base + $dur_125 * 1.25 + $dur_150 * 1.5 ) * $rate;

		// print $sal . " " . $total_sal . "<br/>";
		$total_sal += $sal;
		// var_dump($total_sal);

		$line         .= gui_cell( round( $sal, 2 ) );
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
		round( $counters["base"], 2 ),
		round( $counters["125"], 2 ),
		round( $counters["150"], 2 )
	) );

	$data      .= "</table>";
	$total_sal = round( $total_sal, 1 );

	// print "total_sal " . $total_sal ;
	if ( $total_sal > 0 and ( $month or $week ) and ! $volunteer ) {
		$data      .= "חישוב שכר ראשוני" . "<br/>";
		$data      .= "שכר שעות " . $total_sal . "<br/>";
		$data      .= "סהכ נסיעה " . $total_travel . "<br/>";
		$data      .= "סהכ הוצאות " . $total_expense . "<br/>";
		$total_sal += $total_travel;
		$total_sal += $total_expense;
		$data      .= "סהכ " . $total_sal . "<br/>";

		$b = balance( date( 'Y-m-j', strtotime( "last day of " . $year . "-" . $month ) ), $user_id );
		//
		if ( customer_type( $user_id ) == 0 ) {
			$b = $b * 0.9;
		}

		if ( $b > 0 ) {
			$data .= " חיובי סלים " . round( $b, 2 );
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

	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );

	$row = mysql_fetch_row( $export );

//        print $rate . "<br/>";


	return $row[0];
}

function add_activity( $user_id, $date, $start, $end, $project_id, $vol = true, $traveling = 0, $extra_text = "", $extra = 0 ) {
	my_log( "add_activity", __FILE__ );
	people_add_activity( $user_id, $date, $start, $end, $project_id, $traveling, $extra_text, $extra );
	$fend   = strtotime( $end );
	$fstart = strtotime( $start );
	$amount = - get_rate( $user_id, $project_id ) * ( $fend - $fstart ) / 3600;
	my_log( "add_trans" . $amount, __FILE__ );
	if ( $vol ) {
		account_add_transaction( $user_id, $date, $amount, 1, get_project_name( $project_id ) );
	}
	business_add_transaction( $user_id, $date, $amount * 1.1, 0, 0, get_project_name( $project_id ) );
}

?>

