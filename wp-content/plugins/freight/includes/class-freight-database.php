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

		if (!TableExists("distance")) SqlQuery("CREATE TABLE `${db_prefix}distance` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `distance` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `address_a` varchar(50) DEFAULT NULL,
  `address_b` varchar(50) DEFAULT NULL,  primary key (id)
  
                             
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		if ($current == $version and ! $force) return true;

		SqlQuery("alter table wp_woocommerce_shipping_zone_methods drop week_day");

		SqlQuery("truncate table ${db_prefix}distance");

		SqlQuery("create index  ${db_prefix}distance_address_a_address_b_index
	on im_distance (address_a, address_b)");


		SqlQuery("alter table ${db_prefix}mission_types add default_price float");

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