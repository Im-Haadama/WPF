<?php


class Israel_Database extends  Core_Database
{
	static function Upgrade($version, $force = false)
	{
		self::CreateInfo();
		self::CreateTables($version, $force);
//		self::CreateFunctions($version, $force);
	}

	static function CreateTables($version, $force = false)
	{
		$current = self::CheckInstalled("Israel", "tables");

//		print "ver=$current force=$force";
		if ($current == $version and ! $force) return true;
//		print "cont";

		self::create_cities();
		self::create_conversion(); // Should move to flavor
		self::insert_conversion();

		self::UpdateInstalled("Israel", "tables", $version);
	}

	static private function create_conversion() {
		$db_prefix = get_table_prefix();

		if ( ! table_exists( "conversion" ) ) {
			print "creating conversion<br/>";

			sql_query( "create table ${db_prefix}conversion
(
	id int auto_increment
		primary key,
	table_name varchar(20) not null,
	col varchar(40) not null,
	header varchar(20) not null
)
engine=MyISAM charset=utf8;

" );
		}
	}

	static function insert_conversion()
	{
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'city_name', 'שם_ישוב')" );
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'code', 'סמל_ישוב')" );
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'latin_name', 'שם_ישוב_לועזי')" );
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'region_number', 'סמל_מועצה_איזורית')" );
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'region_name', 'שם_מועצה')" );
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'regional_council_code', 'סמל_לשכת_מנא')" );
		sql_query( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'zone', 'אזור')" );
	}

	static private function create_cities()
	{
		sql_query("create table im_cities
(
	id int auto_increment
		primary key,
	city_name varchar(50) charset utf8 not null,
	zipcode mediumtext charset utf8 null,
	zone int null,
	code int null,
	latin_name varchar(50) null,
	region_number int null,
	region_name varchar(50) charset utf8 null,
	regional_council_code int null
);

");

	}


}