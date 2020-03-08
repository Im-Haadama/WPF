<?php

/**
 * Plugin Name: fresh (full)
 * Plugin URI: https://aglamaz.com
 * Description:  wp-f backoffice for fresh goods store management.
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 * @package Fresh
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define IM_PLUGIN_FILE.
if ( ! defined( 'FRESH_PLUGIN_FILE' ) ) {
	define( 'FRESH_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'Fresh' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-fresh.php';
}
/**
 * Main instance of Fresh.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @return Fresh
  */

function fresh() {
	return Fresh::instance();
}

function run_fresh() {
	$plugin = new Fresh("Fresh");
	$plugin->run();
}

run_fresh();

register_activation_hook(__FILE__, 'Fresh::payment_info_table');
register_activation_hook(__FILE__, 'Fresh::convert_supplier_name_to_id');

// version22();

function version22()
{
//	print Core_Html::gui_header(1, "preq done");
//	sql_query("CREATE FUNCTION SPLIT_STRING(str VARCHAR(255), delim VARCHAR(12), pos INT)
//RETURNS VARCHAR(255)
//RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(str, delim, pos),
//       LENGTH(SUBSTRING_INDEX(str, delim, pos-1)) + 1),
//       delim, '');");


//	if (! add_version("21"))
//		die ("can't install 2.1");

	sql_query("drop function preq_done");
	sql_query("CREATE FUNCTION 	preq_done(_task_id int)
	 RETURNS varchar(200)
BEGIN 
	declare _preq varchar(200);
	declare _status int;
	declare _comma_pos int;
	declare _preq_task varchar(200);
	select preq into _preq
	from im_tasklist
	where id = _task_id;
	
	while (length(_preq)) do
		set _comma_pos = locate(',', _preq);
		if (_comma_pos != 0) then
			set _preq_task =  substring(_preq, 1, _comma_pos - 1);
			set _preq = substr(_preq, _comma_pos+1); 
		else
			set _preq_task = _preq;
			set _preq = null;
		end if;
		if (task_status(_preq_task) < 2) then 
			return 0; 
		end if;
	end while;
	return 1;	   
END;");

	print Core_Html::gui_header(1, "varchar preq");
	sql_query("ALTER TABLE im_tasklist modify preq varchar(200) null;");

	print Core_Html::gui_header(1, "is_active");
	sql_query("ALTER TABLE im_task_templates ADD is_active bit not null default 1;");
	sql_query("ALTER TABLE im_tasklist ADD is_active bit not null default 1;");
	sql_query("ALTER TABLE im_tasklist ADD team int not null default 1;");
	sql_query("ALTER TABLE im_working ADD is_active bit not null default 1;");

	print Core_Html::gui_header(1, "save mission path");
	sql_query("ALTER TABLE im_missions ADD path varchar(4000);");

	print Core_Html::gui_header(1, "part of basket");
	sql_query("ALTER TABLE im_delivery_lines ADD part_of_basket int;");

	print Core_Html::gui_header(1, "bug management");

	sql_query("ALTER TABLE im_tasklist ADD task_type int;");

	sql_query( "create table im_task_type (
	id INT NOT NULL AUTO_INCREMENT
		PRIMARY KEY,
    	description longtext CHARACTER SET utf8 NULL,
	    meta_fields varchar(200))" );

	sql_query( "create table im_task_meta (
	meta_id INT NOT NULL AUTO_INCREMENT
		PRIMARY KEY,
		task_id int,
    	meta_key VARCHAR(40) CHARACTER SET utf8 NULL,
	    meta_value varchar(200))" );

	sql_query("drop function product_price");
	sql_query("create
    function product_price(_id int) returns varchar(100) charset 'utf8'
BEGIN
    declare _price varchar(100) CHARSET 'utf8';
    select meta_value into _price from wp_postmeta where meta_key = '_price' and post_id = _id;
    return _price;
  END; ");


	sql_query("CREATE FUNCTION 	post_status(_post_id int)
	 RETURNS TEXT
BEGIN
	declare _result varchar(200);
	select post_status into _result
	from wp_posts
	where id = _post_id; 
	
	return _result;	   
END;");

	print Core_Html::gui_header(2, "entry comment");
	sql_query("ALTER TABLE im_working_hours ADD comment varchar(200);");

	print Core_Html::gui_header(1, "Working rate");
	sql_query("drop function working_rate");
	sql_query("create
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
	          and project_id = _project;

    return _rate;
  END; ");

	print Core_Html::gui_header(1, "multisite");
	sql_query("ALTER TABLE im_multisite ADD user varchar(100);");
	sql_query("ALTER TABLE im_multisite ADD password varchar(100);");

	return "Multisite user/password, task_type(unfinished), task preq";
}

function view_products() {

	sql_query( "
create  view im_products as select `wp_posts`.`ID`                    AS `ID`,
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
where `wp_posts`.`post_type` in ('product', 'product_variation')
                                              and `wp_posts`.`post_status` = 'publish';
" );
}

//create_supplier_pricelist();
function create_supplier_pricelist()
{
	sql_query("create table im_supplier_price_list
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
}
//supplier_mapping();
function supplier_mapping()
{
	sql_query("create table im_supplier_mapping
(
	id bigint auto_increment
		primary key,
	product_id bigint not null,
	supplier_id bigint not null,
	supplier_product_name varchar(40) not null,
	pricelist_id int(4) default 0 not null,
	supplier_product_code int null,
	selected tinyint(1) null
);
");
}