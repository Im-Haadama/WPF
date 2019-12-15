<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 01/01/19
 * Time: 19:43
 */
class Supplier {
	private $id;

	public function __construct( $_id ) {
		$this->id = $_id;
	}

	static function getByInvoiceSender( $email ) {
		$id = sql_query_single_scalar( "SELECT id FROM im_suppliers WHERE " .
		                               " invoice_email = " . quote_text( $email ) );

		if ( ! $id ) {
			return null;
		}

		return new Supplier( $id );
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	function getSiteId() {
		return sql_query_single_scalar( "SELECT site_id FROM im_suppliers WHERE id = " . $this->id );
	}

	function getAddress()
	{
		$sql = "select address from im_suppliers where id = " . $this->id;
		return sql_query_single_scalar( $sql);
	}
}