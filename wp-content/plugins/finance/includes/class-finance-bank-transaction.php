<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/19
 * Time: 15:34
 * Todo: for now - the code is for single company per installation
 */
class Finance_Bank_Transaction {
	private $id;
	private $in_amount;
	private $out_amount;
	private $date;

	static function createFromDB( $id ) {
		$sql    = "SELECT in_amount, date, out_amount FROM im_bank WHERE id = " . $id;
		$result = sql_query( $sql );
		if ( ! $result ) {
			throw new Exception( "Transaction not found" );
		}

		$row = sql_fetch_row( $result );

		if ( ! $row ) {
			throw new Exception( "Transaction not found" );
		}
		$r = new Finance_Bank_Transaction();

		$r->id         = $id;
		$r->in_amount  = $row[0];
		$r->date       = $row[1];
		$r->out_amount = $row[2];

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
	public function getOutAmount( $attached = false ) {
//		debug_var($this->out_amount);
		if ( ! $attached ) {
			return $this->out_amount;
		}

		$attached_amount = sql_query_single_scalar( "SELECT sum(amount) FROM im_bank_lines " .
		                                            " WHERE line_id = " . $this->id );

//		debug_var($attached_amount);

		return $this->out_amount + $attached_amount;
	}

	public function getAttached() {
		return sql_query_array( "SELECT * FROM im_bank_lines WHERE line_id = " . $this->id );
	}


	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	public function Update( $customer_id, $receipt, $site_id ) {
		if ( is_numeric( $receipt ) ) {
			$sql = "UPDATE im_bank SET customer_id = " . $customer_id .
			       ", receipt = " . $receipt .
			       ", site_id = " . $site_id .
			       " WHERE id = " . $this->id;

			return sql_query( $sql );
		} else {
			throw new Exception( "invalid receipt number" );
		}
	}
}