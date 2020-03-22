<?php


class Fresh_Client {

	private $user_id;

	/**
	 * Fresh_Client constructor.
	 *
	 * @param $user_id
	 */
	public function __construct( $user_id = 0 ) {
		if (! get_user_id()) return null;
		if (! $user_id) $user_id = get_user_id();
		$this->user_id = $user_id;
	}

	public function balance() {
		$sql = 'select sum(transaction_amount) '
		       . ' from im_client_accounts '
		       . ' where client_id = ' . $this->user_id;

		return round( sql_query_single_scalar( $sql ), 2 );
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


}

function Sunday( $date ) {
	$datetime = new DateTime( $date );
	$interval = new DateInterval( "P" . $datetime->format( "w" ) . "D" );
	$datetime->sub( $interval );

	return $datetime;
}
