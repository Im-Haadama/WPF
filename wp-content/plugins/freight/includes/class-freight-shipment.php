<?php


class Freight_Shipment {
	private $id;
	private $week_day;
	private $zone;
	private $start;
	private $end;
	private $instance_id;

	/**
	 * Freight_Shipment constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$db_prefix = get_table_prefix();
		$this->id = $id;

		$row = sql_query_single_assoc("select * from ${db_prefix}path_shipments where id = $id");
		if ($row) {
			$this->week_day    = $row['week_day'];
			$this->zone        = $row['zone'];
			$hours             = $row['hours'];
			$this->start       = strtok( $hours, "-" );
			$this->end         = strtok( "" );
			$this->instance_id = $row['instance'];
		} else {
			die("why? . $id");
		}
	}

	static function AddMethod($path_id, $zone, $week_day)
	{
		$db_prefix = get_table_prefix();

		return sql_query("insert into ${db_prefix}path_shipments (path_id, zone, week_day) values ($path_id, $zone, $week_day)");
	}

	static function exists($path, $day, $zone)
	{
		$db_prefix = get_table_prefix();

		return sql_query_single_scalar("select count(*) from ${db_prefix}path_shipments where path_id = $path and week_day = $day and zone=$zone");
	}

	function CreateInstance()
	{
		$db_prefix = get_table_prefix();
		$zone        = WC_Shipping_Zones::get_zone( $this->zone );

		$this->instance_id = $zone->add_shipping_method( 'flat_rate' );
		$zone->save();
//		print "changing $instance_id $date $start $end<br/>";

		sql_query("update ${db_prefix}path_shipments set instance = $this->instance_id where id = $this->id");

		self::update_instance();
	}

	function update_instance()
	{
		$date = date('Y-m-d', strtotime('next ' . DayName($this->week_day, 'en_US')));
		Fresh_Delivery_Manager::update_shipping_method( $this->instance_id, $date, $this->start, $this->end );
	}

	function delete_instance()
	{
		$db_prefix = get_table_prefix();
		delete_wp_woocommerce_shipping_zone_methods($this->instance_id);
		$this->instance_id = 0;
		sql_query("update ${db_prefix}path_shipments set instance = null where id = $this->id");

	}
}