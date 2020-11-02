<?php
class Focus_Database extends Core_Database
{
	function CreateViews($version, $force )
	{
		$current = $this->checkInstalled( "views");

		if ($current == $version and ! $force) return true;

		self::UpdateInstalled("views", $version);

	}

	function CreateTables($version, $force) {
		$current = $this->checkInstalled(  "tables" );

		if ( ! $current ) {
			// Fresh install - create all tables.
			return self::FreshInstall() and
			       self::UpdateInstalled( "tables", $version );

		}
		if ( $current == $version and ! $force ) {
			return true;
		}
		switch ($current){
			case '1.0':
				SqlQuery( "alter table im_projects add is_active bit" );

			case '1.1':
				SqlQuery( "alter table im_projects add company int" );

            case '1.2':
                SqlQuery("ALTER TABLE im_tasklist MODIFY project_id int NOT NULL");
                SqlQuery( "alter table im_projects add project_contact_id int" );
                SqlQuery( "alter table im_projects change project_contact_id project_contact_email" );
                SqlQuery("ALTER TABLE im_projects MODIFY project_contact_email varchar(50)");

		}
		return self::UpdateInstalled("tables", $version );
	}

	// Create what needed in clean install
	static function FreshInstall()
	{
		SqlQuery("drop function client_displayname");
		SqlQuery( "create function client_displayname (user_id int) returns text charset 'utf8'
BEGIN
    declare _user_id int;
    declare _display varchar(50) CHARSET utf8;
    select display_name into _display from wp_users
    where id = user_id;

    return _display; 
  END;

" );

		SqlQuery("drop function task_status");
		SqlQuery( "CREATE FUNCTION task_status(task_id INT)
  RETURNS TEXT CHARSET 'utf8'
  BEGIN
    declare _status int;
    select status into _status from im_tasklist
    where id = task_id;

    return _status;
  END;
" );


		if (!TableExists("working_teams"))
			SqlQuery("create table im_working_teams
(
	id int auto_increment
		primary key,
	team_name varchar(40) charset utf8 null,
	manager int null,
	is_active bit default b'1' null
);

");
		if (! TableExists("task_templates"))
			SqlQuery("create table im_task_templates
(
	id int auto_increment
		primary key,
	task_description varchar(200) null,
	task_url varchar(200) null,
	project_id int null,
	repeat_days varchar(200) null,
	condition_query varchar(200) null,
	repeat_time varchar(20) null,
	repeat_freq varchar(20) null,
	pathcode varchar(20) null,
	path_code varchar(20) null,
	repeat_freq_numbers varchar(200) null,
	priority int default 0 not null,
	owner int null,
	creator int null,
	team int default 1 not null,
	last_check datetime null,
	working_hours varchar(50) null,
	is_active bit default b'1' not null,
	timezone varchar(20) null
)
charset=utf8;

");
		if (! TableExists("working"))
			SqlQuery("create table im_working
(
	id int auto_increment,
	user_id int not null,
	project_id int not null,
	rate float not null,
	report bit null,
	volunteer bit null,
	company_id int null,
	is_active bit default b'1' not null,
	constraint id_2
		unique (id)
)
charset=utf8;

");
		if (! TableExists("company"))
			SqlQuery("create table im_company
(
	id int auto_increment
		primary key,
	name varchar(60) charset utf8 not null,
	admin int not null
);

");
		if (! TableExists("projects"))
			SqlQuery("create table im_projects
(
	ID int auto_increment
		primary key,
	project_name varchar(20) not null,
	project_contact varchar(20) not null,
	company int null,
	project_priority int null,
	is_active bit null,
	manager int null	
)
charset=utf8;

");
		if (! TableExists("tasklist"))
			SqlQuery("create table im_tasklist
(
	id int auto_increment
		primary key,
	date datetime null,
	task_title varchar(100) charset utf8 null,
	task_description varchar(100) charset utf8 null,
	task_template int null,
	status int default 0 null,
	started datetime null,
	ended datetime null,
	project_id int null,
	mission_id int default 0 not null,
	location_name varchar(50) charset utf8 null,
	location_address varchar(50) charset utf8 null,
	priority int default 0 not null,
	creator int null,
	preq varchar(200) null,
	owner int null,
	task_type int null,
	is_active bit default b'1' not null,
	team int default 1 not null
)
engine=InnoDB;

");
		if (! TableExists("working_teams"))
			SqlQuery("create table im_working_teams
(
	id int auto_increment
		primary key,
	team_name varchar(40) charset utf8 null,
	manager int null,
	is_active bit default b'1' null
)");

		if (! TableExists("log"))
		SqlQuery("create table im_log
(
	id int auto_increment
		primary key,
	source varchar(30) null,
	time datetime not null,
	severity int null,
	message varchar(2000) null
);

");

		return true;

	}

	function CreateFunctions($version, $force = false)
	{
		$current = $this->checkInstalled( "functions");
		$db_prefix = GetTablePrefix();

		if ($current == $version and ! $force) return true;

		SqlQuery("drop function preq_done");
		SqlQuery("CREATE FUNCTION preq_done(_task_id int)
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

		return self::UpdateInstalled("functions", $version);
	}
}