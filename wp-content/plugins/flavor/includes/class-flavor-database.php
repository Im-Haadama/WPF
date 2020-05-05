<?php


class Flavor_Database extends Core_Database {
	static function install( $version, $force = false ) {
		global $conn;

		return;
		if (! $conn) ReconnectDb();
		// Create im_info table if missing.
//		self::CreateInfo();

//		self::CreateFunctions($version, $force);
		self::CreateTables( $version, $force );
//		self::CreateViews($version, $force);
	}

	static function CreateTables( $version, $force ) {
		$current   = self::CheckInstalled( "Flavor", "tables" );
		$db_prefix = GetTablePrefix();

		if ( $current == $version and ! $force ) {
			return true;
		}

		SqlQuery( "create table ${db_prefix}log
(
	id int auto_increment
		primary key,
	time datetime not null,
	source varchar(30) null,
	severity int null,
	message longtext null
);

" );

		self::UpdateInstalled( "Flavor", "tables", $version );

	}
}