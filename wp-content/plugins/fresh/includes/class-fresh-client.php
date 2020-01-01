<?php


class Fresh_Client {
	private $client_id;

	public function __construct($id) {
		$this->client_id = $id;;
	}

	function get_payment_method_name( ) {
		if ( $this->client_id > 0 ) {
			$p = self::get_payment_method( $this->client_id );
			if ( $p > 0 ) {
				return sql_query_single_scalar( "SELECT name FROM im_payments WHERE `id` = " . $p );
			}
			print "לא נבחר אמצעי ברירת מחדל<br/>";
		} else {
			return "לא נבחר לקוח";
		}
	}

	function get_payment_method( ) {
		$m = get_user_meta( $this->client_id, "payment_method", true );
		if ( $m ) {
			return $m;
		}

		$p = sql_query_single_scalar( "SELECT id FROM im_payments WHERE `default` = 1" );
		if ( $p ) {
			return $p;
		} else {
			return "לא נבחר אמצעי ברירת מחדל";
		}
	}


}