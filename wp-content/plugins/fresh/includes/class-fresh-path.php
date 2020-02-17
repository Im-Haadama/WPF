<?php


class Fresh_Path {
	private $id;
	private $description;
	private $start;
	private $end;
	private $zones;

	/**
	 * Fresh_Path constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
		$path_info = sql_query_single_assoc("select * from im_paths where id = " . $id);
		$this->description = $path_info['description'];
		$this->zones = unserialize($path_info['zones_times']);

		$this->start = 23;
		$this->end = 0;
		$zone_times = self::get_zone_times();
		foreach ($zone_times as $zone_id => $zone_time){
			$start = strtok($zone_time, "-");
			if ($start < $this->start) $this->start = $start;
			$end = strtok("");
			if ($end > $this->end) $this->end = $end;
		}
	}

	/**
	 * @return mixed|string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getStart(): string {
		return $this->start;
	}

	/**
	 * @return string
	 */
	public function getEnd(): string {
		return $this->end;
	}

	/**
	 * @return mixed|string
	 */
	public function getZones() {
		return $this->zones;
	}


	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	function get_zone_times($sorted = true)
	{
		// $zones =
		$zone_times = unserialize($raw = sql_query_single_scalar("select zones_times from im_paths where id = $this->id"));
		if (! $zone_times) { // Backward compatibility
			$zone_times = array();
			$zone = strtok($raw, ":");
			while ($zone)
			{
				$zone_times[$zone] = "9-13";
				$zone = strtok(":");
			}
		}
		if ($sorted) uasort($zone_times,
			function($a, $b) {
				$start_a = strtok($a, "-");
				$start_b = strtok($b, "-");
				return $start_a <=> $start_b;
			});

		return $zone_times;
	}

	static function getAll()
	{
		return sql_query_array_scalar("select id from im_paths");
	}
}