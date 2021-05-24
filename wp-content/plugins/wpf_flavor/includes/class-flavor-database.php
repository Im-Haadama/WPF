<?php


class Flavor_Database extends Core_Database {

	/**
	 * Flavor_Database constructor.
	 */
	public function __construct() {
		parent::__construct("flavor");
	}

//	function install( $version, $force = false ) {
//		global $conn;
//
//		if (! $conn) ReconnectDb();
//
//		self::CreateFunctions($version, $force);
//		self::CreateTables( $version, $force );
////		self::CreateViews($version, $force);
//	}

	function CreateFunctions($version, $force)
	{
		$current   = $this->checkInstalled(  "functions" );
		if ( $current == $version and ! $force ) return true;

		SqlQuery("drop function if exists FIRST_DAY_OF_WEEK");

		$first_day = get_wp_option("start_of_week");

		if (!$first_day) { // Israel
			SqlQuery("create function FIRST_DAY_OF_WEEK(day date) 
			returns date
			DETERMINISTIC 
BEGIN
	if (WEEKDAY(day) = 6) then return day;
	end if; 
    RETURN SUBDATE(day, WEEKDAY(day) + 1);
END;
");
		}

		SqlQuery("create function supplier_last_pricelist_date(_supplier_id int)
		returns date
		DETERMINISTIC
		BEGIN
		declare _date date;
		SELECT info_data into _date
		FROM im_info WHERE info_key = concat('import_supplier_', _supplier_id);
		return _date;
		END");

		self::UpdateInstalled( "functions", $version );
	}

	function CreateTables( $version, $force ) {
		$current   = $this->checkInstalled( "tables" );

		$db_prefix = GetTablePrefix();

		//		SqlQuery( "drop index ${db_prefix}links_index1 on ${db_prefix}links;" );
//		SqlQuery( "drop index ${db_prefix}links_index2 on ${db_prefix}links;" );
//
//		SqlQuery( "create index ${db_prefix}links_index1 on ${db_prefix}links (type1, type2, id1);" );
//		SqlQuery( "create index ${db_prefix}links_index2 on ${db_prefix}links (type1, type2, id2);" );

		if (! $current) return
			$this->CreateTablesFresh($version);

		if ( $current == $version and ! $force ) {
			return true;
		}

		switch ($current)
		{
			case '1.0':
			case '1.1':
			case '1.4':
			case '1.5':
				SqlQuery("alter table ${db_prefix}info modify info_data blob null");
			case '1.6':
				SqlQuery("alter table ${db_prefix}mission_types add week_day int");
		}


		return self::UpdateInstalled(  "tables", $version );
	}

	function CreateTablesFresh($version)
	{
		$db_prefix = GetTablePrefix();

		if (!TableExists("log"))
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

		if (! TableExists("mission_types"))
			SqlQuery("create table ${db_prefix}mission_types (
  id int auto_increment
		primary key,
  `mission_name` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `week_day` int(11) NOT NULL,
  `default_price` float DEFAULT NULL,
  `start_address` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `end_address` varchar(200) CHARACTER SET utf8 DEFAULT NULL
) DEFAULT CHARSET=utf8");

		if (!TableExists("links")) {
			SqlQuery( "create table ${db_prefix}links
(
    id int auto_increment primary key,
                type1 int(11) not null,
                type2 int(11) not null,
                id1 int(11) not null, 
                id2 int(11) not null
);

" );
			SqlQuery( "create index index ${db_prefix}links_index1 on ${db_prefix}links (type1, type2, id1);" );
			SqlQuery( "create index index ${db_prefix}links_index2 on ${db_prefix}links (type1, type2, id2);" );
		}

		if (! TableExists("missions"))
			SqlQuery("create table ${db_prefix}missions
(
	id int auto_increment
		primary key,
	date date null,
	start_h time(6) null,
	end_h time(6) null,
	zones_times longtext null,
	name varchar(200) null,
	start_address varchar(50) null,
	end_address varchar(50) null,
	mission_type int(11) null,	
	path varchar(4000),
	accepting bit default b'1' null
)
charset=utf8;
");

		if (! TableExists("info"))
			SqlQuery("create table ${db_prefix}info
(
	info_key varchar(200) charset utf8 null,
	info_data blob null,
	id int auto_increment
		primary key,
	constraint im_info_key_uindex
		unique (info_key)
);

");

		return self::UpdateInstalled(  "tables", $version );
	}
}
