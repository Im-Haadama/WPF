<?php


class Fresh_Database extends Core_Database
{
	static function Upgrade($version, $force = false)
	{
		self::CreateFunctions($version, $force);
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