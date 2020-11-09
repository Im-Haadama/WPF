<?php

require_once dirname( FRESH_PLUGIN_FILE ) . '/includes/core/gui/inputs.php';

class Fresh_Status {

	function status()
	{
		$result = Core_Html::GuiHeader(1, "Store status");
		$result .= $this->OrdersStatus();
		$result .= $this->SupplyStatus();
//		$result .= $this->PaymentStatus();
//		$result .= $this->SupplierStatus();

		return $result;
	}

	function OrdersStatus()
	{
		$result = "";

		$order_status = SqlQueryArray("select count(*), post_status
from wp_posts
where post_status like 'wc%' and post_status not in ('wc-cancelled', 'wc-completed') 
group by post_status");

		//in ('	wc-awaiting-shipment', 'wc-pending', 'wc-processing')
		$args = [];
		foreach ($order_status as $status => $info) {
			$status_parts = explode("-", $order_status[$status][1]);
			unset($status_parts[0]); // remove wc;
			$text_status = ucfirst(implode(" ", $status_parts));
			$order_status[$status] [0] = GuiHyperlink($order_status[$status][0], AddToUrl(array( "operation" => "show_orders", "status" => $info[1])));
			$order_status[ $status ][1] = $text_status;
		}
		$result .= Core_Html::GuiHeader(2, "Orders");

		array_unshift($order_status, array(ETranslate("Count"), ETranslate("Status")));

		$result .= Core_Html::gui_table_args($order_status, "orders_status", $args);

		return $result;
	}

	function SupplyStatus()
	{
		$result = "";

		$supply_status = SqlQueryArray("select count(*), status
from im_supplies
where status > 0 and status < 5
group by status");

//		var_dump($supply_status);
		//in ('	wc-awaiting-shipment', 'wc-pending', 'wc-processing')
		$args = [];
		foreach ($supply_status as $row_number => $info) {
			$status = $supply_status[$row_number][1];
			$supply_status[$row_number] [0] = GuiHyperlink($supply_status[$row_number][0], AddToUrl(array( "operation" => "show_supplies", "status" => $status)));
			$supply_status[ $row_number ][1] = ETranslate(get_supply_status($status));
		}
		$result .= Core_Html::GuiHeader(2, "Supply");

		array_unshift($supply_status, array(ETranslate("Count"), ETranslate("Status")));

		$result .= Core_Html::gui_table_args($supply_status, "orders_status", $args);

		return $result;
	}

	function SupplyTable()
	{
		$status = GetParam("status", false, 1);
		$result = Core_Html::GuiHeader(1, ETranslate("Supplies in status") . " '" . ETranslate(get_supply_status($status)) . "'");

		$result .= SuppliesTable($status);

		return $result;
	}

	function ShowOrders($status)
	{
		$args = [];
		return OrdersTable1($status);
	}

}
