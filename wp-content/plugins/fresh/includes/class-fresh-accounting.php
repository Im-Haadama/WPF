<?php


class Fresh_Accounting
{
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function init_hooks($loader)
	{
		$loader->AddFilter("finance_weekly_update_row", $this, null, 10, 2);
	}

	function finance_weekly_update_row($row, $delivery_id)
	{
//		print "del_id:$delivery_id<br/>";
		$delivery = new Fresh_Delivery($delivery_id);
		//	 Todo: move it to a fresh filter.
		$row['due_vat'] = $delivery->getDeliveryDueVat(); // -  $delivery->DeliveryFee();
//		$due_vat +=$rows_data[ $delivery_id ]['due_vat'];
		$row['fresh'] = $row['amount'] - $row['due_vat'];
		return $row;
	}
}