<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/10/18
 * Time: 21:56
 */
class inventory {
	public static function GetQuantity( $prod_id ) {
		return SqlQuerySingleScalar( "SELECT q FROM i_total WHERE prod_id = " . $prod_id );
	}
}
