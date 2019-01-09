<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/11/18
 * Time: 20:00
 */
class Mission {
	private $id;

	static public function getMission( $id ) {
		if ( ! ( $id > 0 ) ) {
			die ( __METHOD__ . " id = " . $id );
		}
		$m     = new Mission();
		$m->id = $id;

		return $m;
	}

	public function getStartAddress() {
		global $store_address;
		// print "<br/>";var_dump($this);print"<br/>";
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$start = sql_query_single_scalar( "SELECT start_address FROM im_missions WHERE id = " . $this->id );

		return $start ? $start : $store_address;
	}

	public function getEndAddress() {
		global $store_address;

		// print "<br/>";var_dump($this);print"<br/>";
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$end = sql_query_single_scalar( "SELECT end_address FROM im_missions WHERE id = " . $this->id );

		return $end ? $end : $store_address;
	}
}