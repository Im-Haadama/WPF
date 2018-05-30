<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/05/18
 * Time: 08:01
 */
class Product {
	private $id;
	private $p;

	/**
	 * Product constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
		try {
			$this->p = new WC_Product( $id );
		} catch ( Exception $e ) {
			print "can't create prod " . $id . $e->getMessage() . "<br/>";
		}
	}

	function getStockManaged() {
		$v = get_post_meta( $this->id, '_manage_stock', true );

		// print "V: "   . $v . "</br>";

		return $v == "yes";
	}

	function getStock() {
		return $this->p->get_stock_quantity();
	}

	function setStockManaged( $managed ) {
		print $this->id . " " . $managed . "<br/>";
		update_post_meta( $this->id, '_manage_stock', $managed ? "yes" : "no" );
	}

	function isPublished() {
		return get_post_status( $this->id ) == "publish";
	}
}