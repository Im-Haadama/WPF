<?php

//show_errors();
/**
 * Class Focus_Salary
 */
class Finance_Salary {
	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var string
	 */
	protected static $version = "1.0";

	/**
	 * Focus_Salary constructor.
	 * gets the post file.
	 */
	public function __construct( )
	{
	}

	/**
	 * enqueue_scripts - add scripts to be loaded.
	 */
	function enqueue_scripts() {
//		$file = plugin_dir_url( __FILE__ ) . 'org/people.js';
//		print "script $file";
//		wp_enqueue_script( 'people', $file, null, self::$version, false );
	}

	/**
	 * @return Finance_Salary|null
	 * single instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( Focus::getPost() );
		}

		return self::$_instance;
	}

	/**
	 * @param $module_operation
	 *
	 * @return bool
	 */
	static public function handle_operation( $operation ) {
		// Take the operation from module_operation.
		switch ( $operation ) {
			case "salary_delete":
			case "delete":

			case "show_add_working":
				$args = [];
				$args["selectors"] = array("user_id" => "gui_select_client", "project_id" => "Focus_Views::gui_select_project");
				$args["post_file"] = Finance::getPostFile();
				return Core_Gem::GemAddRow("working_rates", "Add", $args);
		}

		return false;
	}

	//* Shortcode handling
	//* 1) Main - list of employees

	/**
	 * @return string
	 * @throws Exception
	 */
	static function main_wrapper()
	{
		$me = self::instance();
		if ($operation = GetParam("operation", false))
			return self::handle_operation($operation);
		return $me->main();
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	function manage_workers() {
		$result = Core_Html::GuiHeader(1, "Manage workers");
		if ($operation = GetParam("operation", false))
		{
			$args = self::Args("working_rates");
			$result .= apply_filters($operation, '', '', $args);
		} else {
			if ( im_user_can( "show_salary" ) ) {
				if ( $id = GetParam( "user_id", false, null ) ) {
					print self::worker_info( $id );

					return;
				}

				$args["sql"]           = "select id, user_id, project_id from im_working_rates ";
				$args["id_field"]      = "id";
				$args["links"]         = array( "id" => self::get_link( "working_rates", "%s" ) );
				$args["selectors"]     = array(
					"project_id" => "Focus_Views::gui_select_project",
					"user_id"    => "Org_Worker::gui_select_user"
				);
				$args["edit"]          = false;
				$args["header_fields"] = array( "user_id" => "Worker", "project_id" => "Main project" );
			    $args["prepare_plug"] = array($this, "salary_info_row");

				$result .= Core_Gem::GemTable( "working_rates", $args );
			} else {
				$year_month = GetParam( "month", false, date( 'Y-m' ) );
				$y          = intval( strtok( $year_month, "-" ) );
				$m          = intval( strtok( "" ) );
				$result     .= self::hours_entry();
				$result     .= self::show_report_worker( get_user_id(), $y, $m );
			}
		}

		print $result;
	}

	function salary_info_row($row)
	{
		$id = $row["user_id"];
		$row["report"] = Core_Html::GuiHyperlink("Report", AddToUrl(array("operation"=>"show_entry", "user_id"=>$id)));
			// get_user_displayname($id);
		return $row;
	}

	// 2) Report - data to salary accountant
	/**
	 * @return string
	 * @throws Exception
	 *
	 */
	static function report_wrapper()
	{
		$year_month = GetParam( "month", false, date( 'Y-m', strtotime('-15 days') ) );

		print self::salary_report($year_month, $args, GetParam("user_id", false, 0));
	}

	/**
	 * @param $month
	 * @param $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function salary_report( $month, &$args, $user_id = 0 ) {
		$edit_lines = GetArg( $args, "edit_lines", false );

		$output = Core_Html::GuiHeader( 1, ETranslate( "Salary data for month" ) . " " . $month );
		$a      = explode( "-", $month );
		$y      = $a[0];
		$m      = $a[1];

		$sql = "select distinct h.user_id, report " .
		       " from im_working_hours h " .
		       " join im_working_rates w " .
		       " where month(date)=" . $m .
		       " and year(date) = " . $y .
		       " and h.is_active = 1 " .
		       " and h.user_id = w.user_id ";

		if ($user_id) $sql .= " and h.user_id = $user_id";

		$result   = SqlQuery( $sql );
		$has_data = false;

		while ( $row = mysqli_fetch_row( $result ) ) {
			$user_id        = $row[0];
			$user           = new Core_Users( $user_id );
			$args["worker"] = $user_id;

			if ( $row[1] ) {
				$output .= Core_Html::GuiHeader( 1, $user->getName() . " (" .
				                                     Core_Html::GuiHyperlink( "$user_id", self::get_link("worker_data", $user_id )) . ")" );
				$output .= "כתובת מייל של העובד/ת: " . $user->CustomerEmail() . "<br/>";

				$output .= self::MonthyWorkerReport( $user_id, $m, $y ); // null, null, $s, true, $edit );

				if ( $edit_lines ) {
					$output .= Core_Html::GuiButton( "btn_delete", "delete_line(" . $user_id . ")", "מחק" );
				}
			}
			$has_data = true;
		}
		if ( ! $has_data ) {
			$output .= ETranslate( "No data entered" ) . Core_Html::Br();
		}

		return $output;
	}

	// 3) Worker - data about the worker.
	/**
	 * @return string
	 * @throws Exception
	 */
	static function worker_wrapper() {
		$user_id = GetParam("user_id", true);
		if (! $user_id) return __FUNCTION__ . ": no worker id";
		$result  = "";
		$year_month = GetParam( "month", false, date( 'Y-m' ) );
		$y          = intval(strtok( $year_month, "-" ));
		$m          = intval(strtok( "" ));
		$me = self::instance();
		$result .= $me->show_report_worker( $user_id, $y, $m );
		$result .= $me->hours_entry($user_id);
		return $result;
	}

	// 4) Entry - what workers use for data entry.
	/**
	 * @return string
	 * @throws Exception
	 */
	static function entry_wrapper()
	{
		$result = "";
		$user_id = GetParam("user_id", false, 0);
		if (! $user_id)
			$user_id = get_user_id(true);
		$result .= self::hours_entry($user_id);
		$year_month = GetParam( "month", false, date( 'Y-m' ) );
		$y          = intval(strtok( $year_month, "-" ));
		$m          = intval(strtok( "" ));

		$result .= self::show_report_worker($user_id, $y, $m);

		print $result;
	}

	// Actions
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
	 * @return bool|string
	 */
	static function people_add_activity( $id, $date, $start, $end, $project_id, $traveling, $expense_text, $expense ) {
		if ( strlen( $traveling ) == 0 ) {
			$traveling = 0;
		}
		if ( strlen( $expense ) == 0 ) {
			$expense = 0;
		}
		MyLog( "people_add_activity", __FILE__ );
		if ( time() - strtotime( $date ) < 0 ) {
			return "לא ניתן להזין תאריכים עתידיים";
		}
		$sql = "INSERT INTO im_working_hours (user_id, date, start_time, end_time, project_id, traveling,
			expense_text, expense) VALUES (" .
		       $id . ", \"" . $date . "\", \"" . $start . "\", \"" . $end . "\", " . $project_id .
		       "," . $traveling . ", \"" . $expense_text . "\", " . $expense . ")";
		$export = SqlQuery( $sql );
		if ( ! $export ) {
			die ( 'Invalid query: ' . $sql . mysql_error() );
		}

		return true; // Success
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
	 * @return bool
	 */
	static function add_activity( $user_id, $date, $start, $end, $project_id, $vol = true, $traveling = 0, $extra_text = "", $extra = 0 ) {
		MyLog( "add_activity", __FILE__ );
		$result = self::people_add_activity( $user_id, $date, $start, $end, $project_id, $traveling, $extra_text, $extra );
		if ( $result !== true ) {
			print $result;

			return false;
		}
//		$fend   = strtotime( $end );
//		$fstart = strtotime( $start );
//		$amount = - self::get_rate( $user_id, $project_id ) * ( $fend - $fstart ) / 3600;
//		my_log( "add_trans" . $amount, __FILE__ );
//		if ( $vol ) {
//			account_add_transaction( $user_id, $date, $amount, 1, Org_Project::GetName( $project_id ) );
//		}
//		my_log( "before business" );
//		business_add_transaction( $user_id, $date, $amount * 1.1, 0, 0, $project_id );
//		my_log( "end add_activity" );
		return true;
	}

	// Get data
	/**
	 * @param $user_id
	 * @param $project_id
	 *
	 * @return false|float|int
	 */
	static function get_rate( $user_id, $project_id ) {
		// Check project specific rate
		$sql = 'select rate '
		       . ' from im_working_rates '
		       . ' where user_id = ' . $user_id
		       . ' and project_id = ' . $project_id;

		$rate = SqlQuerySingleScalar( $sql );

		if ( $rate ) {
			return round( $rate, 2 );
		}

		// Check global rate.
		$sql = 'select rate '
		       . ' from im_working_rates '
		       . ' where user_id = ' . $user_id
		       . ' and project_id = 0';

		$rate = SqlQuerySingleScalar( $sql );
		if ( $rate ) {
			return round( $rate, 2 );
		}

		print "no default rate for " . $user_id . " ";

		return 0;
	}

	/**
	 * @param $user_id
	 *
	 * @return string
	 * @throws Exception
	 */
	static private function hours_entry($user_id) {
		$result  = "";

		$table = array();

		array_push( $table, ( array( "תאריך", Core_Html::gui_input_date( "date", 'date', date( 'Y-m-d' ) ) ) ) );
		array_push( $table, ( array(
			"משעה",
			'<input id="start_h" type="time" value="09:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
		) ) );
		array_push( $table, ( array(
			"עד שעה",
			'<input id="end_h" type="time" value="13:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
		) ) );
		$args["worker_id"] = $user_id;
		array_push( $table, ( array( "פרויקט", Focus_Actions::gui_select_project( "project", null, $args ) ) ) );

		$result .= Core_Html::gui_table_args( $table );

		$result .= Core_Html::GuiHeader( 2, "הוצאות נסיעה" );
		$result .= Core_Html::GuiInput( "traveling", "" ) . "<br/>";
		$result .= Core_Html::GuiHeader( 2, "הוצאות נוספות/משלוחים" );
		$result .= "תיאור";
		$result .= Core_Html::GuiInput( "extra_text", "" ) . "<br/>";
		$result .= "סכום";
		$result .= Core_Html::GuiInput( "extra", "" ) . "<br/>";

		$result .= Core_Html::GuiButton( "btn_add_time", 'add item', array("action" => "salary_add_item('" . Finance::getPostFile() . "', $user_id)" ));

		return $result;
	}

	/**
	 * @param $user_id
	 * @param int $y
	 * @param int $m
	 *
	 * @return string
	 * @throws Exception
	 */
	static function show_report_worker( $user_id, $y = 0, $m = 0 ) {
		if ( ! $m ) {
			$m = date( 'm' );
		}
		if ( ! $y ) {
			$y = date( 'Y' );
		}

		$result             = Core_Html::GuiHeader( 1, __( "Salary info for worker" ) . " " . GetUserName( $user_id ) ) . __( "for month" ) . " " . $m . '/' . $y;
		$args               = array( "add_checkbox" => true, "checkbox_class" => "hours_checkbox" );
		$args["edit_lines"] = 1;
		$data               = self::MonthyWorkerReport( $user_id, $m, $y, $args );
		if ( ! $data ) {
			$result .= __( "No data for month" ) . " " . $m . '/' . $y . "<br/>";
		} else {
			$result .= $data;
//			$result .= Core_Html::GuiButton( "btn_delete",'מחק פעילות', array("action" => 'salary_del_items()') );
		}
		$result .= "<br/>" . Core_Html::GuiHyperlink( "Previous month",
				AddToUrl( "month", date( 'Y-m', strtotime( $y . '-' . $m . '-1 -1 month' ) ) ) );

		return $result;
	}

	/**
	 * @param $user_id
	 * @param int $y
	 * @param int $m
	 *
	 * @return string
	 * @throws Exception
	 */
	function worker_data($user_id, $y = 0, $m = 0)
	{
		$result  = Core_Html::GuiHeader( 1, Finance_Salary::worker_get_name( $user_id ) );

		$result .= self::show_report_worker( $user_id, $y, $m );
		$result .= self::hours_entry($user_id);

		return $result;
	}

	function show_working_row_wrap()
	{
		$row_id = GetParam("row_id", true);
		return self::show_working_row($row_id);
	}

	function show_working_row($row_id)
	{
		$user_id = SqlQuerySingleScalar("select user_id from im_working_rates where id = $row_id");
		$result = Core_Html::GuiHeader(1, get_user_displayname($user_id));
		$args              = [];
		$args["post_file"] = Finance::getPostFile();
		$args["selectors"] = array(
			"project_id" => "Focus_Views::gui_select_project",
			"company_id" => "Focus_Views::gui_select_company"
//		,		"post_file" => $this->post_file
		);
		$args["check_active"] = true;
		$args["hide_cols"] = array("volunteer", "company_id");
		$args["header_fields"] = array("user_id" => "User id", "project_id" => "Main project", "rate" => "Hour rate",
		                               "report" => "Include in report", "day_rate" => "Day rate", "is_active" => "Active?");

		$args["allow_delete"] = true;

//		$row_id = SqlQuerySingleScalar("select min(id) from im_working where user_id = $user_id");
		return $result . Core_Gem::GemElement( "working_rates", $row_id, $args );
	}

	/**
	 * @param $worker_id
	 *
	 * @return string
	 */
	static function worker_get_name( $worker_id ) {
		return SqlQuerySingleScalar( "select client_displayname(" . $worker_id . ")" );
	}

	// Worker monthly report
	/**
	 * @param int $user_id
	 * @param null $month
	 * @param null $year
	 * @param null $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function MonthyWorkerReport( $user_id = 0, $month = null, $year = null, &$args = null ) // , $week = null, $project = null, &$sum = null, $show_salary = false , $edit = false) {
	{
		$day_rate = (float) SqlQuerySingleScalar("select day_rate from im_working_rates where user_id = $user_id");

		$result = "";
		if ($day_rate) $result .= Core_Html::GuiHeader(2, "Daily worker");

		$rate_field = ($day_rate ? '' : ', working_rate(user_id, project_id) as rate');
		$sql  = "SELECT id, date, dayofweek(date) as weekday, start_time, end_time, project_id $rate_field, traveling, expense, expense_text, comment FROM im_working_hours WHERE 1 ";

		// print $sql ."<br/>";
		$edit = GetArg( $args, "edit_lines", false );
		unset( $args["hide_cols"] ); // Remove from previous worker.

		$sql_month = null;
		if ( isset( $month ) and $month > 0 ) {
			if ( ! ( $year > 2016 ) ) {
				return " לא נבחרה שנה $year<br/>" .
				       "אין מידע";
			}
			// print "מציג נתונים לחודש " . $month . " מזהה " . $user_id . "<br/>";
			$sql_month = " and month(date)=" . $month . " and year(date)=" . $year;
		}
		// $month_sum = array();

		if ( isset( $week ) ) $sql_month = " and date >= '" . $week . "' and date < '" . date( 'y-m-d', strtotime( $week . "+1 week" ) ) . "'";
		if ( $user_id > 0 ) 			$sql .= " and user_id = " . $user_id . " ";

		if ( isset( $sql_month ) )		$sql .= $sql_month;
			if ( isset( $project ) )		$sql .= " and project_id = " . $project;

		$sql .= " and is_active = 1 order by 2 " . ( isset($month) ? "asc" : "desc") ." limit 100";

		$args["header_fields"] = array(
			"date"         => "Date",
			"weekday"      => "Weekday",
			"start_time"   => "Start time",
			"end_time"     => "End time",
			"project_id"   => "Project",
			"rate"         => "Rate",
			"traveling"    => "Traveling expense",
			"expense"      => "Other expense",
			"expense_text" => "Expense details",
			"comment"      => "Comment",
			"line_salary" => "Total"
		);
		$args["selectors"]     = array( "project_id" => "Focus_Views::gui_select_project" );
		$args["skip_id"]       = true;
		if ( $edit ) {
			$args["add_checkbox"] = true;
		}

		$rows = Core_Data::TableData( $sql, $args );

		// Add computed rows.
		$total_sal     = 0;
		$total_travel  = 0;
		$total_expense = 0;
		$counters      = [ "base" => 0, "dur_125" => 0, "dur_150" => 0 ];

		$show_base = ! $day_rate;
		$show_125     = false;
		$show_150     = false;
		$show_expense = false;
		$show_comment = false;

		if ( ! $rows ) return $result . ETranslate( "No data" ) . Core_Html::Br();

		foreach ( $rows as $key => &$row ) {
			if ( $key == "header" ) {
				$row["base"]        = ETranslate( "base" );
				$row["dur_125"]     = "125%";
				$row["dur_150"]     = "150%";
				$row["line_salary"] = ETranslate( "total" );
				continue;
			}
			$row["weekday"] = DayName( $row["weekday"] - 1 );
			$start          = new DateTime( $row["start_time"] );
			$end            = new DateTime( $row["end_time"] );

			if ( $end < $start ) $end->add( DateInterval::createFromDateString( "1 day" ) );
			$dur = $end->diff( $start, true );

			if ($day_rate){
				$row["total"] = $day_rate;
				$total_sal += $day_rate;
			} else {
				$total_dur = ( $dur->h + $dur->i / 60 );
				$dur_base  = min( $total_dur, 25 / 3 );
				$dur_125   = min( 2, $total_dur - $dur_base );
				$dur_150   = $total_dur - $dur_base - $dur_125;
				$rate      = $row["rate"];

				if ( $dur_125 > 0 ) { $counters["dur_125"] += $dur_125; $show_125            = true; }
				if ( $dur_150 > 0 ) { $counters["dur_150"] += $dur_150; $show_150            = true; }

				$row["base"]    = FloatToTime( $dur_base );
				$row["dur_125"] = FloatToTime( $dur_125 );
				$row["dur_150"] = FloatToTime( $dur_150 );

				$counters["base"] += $dur_base;

				$sal                = round( ( $dur_base + $dur_125 * 1.25 + $dur_150 * 1.5 ) * $rate, 2 );
				$row["line_salary"] = $sal;

				$total_sal += $sal;
			}

			$travel       = $row["traveling"];
			$total_travel += $travel;

			$expense = $row["expense"];
			if ( $expense > 0 or strlen( $row["expense_text"] ) ) {
				$show_expense = true;
			}
			if ( strlen( $row['comment'] ) ) {
				$show_comment = true;
			}
		}
		if ( ! $show_150 ) {
			unset ( $rows["header"]["dur_150"] );
			$args["hide_cols"]["dur_150"] = 1;
		}
		if ( ! $show_125 ) {
			unset ( $rows["header"]["dur_125"] );
			$args["hide_cols"]["dur_125"] = 1;
		}
		if (! $show_base) {
			unset($rows["header"]["base"]);
			$args["hide_cols"]["base"] =1;
		}
		if ( ! $show_comment ) {
			unset( $rows["header"]["comment"] );
			$args["hide_cols"]["comment"] = 1;
		}

		if ( ! $show_expense ) {
			unset( $rows['header']["expense"] );
			unset( $rows['header']["expense_text"] );
			$args["hide_cols"]["expense"]      = 1;
			$args["hide_cols"]["expense_text"] = 1;
		}
		$args["checkbox_class"] = "working_days";

		//////////////////
		// Totals lines //
		//////////////////

		foreach ( $rows['header'] as $key => $not_used ) {
			$rows["sums"][ $key ] = ( isset( $counters[ $key ] ) ? FloatToTime( $counters[ $key ] ) : "" );
		}
		$rows["sums"]["line_salary"] = $total_sal;
		$rows["sums"]["traveling"] = $total_travel;

//		print "after: " . $data["totals"]["dur_125"] . "<br/>";
		$result .= Core_Html::gui_table_args( $rows, "working_" . $user_id, $args );

		if ( $edit ) {
			$result .= Core_Html::GuiButton( "btn_delete_from_report", "Delete" , array("action" => "salary_del_items('".Finance::getPostFile()."')") );
		}

		$result      .= Core_Html::GuiHeader( 2, "חישוב שכר מקורב" ) . "<br/>";
		$result      .= "שכר שעות " . $total_sal . "<br/>";
		$result      .= "סהכ נסיעה " . $total_travel . "<br/>";
		$result      .= "סהכ הוצאות " . $total_expense . "<br/>";
		$total_sal += $total_travel;
		$total_sal += $total_expense;
		$result      .= "סהכ " . $total_sal . "<br/>";
		if ( $user_id ) {
			$u     = new Core_Users( $user_id );
			$email = $u->CustomerEmail();
			$r     = "people/people-post.php?operation=get_balance_email&date=" .
			         date( 'Y-m-j', strtotime( "last day of " . $year . "-" . $month ) ) . "&email=" . $email;
			// print $r;
			$b = strip_tags( Core_Db_MultiSite::sExecute( $r, 4 ) );
			//print "basket: " . $b . "<br/>";

			if ( $b > 0 ) {
				$result .= " חיובי סלים " . round( $b, 2 );
			}
		}

		return $result;
	}

	//////////////////////////////////////////////////////////////
	// Wrappers: Get parameters and call functions for handling //
	//////////////////////////////////////////////////////////////
	/**
	 * @return string
	 * @throws Exception
	 */
	static public function worker_data_wrapper()
	{
		$user_id = GetParam("user_id", false);
		if (! $user_id) $user_id = get_user_id(true);
		if (! $user_id) return "Not connected";
		$instance = self::instance();
		$year_month = GetParam( "month", false, date( 'Y-m' ) );
		$y          = intval(strtok( $year_month, "-" ));
		$m          = intval(strtok( "" ));

		return $instance->worker_data($user_id, $y, $m);
	}

	/**
	 * @param $type
	 * @param $id
	 *
	 * @return string
	 */
	static public function get_link($type, $id)
	{
		switch ($type) {
			case "worker_monthly":
				return "/salary_worker?user_id=$id";
			case "working_rates":
				return AddToUrl(array("operation" =>"show_working_row", "row_id"=> $id));
		}
	}

	/**
	 * @return array
	 */
	function getShortcodes() {
//		print "get";
		//           code                   function                              capablity (not checked, for now).
//		return array( 'salary_main'        => array( 'Focus_Salary::main',        'show_salary' ), // Workers list for now. ניהול פרטי שכר
//		              'salary_report'      => array( 'Focus_Salary::report',      'show_salary' ), // Report - all workers דוח שכר
//					  'salary_worker_data' => array( 'Focus_Salary::worker_data', 'show_salary' ), // Personal worker card פרטי עובד
//		              'salary_entry'       => array("Focus_Salary::entry",        null));          // Salary data entry
	}

	static function Args($type)
	{
		$args = array("page" => GetParam("page", false, -1),
		             "post_file" => Finance::getPostFile());

		switch($type)
		{
			case "working_rates":
				$args["operation"] = GetParam("operation", false, true);
				$args["selectors"]     = array(
					"project_id" => "Focus_Views::gui_select_project",
					"user_id"    => "Org_Worker::gui_select_user"
				);

				break;
		}
		return $args;
	}

	function init_hooks()
	{
		add_action('admin_menu', array($this, 'admin_menu'));
		AddAction('salary_add_time', array($this, 'salary_add_time'));
		AddAction('salary_delete', array($this, 'salary_delete'));
		AddAction('show_working_row', array($this, 'show_working_row_wrap'));
		AddAction('show_report', array($this, 'report_wrapper'));
		if (im_user_can("show_salary"))
			AddAction('show_entry', array($this, 'entry_wrapper'));

		Core_Gem::AddTable("working_rates");
	}

	// Operations
	function salary_add_time()
	{
		$start     = GetParam( "start", true );
		$end       = GetParam( "end", true );
		$date      = GetParam( "date", true );
		$project   = GetParam( "project", true );
		$worker_id = GetParam( "user_id", true );
		// print "wid=" . $worker_id . "<br/>";
		$vol        = GetParam( "vol", true );
		$traveling  = GetParam( "traveling", true );
		$extra_text = GetParam( "extra_text", true );
		$extra      = GetParam( "extra", true );

		// if ($user_id = 1) $user_id = 238;
		return self::add_activity( $worker_id, $date, $start, $end, $project, $vol, $traveling, $extra_text, $extra );
	}

	function salary_delete() {
		$lines = GetParamArray( "params" );

		return ( Core_Data::Inactive( "working_hours", $lines ) );
	}

	function admin_menu()
	{
		$menu = new Core_Admin_Menu();

//		$menu->AddSubMenu('finance', 'working_hours_self',
//			array('page_title' => 'Hours',
//			      'menu_title' => 'Hours entry',
//			      'menu_slug' => 'salary_entry',
//			      'function' => array($this, 'entry_wrapper')));

		$menu->Add('finance', 'show_salary', 'manage_workers', array($this, 'manage_workers'));
		$menu->Add('finance', 'working_hours_self', 'salary_entry', array($this, 'entry_wrapper'));
		$menu->AddSubMenu('finance', 'working_hours_report',
			array('page_title' => 'Report',
			      'menu_title' => 'Salary report',
			      'menu_slug' => 'salary_report',
			      'function' => array($this, 'report_wrapper')));

	}
}
