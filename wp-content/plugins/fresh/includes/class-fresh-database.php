<?php

if (! class_exists("Core_Database"))
	return;

class Fresh_Database extends Core_Database
{

	/**
	 * Fresh_Database constructor.
	 */
	public function __construct() {
		parent::__construct("Fresh");
	}

	function CreateViews($version, $force )
	{
		$current = $this->checkInstalled("views");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery("create OR REPLACE view im_products_w_drafts as select `wp_posts`.`ID`                    AS `ID`,
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
where ((`wp_posts`.`post_type` in ('product', 'product_variation')));

");


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
from `${db_prefix}delivery_lines` `dl`
where `dl`.`delivery_id` > 503
group by 1
order by 1;");

		return self::UpdateInstalled( "views", $version);

	}

	function CreateTables($version, $force)
	{
		$current = $this->checkInstalled( "tables");
		$db_prefix = GetTablePrefix();

		if ($current) {
			switch ( $current ) {
				case '1.1':
					return SqlQuery("alter table im_payment_info add user_id integer(11)") and
					       self::UpdateInstalled("tables", $version);
			}
		}
		if ($current == $version and ! $force) return true;

		SqlQuery("alter table im_supplier_price_list add product_id integer(11)");

		SqlQuery("alter table ${db_prefix}mission_types add default_price float");

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
	q_min int not null7
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

		self::UpdateInstalled( "tables", $version);
	}

	function CreateFunctions($version, $force = false)
	{
		$current = $this->checkInstalled("functions");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

//		new Fresh_Delivery(0); // Load classes

		SqlQuery("drop function if exists supplier_balance");
		$sql = "create function supplier_balance (_supplier_id int, _date date) returns float   
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


		SqlQuery("drop function supplier_from_business");
		SqlQuery("create
     function supplier_from_business(bus_id int) returns text CHARSET utf8
BEGIN
    declare _supplier_id int;
    declare _display varchar(50) CHARSET utf8;
    SELECT part_id INTO _supplier_id FROM im_business_info where id = bus_id;
    select supplier_name into _display from im_suppliers where id = _supplier_id;

    return _display;
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

		SqlQuery("drop function supplier_last_pricelist_date;");

		SqlQuery("create function supplier_last_pricelist_date(_supplier_id int) returns date
		BEGIN
		declare _date date;
		SELECT info_data into _date
		FROM im_info WHERE info_key = concat('import_supplier_', _supplier_id);
		return _date;
		END");

		SqlQuery("create function order_mission_date(order_id int) returns date
		BEGIN
			declare _mission_id int;
			declare _date date;
			select meta_value into _mission_id
			from wp_postmeta
			where meta_key = 'mission_id'
			and post_id = order_id;
			select date into _date from ${db_prefix}missions 
			where id = _mission_id;
			return _date;
		END;
		");

//		SqlQuery("create function supplier_last_pricelist_date(_supplier_id int) returns date
//		BEGIN
//		declare _date date;
//		select max(date) into _date from im_supplier_price_list where supplier_id = _supplier_id;
//		return _date;
//		END");


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

//		new Fresh_Delivery(0); // Load classes
		SqlQuery("drop function if exists supplier_balance");
		$sql = "create function supplier_balance (_supplier_id int, _date date) returns float   
BEGIN
declare _amount float;
select sum(amount) into _amount from ${db_prefix}business_info
where part_id = _supplier_id
and date <= _date
and is_active = 1
and document_type in (" . Finance_DocumentType::bank . "," . Finance_DocumentType::invoice . "," . Finance_DocumentType::refund . "); 

return round(_amount, 0);
END;";
		SqlQuery($sql);

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
		self::UpdateInstalled( "functions", $version);
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

}