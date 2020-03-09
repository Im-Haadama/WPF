<?php


class Core_Database {
	static function CheckInstalled($plugin, $subsystem)
	{
		return InfoGet("version_${plugin}_$subsystem");
	}

	static function UpdateInstalled($plugin, $subsystem, $version)
	{
		InfoUpdate("version_${plugin}_$subsystem", $version);
	}
}