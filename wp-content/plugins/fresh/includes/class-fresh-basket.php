<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/11/18
 * Time: 07:22
 */
class Fresh_Basket {
	private $id;

	/**
	 * Basket constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	public function GetQuantity( $prod_id ) {
		$sql = "SELECT quantity FROM im_baskets WHERE basket_id = " . $this->id .
		       " AND product_id = " . $prod_id;

		// print $sql;
		return sql_query_single_scalar( $sql );
	}
	function get_basket_date( $basket_id ) {
		$sql = 'SELECT max(date) FROM im_baskets WHERE basket_id = ' . $basket_id;

		$row = sql_query_single_scalar( $sql );

		return substr( $row, 0, 10 );
	}

	function get_basket_content( $basket_id ) {
		// t ;

		$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
		       ' ORDER BY 3';

		$result = sql_query( $sql );

		$basket_content = "";

		while ( $row = mysqli_fetch_row( $result ) ) {
			$prod_id  = $row[0];
			$quantity = $row[1];

			if ( $quantity <> 1 ) {
				$basket_content .= $quantity . " ";
			}
			if ($prod_id > 0){
				$b = new Fresh_Product($prod_id);
				if ($b) $basket_content .= $b->getName(true) . ", ";
			}
		}

		return chop( $basket_content, ", " ) . ".";
	}

	function get_basket_content_array( $basket_id ) {
		$result = array();

		$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
		       ' ORDER BY 3';

		$sql_result = sql_query( $sql );

		while ( $row = mysqli_fetch_row( $sql_result ) ) {
			$prod_id            = $row[0];
			$quantity           = $row[1];
			$result[ $prod_id ] = $quantity;
		}

		return $result;
	}

	function is_basket( ) {
		return sql_query_single_scalar('SELECT count(product_id) FROM im_baskets WHERE basket_id = ' . $this->id);
	}
}

