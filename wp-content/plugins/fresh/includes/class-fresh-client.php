<?php


class Fresh_Client {

	private $user_id;

	/**
	 * Fresh_Client constructor.
	 *
	 * @param $user_id
	 */
	public function __construct( $user_id ) {
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

}

