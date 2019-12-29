<?php


class Fresh_Inventory {

	static function handle()
	{
		$operation = get_param("operation", false, "show_status");

		print self::handle_operation($operation);


	}

	static function handle_operation($operation)
	{
		switch ($operation){
		case "show_status":
			$year = (date('m') < 12 ? date('Y') - 1 : date('Y'));
			return self::show_status($year);
			break;
		}
	}

	static function show_status($year)
	{
		$result = gui_header(1, "Inventory for 31 Dec $year");

		$suppliers = sql_query_array_scalar("select id from im_suppliers");
		$status_table = array(array("supplier id", "status"));

		foreach ($suppliers as $supplier_id) {
			$status = "not entered";
			if (sql_query_single_scalar("select count(*) from im_inventory_count where supplier_id = $supplier_id and year(count_date) = $year")) $status = "entered";
			array_push($status_table, array("id" => $supplier_id, "supplier_name" => get_supplier_name($supplier_id), "status" => $status));
		}

		$args = array("links" => array("id" => "/fresh/inventory/display.php?supplier_id=%d"));

		$result .= gui_table_args($status_table, "inventory_status", $args);
		return $result;
	}
}