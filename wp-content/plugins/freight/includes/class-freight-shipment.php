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
		return true;
	}
}