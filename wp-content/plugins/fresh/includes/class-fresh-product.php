<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/05/18
 * Time: 08:01
 */
//require_once( FRESH_INCLUDES . "/core/options.php" );
//require_once( FRESH_INCLUDES . "/supplies/Supply.php" );

class Fresh_Product {
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
			return null;
//			print "can't create prod " . $id . $e->getMessage() . " " . $this->getName() . "<br/>";
		}
	}

	function getStockManaged() {
		$v = get_post_meta( $this->id, '_manage_stock', true );

		// print "V: "   . $v . "</br>";

		return $v == "yes";
	}

	function setStock( $q ) {
		$debug = false;

		if ($debug) print get_product_name($this->id);

		if ( $q == $this->getStock() ) {
			return true;
		}
//		print "start ";
		if ( $this->isFresh() ) {
			if ($debug)
				print "fresh ";

			$delta = $q + $this->q_out() - $this->q_in();
			if ($debug)
				print " " . $delta;
//			print "delta: " . $this->id . " " . $delta . "<br/>";
			if ( is_null( sql_query_single_scalar( "select meta_value " .
			                                       " from wp_postmeta " .
			                                       " where post_id = " . $this->id .
			                                       " and meta_key = 'im_stock_delta'" ) ) ) {
				if ($debug)
					print " insert";
				sql_query( "insert into wp_postmeta (post_id, meta_key, meta_value) " .
				           " values (" . $this->id . ", 'im_stock_delta', $delta)" );

				sql_query( "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) " .
				           " VALUES (" . $this->id . ", 'im_stock_delta_date', '" . date( 'd/m/Y' ) . "')" );

				return true;
			}

			if ($debug)
				print " update";

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
			return true;
		}
		// print "set stock ";
		// print $this->id . " " . $q . "<br/>";
		sql_query( "update wp_postmeta set meta_value = " . $q .
		           " where post_id = " . $this->id .
		           " and meta_key = '_stock'" );
		return true;
		// return $this->p->set_stock_quantity($q);
	}

	function is_basket( ) {
		return sql_query_single_scalar('SELECT count(product_id) FROM im_baskets WHERE basket_id = ' . $this->id);
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
			$value = $this->p->get_stock_quantity();
			return $value ? $value : 0;
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
		$terms = InfoGet( "fresh" );
		if (! $terms) return false;
		$fresh = explode($terms, "," );

		if ( in_array( $term_id, $fresh ) ) return true;

		$parents = get_ancestors( $term_id, "product_cat", 'taxonomy' );
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
		$sql = "select l.supply_id, s.status, s.supplier, sup.self_collect, s.picked \n" .
		       "from im_supplies_lines l\n" .
		       "join im_supplies s, im_suppliers sup\n" .
		       "where product_id = " . $this->id . "\n" .
		       " and s.status in (" . SupplyStatus::Sent . "," . SupplyStatus::NewSupply . ")\n" .
		       " and l.supply_id = s.id\n" .
		" and sup.id = s.supplier";

		// print $sql;
		$result = sql_query_array( $sql );
		// var_dump($result);
		return $result;
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

	function getName($strip = false) {
		$sql = 'SELECT post_title FROM wp_posts WHERE id = ' . $this->id;

		$name = sql_query_single_scalar( $sql );
		if ($strip and strpos($name, '(')){
			$name = substr($name, 0, strpos($name, '('));
		}
		return $name;
	}

	function GetVatPercent() {
		global $global_vat;
		if ( $this->isFresh() ) {
			return 0;
		}

		return $global_vat;
	}

	function getPrice($customer_type = "regular") {
		return get_price_by_type( $this->id, $customer_type );
	}

	function getRegularPrice() {
		return get_regular_price( $this->id );
	}

	function getSalePrice() {
		return get_sale_price( $this->id );
	}

	function getBuyPrice($supplier_id = 0)
	{
		if ( $this->id > 0 ) {
			if ( $supplier_id > 0 ) {
				$a = alternatives( $this->id );
//				if ($this->id == 3380) var_dump($a);
				foreach ( $a as $s ) {
					if ( $s->getSupplierId() == $supplier_id ) {
						return $s->getPrice();
					}
				}
			}
//			print "not found in alter" . $this->id . "<br/>";
			return get_postmeta_field( $this->id, 'buy_price' );
		}

		return - 1;
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
		return wp_update_post( $my_post );
	}

	function getImageId()
	{
		$r = $this->p->get_image_id();
		if (is_array($r)) return $r[0];
		return $r;
	}
}

class Fresh_ProductIterator implements  Iterator {
	private $position = 0;

	private $array;

	public function iteratePublished($term_id = null) {
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 10000,
			'orderby' => 'name',
			'order' => 'ASC'
		);

		if ($term_id)
		{
			$args['tax_query'] = array(array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $term_id));
		}

		$this->position = 0;

		$loop = new WP_Query( $args );

		while ( $loop->have_posts() ) {
			$loop->the_post();
			$prod_id = $loop->post->ID;

			$this->array[] = $prod_id;
		}
	}

	public function iterateCategory($term_id) {
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 10000,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $term_id
				)
			),
			'orderby' => 'name',
			'order' => 'ASC'
		);

		$this->position = 0;

		$loop = new WP_Query( $args );

		while ( $loop->have_posts() ) {
			$loop->the_post();
			global $product;
			$prod_id = $loop->post->ID;

			$this->array[] = $prod_id;
		}
	}
	/**
	 * Return the current element
	 * @link https://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 * @since 5.0.0
	 */
	public function current() {
		// TODO: Implement current() method.
		return $this->array[$this->position];
	}

	/**
	 * Move forward to next element
	 * @link https://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function next() {
		// TODO: Implement next() method.
		$c = $this->position;
		if ($c < count($this->array)) {
			$this->position++;
			return $this->array[$c];
		}
		return null;
	}

	/**
	 * Return the key of the current element
	 * @link https://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 * @since 5.0.0
	 */
	public function key() {
		// TODO: Implement key() method.
	}

	/**
	 * Checks if current position is valid
	 * @link https://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 * @since 5.0.0
	 */
	public function valid() {
		// TODO: Implement valid() method.
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link https://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function rewind() {
		// TODO: Implement rewind() method.
		$this->position = 0;
	}
}