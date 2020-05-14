<?php

class Core_Database
{
	function install($version, $force = false)
	{
		if (! function_exists("get_user_id")) return;

		if (get_user_id() != 1) return;

		// Create im_info table if missing.
		self::CreateInfo();

		$this->CreateTables($version, $force);
		$this->CreateFunctions($version, $force);
		$this->CreateViews($version, $force);
	}

	static function CreateInfo()
	{
		$db_prefix = GetTablePrefix();
		SqlQuery("alter table ${db_prefix}info modify info_key varchar(100) charset utf8");

		if (! TableExists("info"))
			SqlQuery( "CREATE TABLE ${db_prefix}info (
		info_key VARCHAR(200) NULL,
		info_data VARCHAR(200) NULL,
		id INT NOT NULL AUTO_INCREMENT
			PRIMARY KEY
	);" );
	}
	static function CheckInstalled($plugin, $subsystem)
	{
		self::CreateInfo();
		return InfoGet("version_${plugin}_$subsystem");
	}

	static function UpdateInstalled($plugin, $subsystem, $version)
	{
		InfoUpdate("version_${plugin}_$subsystem", $version);
	}
}
