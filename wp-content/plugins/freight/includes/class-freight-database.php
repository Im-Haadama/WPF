<?php


class Freight_Database extends Core_Database {
	static function install( $version, $force =false ) {
		// Create im_info table if missing.
		self::CreateInfo();

//		self::CreateFunctions($version, $force);
		self::CreateTables( $version, $force );
//		self::CreateViews($version, $force);
	}

	static function CreateTables( $version, $force )
	{
		$current = self::CheckInstalled("Fresh", "functions");
		$db_prefix = get_table_prefix();

		if ($current == $version and ! $force) return true;

		sql_query("alter table wp_woocommerce_shipping_zone_methods add week_day integer;");

		sql_query("alter table wp_woocommerce_shipping_zones drop column delivery_days;");
		sql_query("alter table wp_woocommerce_shipping_zones drop column codes;");
		sql_query("alter table wp_woocommerce_shipping_zones add min_order float;" );
		sql_query("alter table wp_woocommerce_shipping_zones add default_rate float;" );

		sql_query("create table ${db_prefix}path_shipments
(
	id int auto_increment primary key,
	path_id int not null,
	week_day int not null,
	hours varchar(20) null,
	instance int null
);

");

		self::UpdateInstalled("Freight", "tables", $version);

	}
}