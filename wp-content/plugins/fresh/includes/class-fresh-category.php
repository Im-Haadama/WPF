<?php


class Fresh_Category {
	private $term;
	private $id;
	static private $datalist_shown;

	public function __construct( $id ) {
		$this->id = $id;
		if ($id > 0)
			$this->term = get_term($id);
		else
			$this->term = null;
		self::$datalist_shown = false;
	}

	function getName()
	{
		if ($this->term)
			return $this->term->name;
		return "term $this->id not found";
	}

	static function Select( $input_id, $datalist_id = "categories", $args = null ) {
		$args["field"]    = "name";
		$args["id_field"] = "term_id";

		$prefix      = GetTablePrefix();
		$result      = "";
		if ( ! self::$datalist_shown ) {
			$result .= Core_Html::TableDatalist( $datalist_id, "{$prefix}categories", $args );
			self::$datalist_shown = true;
		}

		return $result . Core_Html::GuiInputDatalist($input_id, $datalist_id);
	}

	function get_missing_pictures()
	{
		$result = array();
		if (! class_exists('Fresh_ProductIterator')) new Fresh_Product(1); // Initiate auto load

		$iter = new Fresh_ProductIterator();
		$iter->iterateCategory( $this->term->term_id );

		if ($iter) {
			while ( $prod_id = $iter->next() ) {
				if ( ! has_post_thumbnail( $prod_id ) ) {
					$result[] = $prod_id;
				}
			}
		}
		return $result;
	}

	static function GetTopLevel()
	{
		$args = [
			'taxonomy'     => 'product_cat',
			'parent'        => 0,
			'hide_empty'    => false
		];
		return get_terms( $args );
	}

	function in($array)
	{
		foreach ($array as $term) {
			if ( $term == $this->id ) return true;

		}
		return false;
	}

	function getProductsWithPrice()
	{
		$result = "";
		if (! class_exists('Fresh_ProductIterator')) new Fresh_Product(1); // Initiate auto load

		$iter = new Fresh_ProductIterator();
		$iter->iterateCategory( $this->term->term_id );

		$sort_array = [];
		if ($iter)
			while ( $prod_id = $iter->next() ) {
				$p = new Fresh_Product($prod_id);
				array_push($sort_array, array($p->getPrice(), $p->getName()));
			}

		sort ($sort_array);
		foreach ($sort_array as $p)
			$result .= $p[1] . " " . $p[0] . "<br/>";


		return $result;

	}
}