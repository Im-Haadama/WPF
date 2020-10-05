<?php
if (! class_exists("Core_Database"))
	return;

class Finance_Database extends Core_Database {
	function CreateTables($version, $force) {
		$current = self::CheckInstalled("Finance", "tables");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		self::payment_info_table();

		SqlQuery("CREATE TABLE `im_bank_account` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `number` varchar(20) NOT NULL,
  `owner` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

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

		SqlQuery("CREATE TABLE `im_bank_lines` (
  `id` int(11) NOT NULL,
  `line_id` int(11) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `site_id` int(11) DEFAULT NULL,
  `part_id` int(11) DEFAULT NULL,
  `invoice` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");


		SqlQuery("CREATE TABLE `im_conversion` (
  `id` int(11) NOT NULL,
  `table_name` varchar(20) NOT NULL,
  `col` varchar(20) NOT NULL,
  `header` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");


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

	function CreateFunctions($version) {
		return;
		SqlQuery("drop function working_rate");
		SqlQuery("create
    function working_rate(_worker int, _project int) returns float
BEGIN
    declare _rate float;

	select round(rate, 2) into _rate  
	       from im_working 
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

	function CreateViews($version)
	{
		return true;
	}

	/*-- Start create payment table --*/
	static function payment_info_table(){
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `im_payment_info` (
	    `id` int(11) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
	    `user_id` int(11) NOT NULL,
	    `full_name` varchar(255) NOT NULL,
	    `email` varchar(255) NOT NULL,
	    `card_number` varchar(50) NOT NULL,
	    `card_four_digit` varchar(50) NOT NULL,
	    `card_type` varchar(100) NOT NULL,
	    `exp_date_month` tinyint(4) NOT NULL,
	    `exp_date_year` int(11) NOT NULL,
	    `id_number` varchar(15)  NOT NULL,
	    `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
	) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	/*-- End create payment table --*/


}