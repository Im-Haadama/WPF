<?php


class Focus_Database extends Core_Database
{
	static function install($version, $force = false)
	{
		// Create im_info table if missing.
		self::CreateInfo();

		self::CreateFunctions($version, $force);
		self::CreateTables($version, $force);
		self::CreateViews($version, $force);
	}

	static function CreateViews($version, $force )
	{
		$current = self::CheckInstalled("Fresh", "views");

		if ($current == $version and ! $force) return true;

		self::UpdateInstalled("Fresh", "views", $version);

	}


	static function CreateTables($version, $force)
	{
		$current = self::CheckInstalled("Fresh", "functions");

		if (!table_exists("working_teams"))
			sql_query("create table im_working_teams
(
	id int auto_increment
		primary key,
	team_name varchar(40) charset utf8 null,
	manager int null,
	is_active bit default b'1' null
);

");
		if (! table_exists("task_templates"))
			sql_query("create table im_task_templates
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
		if (! self::table_exists("working"))
			sql_query("create table im_working
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
		if (! self::table_exists("company"))
			sql_query("create table im_company
(
	id int auto_increment
		primary key,
	name varchar(60) charset utf8 not null,
	admin int not null
);

");
		if (! table_exists("projects"))
			sql_query("create table im_projects
(
	ID int auto_increment
		primary key,
	project_name varchar(20) not null,
	project_contact varchar(20) not null,
	project_priority int null,
	is_active bit null,
	manager int null
)
charset=utf8;

");
		if (! table_exists("tasklist"))
			sql_query("create table im_tasklist
(
	id int auto_increment
		primary key,
	date datetime null,
	task_title varchar(100) null,
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
		if (! table_exists("working_teams"))
			sql_query("create table im_working_teams
(
	id int auto_increment
		primary key,
	team_name varchar(40) charset utf8 null,
	manager int null,
	is_active bit default b'1' null
)");
		if ($current == $version and ! $force) return true;

		sql_query("create table im_log
(
	id int auto_increment
		primary key,
	source varchar(30) null,
	time datetime not null,
	severity int null,
	message varchar(2000) null
);

");

		self::UpdateInstalled("Fresh", "tables", $version);

	}

	static function CreateFunctions($version, $force = false)
	{
		$current = self::CheckInstalled("Fresh", "functions");
		$db_prefix = get_table_prefix();


		if ($current == $version and ! $force) return true;

		self::UpdateInstalled("Fresh", "functions", $version);
	}
}