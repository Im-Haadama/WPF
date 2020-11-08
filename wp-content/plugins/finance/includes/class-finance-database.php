<?php
if (! class_exists("Core_Database"))
	return;

class Finance_Database extends Core_Database {

	/**
	 * Finance_Database constructor.
	 */
	public function __construct() {
		parent::__construct("Finance");
	}

	function CreateTables($version, $force) {
		$current = $this->CheckInstalled( "tables");
//		print "==========================cur=$current version=$version<br/>";
		$db_prefix = GetTablePrefix();

		if (! $current) {
			$this->FreshInstall();
			return $this->UpdateInstalled("tables", "version");
		}
		if ($current == $version and ! $force) return true;

		switch ($current)
		{
			case '1.1':
				SqlQuery("alter table ${db_prefix}bank 
				add  `client_name` varchar(400) DEFAULT NULL");

		}
		return self::UpdateInstalled( "tables", $version);
	}

	function CreateFunctions($version, $force) {
		$db_prefix = GetTablePrefix("bank");
		$current = $this->checkInstalled("tables" );

//		if ( $current ) return true;

		SqlQuery("drop function bank_last_transaction");
		SqlQuery("create function bank_last_transaction(_account int) returns date
		BEGIN
			declare _date date;
			select max(date) into _date from ${db_prefix}bank
			where account_id = _account;
			return _date;
		END;");

		SqlQuery("drop function working_rate");
		SqlQuery("create
    function working_rate(_worker int, _project int) returns float
BEGIN
    declare _rate float;

	select round(rate, 2) into _rate  
	       from im_working_rates 
	        where user_id = _worker
	       and project_id = _project;

    if (_rate > 0 ) THEN
      return _rate;
    END IF;

	select round(rate, 2) into _rate
	          from im_working
	          where user_id = _worker
	          and project_id = 0;
    return _rate;
  END;

");		SqlQuery( "drop function reduce_vat" );
		SqlQuery( "create FUNCTION `reduce_vat`(total float) RETURNS float
BEGIN
    return round(total/1.17, 2);
  END;" );
	}

	function FreshInstall()
	{
		$db_prefix = GetTablePrefix();

		if (! TableExists("bank_account"))
			SqlQuery("CREATE TABLE `im_bank_account` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `number` varchar(20) NOT NULL,
  `owner` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		if (! TableExists("bank"))
			SqlQuery("CREATE TABLE `im_bank` (
  `id` int(11) NOT NULL,
  `account_id` int(20) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(200) NOT NULL,
  `reference` int(11) NOT NULL,
  `out_amount` float DEFAULT NULL,
  `in_amount` float DEFAULT NULL,
  `balance` float NOT NULL,
  `client_name` varchar(400) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `receipt` varchar(100) DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `transaction_type` int(11) DEFAULT NULL,
  `comment` varchar(400) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		if (! TableExists("bank_lines"))
			SqlQuery("CREATE TABLE `im_bank_lines` (
  `id` int(11) NOT NULL,
  `line_id` int(11) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `part_id` int(11) DEFAULT NULL,
  `invoice` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		if (! TableExists("conversion"))
			SqlQuery("CREATE TABLE `im_conversion` (
  `id` int(11) NOT NULL,
  `table_name` varchar(20) NOT NULL,
  `col` varchar(20) NOT NULL,
  `header` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		if (! TableExists("yaad_transactions"))
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

	}

}