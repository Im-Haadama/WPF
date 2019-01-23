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

	function getSiteId() {
		return sql_query_single_scalar( "SELECT site_id FROM im_suppliers WHERE id = " . $this->id );
	}
}