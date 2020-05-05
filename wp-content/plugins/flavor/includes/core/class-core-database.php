<?php

class Core_Database {

	static function CreateInfo()
	{
		$db_prefix = GetTablePrefix();
		SqlQuery("alter table ${db_prefix}info modify info_key varchar(100)");

		if (! self::table_exists("info"))
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

	static function table_exists( $table ) {
		$db_prefix = GetTablePrefix();
		$sql = 'SELECT 1 FROM ' . $db_prefix .$table . ' LIMIT 1';
		return SqlQuery( $sql, false) != null;
	}

	static function view_exists( $table ) {
		$db_prefix = GetTablePrefix();
		$sql = 'SELECT 1 FROM ' . $db_prefix .$table . ' LIMIT 1';
		return SqlQuery( $sql, false) != null;
	}



}
