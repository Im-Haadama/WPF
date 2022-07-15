<?php

class Core_Database
{
	private $my_class;

	/**
	 * Core_Database constructor.
	 *
	 * @param $my_class
	 */
	public function __construct( $my_class ) {
		$this->my_class = $my_class;
	}

	function install($version, $force = false)
	{
		// Run just in admin user
		if (! function_exists("get_user_id")) {
			return;
		}
		global $conn;
		if (! $conn) ReconnectDb();

		$user = new Core_Users(get_user_id());

		if (!is_super_admin() && !$user->hasRole('administrator')) {
			return;
		}

		self::CreateInfo();

		$this->CreateTables($version, $force);
		$this->CreateFunctions($version, $force);
		$this->CreateViews($version, $force);
		$this->runMigrations();
	}

	private function CreateInfo()
	{
		$db_prefix = GetTablePrefix();
//		SqlQuery("alter table ${db_prefix}info modify info_key varchar(100) charset utf8");

        if (! TableExists("info")) {
			SqlQuery( "CREATE TABLE ${db_prefix}info (
		info_key VARCHAR(200) NULL,
		info_data VARCHAR(200) NULL,
		id INT NOT NULL AUTO_INCREMENT
			PRIMARY KEY
	);" );
	        SqlQuery( "create unique index ${db_prefix}info_key_uindex
	on ${db_prefix}info (info_key);" );

        }

	}

	function CheckInstalled($subsystem)
	{
		$plugin = $this->my_class;
		return InfoGet("version_${plugin}_$subsystem");
	}

	function UpdateInstalled($subsystem, $version)
	{
		$plugin = $this->my_class;
//		MyLog(__FUNCTION__ . "version_${plugin}_$subsystem $version");
		return InfoUpdate("version_${plugin}_$subsystem", $version);
	}

	// functions to overide in class:
	function CreateFunctions($version, $force)
	{

	}
	function CreateViews($version, $force)
	{

	}

	function runMigrations()
	{
		$class = $this->my_class;
		$db_prefix = GetTablePrefix();
		if (!TableExists("migrations")) {
			SqlQuery( "create table ${db_prefix}migrations ( 
    id bigint auto_increment
		primary key,
	plugin_name varchar(20) not null,
    migration_file varchar(40) not null)" );
		}

		$path = ABSPATH . 'wp-content/plugins/' . strtolower($class) . '/migrations';
		if (! file_exists($path)) return;
		$content = scandir($path);
		foreach ($content as $migration){
			if (($migration == ".") || ($migration == "..")) continue;
			$found = SqlQuerySingleScalar("select count(*) from ${db_prefix}migrations where plugin_name = '${class}' and migration_file = '$migration'");
			if ($found) continue;

			$up = require_once ($path . '/' . $migration);
			if ($up()) {
				SqlQuery("insert into ${db_prefix}migrations (plugin_name, migration_file) values('${class}', '${migration}')");
			};
		}
	}
}
