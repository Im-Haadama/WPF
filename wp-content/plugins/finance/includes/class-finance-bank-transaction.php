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
	private $client_name;

	static function createFromDB( $id ) {
		$table_prefix = GetTablePrefix();

		$sql    = "SELECT in_amount, date, out_amount, client_name FROM ${table_prefix}bank WHERE id = " . $id;
		$result = SqlQuery( $sql );
		if ( ! $result ) {
			throw new Exception( "Transaction not found" );
		}

		$row = SqlFetchRow( $result );

		if ( ! $row ) {
			throw new Exception( "Transaction not found" );
		}
		$r = new Finance_Bank_Transaction();

		$r->id         = $id;
		$r->in_amount  = $row[0];
		$r->date       = $row[1];
		$r->out_amount = $row[2];
		$r->client_name = $row[3];

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
		$table_prefix = GetTablePrefix();
//		debug_var($this->out_amount);
		if ( ! $attached ) {
			return $this->out_amount;
		}

		$attached_amount = SqlQuerySingleScalar( "SELECT sum(amount) FROM ${table_prefix}bank_lines " .
		                                         " WHERE line_id = " . $this->id );

//		debug_var($attached_amount);

		return $this->out_amount + $attached_amount;
	}

	public function getClientName()
	{
		return $this->client_name;
	}

	public function getAttached() {
		$table_prefix = GetTablePrefix();

		return SqlQueryArray( "SELECT * FROM ${table_prefix}bank_lines WHERE line_id = " . $this->id );
	}


	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	public function Update( $customer_id, $receipt, $site_id )
	{
		$table_prefix = GetTablePrefix();
		MyLog(__FUNCTION__ . $receipt);

		if ( is_numeric( $receipt ) ) {
			$sql = "UPDATE ${table_prefix}bank SET customer_id = " . $customer_id .
			       ", receipt = " . $receipt .
			       ", site_id = " . $site_id .
			       " WHERE id = " . $this->id;

			MyLog($sql);

			return SqlQuery( $sql );
		} else {
			throw new Exception( "invalid receipt number" );
		}
	}
}