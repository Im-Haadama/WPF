<?php


class Focus_Manager {
	protected static $_instance = null;
	protected static $logger = null;
	protected static $post_file;

	/**
	 * Focus_Manager constructor.
	 *
	 * @param $post_file
	 */
	public function __construct($post_file) {
		self::$post_file = $post_file;
		self::$logger = new Core_Logger(__CLASS__);
		self::$_instance  = $this;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new Focus_Manager();
		}
		return self::$_instance;
	}

	/**
	 * @return Core_Logger|null
	 */
	public static function getLogger(): ?Core_Logger {
		return self::$logger;
	}


	function init()
	{
		self::create_tasks();
	}

	function create_tasks( $freqs = null, $verbose = false, $default_owner = 1 )
	{
		$table_prefix = get_table_prefix();

		$last_run = get_wp_option("focus_create_tasks_last_run");
		$run_period = get_wp_option("focus_create_tasks_run_period", 5*60); // every 5 min
		if ($last_run and ((time() - $last_run) < $run_period)) return true;

		update_wp_option("Focus_create_tasks_last_run", time()); // Immediate update so won't be activated in parallel

		if ( ! table_exists( "${table_prefix}task_templates" ) ) {
			self::$logger->fatal("no table");
			return false;
		}
		$output = Core_Html::gui_header(1, "Creating tasks freqs");
		if ( ! $freqs ) $freqs = sql_query_array_scalar( "select DISTINCT repeat_freq from ${table_prefix}task_templates" );

		// TODO: create_tasks_per_mission();
		$verbose_table = array( array( "template_id", "freq", "query", "active", "result", "priority", "new task" ));
		foreach ( $freqs as $freq ) {
			$output .= "Handling " . Core_Html::GuiHyperlink($freq, AddToUrl(array( "operation" => "show_templates", "search" =>1, "repeat_freq" => $freq))) . Core_Html::Br();

			$sql = "SELECT id, task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority, creator, team " .
			       " FROM ${table_prefix}task_templates " .
			       " where repeat_freq = '" . $freq . "' and ((last_check is null) or (last_check < " . QuoteText(date('Y-m-j')) . ") or repeat_freq like 'c%')";

			$result = sql_query( $sql );

			$verbose_line = "";
			while ( $row = mysqli_fetch_assoc( $result ) ) {
				Focus_Tasklist::create_if_needed($row["id"], $row, $output, $default_owner, $verbose_line);
				array_push( $verbose_table, $verbose_line);
			}
		}
		if ( $verbose ) $output .= Core_Html::gui_table_args( $verbose_table);

		self::$logger->info($output);
		return true;
	}

	function run()
	{
//		self::create_tasks();
		self::run_robot();
	}

	function enqueue_scripts()
	{

	}

	function getShortcodes()
	{
		return array();
	}

	function run_robot()
	{
		// Default time zone for repeating tasks is the system timezone.
		// To override, user can set timezone in the im_task_templates. (not implemented completely).
		date_default_timezone_set( get_option('timezone_string'));

		$last_run = get_wp_option("focus_robot_last_run");
		$run_period = get_wp_option("focus_robot_run_period", 5*60); // every 5 min
		if ($last_run and ((time() - $last_run) < $run_period)) return;

		update_wp_option("focus_robot_last_run", time()); // Immediate update so won't be activated in parallel
		self::$logger->info("create tasks");

		self::$logger->trace("start robot");
		$team = Org_Team::getByName("robot");
		if (! $team) {
			self::$logger->fatal("robot team not exists");
			return;
		}
		// Just one every time
		$sql = "select id from im_tasklist " .
		       " where team = " . $team->getId() .
		       ' and status < 2 ';

		$debug_message = "";
		$result = sql_query($sql);
		while ($row = sql_fetch_assoc($result)) {
			$task_id = $row["id"];
			$debug_message .= "task $task_id ";
			$task    = new Focus_Tasklist( $task_id, self::$logger );
			if ( ! $task->working_time() ) {
				$debug_message .= " not working_time";
				continue;
			}

			// if (get_user_id() == 1) print $sql;

			self::$logger->trace( "running $task_id" );
			self::execute_task( $task_id );
			$debug_message .= " run. ";
			break;
		}
		self::$logger->trace($debug_message);
	}

	function execute_task($id)
	{
		$task = new Focus_Tasklist($id);
		self::$logger->trace("going to run $id");
		$rc = $task->run();
		self::$logger->info($rc);
		if (! ($rc === true))
			self::$logger->fatal("running task $id: $rc");
	}
}
