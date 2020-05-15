<?php
if (! class_exists("Core_Database"))
	return;


class Finance_Database extends Core_Database {
	function CreateTables($version, $force) {
		$current = self::CheckInstalled("Finance", "tables");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery( "alter table ${db_prefix}yaad_transactions add payment_number int;" );

		if ( ! TableExists( "yaad_transactions" ) ) {
			SqlQuery( "create table im_yaad_transactions
	(
		id int auto_increment
			primary key,
		transaction_id int null,
		CCode int null,
		Amount float null,
		ACode varchar(200) null,
		user_id int null,
		pay_date date null,
		payment_number int	
	);
	
	" );
		}

		self::UpdateInstalled("Finance", "tables", $version);

	}

	function CreateFunctions($version)
	{
		return true;
	}

	function CreateViews($version)
	{
		return true;
	}

}