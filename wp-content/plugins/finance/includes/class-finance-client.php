<?php


class Finance_Client {
	private $user_id;

	/**
	 * Finance_Client constructor.
	 *
	 * @param $user_id
	 */
	public function __construct( $user_id ) {
		$this->user_id = $user_id;
	}

	function add_transaction( $date, $amount, $ref, $type ) {
		$sql = "INSERT INTO im_client_accounts (client_id, date, transaction_amount, transaction_method, transaction_ref) "
		       . "VALUES (" . $this->user_id . ", \"" . $date . "\", " . $amount . ", \"" . $type . "\", " . $ref . ")";

		MyLog( $sql, "account_add_transaction" );
		return SqlQuery( $sql );
	}

	function update_transaction( $total, $delivery_id) {
		$sql = "UPDATE im_client_accounts SET transaction_amount = " . $total .
		       " WHERE transaction_ref = " . $delivery_id . " and client_id = " . $this->user_id;

		MyLog( $sql, "account_update_transaction" );
		SqlQuery( $sql );
	}

}