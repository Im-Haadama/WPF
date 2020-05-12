<?php


class Freight_Database extends Core_Database {
	function install( $version, $force =false ) {
		// Create im_info table if missing.
		self::CreateInfo();

//		self::CreateFunctions($version, $force);
		self::CreateTables( $version, $force );
//		self::CreateViews($version, $force);
	}

	static function CreateTables( $version, $force )
	{
		$current = self::CheckInstalled("Freight", "tables");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery("alter table wp_woocommerce_shipping_zone_methods drop week_day");

		SqlQuery("alter table im_multisite
	add pickup_address varchar(50) not null;

");

		SqlQuery("alter table wp_woocommerce_shipping_zone_methods add week_day integer;");

		SqlQuery("alter table wp_woocommerce_shipping_zones drop column delivery_days;");
		SqlQuery("alter table wp_woocommerce_shipping_zones drop column codes;");
		SqlQuery("alter table wp_woocommerce_shipping_zones add min_order float;" );
		SqlQuery("alter table wp_woocommerce_shipping_zones add default_rate float;" );

		self::UpdateInstalled("Freight", "tables", $version);
	}
}