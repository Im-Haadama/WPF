<?php


class Finance_Payment_Methods {
	static function get_payment_method_name( $method_id ) {
		return sql_query_single_scalar( "SELECT name FROM im_payments WHERE `id` = " . $method_id);
	}

}