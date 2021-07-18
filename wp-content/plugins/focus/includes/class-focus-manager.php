<?php

include_once FLAVOR_INCLUDES_ABSPATH . 'core/core-functions.php';

class Focus_Manager {
	protected static $_instance = null;
	protected $logger = null;
	protected $post_file;

	/**
	 * Focus_Manager constructor.
	 *
	 * @param $post_file
	 */
	private function __construct($post_file) {
		$this->$post_file = $post_file;
		$this->logger = new Core_Logger(__CLASS__, "file", "focus.log");
		self::$_instance  = $this;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new Focus_Manager(WPF_Flavor::getPost());
		}
		return self::$_instance;
	}

	/**
	 * @return Core_Logger|null
	 */
	public static function getLogger(): ?Core_Logger {
		return self::instance()->logger;
	}

	function init()
	{

		self::create_tasks();
	}

	function create_tasks( $freqs = null, $default_owner = 1 )
	{
		// For Debug:
//		 SqlQuery("update im_task_templates set last_check = null where id = 6");
		$table_prefix = GetTablePrefix();

		$last_run = get_wp_option("focus_create_tasks_last_run");
		$run_period = get_wp_option("focus_create_tasks_run_period", 5*60); // every 5 min
		if ($last_run and ((time() - $last_run) < $run_period)) {
			return true;
		}

		update_wp_option("focus_create_tasks_last_run", time()); // Immediate update so won't be activated in parallel

		if ( ! TableExists( "task_templates" ) ) {
			$this->logger->fatal("no table");
			return false;
		}

		if ( ! $freqs ) $freqs = SqlQueryArrayScalar( "select DISTINCT repeat_freq from ${table_prefix}task_templates" );
//		$this->logger->info(StringVar($freqs));

		// TODO: create_tasks_per_mission();
		$verbose_table = array( array( "template_id", "freq", "query", "active", "result", "priority", "new task" ));

		foreach ( $freqs as $freq ) {
			$this->logger->info("handling freq $freq");
			$sql = "SELECT id" .
			       " FROM ${table_prefix}task_templates " .
			       " where is_active=1 and repeat_freq = '" . $freq . "' and ((last_check is null) or (last_check < " . QuoteText(date('Y-m-j')) . ") or repeat_freq like 'c%')";

			$result = SqlQuery( $sql );

			$verbose_line = "";

			while ( $row = mysqli_fetch_assoc( $result ) ) {
				$this->logger->info("handling " . $row["id"]);
				$T = new Focus_Task_Template($row["id"]);
				$T->create_if_needed($verbose_line);
				array_push( $verbose_table, $verbose_line);
			}
		}
		return true;
	}

	function run()
	{
//		self::create_tasks();
		self::run_robot();
	}

	function run_robot()
	{
		// Default time zone for repeating tasks is the system timezone.
		$last_run = get_wp_option("focus_robot_last_run");
		$run_period = get_wp_option("focus_robot_run_period", 5*60); // every 5 min
		if ($last_run and ((time() - $last_run) < $run_period)) return;

		update_wp_option("focus_robot_last_run", time()); // Immediate update so won't be activated in parallel
		$this->logger->info("create tasks");

		$this->logger->trace("start robot");
		$team = Org_Team::getByName("robot");
		if (! $team) {
			$this->logger->fatal("robot team not exists");
			return;
		}
		// Just one every time
		$sql = "select id from im_tasklist " .
		       " where team = " . $team->getId() .
		       ' and status < 2 ';

		$this->logger->trace($sql);
		$debug_message = "";
		$result = SqlQuery($sql);
		while ($row = SqlFetchAssoc($result)) {
			$task_id = $row["id"];
			$debug_message .= "task $task_id ";
			$task    = new Focus_Tasklist( $task_id, $this->logger );
			if ( ! $task->working_time() ) {
				$debug_message .= " not working_time";
				$this->logger->trace($debug_message);
				continue;
			}

			// if (get_user_id() == 1) print $sql;

			$this->logger->trace( "running $task_id" );
			self::execute_task( $task_id );
			$debug_message .= " run. ";
			break;
		}
		$this->logger->trace($debug_message);
	}

	function execute_task($id)
	{
		$task = new Focus_Tasklist($id);
		$this->logger->trace("going to run $id");
		$rc = $task->run();
		$this->logger->info($rc);
		if (! ($rc === true))
			$this->logger->fatal("running task $id: $rc");
	}
}
