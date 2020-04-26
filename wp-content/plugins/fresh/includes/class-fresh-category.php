<?php


class Fresh_Category {
	private $term;
	static private $datalist_shown;

	public function __construct( $id ) {
		$this->term = get_term($id);
		self::$datalist_shown = false;
	}

	function getName()
	{
		return $this->term->name;
	}

	static function Select( $input_id, $datalist_id = "categories", $args = null ) {
		$args["field"]    = "name";
		$args["id_field"] = "term_id";

		$prefix      = get_table_prefix();
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
}