<?php

require_once dirname( FRESH_PLUGIN_FILE ) . '/includes/core/gui/inputs.php';
require_once dirname(FRESH_PLUGIN_FILE) . '/includes/suppliers/suppliers.php';

class Fresh_Suppliers {

	static function status()
	{
//		$result = gui_header(1, "Suppliers");
		$result = self::SuppliersTable();

//		$result .= $this->SupplyStatus();
//		$result .= $this->PaymentStatus();
//		$result .= $this->SupplierStatus();

		print $result;
	}

//	function OrdersStatus()
//	{
//		$result = "";
//
//		$order_status = sql_query_array("select count(*), post_status
//from wp_posts
//where post_status like 'wc%' and post_status not in ('wc-cancelled', 'wc-completed')
//group by post_status");
//
//		//in ('	wc-awaiting-shipment', 'wc-pending', 'wc-processing')
//		$args = [];
//		foreach ($order_status as $status => $info) {
//			$status_parts = explode("-", $order_status[$status][1]);
//			unset($status_parts[0]); // remove wc;
//			$text_status = ucfirst(implode(" ", $status_parts));
//			$order_status[$status] [0] = GuiHyperlink($order_status[$status][0], add_to_url(array("operation" => "show_orders", "status" => $info[1])));
//			$order_status[ $status ][1] = $text_status;
//		}
//		$result .= gui_header(2, "Orders");
//
//		array_unshift($order_status, array(im_translate("Count"), im_translate("Status")));
//
//		$result .= gui_table_args($order_status, "orders_status", $args);
//
//		return $result;
//	}

//	function Status()
//	{
//		$result = "";
//
//		$supply_status = sql_query_array("select count(*), status
//from im_supplies
//where status > 0 and status < 5
//group by status");
//
////		var_dump($supply_status);
//		//in ('	wc-awaiting-shipment', 'wc-pending', 'wc-processing')
//		$args = [];
//		foreach ($supply_status as $row_number => $info) {
//			$status = $supply_status[$row_number][1];
//			$supply_status[$row_number] [0] = GuiHyperlink($supply_status[$row_number][0], add_to_url(array("operation" => "show_supplies", "status" => $status)));
//			$supply_status[ $row_number ][1] = im_translate(get_supply_status($status));
//		}
//		$result .= gui_header(2, "Supply");
//
//		array_unshift($supply_status, array(im_translate("Count"), im_translate("Status")));
//
//		$result .= gui_table_args($supply_status, "orders_status", $args);
//
//		return $result;
//	}

	static function SuppliersTable()
	{
		$result = gui_header(1, "Active suppliers");

		$args = [];
		$args["fields"] = array("id", "supplier_name", "supplier_description");
		$args["links"] = array("id"=> add_to_url(array("operation" => "show_supplier", "id" => "%s")));
		$args["where"] = "active = 1";

		$result .= GuiTableContent("im_suppliers",null, $args);

		return $result;
	}

	static function handle()
	{
		$operation = get_param("operation", false, null);

		if (! $operation) {
			print self::SuppliersTable();
			return;
		}
		$args = array("post_file" => plugin_dir_url(dirname(__FILE__)) . "post.php");

		handle_supplier_operation($operation, $args);
	}


	public function enqueue_scripts() {
		$file = plugin_dir_url( __FILE__ ) . 'core/gui/client_tools.js';
//		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'orders/orders.js', array( 'jquery' ), $this->version, false );
////		wp_localize_script( $this->plugin_name, 'WPaAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
////		if (! file_exists($file) and get_user_id() == 1) print $file . " not exists <br/>";
//		wp_enqueue_script( 'client_tools', $file, array( 'jquery' ), $this->version, false );

	}


}
