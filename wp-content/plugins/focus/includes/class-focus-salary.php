<?php


class Focus_Salary {
	protected static $_instance = null;
	private $post_file;

	/**
	 * Focus_Salary constructor.
	 */
	public function __construct( $post_file ) {
		$this->post_file = $post_file;
		add_action( 'get_header', array( $this, 'create_nav' ) );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "/wp-content/plugins/focus/post.php" ); // Todo: fix this
		}

		return self::$_instance;
	}

	public function handle_operation($module_operation)
	{
		// Take the operation from module_operation.
		strtok($module_operation, "_");
		$operation = strtok(null);

		if (! $operation) die ("invalid activation");
		switch ($operation)
		{
			case "delete":
				$lines = get_param("params");
				if (data_delete("im_working_hours", $lines)) print "done";
				break;
		}
		return false;
	}

	public static function handle_salary_show($operation) {
		// $operation = get_param( "operation", false, "show_main" );
//		print "op=$operation";
		if ( get_user_id( true ) ) {
			switch($operation)
			{
				case "salary_main":
					print self::salary_main();
					break;

				case "show_worker":
					$worker_id = get_param("worker_id", true);
					self::get_month_year($y, $m);
					print self::show_worker($worker_id, $y, $m);
					break;

				case "show_salary":
					$month = get_param("month", false, date( 'Y-m', strtotime( 'last month' ) ));
					$edit = get_param("edit", false, false);

					print self::show_salary($month, $edit);
					break;
			}
		}
	}

	static function salary_main()
	{
		$result = gui_header(1, "Salary info");
		if (im_user_can("show_salary")){
			$args = [];
			$args["sql"] = "select distinct id, user_id, client_displayname(user_id) from im_working where is_active = 1";
			$args["id_field"] = "id";
			$args["links"] = array("id" => add_to_url(array("operation" => "show_worker", "worker_id" => "%s")));
			$result .= GemTable("im_working", $args);
		} else {
			self::get_month_year($y, $m);
			$result .= self::show_report_worker(get_user_id(), $y, $m);
		}

		return $result;
	}

	static private function get_month_year(&$y, &$m)
	{
		$year_month = get_param("month", false, date('Y-m'));
		$y = strtok($year_month, "-");
		$m = strtok("");
	}

	static function show_report_worker($user_id, $y = 0, $m = 0)
	{
		if (! $m) $m = date('m');
		if (! $y) $y = date('Y');
		$result = gui_header(1, __("Salary info for worker") . " " . get_user_name($user_id)) . __("for month") . " " . $m . '/' . $y;
		$data = self::print_transactions($user_id, $m, $y);
		if (! $data) $result .= __("No data for month") . " " . $m . '/' . $y . "<br/>";
		else $result .= $data;
		$result .= "<br/>" . GuiHyperlink("Previouse month",
			add_to_url("month", date('Y-m', strtotime($y . '-' . $m . '-1 -1 month'))));

		return $result;
	}

	static function show_worker($row_id, $y = 0, $m = 0)
	{
		$user_id = sql_query_single_scalar("select user_id from im_working where id = $row_id");
		$result = gui_header(1, Focus_Salary::worker_get_name($user_id));

		$args = [];
		$args["post_file"] = self::instance()->post_file;
		$result .= GemElement("im_working", $row_id, $args);

		$result .= self::show_report_worker($user_id, $y, $m);

		return $result;
	}

	static function worker_get_name($worker_id)
	{
		return sql_query_single_scalar("select client_displayname(" . $worker_id . ")");
	}

	static function show_salary($month, $edit = false)
	{
		$user_id = get_user_id(true);

		if (! user_can($user_id, 'working_hours_all')) {
			print im_translate("No permissions");
			return;
		}

		$result = "";

		$args["show_salary"] = true;
		$args["edit_lines"] = $edit;
		$result .= self::salary_report($month,$args);
		$result .= "<br/>";
		$result .= GuiHyperlink("Previous month", add_to_url("month", date('Y-m', strtotime($month . '-1 -1 month'))));
		if (strtotime($month . '-1') < strtotime('now')) $result .= " " . GuiHyperlink("Next month", add_to_url("month", date('Y-m', strtotime($month . '-1 +1 month'))));

		return $result;
	}

	static function salary_report( $month, &$args)
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
				$output .= "כתובת מייל של העובד/ת: " . Core_Users::CustomerEmail( $user_id ) . "<br/>";

				$output .= self::print_transactions( $user_id, $m, $y, $args); // null, null, $s, true, $edit );

				if ($edit_lines){
					$output .= gui_button("btn_delete", "delete_line(" . $user_id . ")", "מחק");
				}
			}
			$has_data = true;
		}
		if (! $has_data) $output .= im_translate("No data entered") . gui_br();
		return $output;
	}

	static function print_transactions( $user_id = 0, $month = null, $year = null, &$args = null) // , $week = null, $project = null, &$sum = null, $show_salary = false , $edit = false) {
	{
		$sql = "SELECT id, date, dayofweek(date) as weekday, start_time, end_time, project_id, working_rate(user_id, project_id) as rate, traveling, expense, expense_text, comment FROM im_working_hours WHERE 1 ";
		$edit = GetArg($args, "edit_lines", false);
		unset($args["hide_cols"]); // Remove from previous worker.

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
		$counters = ["base"=>0, "dur_125"=>0, "dur_150"=>0];

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
			$dur_125   = min( 2, $total_dur - $dur_base );
			$dur_150   = $total_dur - $dur_base - $dur_125;
			$rate = $row["rate"];

			if ($dur_125 > 0) {	$counters["dur_125"]  += $dur_125; $show_125 = true; }
			if ($dur_150 > 0) { $counters["dur_150"]  += $dur_150; $show_150 = true; }

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

		foreach ($data['header'] as $key => $not_used){
//			print "key: $key<br/>";
			$data["totals"][$key] = (isset($counters[$key]) ? float_to_time($counters[$key]) : "");
		}

//		print "after: " . $data["totals"]["dur_125"] . "<br/>";
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
			$email = Core_Users::CustomerEmail( $user_id );
			$r     = "people/people-post.php?operation=get_balance_email&date=" .
			         date( 'Y-m-j', strtotime( "last day of " . $year . "-" . $month ) ) . "&email=" . $email;
			// print $r;
			$b = strip_tags( Core_Db_MultiSite::sExecute( $r, 4 ) );
			//print "basket: " . $b . "<br/>";

			if ( $b > 0 ) {
				$data .= " חיובי סלים " . round( $b, 2 );
			}
		}

		return $data;
	}

	function get_nav_name() {
		return $this->nav_menu_name;
	}

	function create_nav() {
		$user_id = get_user_id();
		if (! $user_id) return;

		$this->nav_menu_name = "management." . $user_id;

		Focus_Nav::instance()->create_nav($this->nav_menu_name, $user_id);
	}

}

//function load_focus_salary() {
//	new Focus_Salary("/wp-content/plugins/focus/post.php");
//}

// load_focus_salary();
