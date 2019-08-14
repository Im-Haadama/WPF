<?php

require_once( '../r-shop_manager.php' );

$sql    = "SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'product_cat'";
$categs = sql_query_array_scalar( $sql );

print header_text(false);
print gui_header(1, "השלמת תמונות לאתר");

foreach ($categs as $categ){
	$missing_pictures = get_missing_pictures($categ);
	if (count($missing_pictures)){
		print gui_header(2, get_term_name($categ));

		foreach ($missing_pictures as $prod_id){
			// https://fruity.co.il/wp-admin/post.php?post=7015&action=edit
			print gui_hyperlink(get_product_name($prod_id), "/wp-admin/post.php?post=$prod_id&action=edit") . "<br/>";
		}
	}
}

function get_missing_pictures($categ)
{

	$result = array();
	$iter = new ProductIterator();
	$iter->iterateCategory( $categ );

	while ( $prod_id = $iter->next() )
	{
		if (! has_post_thumbnail($prod_id))
			$result[] = $prod_id;
	}
	return $result;
}