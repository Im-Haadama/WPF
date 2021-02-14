<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 02/01/19
 * Time: 09:19
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( "ROOT_DIR", dirname( dirname( dirname( __FILE__ ) ) ) );
}

class Focus_Tasklist {
	private $id;
	private $location_name;
	private $location_address;
	private $task_description;
	private $mission_id;
	private $repeat_freq;
	private $repeat_freq_numbers;
	private $priority;
	private $project;
	private $team;
	private $timezone;
	private $logger;
	private $table_prefix;
	private $creator;

	public function __construct( $_id)
	{
		$db_prefix = GetTablePrefix();
		$this->id = $_id;
		$this->table_prefix = GetTablePrefix();

		$row      = SqlQuerySingle( "SELECT location_name, location_address, task_description, mission_id," .
		                            " task_template, priority, project_id, team, creator " .
		                            " FROM {$this->table_prefix}tasklist " .
		                            " WHERE id = " . $this->id );
		if (! $row) throw new Exception("Task not found");

		$this->location_name    = $row[0];
		$this->location_address = $row[1];
		$this->task_description = $row[2];
		$this->mission_id       = $row[3];
		$this->priority         = $row[5];
		$this->project          = $row[6];
		$this->team             = $row[7];
		$this->creator          = $row[8];
		$this->last_check       = $row[9];

		if ( $row[4] ) {
			$row = SqlQuerySingle( "SELECT repeat_freq, repeat_freq_numbers, timezone " .
			                       " from ${db_prefix}task_templates where id = " . $row[4] );

			if ($row) {
				$this->repeat_freq         = $row[0];
				$this->repeat_freq_numbers = $row[1];
				$this->timezone            = $row[2];
			}
		}
		$this->logger = Focus_Manager::instance()->getLogger();
	}

	/**
	 * @return mixed
	 */
	public function getTeam() {
		return $this->team;
	}

	/**
	 * @return mixed
	 */
	public function getProject() {
		return $this->project;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}
    public function getCreator(){
	    return $this->creator;
    }
	/**
	 * @return mixed
	 */
	public function getLocationName() {
		return $this->location_name;
	}

	/**
	 * @return mixed
	 */
	public function getLocationAddress() {
		return $this->location_address;
	}

	/**
	 * @return mixed
	 */
	public function getTaskDescription() {
		return $this->task_description;
	}

	/**
	 * @return mixed
	 */
	public function getMissionId() {
		return $this->mission_id;
	}

	/**
	 * @return mixed
	 */
	public function getRepeatFreq() {
		return $this->repeat_freq;
	}

	/**
	 * @return mixed
	 */
	public function getRepeatFreqNumbers() {
		return $this->repeat_freq_numbers;
	}

	/**
	 * @return mixeda
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * @param mixed $priority
	 *
	 * @return bool|mysqli_result|null
	 */
	public function setPriority( $priority, $message )
	{
		$db_prefix = GetTablePrefix();
		$this->priority = $priority;
		self::add_time($message . " $priority");
		$sql            = "UPDATE ${db_prefix}tasklist SET priority = $priority " .
		                  " WHERE id = " . $this->id;

		return SqlQuery( $sql );
	}


	public function Ended($user_id)
	{
		$db_prefix = GetTablePrefix();
		$new_status = (($this->creator() == $user_id) ? enumTasklist::done : enumTasklist::done_creator);
		$sql = "UPDATE ${db_prefix}tasklist 
				SET ended = now(),
				    owner = $user_id,
				    status = " . $new_status;
		$message = "ended";
		if ($this->creator() != $user_id) $sql .= ", team = 0, owner = 0 ";
		else $message .= " (creator)";

		$sql .= " WHERE id = " . $this->id;

		FocusLog($sql);
		self::add_time($message);
		// print $sql;
		return SqlQuery( $sql );
	}

	public function Postpone()
	{
		$db_prefix = GetTablePrefix();
		$sql = "UPDATE ${db_prefix}tasklist set date = NOW() + INTERVAL 1 DAY\n" .
		       " where id = " . $this->id;
		if ( SqlQuery( $sql ) ) {
			self::add_time("postpone");
			return true;
		}

		return false;
	}

	private function add_time($message)
	{
		$db_prefix = GetTablePrefix();
		$id = $this->getId();
		$user = get_user_id();
		SqlQuery("insert into ${db_prefix}tasklist_times (task_id, action, user, time) values ($id, '$message', $user, NOW())");
	}

	function task_started( $owner )
	{
		$db_prefix = GetTablePrefix();

		$started = SqlQuerySingleScalar( "select started from ${db_prefix}tasklist where id = " . $this->id );
		if ( ! $started ) {
			$this->update_status(enumTasklist::started, $owner);
		}

		return true;
	}

	function get_status()
	{
		$db_prefix = GetTablePrefix();
		$rc =  SqlQuerySingleScalar("select status from ${db_prefix}tasklist where id = " . $this->getId());
//		print "stat=$rc<br/>";
		return $rc;
	}

	function update_status( $status, $owner = 0 )
	{
		global $Tasklist_Status_Names;
		$db_prefix = GetTablePrefix();

		self::add_time($Tasklist_Status_Names[$status]);

		$sql = "UPDATE ${db_prefix}tasklist SET owner = $owner, started = now(), status = $status " .
		       " WHERE id = " . $this->id;
		return SqlQuery( $sql );
	}

	static function task_cancelled($task_id) {
		$t = new Focus_Tasklist($task_id);
		if ($t->creator() == get_current_user())
			return $t->cancel();

		return $t->remove_assignment();
	}

	function task_template()
	{
		$db_prefix = GetTablePrefix();

		return SqlQuerySingleScalar( "select task_template from ${db_prefix}tasklist where id = " . $this->id);
	}

	function task_query($task_id)
	{
		$task_template = task_template($task_id);

		if (! $task_template) return null;

		return SqlQuerySingleScalar( "select condition_query from {$this->table_prefix}task_templates where id = " . $task_template);
	}

	function get_task_link( $template_id ) {
		$table_prefix = GetTablePrefix();

		if ( $template_id > 0 ) {
			return SqlQuerySingleScalar( "SELECT task_url FROM {$this->table_prefix}task_templates WHERE id = " . $template_id );
		}

		return "";
	}

	function task_url()
	{
		$sql = "SELECT task_url FROM {$this->table_prefix}task_templates WHERE id = " . self::task_template();
		return SqlQuerySingleScalar( $sql );
	}

	static function get_task_status( $id, $status )
	{
		switch ( $status ) {
			case enumTasklist::waiting:
				return "ממתין";
				break;
			case enumTasklist::started:
				return "התחיל";
				break;
			case enumTasklist::done:
				return "הסתיים";
				break;
			case enumTasklist::canceled:
				return "בוטל";
			case enumTasklist::done_creator:
				return "<b>בוצע!</b>";
				break;
		}
	}

	function check_condition() {
		$db_prefix = GetTablePrefix();

		if ( ! isset( $_GET["condition"] ) ) {
			print "must send condition";
			die ( 1 );
		}
		$condition = $_GET["condition"];
		switch ( $condition ) {
			case "daily":
				if ( ! isset( $_GET["id"] ) ) {
					print "daily must send id";
					die ( 2 );
				}
				$id          = $_GET["id"];
				$last_finish = SqlQuerySingleScalar( "SELECT max(datediff(curdate(), ended)) FROM ${db_prefix}tasklist WHERE task_template = " . $id );
				// print $last_finish;
				print $last_finish >= 1;
				break;
		}
	}

	function tasklist_set_defaults( &$values ) {
		$values["date"] = date( 'Y-m-d' );
	}

	function create_tasks_per_mission() {
		$db_prefix = GetTablePrefix();

		$mission_ids = SqlQueryArrayScalar( "SELECT id FROM ${db_prefix}missions WHERE date=CURDATE()" );
		$owner       = 1; // Todo: get it from the template

		foreach ( $mission_ids as $mission_id ) {
			print "handling mission " . $mission_id . "<br/>";
			$m = Mission::getMission( $mission_id );

			if ( $m->getTaskCount() ) {
				continue;
			}

			$path_code = $m->getPathCode();
			print "path_code = " . $path_code . "<br/>";

			if ($path_code === '') {
				print "empty path code for mission $mission_id. skipping<br/>";
				continue;
			}

			$template_ids = SqlQueryArrayScalar( "SELECT id FROM {$this->table_prefix}task_templates WHERE path_code = " . QuoteText( $path_code ) );

			foreach ( $template_ids as $template_id ) {
				$sql    = "SELECT task_description, task_url, project_id, repeat_freq, repeat_freq_numbers, condition_query, priority " . "
		   		 FROM {$this->table_prefix}task_templates " .
				          " where id = " . $template_id;
				$result = SqlQuery( $sql );

				$row = mysqli_fetch_assoc( $result );
				$project_id = $row["project_id"];

				$priority = SqlQuerySingleScalar( "SELECT priority FROM {$this->table_prefix}task_templates WHERE id = " . $template_id );

				if ( ! $priority and ( $project_id > 0 ) ) {
					$priority = SqlQuerySingleScalar( "SELECT project_priority FROM ${db_prefix}projects WHERE id = " . $project_id );
				}

				if ( ! $priority ) {
					$priority = 0;
				}

				if ( ! $project_id > 0 ) {
					$project_id = 0;
				}

				$sql = "INSERT INTO ${db_prefix}tasklist " .
				       "(task_description, task_template, status, date, project_id, priority, owner) VALUES ( " .
				       "'" . $row["task_description"] . "', " . $template_id . ", " . enumTasklist::waiting . ", now(), " . $project_id . ",  " .
				       $priority . "," . $owner . ")";

				SqlQuery( $sql );
				//print $sql;
			}
		}
	}

// Select relative optional preq for given task:
// 1) not finished.
// 2) in the same project.

	function gui_select_task_related( $id, $value, $events, $task_id ) {
		$query = "";
		$db_prefix = GetTablePrefix();

		if ( $project_id = SqlQuerySingleScalar( "select project_id from ${db_prefix}tasklist where id = " . $task_id ) ) {
			$query = "status in (0, 1) \n" .
			         " and project_id = " . $project_id;
		}

		return gui_select_task( $id, $value, $events, $query );
	}

	function task_table( $task_ids ) {
		$rows = array( array( "מזהה", "עדיפות", "תיאור" ) );

		foreach ( $task_ids as $task_id ) {
			$t = new Focus_Tasklist( $task_id );

			$row = array(
				Core_Html::GuiHyperlink( $t->getId(), self::get_url() ),
				$t->getPriority(),
				$t->getTaskDescription()
			);

			array_push( $rows, $row );
		}


		return Core_Html::gui_table_args( $rows );
	}

	function working_time() {
		$template = self::task_template();
		if (! $template) return true; // For now just templates has working time.
		$working_hours = SqlQuerySingleScalar("select working_hours from {$this->table_prefix}task_templates where id = $template");
		if (! $working_hours) return true;

		// For now allow just start-end format;
		$start = strtok($working_hours, "-");
		$end = strtok(null);

		if (! $start or ! $end) return true;
		if (date('G') < $start) return false;
		if (date('G') > $end) return false;
		return true;
	}

	function run()
	{
		$task_url = self::task_url();

		$this->logger->info("start run ". $this->id);
		if ((! is_string($task_url)) or (strlen($task_url) < 5)) {
			self::update_status( enumTasklist::bad_url );
			return false;
		}
		$rc = GetContent($task_url);

		if (! $rc) {
			if ($this->logger) $this->logger->fatal($rc);
			self::update_status(enumTasklist::failed);
		}
		else self::update_status(enumTasklist::done);
		return $rc;
	}

	function creator()
	{
		return $this->creator;
	}

	function cancel()
	{
		$db_prefix = GetTablePrefix();

		$sql = "UPDATE ${db_prefix}tasklist SET status = " . enumTasklist::canceled . // . ", status = " . enumTasklist::done .
		       " WHERE id = " . $this->id;
		$this->add_time("cancel");

		MyLog($sql);
		return SqlQuery( $sql );
	}

	function remove_assignment()
	{
		$db_prefix = GetTablePrefix();

		$sql = "UPDATE ${db_prefix}tasklist SET team = null " .
		       " WHERE id = " . $this->id;

		$this->add_time("remove assignment");
		MyLog($sql);
		return SqlQuery( $sql );

	}

	function print_task( ) {
		$fields = array();
		array_push( $fields, "משימות" );

		$ref = Core_Html::GuiHyperlink( $this->id, self::get_url());

		array_push( $fields, $ref );

		array_push( $fields, "" ); // client number
		array_push( $fields, $this->getLocationName() ); // name
		array_push( $fields, $this->getLocationAddress() ); // address 1
		array_push( $fields, "" ); // Address 2
		array_push( $fields, "" ); // phone
		array_push( $fields, "" ); // payment
		array_push( $fields, "" ); //

		array_push( $fields, $this->getMissionId() ); // payment
		array_push( $fields, Core_Db_MultiSite::LocalSiteID() );
		array_push( $fields, "" ); // fee
		array_push( $fields, $this->getTaskDescription() ); // Comments

		$line = Core_Html::gui_row( $fields );

		return $line;
	}

	function get_url()
	{
		return "/task?id=" . $this->getId();
	}

}

$Tasklist_Status_Names = array("waiting", "started", "done", "canceled", "bad_url", "failed", "done_creator");