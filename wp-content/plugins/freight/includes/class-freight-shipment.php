<?php


class Freight_Shipment {
	private $instance_id;

	/**
	 * Freight_Shipment constructor.
	 *
	 * @param $instance_id
	 */
	function __construct($instance_id) {
		$this->instance_id = $instance_id;
	}


//	static function CreateInstance(&$zone_id, $path_id, $week_day, $hours)
//	{
//		$woo_prefix = get_table_prefix("woocommerce_shipping_zones");
//		$zone        = WC_Shipping_Zones::get_zone( $zone_id );
//		$default_price = sql_query_single_scalar("select default_rate from ${woo_prefix}woocommerce_shipping_zones where zone_id=" . $zone_id );
//
//		$instance_id = $zone->add_shipping_method( 'flat_rate');
//		$zone->save();
////		print "changing $instance_id $date $start $end<br/>";
//
//		$db_prefix = get_table_prefix();
////		sql_query("update ${db_prefix}path_shipments set instance = $instance_id where id = $this->id");
//
//		$sql = "insert into ${db_prefix}path_shipments (path_id, zone, week_day, hours, instance) values " .
//		       "($path_id, $zone_id, $week_day, '$hours', $instance_id)";
//
//		print "sql: $sql<br/>";
//		sql_query($sql);
//
//		$new = Freight_Shipment::AddMethod($path_id, $zone_id, $week_day, $hours);
//		$new->id = sql_insert_id();
//
//		return $new->update_instance($default_price);
//	}

	function update_instance($price = 0)
	{
//		print "updating instance ". $this->instance_id . "<br/>";
		$date = date('Y-m-d', strtotime('next ' . DayName($this->week_day, 'en_US')));
		$i = new WC_Shipping_Flat_Rate($this->instance_id);
		if (! $i) {
			print "CANT LOAD " . $this->instance_id . "<br/>";

			return false;
		}
		return Fresh_Delivery_Manager::update_shipping_method( $this->instance_id, $date, $this->start, $this->end, $price );
	}

	function delete_instance()
	{
		delete_wp_woocommerce_shipping_zone_methods($this->instance_id);
		$this->instance_id = 0;

	}
}