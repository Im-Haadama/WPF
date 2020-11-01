<?php

class Core_Database
{
	function install($version, $force = false)
	{
		if (! function_exists("get_user_id")) {
			return;
		}
		$user = new Core_Users();
		if (!$user->hasRole('administrator')) return;

		// Create im_info table if missing.
		self::CreateInfo();

		$this->CreateTables($version, $force);
		$this->CreateFunctions($version, $force);
		$this->CreateViews($version, $force);
	}

	static function CreateInfo()
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

	static function CheckInstalled($plugin, $subsystem)
	{
		self::CreateInfo();
		return InfoGet("version_${plugin}_$subsystem");
	}

	static function UpdateInstalled($plugin, $subsystem, $version)
	{
		return InfoUpdate("version_${plugin}_$subsystem", $version);
	}
}
