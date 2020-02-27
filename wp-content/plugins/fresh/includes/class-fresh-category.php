<?php


class Fresh_Category {
	private $term;

	public function __construct( $id ) {
		$this->term = get_term($id);
	}

	function getName()
	{
		return $this->term->name;
	}


	static function gui_select_category( $id, $args ) {
		$result = "";

//		$args["include_id"] = $include_id;
		$args["field"]      = "name";
		$args["id_field"] = "term_id";

		$datalist_id = $id . "_datalist";
		$prefix = get_table_prefix();
		return Core_Html::TableDatalist($datalist_id , "{$prefix}categories", $args ) .
		        Core_Html::GuiInputDatalist($id, $datalist_id);

//		return Core_Html::GuiAutoList($id, "categories", $args);
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