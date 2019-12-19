<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/19
 * Time: 11:36
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ABSPATH" ) ) {
	define( 'ABSPATH', dirname(dirname(dirname(dirname(dirname( dirname( __FILE__ ) ) )))) . '/');
}

require_once(ABSPATH . 'wp-config.php');
//require_once (FRESH_INCLUDES . "/im-config.php");

// print "host=" . DB_HOST . "<br/>";
//require_once( FRESH_INCLUDES . '/core/gui/sql_table.php' );
//require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );

//require_once( FRESH_INCLUDES . "/init.php" );
//init();

if (! get_user_id()) {
	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

	print '<script language="javascript">';
	print "window.location.href = '" . $url . "'";
	print '</script>';
	return;
}

if (get_user_id() !== 1 and get_user_id() !== 2) die ("no permissions: " . get_user_id());

$current_version = get_versions();

print "Installation info: $current_version<br/>";

$version = get_param( "version" );
$force = get_param("force", false, false);

add_version($version, $force);

function add_version($version, $force = false)
{
	if (! $force){
		$exists = sql_query_single_scalar("select count(*) from im_versions where version = '$version'");
		if ($exists >= 1) {
			print "Version already installed<br/>";
			return true;
		}
	}
	$description = null;

	print "version = $version<br/>";
	switch ( $version ) {
		case "29":
			print "start";
			$description = version29();
			print "after";
			var_dump($description);
			break;
		case "28":
			$description = version28();
			break;
		case "aa":
			$description = aa();
			break;
		case "27":
			$description = version27();
			break;
		case "26":
			$description = version26();
			break;
		case "22":
			$description = version22();
			break;
		case "21":
			$description = version21();
			break;
		case "20":
			$description = version20();
			break;
		case "check2":
			print GuiTableContent( "a", "SELECT * FROM im_task_templates" );
			break;
		case "check":
			check();
			break;
		case "basic":
			$description = basic();
			break;
		case "16":
			$description = version16();
			break;
		case "17":
			$description = version17();
			break;
		case "18":
			$description = version18();
			break;
		case "tasklist":
			$description = create_tasklist();
			break;
		default:
			die( "no valid option selected: $version" );
	}
	if (is_string($description))
		sql_query("insert into im_versions(version, description, install_date) values ('$version', '$description', now())");
	return true;
}

print "done";
die ( 0 );

function version28()
{
	if (! add_version("27"))
		die ("can't install 2.7");
	print gui_header(1, "install date");
	sql_query("alter table im_versions add install_date date;");

	print gui_header(1, "zone_times");
	sql_query("alter table im_paths change zones zones_times longtext null;");
	sql_query("alter table im_missions change zones zones_times longtext null;");

	print gui_header(1, "function template_last_task");
	sql_query("CREATE FUNCTION 	template_last_task(_template_id int)
	 RETURNS date
BEGIN
	declare _result date;

	select max(date) into _result from im_tasklist where task_template = _template_id;

	return _result;	   
END;");

	print gui_header(1, "shipping codes");
//	sql_query("drop table im_paths");
	sql_query( "create table im_paths (
	id INT NOT NULL AUTO_INCREMENT	PRIMARY KEY,
	path_code varchar(10) character set utf8 not null,
    	description VARCHAR(40) CHARACTER SET utf8 NULL,
    	week_days varchar(40),
	    zones varchar(200))"
	);

	// sql_query("alter table im_paths add end_h time");
	return "Update shipping methods";
}
function version27()
{
	if (! add_version("26"))
		die ("can't install 2.6");

	print gui_header(1, "add company to project");
	sql_query("ALTER TABLE im_projects ADD company int not null default 1;");

	print gui_header(1, "add is_active to project");
	sql_query("ALTER TABLE im_projects ADD is_active int(1) not null default 1;");

	print gui_header(1, "first_day_of_week");
	sql_query("create function FIRST_DAY_OF_WEEK(day date) returns date
BEGIN
  RETURN SUBDATE(day, WEEKDAY(day) + 1);
END;");
	return "Focus: project-company, isactive";
}
function version26()
{
	if (! add_version("22"))
		die ("can't install 2.2");

	print gui_header(1, "worker_teams");
	sql_query("drop function worker_teams");
	sql_query("CREATE FUNCTION 	worker_teams(_user_id int)
	 RETURNS TEXT
BEGIN
	declare _result longtext;
	select meta_value into _result
	from wp_usermeta
	where meta_key = 'teams' 
	and user_id = _user_id;
	
	return _result;	   
END;"
	);

print gui_header(1, "month_with_index");
	sql_query("drop function month_with_index;");
	sql_query("CREATE FUNCTION month_with_index(_date date) RETURNS VARCHAR(20) 
	BEGIN
		declare _name varchar(20);
		declare _index varchar(20);
		select month(_date) into _index;
		select date_format(_date, '%m') into _index;
		return con1`t(_index, ' ', _name);
	END; ");

	sql_query("drop function month_with_index;");
	sql_query("CREATE FUNCTION month_with_index(_date date) RETURNS VARCHAR(20) 
	BEGIN
		declare _name varchar(20);
		declare _index varchar(20);
		select month(_date) into _index;
		select monthname(_date) into _name;
		return concat(_index, ' ', _name);
	END;	");

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
	return "Focus: project, template last_check, invoice_table sort";
}
function version22()
{
//	print gui_header(1, "preq done");
//	sql_query("CREATE FUNCTION SPLIT_STRING(str VARCHAR(255), delim VARCHAR(12), pos INT)
//RETURNS VARCHAR(255)
//RETURN REPLACE(SUBSTRING(SUBSTRING_INDEX(str, delim, pos),
//       LENGTH(SUBSTRING_INDEX(str, delim, pos-1)) + 1),
//       delim, '');");


	if (! add_version("21"))
		die ("can't install 2.1");

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
  END; ");

	print gui_header(1, "multisite");
	sql_query("ALTER TABLE im_multisite ADD user varchar(100);");
	sql_query("ALTER TABLE im_multisite ADD password varchar(100);");

	return "Multisite user/password, task_type(unfinished), task preq";
}

function version21()
{
	if (! add_version("20"))
		die ("can't install 2.0");

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

	return "transaction types";
}

function version20()
{
	if (! add_version("18"))
		die ("can't install 1.8");

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
	declare _result longtext;
	select meta_value into _result
	from wp_usermeta
	where meta_key = 'teams' 
	and user_id = _user_id;
	
	return _result;	   
END;"
	);
	return "working teams";
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
	return "basic";
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
	if (! add_version("17"))
		die ("can't install 1.7");

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
	return "task times, suppliers balance, more";
}

function version17() {
	if (! add_version("16"))
		die ("can't install 1.6");

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
	return "draft products (for late delivery notes), bank account";
}
// Version 1.6
function version16() {

	if ( ! add_version( "basic" ) ) {
		die ( "can't install basic" );
	}


	sql_query( "ALTER TABLE im_business_info
ADD net_amount DOUBLE;
" );

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

	return "business_info";
}

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

function get_versions()
{
	$versions = sql_query_array("select version, description, install_date from im_versions");
	if (is_string($versions) and substr($versions, 0, 5) === "Error"){
		sql_query( "create table im_versions (
	id INT NOT NULL AUTO_INCREMENT
		PRIMARY KEY,
		version varchar(20),
    	description VARCHAR(40) CHARACTER SET utf8 NULL,
    	install_date date)" );
		return "init";
	}
	return gui_table_args($versions);
}

function version29()
{
	print gui_header(1, "bank owner");
	sql_query("alter table im_bank_account
    add owner int(11)");

	print gui_header(1, "supplier_description");
	sql_query("alter table im_suppliers 
    add supplier_description varchar(200)");

	print gui_header(1, "mission_accepting");
	sql_query("alter table im_missions add accepting bit default 1;");


	print gui_header(1, "Herbal");

	sql_query( "create table me_clients ( " . // Client record
	           "id INT NOT NULL AUTO_INCREMENT	PRIMARY KEY, " .
	           "user_id int(11), " .
	           "symptoms longtext); " );

//	sql_query( "create table pl_plants ( " . // Plants
//	           "id INT NOT NULL AUTO_INCREMENT	PRIMARY KEY, " .
//	           "latin_name varchar(200) charset utf8, " .
//	           "hebrew_name varchar(200) charset utf8); " );
//
//	sql_query( "create table he_symptoms (" . // Health
//	           "id INT NOT NULL AUTO_INCREMENT	PRIMARY KEY, " .
//	           "latin_name varchar(200) charset utf8, " .
//	           "hebrew_name varchar(200) charset utf8); " );
//
//	sql_query( "create table he_affects (" . // How the plant affects us
//	           "id INT NOT NULL AUTO_INCREMENT	PRIMARY KEY, " .
//	           "hebrew_name varchar(200) charset utf8); " );
//
//	sql_query( "create table hc_symptoms (" . // Relation between plant, symptom and affect
//	           "id INT(11), " .
//	           "symptom_id int(11), " .
//	           "affect_id int(11), " .
//	           "plant_id int(11)); " );

	return "bank owner, supplier_description and herbal medical";
}
