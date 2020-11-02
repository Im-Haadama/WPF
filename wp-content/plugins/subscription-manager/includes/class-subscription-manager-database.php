<?php

if (! class_exists("Core_Database")) {
//	print "XXXXXXXXXXXXXXXXXXXXXXXXXXX";
	return;
}

class Subscription_Manager_Database extends Core_Database
{

	/**
	 * Subscription_Manager_Database constructor.
	 */
	public function __construct() {
		parent::__construct("Subscription_Manager");
	}

	function CreateTables($version, $force) {
		$current   = $this->checkInstalled(  "tables" );
		$db_prefix = GetTablePrefix();

		if ( $current == $version and ! $force ) {
			return true;
		}

//		SqlQuery("drop table ${db_prefix}subscriptions");
		SqlQuery("create table ${db_prefix}subscriptions
		(
			id int auto_increment
				primary key,
			user_id int null,
			subscription_name varchar(200) null,
			start_date date null,
			valid_date date null
		);
		");
		SqlQuery("create unique index ${db_prefix}subscriptions_index on ${db_prefix}subscriptions (user_id, subscription_name);");


		return self::UpdateInstalled( "tables", $version);
	}
}