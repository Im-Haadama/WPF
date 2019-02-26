<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/19
 * Time: 15:34
 */
class BankTransaction {
	private $id;
	private $in_amount;
	private $date;

	static function createFromDB( $id ) {
		$sql    = "SELECT in_amount, date FROM im_bank WHERE id = " . $id;
		$result = sql_query( $sql );
		if ( ! $result ) {
			throw new Exception( "Transaction not found" );
		}

		$row = sql_fetch_row( $result );

		if ( ! $row ) {
			throw new Exception( "Transaction not found" );
		}
		$r = new BankTransaction();

		$r->id        = $id;
		$r->in_amount = $row[0];
		$r->date      = $row[1];

		return $r;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getInAmount() {
		return $this->in_amount;
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	public function Update( $customer_id, $reference ) {
		$sql = "UPDATE im_bank SET customer_id = " . $customer_id .
		       ", reference = " . $reference .
		       " WHERE id = " . $this->id;

		sql_query( $sql );
	}


}