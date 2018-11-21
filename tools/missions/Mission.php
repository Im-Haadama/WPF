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
		// print "<br/>";var_dump($this);print"<br/>";
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$start = sql_query_single_scalar( "SELECT start_address FROM im_missions WHERE id = " . $this->id );

		// TODO: save default start and end in info.
		return $start ? $start : "גרניט 23 כפר יונה";
	}

	public function getEndAddress() {

		// print "<br/>";var_dump($this);print"<br/>";
		if ( ! ( $this->id > 0 ) ) {
			die ( __METHOD__ . " id = " . $this->id );
		}
		$end = sql_query_single_scalar( "SELECT end_address FROM im_missions WHERE id = " . $this->id );

		return $end ? $end : "גרניט 23 כפר יונה";
	}
}