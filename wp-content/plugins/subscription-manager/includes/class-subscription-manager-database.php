<?php

if (! class_exists("Core_Database")) {
//	print "XXXXXXXXXXXXXXXXXXXXXXXXXXX";
	return;
}

class Subscription_Manager_Database extends Core_Database
{
	function CreateTables($version, $force) {
		$current   = self::CheckInstalled( "Finance", "tables" );
		$db_prefix = GetTablePrefix();

		if ( $current == $version and ! $force ) {
			return true;
		}

		SqlQuery("create table ${db_prefix}subscriptions
		(
			id int auto_increment
				primary key,
			user_id int null,
			`subscription name` varchar(200) null,
			`from` date null,
			valid date null
		);

");
	}

	static function CreateFunctions($version, $force = false) {
		$current   = self::CheckInstalled( "Fresh", "functions" );
		$db_prefix = GetTablePrefix();

		if ( $current == $version and ! $force ) {
			return true;
		}
	}

	static function CreateViews($version, $force ) {
		$current   = self::CheckInstalled( "Fresh", "views" );
		$db_prefix = GetTablePrefix();

		if ( $current == $version and ! $force ) {
			return true;
		}
	}
}