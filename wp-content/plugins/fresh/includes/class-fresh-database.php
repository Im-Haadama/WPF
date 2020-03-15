<?php


class Fresh_Database extends Core_Database
{
	static function install($version, $force = false)
	{
		// Create im_info table if missing.
		self::CreateInfo();

		self::CreateFunctions($version, $force);
		self::CreateTables($version, $force);
	}

	static function CreateTables($version, $force)
	{
		$current = self::CheckInstalled("Fresh", "functions");

		if ($current == $version and ! $force) return true;

		if (! table_exists("im_suppliers")) {
			sql_query( "create table im_suppliers
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

	sql_query("ALTER TABLE im_suppliers AUTO_INCREMENT = 100001");
}

		if (! table_exists("im_supplier_price_list"))
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

	static function CreateFunctions($version, $force = false)
	{
		$current = self::CheckInstalled("Fresh", "functions");

		if ($current == $version and ! $force) return true;

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

		self::UpdateInstalled("Fresh", "functions", $version);
	}

	/* temp: convert supplier name to id in products */
	static function convert_supplier_name_to_id()
	{
		$suppliers = sql_query_array("select id, supplier_name from im_suppliers");
		foreach ($suppliers as $supplier_tuple){
			$supplier_id = $supplier_tuple[0];
			$supplier_name = $supplier_tuple[1];
			sql_query("update wp_postmeta set meta_value = $supplier_id, meta_key = 'supplier_id' ".
			          " where meta_key = 'supplier_name' and meta_value = '" . $supplier_name . "'");
		}
	}

	/*-- Start create payment table --*/
	static function payment_info_table(){
		print 1/0;
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
	    `cvv_number` varchar(20) NOT NULL,
	    `id_number` varchar(15)  NOT NULL,
	    `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
	) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	/*-- End create payment table --*/
}