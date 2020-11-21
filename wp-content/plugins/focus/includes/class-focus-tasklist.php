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

		$this->location_name    = $row[0];
		$this->location_address = $row[1];
		$this->task_description = $row[2];
		$this->mission_id       = $row[3];
		$this->priority         = $row[5];
		$this->project          = $row[6];
		$this->team             = $row[7];
		$this->creator = $row[8];

		if ( $row[4] ) {
			$row = SqlQuerySingle( "SELECT repeat_freq, repeat_freq_numbers, timezone " .
			                       " from ${db_prefix}task_templates where id = " . $row[4] );

			$this->repeat_freq         = $row[0];
			$this->repeat_freq_numbers = $row[1];
			$this->timezone = $row[2];
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
	public function setPriority( $priority )
	{
		$db_prefix = GetTablePrefix();
		$this->priority = $priority;
		$sql            = "UPDATE ${db_prefix}tasklist SET priority = $priority " .
		                  " WHERE id = " . $this->id;

		return SqlQuery( $sql );
	}


	public function Ended($user_id)
	{
		$db_prefix = GetTablePrefix();
		$sql = "UPDATE ${db_prefix}tasklist 
				SET ended = now(),
				    owner = $user_id,
				    status = " . enumTasklist::done .
		       " WHERE id = " . $this->id;

		// print $sql;
		return SqlQuery( $sql );
	}

	public function Postpone()
	{
		$db_prefix = GetTablePrefix();
		$sql = "UPDATE ${db_prefix}tasklist set date = NOW() + INTERVAL 1 DAY\n" .
		       " where id = " . $this->id;
		if ( SqlQuery( $sql ) ) {
			return true;
		}

		return false;
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

	function update_status( $status, $owner = 0 )
	{
		$db_prefix = GetTablePrefix();

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

	function get_task_status( $status ) {
		switch ( $status ) {
			case enumTasklist::waiting:
				print "ממתין";
				break;
			case enumTasklist::started:
				print "התחיל";
				break;
			case enumTasklist::done:
				print "הסתיים";
				break;
			case enumTasklist::canceled:
				print "בוטל";
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

	static function create_if_needed($id, $row, &$output, $default_owner, &$verbose_line)
	{
		$db_prefix = GetTablePrefix();
		$verbose_line = array();
		$last_run = SqlQuerySingleScalar( "select max(date) from ${db_prefix}tasklist where task_template = " . $id);
		if ($last_run=='Error') die('Can\'t work');
		$project_id = $row["project_id"];
		if ( ! $project_id ) {
			$project_id = 0;
		}

		array_push( $verbose_line, $id );
		array_push( $verbose_line, Focus_Tasklist::check_frequency( $row["repeat_freq"], $row["repeat_freq_numbers"], $last_run ) );
		array_push( $verbose_line, Focus_Tasklist::check_query( $row["condition_query"] ) );
		array_push( $verbose_line, Focus_Tasklist::check_active( $id, $row["repeat_freq"] ) );
		$test_result = "";
		for ( $i = 1; $i < 4; $i ++ )
			$test_result .= substr( $verbose_line[ $i ], 0, 1);

		$output .= "template " . $id . " result: $test_result" . Core_Html::Br();

		SqlQuery( "update {$db_prefix}task_templates set last_check = now() where id = " . $id);

		array_push( $verbose_line, $test_result );
		if ( strpos(  $test_result, "0" ) !== false) {
			array_push( $verbose_line, "skipped" );
			// array_push( $verbose_table, $verbose_line);
			$output .= CommaImplode($verbose_line) . "Template " . $id . Core_Html::Br();
			return;
		}

		$team = SqlQuerySingleScalar( "SELECT team FROM {$db_prefix}task_templates WHERE id = " . $id );

		$creator = SqlQuerySingleScalar( "SELECT creator FROM {$db_prefix}task_templates WHERE id = " . $id );
		if (! $creator)
			$creator = $default_owner;

		$priority = SqlQuerySingleScalar( "SELECT priority FROM {$db_prefix}task_templates WHERE id = " . $id );
		if ( ! $priority ) $priority = SqlQuerySingleScalar( "SELECT project_priority FROM ${db_prefix}projects WHERE id = " . $project_id );
		if ( ! $priority ) $priority = 0;
		array_push( $verbose_line, $priority);

		$sql = "INSERT INTO ${db_prefix}tasklist " .
		       "(task_description, task_template, status, date, project_id, priority, team, creator) VALUES ( " .
		       "'" . $row["task_description"] . "', " . $id . ", " . enumTasklist::waiting . ", now(), " . $project_id . ",  " .
		       $priority . "," . $team . "," . $creator . ")";

		SqlQuery( $sql );

		array_push( $verbose_line, SqlInsertId() );

		$output .= "Template " . $id . " " . CommaImplode($verbose_line) . Core_Html::Br();
	}

	static function check_frequency( $repeat_freq, $repeat_freq_numbers, $last_run )
	{
		$result = "";
		// print "rf=$repeat_freq. rfn=$repeat_freq_numbers<br/>";
		if ( strlen( $repeat_freq ) == 0 ) return "1 empty freq passed";

		if (substr($repeat_freq, 0, 1) == 'c') return "1 c passed";

		// Check from day after last run till today
		$check_date = ($last_run ? strtotime($last_run . ' +1 day') : strtotime('now'));
		$now = strtotime(date('y-m-d'));

		$result .= "Now= " . date('y-m-d') . " Checking from " . date('y-m-d', $check_date) . "<br/>";

		while ($check_date <= ($now + 23*60*60)){
			$repeat_freq = explode( " ", $repeat_freq )[0]; // Change from "w - weekly" to "w"
			if ( in_array( date( $repeat_freq, $check_date ), explode( ",", $repeat_freq_numbers ) ) ) {
				return "1 " . $result;
			}

			$check_date += 86400;
		}
		return "0 " . $result;
	}

	static function check_query( $query ) {
		require_once( ABSPATH . 'vendor/simple_html_dom.php' );

		if ( strlen( $query ) == 0 ) {
			return "1 empty query passed<br/>";
		}
		if ( ! ( strlen( $query ) > 5 ) ) {
			return "0 short or bad query. " . $query . " failed";
		}

		return strip_tags( \Dom\file_get_html( $query ));
	}

	static function check_active( $id, $repeat_freq ) {
		$db_prefix = GetTablePrefix();

		// status < 2 - active.
		// Date(date) = curdate() - due today. (or should it be finish date?)

		$running_id = SqlQuerySingleScalar( "select id from ${db_prefix}tasklist where task_template = " . $id .
		                                    " and (status < 2 or date(ended) = curdate()) limit 1");

		if ($running_id  == 'Error')
			die ("Can't work");
		if ( $running_id ) {
			return "0 " . $running_id;
		}

		return "1 not active";
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
				Core_Html::GuiHyperlink( $t->getId(), "c-get-tasklist.php?id=" . $t->getId() ),
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
		MyLog($sql);
		return SqlQuery( $sql );
	}

	function remove_assignment()
	{
		$db_prefix = GetTablePrefix();

		$sql = "UPDATE ${db_prefix}tasklist SET team = null " .
		       " WHERE id = " . $this->id;

		MyLog($sql);
		return SqlQuery( $sql );

	}

	function print_task( ) {
		$fields = array();
		array_push( $fields, "משימות" );

		$ref = Core_Html::GuiHyperlink( $this->id, "../tasklist/c-get-tasklist.php?id=" . $this->id );

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

}

class enumTasklist {
	const
		waiting = 0,
		started = 1,
		done = 2,
		canceled = 3,
		bad_url = 4,
		failed = 5;
}
