<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/11/18
 * Tim7e: 20:00
 */

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}



//require_once( FRESH_INCLUDES . '/core/data/data.php' );
//require_once( FRESH_INCLUDES . '/routes/gui.php' );


/**
 * Class Mission
 */
class Mission {
	/**
	 * @var
	 */
	/**
	 * @var
	 */
	/**
	 * @var
	 */
	/**
	 * @var string
	 */
	/**
	 * @var string
	 */
	/**
	 * @var string
	 */
	private $id, $start_address, $end_adress, $start_time, $end_time, $date;

	/**
	 * Mission constructor.
	 *
	 * @param $id
	 *
	 * @throws Exception
	 */
	public function __construct( $id ) {
		$this->id = $id;
		if (! $id) return; // New mission.

		// Load from db
		$sql      = "select name, hour(start_h), MINUTE(start_h), start_address, end_address, end_h, date from im_missions where id = " . $id;
		$result   = sql_query_single( $sql );
		if ( ! $result ) {
			throw new Exception( "Can't find mission " . $id );
		}

		$this->name       = $result[0];
		$this->start_time = $result[1] . ":" . $result[2];
		$this->start_address = $result[3];
		$this->end_address   = $result[4];
		$this->end_time      = $result[5];
		$this->date          = $result[6];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getEndAdress() {
		return $this->end_adress;
	}

	public function getZoneTimes()
	{
		return unserialize($raw = sql_query_single_scalar("select zones_times from im_missions where id = " . $this->getId()));
	}

	/** return mission hour
	 * @return mixed
	 */
	public function getStartTime() {
		return $this->start_time;
	}

	/** get seconds from epoch till mission start
	 * @return false|int
	 */
	public function getStart() {
		$s = $this->date . " " . $this->start_time;

		return strtotime( $s );
	}

	/** return mission date.
	 * @return string
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @param mixed $start_address
	 */
	public function setStartAddress( $start_address ): void {
		$this->start_address = $start_address;
		sql_query( "update im_missions set start_address = '" . $start_address . "' where id = " . $this->id );
	}

	/**
	 * @param mixed $start_time
	 */
	public function setStartTime( $start_time ): void {
		$this->start_time = $start_time;
		$sql              = "update im_missions set start_h = '" . $start_time . "' where id = " . $this->id;
		sql_query( $sql );
	}

	/**
	 * @return mixed
	 */
	public function getEndTime() {
		return $this->end_time;
	}

	/**
	 * @param $id
	 *
	 * @return Mission
	 * @throws Exception
	 */
	static public function getMission( $id ) {
		if ( ! ( $id > 0 ) ) {
			die ( __METHOD__ . " id = " . $id );
		}
		$m = new Mission( $id );

		return $m;
	}

	/**
	 * @return string
	 */
	public function getPathCode() {
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}

		return sql_query_single_scalar( "SELECT path_code FROM im_missions WHERE id = " . $this->id );
	}

	/**
	 * @return string
	 */
	public function getStartAddress() {
		global $store_address;
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$start = sql_query_single_scalar( "SELECT start_address FROM im_missions WHERE id = " . $this->id );

		return $start ? $start : $store_address;
	}

	/**
	 * @return string
	 */
	public function getEndAddress() {
		global $store_address;

		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$end = sql_query_single_scalar( "SELECT end_address FROM im_missions WHERE id = " . $this->id );

		return $end ? $end : $store_address;
	}

	/**
	 * @return int
	 */
	public function getTaskCount() {
		return (int) sql_query_single_scalar( "SELECT count(*) FROM im_tasklist WHERE mission_id = " . $this->id );
	}

	/**
	 * @return string
	 */
	public function getMissionName() {
		return sql_query_single_scalar( "SELECT name FROM im_missions WHERE id = " . $this->id );
	}

	/**
	 * @param $path_id
	 *
	 * @param $forward_week
	 *
	 * @return bool
	 * @throws Exception
	 */
	public static function CreateFromType($type_id, $forward_days = 8) // from tomorrow till tomorrow+forward_days
	{
		$last_mission_id = sql_query_single_scalar("select max(id) from im_missions where mission_type = $type_id and date > curdate()");
		if ($last_mission_id) return;

		$type_info = sql_query_single_assoc("select * from im_mission_types where id = $type_id");

		$name = $type_info['mission_name'];
		$week_day = $type_info['week_day'];
		$date = next_weekday($week_day);

		 $sql = "insert into im_missions (date, name, mission_type) values('$date', '$name', $type_id)";

//		 print $sql ."<br/>";

		return sql_query($sql);
	}

	private function create_mission_path_date($path_id, $date)
	{
		global $store_address;
		$path = new Fresh_Path($path_id);
		if (! $path) return false;

		// Set mission times
		$start_hour = $path->getStart();
		$end_hour = $path->getEnd();
		$zones = $path->getZones();
		$name = $path->getDescription();

		$s_address = ($this->getId() ? $this->getStartAddress() : $store_address);
		$e_address = ($this->getId() ? $this->getEndAddress() : $store_address);

		$sql = "insert into im_missions (date, start_h, end_h, zones_times, name, path_code, start_address, end_address) " .
			" values ('$date', '$start_hour:00', '$end_hour:00', '" . serialize($zones) . "', '$name', '$path_id', '" . $s_address. "', '" . $e_address ."') ";

		sql_query($sql);
		return sql_insert_id();
	}

	public function stopAccept()
	{
		sql_query("update im_missions set accepting = 0 where id =" . $this->id);
	}
	/**
	 * @return array|null
	 */
	public function getShippingMethods() {
		$ids = [];
		$mission_day = DateDayName( $this->getDate() );
		$path_id     = sql_query_single_scalar( "select path_code from im_missions where id = $this->id" );

		if (! ($path_id > 0)) return null;

		$p = new Freight_Path($path_id);
		$zones = $p->getZones();

		foreach ($zones as $zone_id => $times){
			$w              = WC_Shipping_Zones::get_zone( $zone_id );
			if ($w) {
				$z              = $w->get_shipping_methods();
				$mission_method = null;
				foreach ( $z as $shipping_method ) {
					if ( strstr( $shipping_method->title, $mission_day ) ) {
						$ids[ $zone_id ] = $shipping_method;
						// array_push($ids, $shipping_method);
					}
				}
			} else {
			    print "zone $zone_id couldn't load<br/>";
			    print "path_id=$path_id<br/>";
			    die (1);
			}
		}
		return $ids;
	}

	function setType($type){
		return sql_query("update im_missions set mission_type = $type where id = " .$this->id);
	}
}

// Workaround: strftime doesn't get the day name according to locale (he_IL).

/**
 * @param $args
 *
 * @return bool
 */


/**
 * @param $mission_id
 *
 * @return string
 * @throws Exception
 */

/**
 * @return string
 */
function show_add_mission()
{
	$args = [];
	$args["mandatory_fields"] = array("date", "start_address", "name");
	$args["selectors"] = array("path_code" => "gui_select_path");
	return GemAddRow("im_missions", "New mission", $args);
}

//$debug = get_param( "debug", false, false );
//
//$operation   = get_param( "operation", false, null );
//$entity_name = "mission";
$table_name  = "im_missions";

/**
 * @param null $path_id
 *
 * @param int $forward_week (0 - for this week, 1 - next week, etc.
 *
 * @return string
 * @throws Exception
 */
/////////
// OLD //
/////////
//function create_missions() {
//	$this_week = date( "Y-m-d", strtotime( "last sunday" ) );
//	$sql       = "SELECT id FROM im_missions WHERE FIRST_DAY_OF_WEEK(date) = '" . $this_week . "' ORDER BY 1";

//
//	$result = sql_query( $sql );
//	while ( $row = sql_fetch_row( $result ) ) {
//		$mission_id = $row[0];
//		print "משכפל את משימה " . $mission_id . "<br/>";
//
//		duplicate_mission( $mission_id );
//	}
//}

