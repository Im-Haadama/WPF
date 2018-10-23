<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/05/18
 * Time: 08:01
 */
require_once( TOOLS_DIR . "/options.php" );

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

	function getName() {
		return get_product_name( $this->id );
	}

	function GetVatPercent() {
		global $global_vat;
		if ( $this->isFresh() ) {
			return 0;
		}

		return $global_vat;
	}

	function isFresh() {
		$debug = false;
		// if ($this->id == 149) $debug = true;
		$terms = $this->getTerms();
		if ( $debug ) {
			print info_get( "fresh" );
			var_dump( $terms );
		}
		// print "checking .. ";

		foreach ( $terms as $term ) {
			$term_id = $term->term_id;
			// print $term_id . " ";

			if ( $this->is_fresh( $term_id, $debug ) ) {
				return true;
			}
		}

		return false;
	}

	function getTerms() {
		$terms = get_the_terms( $this->id, 'product_cat' );

		return $terms;
	}

	function is_fresh( $term_id, $debug = false ) {
		$fresh = explode( ",", info_get( "fresh" ) );
//		if ($debug) {
//			print "<br/>Fresh: ";
//			var_dump( $fresh );
//			print "<br/>";
//		}
		if ( ! $fresh ) {
			print "no fresh terms<br/>";

			return false;
		}

		if ( $debug ) {
			print "term:" . $term_id . "<br/>";
			print "fresh: ";
			var_dump( $fresh );
			print( "<br/>" );
		}
		if ( in_array( $term_id, $fresh ) ) {
			if ( $debug ) {
				print "fresh<br/>";
			}

			return true;
		}

		$parents = get_ancestors( $term_id, "product_cat", 'taxonomy' );
		if ( $debug ) {
			print "parents: ";
			var_dump( $parents );
			print "<br/>";
		}
		foreach ( $parents as $parent ) {
			if ( $this->is_fresh( $parent, $debug ) ) {
				return true;
			}
		}

		return false;
	}

	function getPrice() {
		return get_price( $this->id );
	}

	function getRegularPrice() {
		return get_regular_price( $this->id );
	}

	function getSalePrice() {
		return get_sale_price( $this->id );
	}

	function getBuyPrice() {
		return get_buy_price( $this->id );
	}

	function Draft() {
		$my_post                = array();
		$my_post['ID']          = $this->id;
		$my_post['post_status'] = 'draft';

		// Update the post into the database
		wp_update_post( $my_post );
	}
}