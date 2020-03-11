<?php

class Core_Database {
	static function CreateInfo()
	{
		if (! table_exists("im_info"))
			sql_query( "CREATE TABLE im_info (
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
