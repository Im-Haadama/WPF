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
		if ( ! class_exists( 'WC_Product' ) ) {
			return null;
		}

		$this->id = $id;
		try {
			$this->wp_p = new WC_Product( $id );
		} catch ( Exception $e ) {
			$this->wp_p = null;
			return null;
//			print "can't create prod " . $id . $e->getMessage() . " " . $this->getName() . "<br/>";
		}
	}

	public function found(): bool {
		return null != $this->wp_p;
	}

	static function getByName( $prod_name ) {
		$id = SqlQuerySingleScalar( "select id from im_products where post_title = " . QuoteText( $prod_name ) );
		if ( $id ) {
			return new self( $id );
		}

		return null;
	}

	function getPrice() {
		return get_postmeta_field( $this->id, '_price' );
	}

	public function getEditLink($hyper=false)
	{
		if ($hyper)
			return Core_Html::GuiHyperlink($this->getName(), self::get_edit_link($this->id));

		return self::get_edit_link($this->id);
	}
	static public function get_edit_link( $id) {
		return "/wp-admin/post.php?post=" . $id . '&action=edit';
	}

	public function getPublish()
	{
		return SqlQuerySingleScalar("select post_status from wp_posts where id = " . $this->id);
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
		// Default vat is 0. To overide include plugin like Israel_Shop.
		return apply_filters('vat_percent', 0);
//		return Israel_Shop::getVatPercent();
	}

	function getName( $strip = false, $include_tags = false ) {
		if (! ($this->id > 0)) return "Error";
		$sql = 'SELECT post_title FROM wp_posts WHERE id = ' . $this->id;

		$name = SqlQuerySingleScalar( $sql );
		if ( $strip and strpos( $name, '(' ) ) {
			$name = trim( substr( $name, 0, strpos( $name, '(' ) ) );
		}
		if ($include_tags){
			$tags = $this->getTags(false, true);
			foreach ($tags as $tag){
				$t = get_term( $tag, 'product_tag');
				if ($t)
					$name .= " " . $t->name;
			}
		}

		return $name;
	}

}