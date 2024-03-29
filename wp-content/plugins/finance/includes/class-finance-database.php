<?php
if (! class_exists("Core_Database"))
	return;

class Finance_Database extends Core_Database {
	function CreateTables($version, $force) {

		$db_prefix = GetTablePrefix();
		$current = self::CheckInstalled("tables");

		if (! $current) {
			self::FreshInstall();
		}
		if ($current == $version and ! $force) return true;

		FinanceLog(__FUNCTION__ . " upgrading from $current to $version");

		if (! TableExists("suppliers")) {
			SqlQuery( "create table ${db_prefix}suppliers
(
	id bigint auto_increment
		primary key,
	supplier_name varchar(20) not null,
	supplier_contact_name varchar(20) not null,
	supplier_contact_phone varchar(20) not null,
	factor float default 3 null,
	site_id int null,
	email varchar(50) null,
	supplier_priority int(2) default 5 null,
	machine_update bit default b'0' null,
	category int null,
	eng_name varchar(40) null,
	print tinyint(1) default 0 null,
	is_active bit default b'1' null,
	address varchar(100) null,
	self_collect bit default b'0' null,
	source_path varchar(500) null,
	auto_order_day int(1) null,
	min_order int null,
	invoice_email varchar(50) null,
	supplier_description varchar(200) null
) charset=utf8;" );

			SqlQuery("ALTER TABLE im_suppliers AUTO_INCREMENT = 100001");
		}

//		print "=====================================================cur=$current<br/>";
		if ($current)
		switch($current)
		{
			case '1.2':
			case '1.4':
				 SqlQuery("alter table ${db_prefix}delivery_lines add has_vat bool default true;");
			case '1.6':
				SqlQuery("alter table wp_woocommerce_shipping_zone_methods " .
				         " drop week_day");
				return $this->UpdateInstalled("tables", $version);
			case '1.7':
			case '1.7.1':
				SqlQuery("alter table ${db_prefix}payments add	default_method bit default b'0' null");
				SqlQuery("create table ${db_prefix}inventory_count
(
	id int auto_increment
		primary key,
	count_date date not null,
	supplier_id int null,
	product_id int null,
	product_name varchar(200) charset utf8 null,
	quantity int not null
)
engine=MyISAM;");
			case '1.7.13':

				SqlQuery("alter table wp_woocommerce_shipping_zone_methods add mission_code int null");
//				SqlQuery("alter table ${db_prefix}payments add
//	    	default_method bit default b'0' null");


				case '1.7.19':
					SqlQuery("alter table ${db_prefix}mission_types add default_price float");

				case '1.17.20':
				case '1.17.21':

				SqlQuery("alter table ${db_prefix}mission_types add start_address varchar(200) charset utf8, add end_address varchar(200) charset utf8;");

			}

		self::UpdateInstalled( "tables", $version);
	}

	function CreateFunctions($version, $force = false)
	{
		$current = self::CheckInstalled("functions");

		if ($current == $version and ! $force) return true;

		$db_prefix = GetTablePrefix();

		SqlQuery("drop function supply_from_business");
		SqlQuery("create function supply_from_business( _business_id int) returns integer
		DETERMINISTIC
	BEGIN
		declare _supply_id integer;
		select id into _supply_id 
	        from ${db_prefix}supplies where business_id = _business_id; 
	    return _supply_id;
	END;
");

		SqlQuery("drop function supplier_last_pricelist_date;");

		SqlQuery("create function supplier_last_pricelist_date(_supplier_id int) returns date
		DETERMINISTIC
		BEGIN
		declare _date date;
		SELECT info_data into _date
		FROM im_info WHERE info_key = concat('import_supplier_', _supplier_id);
		return _date;
		END");

		SqlQuery("drop function supplier_from_business");
		SqlQuery("create
     function supplier_from_business(bus_id int) returns text CHARSET utf8
     DETERMINISTIC
BEGIN
    declare _supplier_id int;
    declare _display varchar(50) CHARSET utf8;
    SELECT part_id INTO _supplier_id FROM im_business_info where id = bus_id;
    select supplier_name into _display from im_suppliers where id = _supplier_id;

    return _display;
  END;

");

		SqlQuery("drop function if exists  supplier_displayname");
		SqlQuery( "create function supplier_displayname (supplier_id int) 
		returns text charset utf8
		DETERMINISTIC  
BEGIN
declare _user_id int;
declare _display varchar(50) CHARSET utf8;
select supplier_name into _display from ${db_prefix}suppliers
where id = supplier_id;

return _display;
END;

" );

		SqlQuery("drop function if exists supplier_balance");
		$sql = "create function supplier_balance (_supplier_id int, _date date)
		 returns float
		 DETERMINISTIC   
BEGIN
declare _amount float;
select sum(amount) into _amount from ${db_prefix}business_info
where part_id = _supplier_id
and date <= _date
and is_active = 1
and document_type in (" . Finance_DocumentType::bank . "," . Finance_DocumentType::invoice . "," . Finance_DocumentType::invoice_refund . "); 

return round(_amount, 0);
END;";
		SqlQuery($sql);

		SqlQuery("drop function if exists working_rate");
		SqlQuery("create
    function working_rate(_worker int, _project int)
     returns float
     DETERMINISTIC
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
	          from im_working_rates
	          where user_id = _worker
	          limit 1;
    return _rate;
  END;

");

		SqlQuery("drop function client_from_delivery");
		SqlQuery("create function client_from_delivery(del_id int) 
		returns text CHARSET 'utf8'
		DETERMINISTIC
BEGIN
  declare _order_id int;
  declare _user_id int;
  declare _display varchar(50) CHARSET utf8;
  SELECT order_id INTO _order_id FROM im_delivery where id = del_id;
  select meta_value into _user_id from wp_postmeta
  where post_id = _order_id and
  meta_key = '_customer_user';
  select display_name into _display from wp_users where id = _user_id;

  return _display;
END;

");

		SqlQuery("drop function if exists  order_user");
		SqlQuery("create function order_user(order_id int) returns int
BEGIN
    declare _user_id int;
    SELECT meta_value INTO _user_id FROM wp_postmeta where post_id = order_id and meta_key = '_customer_user';

    return _user_id;
  END;

");

		SqlQuery("drop function if exists delivery_receipt");
		SqlQuery("create function delivery_receipt(_del_id int) 
		returns int
		DETERMINISTIC
BEGIN
		declare _receipt integer;
		select payment_receipt into _receipt 
	        from im_delivery where id = _del_id; 
	    return _receipt;
	END;
");

		SqlQuery("drop function order_from_delivery");
		SqlQuery("create function order_from_delivery(del_id int)
		 returns text
		 DETERMINISTIC
BEGIN
    declare _order_id int;
    SELECT order_id INTO _order_id FROM im_delivery where id = del_id;

    return _order_id;
END;

");

		SqlQuery("drop function client_balance");
		SqlQuery("create function client_balance(_client_id int, _date date)
		 returns float
		 DETERMINISTIC
BEGIN
    declare _amount float;
select sum(transaction_amount) into _amount
from im_client_accounts where date <= _date
                          and client_id = _client_id;
return round(_amount, 0);
END;

");

		SqlQuery("drop function client_payment_method");
		SqlQuery("create function client_payment_method(_user_id int) 
		returns text charset utf8
		DETERMINISTIC
BEGIN
    declare _method_id int;
    declare _name VARCHAR(50) CHARSET 'utf8';
    select meta_value into _method_id from wp_usermeta where user_id = _user_id and meta_key = 'payment_method';
    select name into _name from im_payments where id = _method_id;

    return _name;
  END;

");

		SqlQuery("drop function if exists  order_line_get_variation");
		SqlQuery("create function order_line_get_variation(_order_item_id int)
		 RETURNS text
		 DETERMINISTIC
BEGIN
    declare _variation int;
    select meta_value into _variation from wp_woocommerce_order_itemmeta
    where order_item_id = _order_item_id
      and meta_key = '_variation_id';

    return _variation;
  END;

");

		SqlQuery( "drop function reduce_vat" );
		SqlQuery( "create FUNCTION `reduce_vat`(total float) 
		RETURNS float
		DETERMINISTIC
BEGIN
    return round(total/1.17, 2);
  END;" );

		self::UpdateInstalled("functions", $version);

	}

	function CreateViews($version, $force)
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


	function FreshInstall()
	{
		$db_prefix = GetTablePrefix();

		FinanceLog(__FUNCTION__);

		if (! TableExists("payments"))
			SqlQuery("create table ${db_prefix}payments
(
	id int auto_increment,
	name varchar(20) null,
	default_method bit default b'0' null,
	mail_delivery bit not null default b'0',
	accountants varchar(100) null,
	constraint im_payments_id_uindex
		unique (id)
)

");
		if (! TableExists("delivery_lines")) {
			SqlQuery( "create table ${db_prefix}delivery_lines
(
	id bigint auto_increment
		primary key,
	delivery_id bigint not null,
	product_name varchar(40) not null,
	quantity float not null,
	quantity_ordered float not null,
	vat float not null,
	price float not null,
	line_price float not null,
	prod_id int null,
	unit_ordered float null,
	has_vat bool,
	a int null
);

" );
		}

		if (!TableExists("business_info"))
			SqlQuery("create table ${db_prefix}business_info
(
	id bigint auto_increment
		primary key,
	part_id int not null,
	date date not null,
	week date not null,
	amount double not null,
	ref varchar(20) not null,
	delivery_fee float null,
	project_id int default 3 not null,
	is_active bit default b'1' null,
	document_type int(2) default 1 not null,
	net_amount double null,
	invoice_file varchar(200) charset utf8 null,
	invoice int(10) null,
	pay_date date null
);");

		if (! TableExists("client_accounts"))
			SqlQuery("create table ${db_prefix}client_accounts
(
	ID bigint auto_increment
		primary key,
	client_id bigint not null,
	date date not null,
	transaction_amount double not null,
	transaction_method text not null,
	transaction_ref bigint not null
)
charset=utf8;

");

		if (! TableExists("delivery")) {
			SqlQuery( "create table ${db_prefix}delivery
(
	ID bigint auto_increment
		primary key,
	date date not null,
	order_id bigint not null,
	vat float not null,
	total float not null,
	dlines int(5) default 0 not null,
	fee float not null,
	payment_receipt int null,
	draft bit default b'0' not null,
	draft_reason varchar(50) null
)
charset=utf8;

" );
			SqlQuery( "ALTER TABLE im_delivery
    ADD UNIQUE (order_id);
" );
		}

		$current = self::CheckInstalled( "tables");

		self::payment_info_table();

		if (!TableExists("bank_account"))
			SqlQuery("CREATE TABLE `im_bank_account` (
  `id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `number` varchar(20) NOT NULL,
  `owner` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		if (!TableExists("bank"))
			SqlQuery("CREATE TABLE `im_bank` (
                           `id` int(11) NOT NULL auto_increment,
                           `account_id` int(20) NOT NULL,
                           `date` date NOT NULL,
                           `description` varchar(200) NOT NULL,
                           `reference` int(11) NOT NULL,
                           `out_amount` float DEFAULT NULL,
                           `in_amount` float DEFAULT NULL,
                           `balance` double NOT NULL,
                           `client_name` varchar(400) DEFAULT NULL,
                           `customer_id` int(11) DEFAULT NULL,
                           `receipt` varchar(100) DEFAULT NULL,
                           `site_id` int(11) DEFAULT NULL,
                           `transaction_type` int(11) DEFAULT NULL,
                           `comment` varchar(400) DEFAULT NULL,
                       primary key (id)
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


		if (!TableExists("conversion"))
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

	}
}
