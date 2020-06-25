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
	protected $id;
	private $p;

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

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
		if ( $q == $this->getStock() ) return true;
		if ( $this->isFresh() ) {

			$delta = $q + $this->q_out() - $this->q_in();
			if ( is_null( SqlQuerySingleScalar( "select meta_value " .
			                                    " from wp_postmeta " .
			                                    " where post_id = " . $this->id .
			                                    " and meta_key = 'im_stock_delta'" ) ) ) {
				SqlQuery( "insert into wp_postmeta (post_id, meta_key, meta_value) " .
				          " values (" . $this->id . ", 'im_stock_delta', $delta)" );

				SqlQuery( "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) " .
				          " VALUES (" . $this->id . ", 'im_stock_delta_date', '" . date( 'd/m/Y' ) . "')" );

				return true;
			}

			SqlQuery( "update wp_postmeta set meta_value = " . $delta .
			          " where meta_key = 'im_stock_delta' and post_id = " . $this->id );

			if ( is_null( SqlQuerySingleScalar( "select meta_value " .
			                                    " from wp_postmeta " .
			                                    " where post_id = " . $this->id .
			                                    " and meta_key = 'im_stock_delta_date'" ) ) ) {
				SqlQuery( "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) " .
				          " VALUES (" . $this->id . ", 'im_stock_delta_date', '" . date( 'd/m/Y' ) . "')" );
			} else {
				SqlQuery( "UPDATE wp_postmeta SET meta_value = '" . date( 'd/m/Y' ) . "'" .
				          " WHERE meta_key = 'im_stock_delta_date' AND post_id = " . $this->id );
			}

			return true;
		}
		SqlQuery( "update wp_postmeta set meta_value = " . $q .
		          " where post_id = " . $this->id .
		          " and meta_key = '_stock'" );

		return true;
	}

	function is_basket() {
		return SqlQuerySingleScalar( 'SELECT count(product_id) FROM im_baskets WHERE basket_id = ' . $this->id );
	}

	function is_bundle() {
		$sql = 'SELECT count(bundle_prod_id) FROM im_bundles WHERE bundle_prod_id = ' . $this->id;

		return SqlQuerySingleScalar( $sql );
	}

	function getStock( $arrived = false ) {
		if ( $this->isFresh() ) {
//			print "<br/> fresh " . $this -> q_in() . " " . $this->q_out() . " ";
			$inv         = $this->q_in( $arrived ) - $this->q_out();
			$stock_delta = SqlQuerySingleScalar( "select meta_value " .
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
		$terms = $this->getTerms();

		if (! $terms or ! is_array($terms)) return "no terms";
		if ( $terms )
			foreach ( $terms as $term ) {
				if ( $this->is_fresh( $term, $debug ) ) {
					return true;
				}
			}

		return false;
	}

	function getTerms( $as_string = false )
	{
		$terms = self::getAllTermsIds();
		if ( $as_string ) {
			if ( ! $terms ) {
				return "(no terms)";
			}
			$result = "";
			foreach ( $terms as $term_id ) {
				$term   = get_term( $term_id );
				$result .= $term->name . ", ";
			}

			return rtrim( $result, ", " );
		}

		return $terms;
	}

	// include parents
	private function getAllTermsIds()
	{
		$result = [];
		$terms = get_the_terms( $this->id, 'product_cat' );
		if (! $terms) return "no terms";
		foreach ($terms as $term)
		{
			array_push($result, $term->term_id);
			$parents = get_ancestors( $term->term_id, "product_cat", 'taxonomy' );
			if ($parents) foreach ($parents as $parent) array_push($result, $parent);
		}
		return $result;
	}

	function is_fresh( $term_id, $debug = false ) {
		$terms = InfoGet( "fresh" );
		if ( ! $terms ) return false;
		$fresh = explode( ",", $terms);

		if ( in_array( $term_id, $fresh ) ) return true;

		$parents = get_ancestors( $term_id, "product_cat", 'taxonomy' );
		foreach ( $parents as $parent )
			if ( $this->is_fresh( $parent, $debug ) ) return true;

		return false;
	}

	private function q_in( $arrived = false ) {
		$sql = "SELECT q_in FROM i_in WHERE product_id = " . $this->id;

		$in = SqlQuerySingleScalar( $sql );

		if ( $arrived ) {
			// print "before: " . $in . "<br/>";
			$sql1 = "SELECT
					    sum(`l`.`quantity`)
  						FROM (`im_supplies_lines` `l`
    					JOIN `im_supplies` `s`)
  			WHERE ((`l`.`status` < 8) AND (`s`.`status` IN (" . SupplyStatus::Sent . "," . SupplyStatus::NewSupply . ")) 
  			
  				AND (`s`.`id` = `l`.`supply_id`))
  				AND l.product_id = " . $this->id;
			$in   -= SqlQuerySingleScalar( $sql1 );
			// print $sql1 . "<br/>";
			// print "after: " . $in . "<br/>";
		}

		// Add Bundles
		$sql    = "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $this->id;
		$result = SqlQuery( $sql );
		while ( $row = SqlFetchRow( $result ) ) {
			$delta = SqlQuerySingleScalar( "SELECT q_in FROM i_in WHERE product_id = " . $row[0] );
			if ( is_numeric( $delta ) ) {
				$in += $delta;
			}
		}


		return round( $in, 1 );
	}

	private function q_out() {
		$sql = "SELECT q_out FROM i_out WHERE prod_id = " . $this->id;

		$out = SqlQuerySingleScalar( $sql );

		$sql    = "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $this->id;
		$result = SqlQuery( $sql );
		while ( $row = SqlFetchRow( $result ) ) {
			$delta = SqlQuerySingleScalar( "SELECT q_out FROM i_out WHERE prod_id = " . $row[0] );
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
		       " and s.status in (" . eSupplyStatus::Sent . "," . eSupplyStatus::NewSupply . ")\n" .
		       " and l.supply_id = s.id\n" .
		       " and sup.id = s.supplier";

		// print $sql;
		$result = SqlQueryArray( $sql );

		// var_dump($result);
		return $result;
	}

	function getOrdered() {
		return Fresh_Packing::orders_per_item( $this->id, 1, true, true, true, true );
	}

	function getOrderedDetails() {
		return Fresh_Packing::orders_per_item( $this->id, 1, true, true, true );
	}

	function setStockManaged( $managed, $backorder ) {
		print $this->id . " " . $managed . "<br/>";
		update_post_meta( $this->id, '_manage_stock', $managed ? "yes" : "no" );
		update_post_meta( $this->id, '_backorders', $backorder ? "yes" : "no" );
		update_post_meta( $this->id, '_stock_status', $backorder ? "yes" : "no" );
		if ( is_null( $this->getStock() ) ) {
			print "setting stock to 0<br/>";
			update_post_meta( $this->id, '_stock', 0 );
		}
	}

	function getStockDate() {
		$stock_date = SqlQuerySingleScalar( "select meta_value " .
		                                    " from wp_postmeta " .
		                                    " where post_id = " . $this->id .
		                                    " and meta_key = 'im_stock_delta_date'" );

		return $stock_date;
	}

	function isPublished() {
		return get_post_status( $this->id ) == "publish";
	}

	function getName( $strip = false ) {
		if (! ($this->id > 0)) return "Error";
		$sql = 'SELECT post_title FROM wp_posts WHERE id = ' . $this->id;

		$name = SqlQuerySingleScalar( $sql );
		if ( $strip and strpos( $name, '(' ) ) {
			$name = trim( substr( $name, 0, strpos( $name, '(' ) ) );
		}

		return $name;
	}

	function getVatPercent() {
		if ( $this->isFresh() ) return 0;

		return Fresh_Pricing::getVatPercent();
	}

	function getPrice( $customer_type = "regular" ) {
		return Fresh_Pricing::get_price_by_type( $this->id, $customer_type );
	}

	function getRegularPrice() {
		return get_regular_price( $this->id );
	}

	function getSalePrice() {
		return get_postmeta_field( $this->id, '_sale_price' );
	}

	function getSupplierName() {
		return SqlQuerySingleScalar( "select meta_value from wp_postmeta where post_id = " . $this->id . " and meta_key = 'supplier_name'" );
	}

	function setRegularPrice($price)
	{
		Fresh_Pricing::set_regular_price($this->id, $price);
	}

	function setSalePrice($price) {
		Fresh_Pricing::set_saleprice( $this->id, $price );
	}

	function getSupplierId($debug = false)
	{
		if ($debug) MyLog(__FUNCTION__, $this->id);
		// For now create post saves the supplier name.
		// Planned to save there the supplier id.
		$b = Fresh_Catalog::best_alternative($this->id, $debug);

		if ($b)
			return $b->getSupplierId();

		return null;
	}

	function getCalculatedPrice($supplier_id)
	{
		return Fresh_Pricing::calculate_price($this->getBuyPrice($supplier_id), $supplier_id);
	}

	function getBuyPrice($supplier_id = 0, $debug_product = 0)
	{
		if (! $this->id) return -1;

		$b = Fresh_Catalog::best_alternative($this->id, $debug_product == $this->id);
		if ($b)
			return $b->getPrice();

		return get_postmeta_field( $this->id, 'buy_price' );
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

	function get_variations(  ) {
		$vars = array();

		$args       = array(
			'post_type'   => 'product_variation',
			'post_status' => 'publish',
			'numberposts' => - 1,
			'orderby'     => 'menu_order',
			'order'       => 'asc',
			'post_parent' => $this->id // $post->ID
		);
		$variations = get_posts( $args );

		foreach ( $variations as $v ) {
			array_push( $vars, $v->ID );
		}

		return $vars;
	}

	static function gui_select_product( $id, $data = null, $args = null)
	{
		if (! $args)
			$args = array();

		if ($data > 0)
		{
			$p = new Fresh_Product($data);
			$product_name = $p->getName();
		} else {
			$product_name = $data;
		}
		if (isset($args["edit"]) and !$args["edit"]) return $product_name;
		$args["selected"] = $data;
		$args["name"] = "post_title";
		$args["selected"] = $product_name;
		$args["datalist"] = true;
		$args["id_field"] = "ID";
		$args["include_id"] = true;
		$args["post_file"] = get_site_url() . "/wp-content/plugins/fresh/post.php";

//		print "v1=" . $args["value"] . "<br/>";
		// return GuiSelectTable( $id, "im_products", $args);
		return Core_Html::GuiAutoList($id, "products", $args);
	}

	function PublishItem($price = 0) {
		MyLog(__FUNCTION__ . " " . $this->id . " $price");
		$my_post                = array();
		$my_post['ID']          = $this->id;
		$my_post['post_status'] = 'publish';
		if ($price)
			$this->setRegularPrice($price);

		// Update the post into the database
		MyLog( "publish prod id: " . $this->id . " price: $price", __FUNCTION__ );
		wp_update_post( $my_post );
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

	public function iterateCategory($term_id, $post_status = 'publish', $orderby = 'name') {
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
			'orderby' => $orderby,
			'order' => 'ASC'
		);
		if ($post_status)
			$args["post_status"] = $post_status;

		$this->position = 0;
		$this->array = [];

		$loop = new WP_Query( $args );

		while ( $loop->have_posts() ) {
			$loop->the_post();
			$prod_id = $loop->post->ID;
//			print $prod_id . "<br/>";

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
		if ( ! is_countable( $this->array ) )
		{
			return null;
		}
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