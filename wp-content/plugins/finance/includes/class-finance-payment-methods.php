<?php


class Finance_Payment_Methods {
	static function get_payment_method_name( $method_id ) {
		return sql_query_single_scalar( "SELECT name FROM im_payments WHERE `id` = " . $method_id);
	}

	static function gui_select_payment( $id, $events, $default ) {
		return Core_Html::gui_select_table( $id, "im_payments", $default, $events );
	}

}