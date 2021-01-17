<?php

class Finance_Product {
	protected $id;
	protected $wp_p;

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
		if (! class_exists('WC_Product')) return null;

		$this->id = $id;
		try {
			$this->wp_p = new WC_Product( $id );
		} catch ( Exception $e ) {
			$this->wp_p = null;
			return null;
//			print "can't create prod " . $id . $e->getMessage() . " " . $this->getName() . "<br/>";
		}
	}

	public function found() : bool
	{
		return null != $this->wp_p;
	}

	static function getByName($prod_name)
	{
		$id = SqlQuerySingleScalar("select id from im_products where post_title = " . QuoteText($prod_name));
		if ($id) return new self($id);
		return null;
	}

	function getPrice()
	{
		return get_postmeta_field( $this->id, '_price' );
	}

	static public function get_edit_link( $id ) {
		return "/wp-admin/post.php?post=" . $id . '&action=edit';
	}

	function getStockManaged() {
		$v = get_post_meta( $this->id, '_manage_stock', true );

		// print "V: "   . $v . "</br>";

		return $v == "yes";
	}

	function getTerms( $as_string = false ) {
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
	private function getAllTermsIds() {
		$result = [];
		$terms  = get_the_terms( $this->id, 'product_cat' );
		if ( ! $terms ) {
			return "no terms";
		}
		foreach ( $terms as $term ) {
			array_push( $result, $term->term_id );
			$parents = get_ancestors( $term->term_id, "product_cat", 'taxonomy' );
			if ( $parents ) {
				foreach ( $parents as $parent ) {
					array_push( $result, $parent );
				}
			}
		}

		return $result;
	}

	public function getVatPercent() {
		return Israel_Shop::getVatPercent();
	}
}