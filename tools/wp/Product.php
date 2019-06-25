<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/05/18
 * Time: 08:01
 */
require_once( TOOLS_DIR . "/options.php" );
require_once( ROOT_DIR . "/tools/supplies/Supply.php" );

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

	function setStock( $q ) {
		if ( $q == $this->getStock() ) {
			return;
		}
//		print "start ";
		if ( $this->isFresh() ) {
//			print "fresh ";
			$delta = $q + $this->q_out() - $this->q_in();
//			print "delta: " . $this->id . " " . $delta . "<br/>";
			if ( is_null( sql_query_single_scalar( "select meta_value " .
			                                       " from wp_postmeta " .
			                                       " where post_id = " . $this->id .
			                                       " and meta_key = 'im_stock_delta'" ) ) ) {
				sql_query( "insert into wp_postmeta (post_id, meta_key, meta_value) " .
				           " values (" . $this->id . ", 'im_stock_delta', $delta)" );

				sql_query( "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) " .
				           " VALUES (" . $this->id . ", 'im_stock_delta_date', '" . date( 'd/m/Y' ) . "')" );

				return;
			}

			sql_query( "update wp_postmeta set meta_value = " . $delta .
			           " where meta_key = 'im_stock_delta' and post_id = " . $this->id );

			if ( is_null( sql_query_single_scalar( "select meta_value " .
			                                       " from wp_postmeta " .
			                                       " where post_id = " . $this->id .
			                                       " and meta_key = 'im_stock_delta_date'" ) ) ) {
				sql_query( "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) " .
				           " VALUES (" . $this->id . ", 'im_stock_delta_date', '" . date( 'd/m/Y' ) . "')" );
			} else {
				sql_query( "UPDATE wp_postmeta SET meta_value = '" . date( 'd/m/Y' ) . "'" .
				           " WHERE meta_key = 'im_stock_delta_date' AND post_id = " . $this->id );
			}
			return;
		}
		// print "set stock ";
		// print $this->id . " " . $q . "<br/>";
		sql_query( "update wp_postmeta set meta_value = " . $q .
		           " where post_id = " . $this->id .
		           " and meta_key = '_stock'" );
		// return $this->p->set_stock_quantity($q);
	}

	function getStock( $arrived = false ) {
		if ( $this->isFresh() ) {
//			print "<br/> fresh " . $this -> q_in() . " " . $this->q_out() . " ";
			$inv         = $this->q_in( $arrived ) - $this->q_out();
			$stock_delta = sql_query_single_scalar( "select meta_value " .
			                                        " from wp_postmeta " .
			                                        " where post_id = " . $this->id .
			                                        " and meta_key = 'im_stock_delta'" );
			if ( $stock_delta ) {
				$inv += $stock_delta;
			}

			return round( $inv, 1 );
		}
		// BUG: some products, maybe just variables can't create WC_Product.
		if ( $this->p ) {
			return $this->p->get_stock_quantity();
		} else {
			return 0;
		}
	}

	function isFresh() {
		$debug = false;
		// if ($this->id == 149) $debug = true;
		$terms = $this->getTerms();
		// print var_dump($terms);
		if ( $debug ) {
			print info_get( "fresh" );
			var_dump( $terms );
		}
		// print "checking .. ";

		if ( $terms )
			foreach ( $terms as $term ) {
				$term_id = $term->term_id;
				// print "term: " . $term_id . " ";

				if ( $this->is_fresh( $term_id, $debug ) ) {
					// print "fresh";
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
				print "fresh!<br/>";
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

	private function q_in( $arrived = false ) {
		$sql = "SELECT q_in FROM i_in WHERE product_id = " . $this->id;

		$in = sql_query_single_scalar( $sql );

		if ( $arrived ) {
			// print "before: " . $in . "<br/>";
			$sql1 = "SELECT
					    sum(`l`.`quantity`)
  						FROM (`im_supplies_lines` `l`
    					JOIN `im_supplies` `s`)
  			WHERE ((`l`.`status` < 8) AND (`s`.`status` IN (" . SupplyStatus::Sent . "," . SupplyStatus::NewSupply . ")) 
  			
  				AND (`s`.`id` = `l`.`supply_id`))
  				AND l.product_id = " . $this->id;
			$in   -= sql_query_single_scalar( $sql1 );
			// print $sql1 . "<br/>";
			// print "after: " . $in . "<br/>";
		}

		// Add Bundles
		$sql    = "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $this->id;
		$result = sql_query( $sql );
		while ( $row = sql_fetch_row( $result ) ) {
			$delta = sql_query_single_scalar( "SELECT q_in FROM i_in WHERE product_id = " . $row[0] );
			if ( is_numeric( $delta ) ) {
				$in += $delta;
			}
		}


		return round( $in, 1 );
	}

	private function q_out() {
		$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $this->id;

		$out = sql_query_single_scalar( $sql );

		$sql    = "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $this->id;
		$result = sql_query( $sql );
		while ( $row = sql_fetch_row( $result ) ) {
			$delta = sql_query_single_scalar( "SELECT q_out FROM i_out WHERE prod_id = " . $row[0] );
			if ( is_numeric( $delta ) ) {
				$out += $delta;
			}
		}

		return round( $out, 1 );
	}

	function PendingSupplies() {
		// $data = "";
		$sql = "select l.supply_id, s.status \n" .
		       "from im_supplies_lines l\n" .
		       "join im_supplies s\n" .
		       "where product_id = " . $this->id . "\n" .
		       " and s.status in (" . SupplyStatus::Sent . "," . SupplyStatus::NewSupply . ")\n" .
		       " and l.supply_id = s.id";

		// print $sql;
		return sql_query_array( $sql );
	}

	function getOrdered() {
//		print "id: " . $this->id . "<br/>";
		return orders_per_item( $this->id, 1, true, true, true, true );
	}

	function getOrderedDetails() {
		return orders_per_item( $this->id, 1, true, true, true );

	}

	function setStockManaged( $managed, $backorder ) {
		print $this->id . " " . $managed . "<br/>";
		update_post_meta( $this->id, '_manage_stock', $managed ? "yes" : "no" );
//		$this->p->set_backorders( $backorder );
//		$this->p->save();
		update_post_meta( $this->id, '_backorders', $backorder ? "yes" : "no" );
		update_post_meta( $this->id, '_stock_status', $backorder ? "yes" : "no" );
		if ( is_null( $this->getStock() ) ) {
			print "setting stock to 0<br/>";
			update_post_meta( $this->id, '_stock', 0 );
//			$this->setStock(0);
//			$this->p->save();
		}

	}

	function getStockDate() {
		$stock_date = sql_query_single_scalar( "select meta_value " .
		                                       " from wp_postmeta " .
		                                       " where post_id = " . $this->id .
		                                       " and meta_key = 'im_stock_delta_date'" );

		return $stock_date;
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

	function isDraft()
	{
		return get_post_status( $this->id ) == "draft";
	}

	function Draft() {
		$this->setStock(0);
		$my_post                = array();
		$my_post['ID']          = $this->id;
		$my_post['post_status'] = 'draft';

		// Update the post into the database
		wp_update_post( $my_post );
	}
}