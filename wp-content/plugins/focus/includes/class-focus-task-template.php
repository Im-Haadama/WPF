<?php
/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan. 
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna. 
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus. 
 * Vestibulum commodo. Ut rhoncus gravida arcu. 
 */

class Focus_Task_Template {
	private $id;
	private $task_description;
	private $task_url;
	private $project_id;
	private $repeat_days;
	private $condition_query;
	private $repeat_time;
	private $repeat_freq;
	private $path_code;
	private $repeat_freq_numbers;
	private $priority;
	private $owner;
	private $creator;
	private $team;
	private $last_check;
	private $working_hours;
	private $is_active;
	private $timezone;
	private $date_created;

	/**
	 * Focus_Task_Template constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
		
		$row = SqlQuerySingleAssoc("select * from im_task_templates where id = $id");
		$this->task_description = $row['task_description'];
		$this->task_url = $row['task_url'];
		$this->project_id = $row['project_id'];
		$this->repeat_days = $row['repeat_days'];
		$this->condition_query = $row['condition_query'];
		$this->repeat_time = $row['repeat_time'];
		$this->repeat_freq = $row['repeat_freq'];
		$this->path_code = $row['path_code'];
		$this->repeat_freq_numbers = $row['repeat_freq_numbers'];
		$this->priority = $row['priority'];
		$this->owner = $row['owner'];
		$this->creator = $row['creator'];
		$this->team = $row['team'];
		$this->last_check = $row['last_check'];
		$this->working_hours = $row['working_hours'];
		$this->is_active = $row['is_active'];
		$this->created = $row['created'];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed|string
	 */
	public function getTaskDescription(): string {
		return $this->task_description;
	}

	/**
	 * @return mixed|string
	 */
	public function getTaskUrl(): string {
		return $this->task_url;
	}

	/**
	 * @return mixed|string
	 */
	public function getProjectId(): string {
		return $this->project_id;
	}

	/**
	 * @return mixed|string
	 */
	public function getRepeatDays(): string {
		return $this->repeat_days;
	}

	/**
	 * @return mixed|string
	 */
	public function getConditionQuery(): string {
		return $this->condition_query;
	}

	/**
	 * @return mixed|string
	 */
	public function getRepeatTime(): string {
		return $this->repeat_time;
	}

	/**
	 * @return mixed|string
	 */
	public function getRepeatFreq(): string {
		return $this->repeat_freq;
	}

	/**
	 * @return mixed|string
	 */
	public function getPathcode(): string {
		return $this->pathcode;
	}

	/**
	 * @return mixed|string
	 */
	public function getRepeatFreqNumbers(): string {
		return $this->repeat_freq_numbers;
	}

	/**
	 * @return mixed|string
	 */
	public function getPriority(): string {
		return $this->priority;
	}

	/**
	 * @return mixed|string
	 */
	public function getOwner(): string {
		return $this->owner;
	}

	/**
	 * @return mixed|string
	 */
	public function getCreator(): string {
		return $this->creator;
	}

	/**
	 * @return mixed|string
	 */
	public function getTeam(): string {
		return $this->team;
	}

	/**
	 * @return mixed|string
	 */
	public function getLastCheck(): string {
		return $this->last_check;
	}

	/**
	 * @return mixed|string
	 */
	public function getWorkingHours(): string {
		return $this->working_hours;
	}

	/**
	 * @return mixed|string
	 */
	public function getIsActive(): string {
		return $this->is_active;
	}

	/**
	 * @return mixed
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * @param mixed $id
	 */
	public function setId( $id ): void {
		$this->id = $id;
	}

	/**
	 * @param mixed|string $task_description
	 */
	public function setTaskDescription( string $task_description ): void {
		$this->task_description = $task_description;
	}

	/**
	 * @param mixed|string $task_url
	 */
	public function setTaskUrl( string $task_url ): void {
		$this->task_url = $task_url;
	}

	/**
	 * @param mixed|string $project_id
	 */
	public function setProjectId( string $project_id ): void {
		$this->project_id = $project_id;
	}

	/**
	 * @param mixed|string $repeat_days
	 */
	public function setRepeatDays( string $repeat_days ): void {
		$this->repeat_days = $repeat_days;
	}

	/**
	 * @param mixed|string $condition_query
	 */
	public function setConditionQuery( string $condition_query ): void {
		$this->condition_query = $condition_query;
	}

	/**
	 * @param mixed|string $repeat_time
	 */
	public function setRepeatTime( string $repeat_time ): void {
		$this->repeat_time = $repeat_time;
	}

	/**
	 * @param mixed|string $repeat_freq
	 */
	public function setRepeatFreq( string $repeat_freq ): void {
		$this->repeat_freq = $repeat_freq;
	}

	/**
	 * @param mixed|string $pathcode
	 */
	public function setPathcode( string $pathcode ): void {
		$this->pathcode = $pathcode;
	}

	/**
	 * @param mixed|string $repeat_freq_numbers
	 */
	public function setRepeatFreqNumbers( string $repeat_freq_numbers ): void {
		$this->repeat_freq_numbers = $repeat_freq_numbers;
	}

	/**
	 * @param mixed|string $priority
	 */
	public function setPriority( string $priority ): void {
		$this->priority = $priority;
	}

	/**
	 * @param mixed|string $owner
	 */
	public function setOwner( string $owner ): void {
		$this->owner = $owner;
	}

	/**
	 * @param mixed|string $creator
	 */
	public function setCreator( string $creator ): void {
		$this->creator = $creator;
	}

	/**
	 * @param mixed|string $team
	 */
	public function setTeam( string $team ): void {
		$this->team = $team;
	}

	/**
	 * @param mixed|string $last_check
	 */
	public function setLastCheck( string $last_check ): void {
		$this->last_check = $last_check;
	}

	/**
	 * @param mixed|string $working_hours
	 */
	public function setWorkingHours( string $working_hours ): void {
		$this->working_hours = $working_hours;
	}

	/**
	 * @param mixed|string $is_active
	 */
	public function setIsActive( string $is_active ): void {
		$this->is_active = $is_active;
	}

	/**
	 * @param mixed $timezone
	 */
	public function setTimezone( $timezone ): void {
		$this->timezone = $timezone;
	}
	
//	public function save()
//	{
//		$sql = "update im_task_templates set
//		task_description = '$this->task_description',
//		task_url = '$this->task_url',
//		project_id = $this->project_id,
//		repeat_days = '$this->repeat_days',
//		condition_query = '$this->condition_query',
//		repeat_time = '$this->repeat_time',
//		repeat_freq = '$this->repeat_freq',
//		path_code = $this->path_code,
//		repeat_freq_numbers = '$this->repeat_freq_numbers',
//		priority = $this->priority,
//		owner = $this->owner,
//		creator = $this->creator,
//		team = $this->team,
//		last_check = $this->last_check,
//		working_hours = '$this->working_hours',
//		is_active = $this->is_active,
//		last_check = $this->last_check
//		where id = " . $this->id;
//		SqlQuery($sql);
//	}


	function create_if_needed()
	{
		$debug = ($this->id == 143);
		$db_prefix = GetTablePrefix();
		$verbose_line = array();
		$output = "";

		$id = $this->id;

		array_push( $verbose_line, $id );
		array_push( $verbose_line, $this->check_frequency() );
		array_push( $verbose_line, $this->check_query());
		array_push( $verbose_line, $this->check_active( ) );
		$test_result = "";
		for ( $i = 1; $i < 4; $i ++ )
			$test_result .= substr( $verbose_line[ $i ], 0, 1);

		$output .= "template " . $id . " result: $test_result" . Core_Html::Br();

		array_push( $verbose_line, $test_result );
		if ( strpos(  $test_result, "0" ) !== false) {
			array_push( $verbose_line, "skipped" );
			// array_push( $verbose_table, $verbose_line);
			$output .= CommaImplode($verbose_line) . "Template " . $id . Core_Html::Br();
			FocusLog($output);
			return;
		}

//		if (! $creator)
//			$creator = $default_owner;

		 $this->priority = 10; // SqlQuerySingleScalar( "SELECT project_priority FROM ${db_prefix}projects WHERE id = " . $project_id );
		array_push( $verbose_line, $this->priority);

		if (! $this->creator)
		{
			$this->creator = 1;
		}

		$sql = "INSERT INTO ${db_prefix}tasklist " .
		       "(task_description, task_template, status, date, project_id, priority, team, creator) VALUES ( " .
		       "'$this->task_description', $id, " . enumTasklist::waiting . ", NOW(), " . $this->project_id . ",  " .
		       $this->priority . "," . $this->team . "," . $this->creator . ")";

		SqlQuery( $sql );

		array_push( $verbose_line, SqlInsertId() );

		$output .= "Template " . $id . " " . CommaImplode($verbose_line) . Core_Html::Br();
		SqlQuery("update im_task_templates set last_check = now() where id = " . $this->id);
		if ($debug) FocusLog($output);
	}

	function check_frequency()
	{
		$result = "";
		// print "rf=$repeat_freq. rfn=$repeat_freq_numbers<br/>";
		if ( strlen( $this->repeat_freq ) == 0 ) return "1 empty freq passed";

		if (substr($this->repeat_freq, 0, 1) == 'c') return "1 c passed";

		// Check from day after last run till today
		$check_date = self::get_check_date();

		$now = strtotime(date('y-m-d'));

		$result .= "Now= " . date('y-m-d') . " Checking from " . date('y-m-d', $check_date) . "<br/>";

		while ($check_date <= ($now + 23*60*60)){
			$repeat_freq = explode( " ", $this->repeat_freq )[0]; // Change from "w - weekly" to "w"
			if ( in_array( date( $repeat_freq, $check_date ), explode( ",", $this->repeat_freq_numbers ) ) ) {
				return "1 " . $result;
			}

			$check_date += 86400;
		}
		return "0 " . $result;
	}

	function get_check_date()
	{
		$rc = strtotime('2020-01-01');
		if ($this->last_check) $rc = strtotime($this->last_check . ' +1 day');
		else if ($this->created) $rc = strtotime ($this->created);
		return $rc;
	}

	function check_query(  ) {
		require_once( ABSPATH . 'vendor/simple_html_dom.php' );

		if ( strlen( $this->condition_query ) == 0 ) {
			return "1 empty query passed<br/>";
		}
		if ( ! ( strlen( $this->condition_query ) > 5 ) ) {
			return "0 short or bad query. " . $this->condition_query . " failed";
		}

		return strip_tags( \Dom\file_get_html( $this->condition_query ));
	}

	function check_active(  ) {
		$db_prefix = GetTablePrefix();

		// status < 2 - active.
		// Date(date) = curdate() - due today. (or should it be finish date?)

		$running_id = SqlQuerySingleScalar( "select id from ${db_prefix}tasklist where task_template = " . $this->id .
		                                    " and (status < 2 or date(ended) = curdate()) limit 1");

		if ($running_id  == 'Error')
			die ("Can't work");
		if ( $running_id ) {
			return "0 " . $running_id;
		}

		return "1 not active";
	}
}
