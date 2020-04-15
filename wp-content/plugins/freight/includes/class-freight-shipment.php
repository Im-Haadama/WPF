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
	 */
	private function __construct() {
		$id = null;
	}

	static public function LoadFromDB( $id ) {
		$db_prefix = get_table_prefix();
		$me        = new self();
		$me->id    = $id;

		if ( ! $id ) {
			print "no id for " . __CLASS__ . "<br>";
			print debug_trace( 10 );
			die( 1 );
		}
		$row = sql_query_single_assoc( "select * from ${db_prefix}path_shipments where id = $id" );
		if ( $row ) {
			$me->week_day    = $row['week_day'];
			$me->zone        = $row['zone'];
			$hours           = $row['hours'];
			$me->start       = strtok( $hours, "-" );
			$me->end         = strtok( "" );
			$me->instance_id = $row['instance'];
		} else {
			die( "why? . $id" );
		}

		return $me;
	}

	static function AddMethod($path_id, $zone, $week_day, $hours)
	{
		$db_prefix = get_table_prefix();
		
		$new = new Freight_Shipment();
		$new->week_day= $week_day;
		$new->zone= $zone;
		$new->start       = strtok( $hours, "-" );
		$new->end         = strtok( "" );
		$new->instance_id=

		$new->path_id = $path_id;

		sql_query("insert into ${db_prefix}path_shipments (path_id, zone, week_day, hours) values ($path_id, $zone, $week_day, $hours)");
		$new->id= sql_insert_id();

		return $new;
	}

	static function exists($path, $day, $zone)
	{
		$db_prefix = get_table_prefix();

		return sql_query_single_scalar("select count(*) from ${db_prefix}path_shipments where path_id = $path and week_day = $day and zone=$zone");
	}

	static function CreateInstance(&$zone, $path_id, $week_day, $hours)
	{
		$woo_prefix = get_table_prefix("woocommerce_shipping_zones");
		$zone        = WC_Shipping_Zones::get_zone( $zone );
		$zone_id = $zone->get_id();
		$default_price = sql_query_single_scalar("select default_rate from ${woo_prefix}woocommerce_shipping_zones where zone_id=" . $zone_id );

		$instance_id = $zone->add_shipping_method( 'flat_rate');
		$zone->save();
//		print "changing $instance_id $date $start $end<br/>";

		$db_prefix = get_table_prefix();
//		sql_query("update ${db_prefix}path_shipments set instance = $instance_id where id = $this->id");

		$sql = "insert into ${db_prefix}path_shipments (path_id, zone, week_day, hours, instance) values " .
		       "($path_id, $zone_id, $week_day, $hours, $instance_id)";

		print "sql: $sql<br/>";
		sql_query($sql);
		
		$new = Freight_Shipment::AddMethod($path_id, $zone, $week_day, $hours);
		$new->id = sql_insert_id();

		$new->update_instance($default_price);
	}

	function update_instance($price = 0)
	{
		$date = date('Y-m-d', strtotime('next ' . DayName($this->week_day, 'en_US')));
		Fresh_Delivery_Manager::update_shipping_method( $this->instance_id, $date, $this->start, $this->end, $price );
	}

	function delete_instance()
	{
		$db_prefix = get_table_prefix();
		delete_wp_woocommerce_shipping_zone_methods($this->instance_id);
		$this->instance_id = 0;
		sql_query("update ${db_prefix}path_shipments set instance = null where id = $this->id");

	}
}