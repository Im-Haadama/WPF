<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/19
 * Time: 11:36
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once (ROOT_DIR . "/im-config.php");
// print "host=" . DB_HOST . "<br/>";
require_once( ROOT_DIR . '/niver/gui/sql_table.php' );

require_once( ROOT_DIR . "/init.php" );
init();

$version = get_param( "version" );

switch ( $version ) {
	case "utf8":
		var_dump(sql_query_array_scalar("show create table im_tasklist"));
		break;
	case "aa":
		aa();
		break;
	case "all":
		basic();
		create_tasklist();
		version16();
		version17();
		version18();
		break;
	case "26":
		version26();
		break;
	case "22":
		version22();
		break;
	case "21":
		version21();
		break;
	case "20":
		version20();
		break;
	case "check2":
		print GuiTableContent( "a", "SELECT * FROM im_task_templates" );
		break;
	case "check":
		check();
		break;
	case "basic":
		basic();
		break;
	case "16":
		version16();
		break;
	case "17":
		version17();
		break;
	case "18":
		version18();
		break;
	case "tasklist":
		create_tasklist();
		break;
	default:
		die( "no valid option selected" );

}
print "done";
die ( 0 );


function version26()
{

	print gui_header(1, "task_template team");
	sql_query("ALTER TABLE im_task_templates ADD team int not null default 1;");

	print gui_header(1, "template check info");
	sql_query("alter table im_task_templates add last_check datetime;");
	print gui_header(1, "template working hours");
	sql_query("alter table im_task_templates add working_hours varchar(50);");
	print gui_header(1, "worker projects");
	sql_query("drop function worker_projects");
	sql_query("CREATE FUNCTION 	worker_projects(_user_id int)
	 RETURNS TEXT
BEGIN
	declare _project int(11);
	 declare projectList varchar(200);
	 declare finished int(11);
	
	DEClARE curProject 
    CURSOR FOR 
            SELECT project_id from im_working where user_id = _user_id;
 
    DECLARE CONTINUE HANDLER 
        FOR NOT FOUND SET finished = 1;
	
	getLoop: LOOP
        FETCH curProject INTO _project;
        IF finished = 1 THEN 
            LEAVE getLoop;
        END IF;
        -- build email list
        SET projectList = CONCAT(curProject,\",\",projectList);
    END LOOP getLoop;
    
	return projectList;	   
END;");

}
function version22()
{
//	print gui_header(1, "preq done");
//	sql_query("CREATE FUNCTION SPLIT_STRING(str VARCHAR(255), delim VARCHAR(12), pos INT)
//RETURNS VARCHAR(255)
//RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(str, delim, pos),
//       LENGTH(SUBSTRING_INDEX(str, delim, pos-1)) + 1),
//       delim, '');");


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

	print gui_header(1, "varchar preq");
	sql_query("ALTER TABLE im_tasklist modify preq varchar(200) null;");

	print gui_header(1, "is_active");
	sql_query("ALTER TABLE im_task_templates ADD is_active bit not null default 1;");
	sql_query("ALTER TABLE im_tasklist ADD is_active bit not null default 1;");
	sql_query("ALTER TABLE im_tasklist ADD team int not null default 1;");
	sql_query("ALTER TABLE im_working ADD is_active bit not null default 1;");

	print gui_header(1, "save mission path");
	sql_query("ALTER TABLE im_missions ADD path varchar(4000);");

	print gui_header(1, "part of basket");
	sql_query("ALTER TABLE im_delivery_lines ADD part_of_basket int;");

	print gui_header(1, "bug management");

	sql_query("ALTER TABLE im_tasklist ADD task_type int;");

	sql_query( "create table im_task_type (
	id INT NOT NULL AUTO_INCREMENT
		PRIMARY KEY,
    	description VARCHAR(40) CHARACTER SET utf8 NULL,
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
  END;

");


	sql_query("CREATE FUNCTION 	post_status(_post_id int)
	 RETURNS TEXT
BEGIN
	declare _result varchar(200);
	select post_status into _result
	from wp_posts
	where id = _post_id; 
	
	return _result;	   
END;");

	print gui_header(2, "entry comment");
	sql_query("ALTER TABLE im_working_hours ADD comment varchar(200);");

	print gui_header(1, "Working rate");
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
  END;

");


	print gui_header(1, "multisite");
	sql_query("ALTER TABLE im_multisite ADD user varchar(100);");
	sql_query("ALTER TABLE im_multisite ADD password varchar(100);");

}
function version21()
{
	print gui_header(1, "transaction types");

	if (! table_exists("im_bank_transaction_types")) {
		sql_query("ALTER TABLE im_bank ADD transaction_type int;");

		sql_query( "create table im_bank_transaction_types (
	id INT NOT NULL AUTO_INCREMENT
		PRIMARY KEY,
    	description VARCHAR(40) CHARACTER SET utf8 NULL,
	    part_id int(11))" );
	}

	sql_query("ALTER TABLE im_payments ADD accountants varchar(100);");

	sql_query("ALTER TABLE im_delivery_lines ADD a int;");

}
function version20()
{
	print gui_header(1, "company_id");
	sql_query("ALTER TABLE im_working ADD company_id int;");

	sql_query("ALTER TABLE im_working rename worker_id user_id");

	print gui_header(1, "management");

	if (! table_exists("im_working_teams")) sql_query("create table im_working_teams (
	id INT NOT NULL AUTO_INCREMENT
		PRIMARY KEY,
    	team_name VARCHAR(40) CHARACTER SET utf8 NULL,
	    manager int(11))");

	sql_query("CREATE FUNCTION 	worker_teams(_user_id int)
	 RETURNS TEXT
BEGIN
	declare _result varchar(200);
	select meta_value into _result
	from wp_usermeta
	where meta_key = 'teams' 
	and user_id = _user_id;
	
	return _result;	   
END;"
	);
}

function check() {
	sql_query( "alter table im_tasklist collate 	utf8_general_ci" );
	$result = sql_query( "show create table im_tasklist" );
	$row    = sql_fetch_row( $result );
	print $row[1];
	/*
		print sql_query_single_scalar("show create table im_task_templates");*/

}

function basic() {
	if (! table_exists("im_info"))
	sql_query( "CREATE TABLE im_info (
		info_key VARCHAR(200) NULL,
		info_data VARCHAR(200) NULL,
		id INT NOT NULL AUTO_INCREMENT
			PRIMARY KEY
	)
	;" );

	if (! table_exists("im_projects"))
	{
		sql_query("create table im_projects
(
	ID int auto_increment
		primary key,
	project_name varchar(20) charset 'utf8' not null,
	project_contact varchar(20) charset 'utf8' not null,
	project_priority int null
);
");
	}

	if (!table_exists("im_business_info"))
		sql_query("create table im_business_info
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
);

");


	if (! table_exists("im_missions"))
		sql_query("create table im_missions
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
charset=utf8;

");

}
function create_tasklist() {

	if (! table_exists ("im_working"))
		sql_query("create table im_working
(
	id int auto_increment,
	user_id int not null,
	project_id int not null,
	rate float not null,
	report bit null,
	volunteer bit null,
	day_rate float null,
	is_active bit default 1,
	company_id int,
	constraint id_2
		unique (id)
);

");
	if (! table_exists("im_company")) sql_query("create table im_company
(
	id int not null AUTO_INCREMENT PRIMARY KEY,
	name varchar(60) not null,
	admin int not null
);

");
	if (! table_exists("im_tasklist"))  sql_query( "CREATE TABLE `im_tasklist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATETIME DEFAULT NULL,
  `task_description` VARCHAR(100) CHARACTER SET utf8 DEFAULT NULL,
  `task_template` INT(11) DEFAULT NULL,
  `status` INT(11) DEFAULT '0',
  `started` DATETIME DEFAULT NULL,
  `ended` DATETIME DEFAULT NULL,
  `project_id` INT(11) DEFAULT NULL,
  `mission_id` INT(11) NOT NULL DEFAULT '0',
  `location_name` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL,
  `location_address` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL,
  `priority` INT(11) NOT NULL DEFAULT '0',
`creator` INT(11), 
 `preq` INT(11),
 `owner` INT(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1" );

	sql_query( "ALTER TABLE im_tasklist
	MODIFY `location_name` VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL;
	" );

}

// $receipt     = sql_query_single_scalar( "SELECT payment_receipt FROM im_delivery WHERE id = " . $doc_id );


function version18()
{
	print "task_template_time<br/>";

	sql_query( "drop function task_active_time;" );
	sql_query( "
CREATE FUNCTION task_active_time(`_id` INT)
  RETURNS TEXT
  BEGIN
    declare _template_id int;
    declare _working_hours, _start_hour, _end_hour varchar(50);
    declare _now integer;
    
    select task_template into _template_id
      from im_tasklist
      where id = _id;

    select (curtime() + 0) / 100 into _now;
    
    select task_template into _template_id
      from im_tasklist
      where id = _id;

    select working_hours into _working_hours
    from im_task_templates
    where id = _template_id;

    if (_working_hours is Null) THEN
      return 1;
    END IF;

    select substring_index(working_hours, \"-\", 1) * 100 into _start_hour
    from im_task_templates
      where id = _template_id;

    select substring_index(working_hours, \"-\", -1) *100 into _end_hour
    from im_task_templates
    where id = _template_id;
    
    if (_now <  _start_hour) then
        return 0;
    end if;
    if (_now > _end_hour) then 
        return 0;
    end if;
	
	return 1;
  END;
" );

	sql_query( "drop function task_template_time;" );
	sql_query( "
CREATE FUNCTION task_template_time(`_id` INT) 
RETURNS TEXT
BEGIN
    declare _template_id int;
    declare _working_hours, _start_hour, _end_hour varchar(50);
    declare _result varchar(200);
    declare _now integer;
    
    select (curtime() + 0) / 100 into _now;
    
    select task_template into _template_id
      from im_tasklist
      where id = _id;

    select working_hours into _working_hours
    from im_task_templates
    where id = _template_id;

    if (_working_hours is Null) THEN
      return \"No working hours\";
    END IF;

    select substring_index(working_hours, \"-\", 1) * 100 into _start_hour

    from im_task_templates
      where id = _template_id;

    select substring_index(working_hours, \"-\", -1) *100 into _end_hour

    from im_task_templates
    where id = _template_id;
    
    set _result = concat(_now, '<br/>');
    if (_now >=  _start_hour) then
        set _result = CONCAT(_result,  'after start ' , _start_hour , '<br/>');
     else 
        set _result = concat(_result, ' not after ', _start_hour, '<br/>');
    end if;
    if (_now <= _end_hour) then 
        set _result = CONCAT(_result, ' before end' , _end_hour, ' <br/>');
    else
        set _result = concat(_result, ' not before ', _end_hour, '<br/>');
    end if;
	
	return _result;
END;
");

	//    

	print "client_last_order, date<br/>";
	sql_query("drop function client_last_order_date");
	sql_query("create
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

	sql_query("drop function client_last_order");
	sql_query("create
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

	print "get_product_name<br/>";
	sql_query("drop function get_product_name");
	sql_query("create
    function get_product_name(_id int) returns varchar(100) charset 'utf8'
BEGIN
    declare _name varchar(100) CHARSET 'utf8';
    select post_title into _name from wp_posts where id = _id;
    return _name;
  END;

");
	print "supply_from_business<br/>";
	sql_query("drop function supply_from_business");
	sql_query("create function supply_from_business( _business_id int) returns integer
	BEGIN
		declare _supply_id integer;
		select id into _supply_id 
	        from im_supplies where business_id = _business_id; 
	    return _supply_id;
	END;
");

	print "delivery_receipt<br/>";
	sql_query("drop function delivery_receipt");
	sql_query("create function delivery_receipt( _del_id int) returns integer
	BEGIN
		declare _receipt integer;
		select payment_receipt into _receipt 
	        from im_delivery where id = _del_id; 
	    return _receipt;
	END;
");
	print "project name<br/>";
	sql_query("drop function project_name");
	sql_query("create function project_name( _project_id int) returns varchar(100) charset utf8
	BEGIN
		declare _name varchar(100) charset utf8;
		select project_name into _name 
	        from im_projects where id = _project_id; 
	    return _name;
	END;
");

	print "client balance<br/>";
	sql_query("drop function client_balance");
	sql_query("create function client_balance( _client_id int, _date date) returns float
	BEGIN
	declare _amount float;
		select sum(transaction_amount) into _amount 
	        from im_client_accounts where date <= _date 
	        and client_id = _client_id;
	    return round(_amount, 0);
	END;
");
	print "pay_date<br/>";
	sql_query("ALTER TABLE im_business_info ADD pay_date date;");
	print "month name<br/>";
	sql_query("drop function month_with_index;");
	sql_query("CREATE FUNCTION month_with_index(_date date) RETURNS VARCHAR(20) 
	BEGIN
		declare _name varchar(20);
		declare _index varchar(20);
		select month(_date) into _index;
		select monthname(_date) into _name;
		return concat(_index, ' ', _name);
	END;
		
		
	");
	print "supplier balance<br/>";


	print "template onwer";
	sql_query("alter table im_task_templates " .
	" add owner int(11), " .
	" add creator int(11); ");

	print "project_count";
	sql_query("drop function project_count");
	sql_query( "create function project_count (_project_id int, _owner_id int) returns int   
BEGIN
declare _count int;
select count(*) into _count from im_tasklist
where project_id = _project_id
and owner = _owner_id
and status = 0;

return _count;
END;" );

}

function version17() {
	print "tasklist<br/>";
	sql_query( "ALTER TABLE im_tasklist " .
	           "ADD creator INT(11), " .
	           "ADD preq INT(11), " .
	           "ADD owner INT(11); " );
	print "project priority<br/>";
	sql_query( "ALTER TABLE im_projects ADD project_priority INT(11);" );
	print "auto_order_day<br/>";
	sql_query( "ALTER TABLE im_suppliers ADD auto_order_day INT(11);" );
	sql_query( "drop function bank_amount_to_link" );
	sql_query( "create function bank_amount_to_link (_line_id int) returns float   
BEGIN
declare _sum int;
declare _amount float;
declare _linked float;
select out_amount into _amount from im_bank
where id = _line_id;

select sum(amount) into _linked from im_bank_lines
where line_id = _line_id;

IF(_linked IS NULL) then set _linked = 0;
end if;

return round(_amount + _linked, 2);
END;" );


	sql_query( "alter table im_suppliers
	add supplier_priority INT(2) DEFAULT '5';" );
//	print "tasklist<br/>";
//	sql_query("alter table
//	im_tasklist
//	add mission_id INT(11) DEFAULT '0' NOT NULL;");

	sql_query( "alter table 
	im_task_templates
	add priority INT(11) DEFAULT '0' NOT NULL;" );

//	sql_query("alter table
//	im_task_templates
//	add repeat_freq_numbers VARCHAR(200);");
//	print "task_template<br/>";
//
//	sql_query("alter table
//	im_task_templates
//	add repeat_freq VARCHAR(20);");

	print "bank_account";
	sql_query( "create table im_bank_account
(
	id int not null auto_increment
		primary key,
	name varchar(20) not null,
	number varchar(20) not null
)
;

" );
	print "supplier_display_name" . '<br/>';
	sql_query( "drop function supplier_displayname" );
	sql_query( "create function supplier_displayname (supplier_id int) returns text charset utf8  
BEGIN
declare _user_id int;
declare _display varchar(50) CHARSET utf8;
select supplier_name into _display from im_suppliers
where id = supplier_id;

return _display;
END;

" );
	print "bank_lines<br/>";
	sql_query( "create table im_bank_lines
(
	id int not null auto_increment
		primary key,
	line_id int null,
	amount float null,
	site_id int null,
	part_id int null,
	invoice int null
)
;

" );
	print "task_active_time<br/>";

	sql_query( "drop function task_active_time;" );
	sql_query( "
CREATE FUNCTION task_active_time(`_id` INT)
  RETURNS TEXT
  BEGIN
    declare _template_id int;
    declare _working_hours, _start_hour, _end_hour varchar(50);
    select task_template into _template_id
      from im_tasklist
      where id = _id;

    select (curtime() + 0) / 100 into _now;
    
    select task_template into _template_id
      from im_tasklist
      where id = _id;

    select working_hours into _working_hours
    from im_task_templates
    where id = _template_id;

    if (_working_hours is Null) THEN
      return 1;
    END IF;

    select substring_index(working_hours, \"-\", 1) * 100 into _start_hour
    from im_task_templates
      where id = _template_id;

    select substring_index(working_hours, \"-\", -1) *100 into _end_hour
    from im_task_templates
    where id = _template_id;
    
    if (_now <  _start_hour) then
        return 0;
    end if;
    if (_now > _end_hour) then 
        return 0;
    end if;
	
	return 1;
  END;
" );

	sql_query( "drop function prod_get_name;" );
	sql_query( "CREATE FUNCTION `prod_get_name`(`prod_id` INT)
	 RETURNS varchar(200) CHARSET utf8
   NO SQL
BEGIN
   declare _name varchar(50) CHARSET utf8;
   select post_title into _name from im_products
   where id = prod_id;

   return _name;
 END" );
	sql_query( "drop view im_products_draft" );

	sql_query( "create view im_products_draft as 
	SELECT
    `wp_posts`.`ID`                    AS `ID`,
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
  FROM `wp_posts`
  WHERE ((`wp_posts`.`post_type` = 'product') AND 
  (`wp_posts`.`post_status` = 'draft'));

" );

	print "task status<br/>";
	sql_query( "CREATE FUNCTION task_status(task_id INT)
  RETURNS TEXT CHARSET 'utf8'
  BEGIN
    declare _status int;
    select status into _status from im_tasklist
    where id = task_id;

    return _status;
  END;
" );

	sql_query( "create function client_displayname (user_id int) returns text charset 'utf8'
BEGIN
    declare _user_id int;
    declare _display varchar(50) CHARSET utf8;
    select display_name into _display from wp_users
    where id = user_id;

    return _display;
  END;

" );
}
// Version 1.6
function version16() {

	sql_query( "ALTER TABLE im_business_info
ADD net_amount DOUBLE;
" );
}
//
//sql_query( "ALTER TABLE im_delivery
//ADD draft_reason VARCHAR(50);
//" );

// Version 1.7
//sql_query( "ALTER TABLE im_suppliers
//drop invoice_email;
//" );

sql_query( "ALTER TABLE im_supplies
ADD picked BIT  
" );

sql_query( "ALTER TABLE im_suppliers
ADD invoice_email VARCHAR(50)  
  CHARACTER SET utf8
  COLLATE utf8_general_ci;
" );

sql_query( "ALTER TABLE im_business_info
ADD invoice_file VARCHAR(200)  
  CHARACTER SET utf8
  COLLATE utf8_general_ci

" );

sql_query( "ALTER TABLE im_business_info
   add `occasional_supplier` varchar(50) CHARACTER SET utf8 DEFAULT NULL;

" );

sql_query( "ALTER TABLE im_business_info
ADD invoice INTEGER(10);  
" );

sql_query( "ALTER TABLE im_business_info
ADD document_type INT(2) DEFAULT '1' NOT NULL;
" );

sql_query( "ALTER TABLE im_business_info
MODIFY week DATE;
" );

sql_query( "ALTER TABLE im_business_info
MODIFY delivery_fee FLOAT;
" );

sql_query( "ALTER TABLE im_suppliers
ADD auto_order_day INT(11),
add invoice_email VARCHAR(50);
" );

sql_query( "ALTER TABLE im_suppliers
ADD auto_order_day INT(11),
ADD invoice_email VARCHAR(50);
" );

function drop_im_projects()
{
	sql_query("drop table im_projects");
}
function aa()
{
	sql_query("drop function supplier_balance");
	$sql = "create function supplier_balance (_supplier_id int, _date date) returns float   
BEGIN
declare _amount float;
select sum(amount) into _amount from im_business_info
where part_id = _supplier_id
and date <= _date
and is_active = 1
and document_type in (" . ImDocumentType::bank . "," . ImDocumentType::invoice . "," . ImDocumentType::refund . "); 

return round(_amount, 0);
END;";
	sql_query($sql);

}

print "done";

