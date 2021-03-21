<?php


class Israel_Database extends Core_Database
{

	/**
	 * Israel_Database constructor.
	 */
	public function __construct() {
		parent::__construct("Israel");
	}

	function CreateTables($version, $force = false)
	{
		$current = $this->checkInstalled( "tables");

//		print "ver=$current force=$force";
//		if ($current == $version and ! $force) return true;
//		print "cont";

		$this->create_cities();
		$this->create_conversion(); // Should move to flavor
		$this->insert_conversion();

		return $this->UpdateInstalled( "tables", $version);
	}

	static private function create_conversion() {
		$db_prefix = GetTablePrefix();

		if ( 1 or ! TableExists( "conversion" ) ) {
			print "creating conversion<br/>";

		SqlQuery("drop table develop.im_conversion");
			SqlQuery( "create table ${db_prefix}conversion
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
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'city_name', 'שם_ישוב')" );
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'code', 'סמל_ישוב')" );
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'latin_name', 'שם_ישוב_לועזי')" );
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'region_number', 'סמל_מועצה_איזורית')" );
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'region_name', 'שם_מועצה')" );
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'regional_council_code', 'סמל_לשכת_מנא')" );
		SqlQuery( "INSERT INTO im_conversion (table_name, col, header) VALUES ('im_cities', 'zone', 'אזור')" );
	}

	static private function create_cities()
	{
		SqlQuery("create table im_cities
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