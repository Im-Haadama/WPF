<?php


class Freight_Database extends Core_Database {

	/**
	 * Freight_Database constructor.
	 */
	public function __construct() {
		parent::__construct("Freight");
	}

	function CreateTables( $version, $force )
	{
		$current = $this->checkInstalled( "tables");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery("alter table ${db_prefix}mission_types add default_price float");

		SqlQuery("alter table ${db_prefix}mission_types add start_address varchar(200) charset utf8, add end_address varchar(200) charset utf8;");


		SqlQuery("alter table wp_woocommerce_shipping_zone_methods drop week_day");

		SqlQuery("alter table im_multisite
	add pickup_address varchar(50) not null;

");

		SqlQuery("alter table wp_woocommerce_shipping_zone_methods add week_day integer;");

		SqlQuery("alter table wp_woocommerce_shipping_zones drop column delivery_days;");
		SqlQuery("alter table wp_woocommerce_shipping_zones drop column codes;");
		SqlQuery("alter table wp_woocommerce_shipping_zones add min_order float;" );
		SqlQuery("alter table wp_woocommerce_shipping_zones add default_rate float;" );

		return $this->UpdateInstalled( "tables", $version);
	}
}