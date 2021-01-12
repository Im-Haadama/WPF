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

		$user = new Core_Users();
		if (!$user->hasRole('administrator')) return;

		self::CreateInfo();

		$this->CreateTables($version, $force);
		$this->CreateFunctions($version, $force);
		$this->CreateViews($version, $force);
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
}
