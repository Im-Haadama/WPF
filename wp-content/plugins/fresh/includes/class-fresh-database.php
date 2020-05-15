<?php

if (! class_exists("Core_Database"))
	return;

class Fresh_Database extends Core_Database
{
	static function CreateViews($version, $force )
	{
		$current = self::CheckInstalled("Fresh", "views");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery("CREATE OR REPLACE view ${db_prefix}categories as select `wp_terms`.`term_id`    AS `term_id`,
       `wp_terms`.`name`       AS `name`,
       `wp_terms`.`slug`       AS `slug`,
       `wp_terms`.`term_group` AS `term_group`
from `wp_terms`
where `wp_terms`.`term_id` in (select `wp_term_taxonomy`.`term_id`
                                        from `wp_term_taxonomy`
                                        where (`wp_term_taxonomy`.`taxonomy` = 'product_cat'));

");

		SqlQuery("create table ${db_prefix}supplier_mapping
(
	id bigint auto_increment
		primary key,
	product_id bigint not null,
	supplier_id bigint not null,
	supplier_product_name varchar(40) not null,
	pricelist_id int(4) default 0 not null,
	supplier_product_code int null,
	selected tinyint(1) null
) charset=utf8");


		SqlQuery("create OR REPLACE view im_products as select `wp_posts`.`ID`                    AS `ID`,
       `wp_posts`.`post_author`           AS `post_author`,
       `wp_posts`.`post_date`             AS `post_date`,
       `wp_posts`.`post_date_gmt`         AS `post_date_gmt`,
       `wp_posts`.`post_content`          AS `post_content`,
       `wp_posts`.`post_title`            AS `post_title`,
       `wp_posts`.`post_excerpt`          AS `post_excerpt`,
       `wp_posts`.`post_status`           AS `post_status`,
       `wp_posts`.`comment_status`        AS `comment_status`,
       `wp_posts`.`ping_status`           AS `ping_status`,
       `wp_posts`.`post_password`         AS `post_password`,
       `wp_posts`.`post_name`             AS `post_name`,
       `wp_posts`.`to_ping`               AS `to_ping`,
       `wp_posts`.`pinged`                AS `pinged`,
       `wp_posts`.`post_modified`         AS `post_modified`,
       `wp_posts`.`post_modified_gmt`     AS `post_modified_gmt`,
       `wp_posts`.`post_content_filtered` AS `post_content_filtered`,
       `wp_posts`.`post_parent`           AS `post_parent`,
       `wp_posts`.`guid`                  AS `guid`,
       `wp_posts`.`menu_order`            AS `menu_order`,
       `wp_posts`.`post_type`             AS `post_type`,
       `wp_posts`.`post_mime_type`        AS `post_mime_type`,
       `wp_posts`.`comment_count`         AS `comment_count`
from `wp_posts`
where ((`wp_posts`.`post_type` in ('product', 'product_variation')) and
       (`wp_posts`.`post_status` = 'publish'));

");

		SqlQuery("
		create OR REPLACE view i_in as select `l`.`product_id` AS `product_id`, sum(`l`.`quantity`) AS `q_in`
from (`im_supplies_lines` `l`
         join .`im_supplies` `s`)
where `l`.`supply_id` > 393
      and `l`.`status` < 8
          and `s`.`status` < 9
              and `s`.`id` = `l`.`supply_id`
group by 1;");

		SqlQuery("create OR REPLACE view i_out as select `dl`.`prod_id` AS `prod_id`, round(sum(`dl`.`quantity`), 1) AS `q_out`
from `im_delivery_lines` `dl`
where `dl`.`delivery_id` > 503
group by 1
order by 1;");

		self::UpdateInstalled("Fresh", "views", $version);

	}


	static function CreateTables($version, $force)
	{
		$current = self::CheckInstalled("Fresh", "functions");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		if (! TableExists("client_accounts"))
			SqlQuery("create table im_client_accounts
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
		self::payment_info_table();

		SqlQuery("alter table ${db_prefix}mission_types add default_price float");

		SqlQuery("create table ${db_prefix}mission_types
(
	id int auto_increment
		primary key,
	mission_name varchar(20) null)
	charset = utf8");

		SqlQuery("create table im_distance
(
	id int auto_increment
		primary key,
	distance int null,
	duration int null,
	address_a varchar(50) null,
	address_b varchar(50) null
)
engine=MyISAM charset=utf8;

");

		SqlQuery("create table im_client_types
(
	id int auto_increment
		primary key,
	type varchar(20) null,
	rate float null,
	is_group bit default b'0' not null,
	dry_rate float not null,
	q_min int not null
)
");

		SqlQuery("alter table ${db_prefix}missions drop path_code");
		SqlQuery("alter table ${db_prefix}missions add mission_type int");


		SqlQuery("alter table wp_woocommerce_shipping_zone_methods add mission_code varchar(10);");

		SqlQuery("alter table wp_woocommerce_shipping_zones add min_order float, add default_rate float");

		SqlQuery("create table im_bundles
(
	id bigint(10) unsigned auto_increment
		primary key,
	prod_id bigint(10) unsigned not null,
	quantity float unsigned not null,
	margin varchar(10) not null,
	bundle_prod_id int(10) not null,
	is_active bit not null
)
engine=MyISAM charset=utf8;

");

		SqlQuery("create table im_supplies_lines
(
	id bigint(10) auto_increment
		primary key,
	status int(4) default 1 not null,
	supply_id bigint(10) not null,
	product_id int(10) not null,
	quantity float not null,
	units int null,
	collected float null,
	price float not null
)
charset=utf8;

");

		SqlQuery("drop function delivery_receipt");
		SqlQuery("create function delivery_receipt(_del_id int) returns int
BEGIN
		declare _receipt integer;
		select payment_receipt into _receipt 
	        from im_delivery where id = _del_id; 
	    return _receipt;
	END;
");

		SqlQuery("drop function order_from_delivery");
		SqlQuery("create function order_from_delivery(del_id int) returns text
BEGIN
    declare _order_id int;
    SELECT order_id INTO _order_id FROM im_delivery where id = del_id;

    return _order_id;
END;

");

		SqlQuery("drop function client_payment_method");
		SqlQuery("create function client_payment_method(_user_id int) returns text charset utf8
BEGIN
    declare _method_id int;
    declare _name VARCHAR(50) CHARSET 'utf8';
    select meta_value into _method_id from wp_usermeta where user_id = _user_id and meta_key = 'payment_method';
    select name into _name from im_payments where id = _method_id;

    return _name;
  END;

");

		SqlQuery("drop function client_balance");
		SqlQuery("create function client_balance(_client_id int, _date date) returns float
BEGIN
    declare _amount float;
select sum(transaction_amount) into _amount
from im_client_accounts where date <= _date
                          and client_id = _client_id;
return round(_amount, 0);
END;

");

		if (! TableExists("delivery_lines"))
		SqlQuery("create table ${db_prefix}delivery_lines
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
	part_of_basket int null,
	a int null
);

");


		if (! TableExists("payments"))
		SqlQuery("create table ${db_prefix}payments
(
	id int auto_increment,
	name varchar(20) null,
	`default` bit default b'0' null,
	accountants varchar(100) null,
	constraint im_payments_id_uindex
		unique (id)
)

");

		if (! TableExists("supplies"))

		SqlQuery("create table ${db_prefix}supplies
(
	id bigint(10) unsigned auto_increment
		primary key,
	status tinyint(2) not null,
	date datetime not null,
	supplier int(4) not null,
	text varchar(500) null,
	business_id int null,
	paid_date date null,
	mission_id int null,
	picked bit default b'0' not null
)
");


		if (! TableExists("bundles"))
		SqlQuery("create table ${db_prefix}bundles
(
	id bigint(10) unsigned auto_increment
		primary key,
	prod_id bigint(10) unsigned not null,
	quantity float unsigned not null,
	margin varchar(10) not null,
	bundle_prod_id int(10) not null,
	is_active bit not null
)

");

		if (!TableExists("need"))
		SqlQuery("create table ${db_prefix}need
(
	id int auto_increment
		primary key,
	prod_id int null,
	need_q float null,
	need_u int null
)
");

		if (! TableExists("need_orders"))
		SqlQuery("create table ${db_prefix}need_orders
(
	id int auto_increment
		primary key,
	order_id int null
)
");

		if (! TableExists("delivery_lines"))
		SqlQuery("create table ${db_prefix}delivery_lines
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
	part_of_basket int null,
	a int null
) charset=utf8;
");

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

		if (! TableExists("supplier_price_list"))
				SqlQuery("create table im_supplier_price_list
(
	ID bigint auto_increment,
	product_name varchar(40) not null,
	supplier_id bigint not null,
	date date not null,
	price float not null,
	supplier_product_code varchar(100) default '10' null,
	line_status int default 1 null,
	variation bit default b'0' null,
	parent_id int null,
	sale_price float null,
	category varchar(100) null,
	picture_path varchar(200) null,
	constraint ID_3
		unique (ID)
)
charset=utf8;

");

		if (! TableExists("delivery"))
			SqlQuery("create table ${db_prefix}delivery
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
	driver int not null,
	draft bit default b'0' not null,
	draft_reason varchar(50) null
)
charset=utf8;

");

		if (! TableExists("missions"))
		{
			SqlQuery("create table ${db_prefix}missions
(
	id int auto_increment
		primary key,
	date date null,
	start_h time(6) null,
	end_h time(6) null,
	zones_times longtext null,
	name varchar(200) null,
	path_code varchar(10) null,
	start_address varchar(50) null,
	end_address varchar(50) null,
	path varchar(4000) null,
	accepting bit default b'1' null
)
charset=utf8;
");
		}

		if (! TableExists("baskets"))
			SqlQuery("create table ${db_prefix}baskets
(
	id int auto_increment
		primary key,
	basket_id bigint not null,
	date datetime not null,
	product_id int null,
	quantity tinyint(1) unsigned default 1 not null
)
charset=utf8;

");

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

		if (! TableExists("missions"))
			SqlQuery("create table ${db_prefix}missions
(
	id int auto_increment
		primary key,
	date date null,
	name varchar(200) null,
	path_code varchar(20) null,
	start_address varchar(50) null,
	end_address varchar(50) null,
	start_h time null,
	end_h time null,
	zones varchar(100) null
)
charset=utf8;");

		self::UpdateInstalled("Fresh", "tables", $version);

	}

	static function CreateFunctions($version, $force = false)
	{
		$current = self::CheckInstalled("Fresh", "functions");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery("drop function supplier_last_pricelist_date;");

		SqlQuery("create function supplier_last_pricelist_date(_supplier_id int) returns date
		BEGIN
		declare _date date;
		select max(date) into _date from im_supplier_price_list where supplier_id = _supplier_id;
		return _date;
		END");

		SqlQuery("drop function client_id_from_delivery");
		SqlQuery("create  function client_id_from_delivery(del_id int) returns text
BEGIN
  declare _order_id int;
  declare _user_id int;
  declare _display varchar(50) CHARSET utf8;
  SELECT order_id INTO _order_id FROM im_delivery where id = del_id;
  select meta_value into _user_id from wp_postmeta
  where post_id = _order_id and
  meta_key = '_customer_user';
  
  return _user_id;
END;

");

		SqlQuery("drop function client_last_order");
		SqlQuery("create
    function client_last_order(_id int) returns integer
BEGIN
	declare _last_order_id integer;
	SELECT max(id) into _last_order_id 
        FROM `wp_posts` posts, wp_postmeta meta 
        WHERE post_status like 'wc-%' 
        and meta.meta_key = '_customer_user' and meta.meta_value = _id 
        and meta.post_id = posts.ID;
	        
	return _last_order_id;	   
END;

");

		SqlQuery("drop function client_last_order_date");
		SqlQuery("create
    function client_last_order_date(_id int) returns date
BEGIN
	declare _date date;
	declare _last_order_id integer;
	SELECT max(id) into _last_order_id 
        FROM `wp_posts` posts, wp_postmeta meta 
        WHERE post_status like 'wc-%' 
        and meta.meta_key = '_customer_user' and meta.meta_value = _id 
        and meta.post_id = posts.ID;
	        
	select post_date into _date
	from wp_posts where id = _last_order_id;
	
	return _date;	   
END;

");

		SqlQuery("drop function supply_from_business");
		SqlQuery("create function supply_from_business( _business_id int) returns integer
	BEGIN
		declare _supply_id integer;
		select id into _supply_id 
	        from ${db_prefix}supplies where business_id = _business_id; 
	    return _supply_id;
	END;
");

		new Fresh_Delivery(0); // Load classes
		SqlQuery("drop function if exists supplier_balance");
		$sql = "create function supplier_balance (_supplier_id int, _date date) returns float   
BEGIN
declare _amount float;
select sum(amount) into _amount from ${db_prefix}business_info
where part_id = _supplier_id
and date <= _date
and is_active = 1
and document_type in (" . FreshDocumentType::bank . "," . FreshDocumentType::invoice . "," . FreshDocumentType::refund . "); 

return round(_amount, 0);
END;";
		SqlQuery($sql);

		SqlQuery("drop function client_from_delivery");
		SqlQuery("create function client_from_delivery(del_id int) returns text CHARSET 'utf8'
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

		SqlQuery("drop function if exists  first_day_of_week");
		SqlQuery("create function FIRST_DAY_OF_WEEK(day date) returns date
BEGIN
  RETURN SUBDATE(day, WEEKDAY(day) + 1);
END;

");

		SqlQuery("create function client_displayname(user_id int) returns text CHARSET 'utf8'
BEGIN
    declare _user_id int;
    declare _display varchar(50) CHARSET utf8;
    select display_name into _display from wp_users
    where id = user_id;

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

		SqlQuery("drop function if exists  post_status");
		SqlQuery("CREATE FUNCTION 	post_status(_post_id int)
	 RETURNS TEXT
BEGIN
	declare _result varchar(200);
	select post_status into _result
	from wp_posts
	where id = _post_id; 
	
	return _result;	   
END;");

		SqlQuery("drop function if exists  product_price");
		SqlQuery("create
    function product_price(_id int) returns varchar(100) charset 'utf8'
BEGIN
    declare _price varchar(100) CHARSET 'utf8';
    select meta_value into _price from wp_postmeta where meta_key = '_price' and post_id = _id;
    return _price;
  END; ");

		SqlQuery("drop function if exists  supplier_displayname");
		SqlQuery( "create function supplier_displayname (supplier_id int) returns text charset utf8  
BEGIN
declare _user_id int;
declare _display varchar(50) CHARSET utf8;
select supplier_name into _display from ${db_prefix}suppliers
where id = supplier_id;

return _display;
END;

" );

		SqlQuery("drop function if exists  order_is_group");
		SqlQuery("create function order_is_group(order_id int) returns bit
BEGIN
    declare _customer_type varchar(20);
    declare _user_id int;
    declare _customer_is_group bit;
    
SELECT meta_value 
INTO _user_id 
FROM wp_postmeta 
where post_id = order_id and meta_key = '_customer_user';

select meta_value 
into _customer_type
from wp_usermeta
where user_id = _user_id and meta_key = '_client_type';

select is_group
into _customer_is_group
from im_client_types
where type = _customer_type;

return _customer_is_group;

END;

");
		SqlQuery( "drop function if exists  prod_get_name;" );
		SqlQuery( "CREATE FUNCTION `prod_get_name`(`prod_id` INT)
	 RETURNS varchar(200) CHARSET utf8
   NO SQL
BEGIN
   declare _name varchar(50) CHARSET utf8;
   select post_title into _name from im_products
   where id = prod_id;

   return _name;
 END" );

		SqlQuery("drop function if exists  order_line_get_variation");
		SqlQuery("create function order_line_get_variation(_order_item_id int) RETURNS text
BEGIN
    declare _variation int;
    select meta_value into _variation from wp_woocommerce_order_itemmeta
    where order_item_id = _order_item_id
      and meta_key = '_variation_id';

    return _variation;
  END;

");
		self::UpdateInstalled("Fresh", "functions", $version);
	}

	/* temp: convert supplier name to id in products */
	static function convert_supplier_name_to_id()
	{
		$suppliers = SqlQueryArray("select id, supplier_name from im_suppliers");
		foreach ($suppliers as $supplier_tuple){
			$supplier_id = $supplier_tuple[0];
			$supplier_name = $supplier_tuple[1];
			SqlQuery( "update wp_postmeta set meta_value = $supplier_id, meta_key = 'supplier_id' " .
			          " where meta_key = 'supplier_name' and meta_value = '" . $supplier_name . "'");
		}
	}

	/*-- Start create payment table --*/
	static function payment_info_table(){
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `im_payment_info` (
	    `id` int(11) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
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