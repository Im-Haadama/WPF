<?php


class Fresh_Client {

	private $user_id;
	private $user;

	/**
	 * Fresh_Client constructor.
	 *
	 * @param $user_id
	 */
	public function __construct( $user_id = 0 ) {
		if (! $user_id) {
			if (! get_user_id()) die("no user");
			$user_id = get_user_id();
		}
		$this->user_id = $user_id;
	}

	public function balance() {
		$sql = 'select sum(transaction_amount) '
		       . ' from im_client_accounts '
		       . ' where client_id = ' . $this->user_id;

		return round( sql_query_single_scalar( $sql ), 2 );
	}

	function get_payment_method( ) {
		$m = get_user_meta( $this->user_id, "payment_method", true );
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

	function customer_type( ) {
		$key = get_user_meta( $this->user_id, '_client_type', true );

		if ( is_null( $key ) ) {
			return 0;
		}

		return $key;
	}

	function add_transaction( $date, $amount, $ref, $type ) {
		$sql = "INSERT INTO im_client_accounts (client_id, date, transaction_amount, transaction_method, transaction_ref) "
		       . "VALUES (" . $this->user_id . ", \"" . $date . "\", " . $amount . ", \"" . $type . "\", " . $ref . ")";

		MyLog( $sql, "account_add_transaction" );
		sql_query( $sql );
	}

	function update_transaction( $total, $delivery_id) {
		$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
		       " WHERE transaction_ref = " . $delivery_id . " and client_id = " . $this->user_id;

		MyLog( $sql, "account_update_transaction" );
		sql_query( $sql );
	}

	function get_customer_email()
	{
		return self::get_user()->user_email;
	}

	function get_phone_number()
	{
		return get_post_meta( self::get_last_order( ), '_billing_phone', true );
	}

	function get_last_order( ) {
		return sql_query_single_scalar( " SELECT max(meta.post_id) " .
		                                " FROM `wp_posts` posts, wp_postmeta meta" .
		                                " where meta.meta_key = '_customer_user'" .
		                                " and meta.meta_value = " . $this->user_id .
		                                " and meta.post_id = posts.ID");
	}


	private function get_user()
	{
		if (! $this->user) {
			$this->user = get_user_by('id', $this->user_id);
		}
		if (! $this->user) return null;
		return $this->user;
	}

}

function Sunday( $date ) {
	$datetime = new DateTime( $date );
	$interval = new DateInterval( "P" . $datetime->format( "w" ) . "D" );
	$datetime->sub( $interval );

	return $datetime;
}
