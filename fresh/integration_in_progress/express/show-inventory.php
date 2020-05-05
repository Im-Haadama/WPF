<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/18
 * Time: 06:12
 */

require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
require_once( STORE_DIR . "/fresh/pricing.php" );
function show_inventory_client() {
	foreach ( array( 18, 19, 84 ) as $term_id ) {
		show_inventory_term( $term_id );
	}
}

function show_inventory_term( $term_id ) {
	$term = get_term( $term_id );
	print Core_Html::gui_header( 2, $term->name );

	// $the_term = get_term_by("name", $term, "product_cat");

	if ( ! $term ) {
		print "term not found<br/>";

		return;
	}

	$rows = array( array( "פריט", "מחיר" ) );

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 1000,
//		'product_cat'    => $term,
		'orderby'        => 'name',
		'order'          => 'ASC',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'id',
				'terms'    => $term_id
			)
		)
	);
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) {
		$loop->the_post();
		$prod_id = $loop->post->ID;
		$q_in    = SqlQuerySingleScalar( "SELECT q_in FROM i_in WHERE product_id = " . $prod_id );
		$q_tot   = SqlQuerySingleScalar( "SELECT q FROM i_total WHERE prod_id = " . $prod_id );
//		print $q . "<br/>";
		if ( $q_tot >= 1 or ( $q_in and is_null( $q_tot ) ) ) {
			array_push( $rows, array(
				get_product_name( $prod_id ),
				get_price_by_type( $prod_id, "pos" )
				/*, $q_in, $q_tot*/
			) );
		}
	}

	print gui_table_args( $rows );
}