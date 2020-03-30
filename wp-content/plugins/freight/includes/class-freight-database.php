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

		sql_query("create table ${db_prefix}path_shipments
(
	id int auto_increment primary key,
	path_id int not null,
	week_day int not null,
	hours varchar(20) null,
	instance int null
);

");

//		if (! table_exists("paths"))
//			sql_query("create table ${db_prefix}paths
//(
//	id int auto_increment
//		primary key,
//	path_code varchar(10) charset utf8 not null,
//	description varchar(40) charset utf8 null,
//	zones_times longtext null,
//	week_days varchar(40) null
//);
//
//");
	}
}