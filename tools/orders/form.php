<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/11/18
 * Time: 18:41
 */

function show_category_all( $sale, $text, $fresh = false ) {
	$result = "";
	if ( $fresh ) {
		$categs = explode( ",", info_get( "fresh" ) );
	} else {
		$sql    = "SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'product_cat'";
		$categs = sql_query_array_scalar( $sql );
	}
	foreach ( $categs as $categ ) {
		// print get_term($categ)->name . "<br/>";
		$result .= show_category_by_id( $categ, $sale, $text );
	}

	return $result;
}

function show_category_by_id( $term_id, $sale = false, $text = false, $customer_type = "regular" ) {
	$result   = "";
	$img_size = 40;

	$the_term = get_term( $term_id );

	$result .= gui_header( 2, $the_term->name );

	if ( $sale ) {
		$table = array( array( "", "מוצר", "מחיר מוזל", "מחיר רגיל", "כמות", "סה\"כ" ) );
	} else {
		$table = array( array( "", "מוצר", "מחיר", gui_link( "מחיר לכמות", "", "" ), "כמות", "סה\"כ" ) );
	}

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 1000,
		'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term_id ) ),
		'orderby'        => 'name',
		'order'          => 'ASC'
	);
	// var_dump($args);
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) {
		$loop->the_post();
		$line = array();
		global $product;
		if ( ! $product->get_regular_price() ) {
			continue;
		}
		$prod_id = $loop->post->ID;
		// $terms   = get_the_terms( $prod_id, 'product_cat' );
// print "<br/>" . $prod_id . " "; print get_product_name($prod_id);
		$found = false;
//		foreach ( $terms as $term ) {
//			if ( $term->term_id == $term_id ) {
//				$found = true;
//			}
//		}
//		if ( ! $found ) {
//			continue;
//		}
		if ( $text ) {
			$line = get_product_name( $prod_id ) . " - " . get_price_by_type( $prod_id, $customer_type ) . "<br/>";
			// print "line = " . $line . "<br/>";
			$result .= $line;
			continue;
		}
		if ( has_post_thumbnail( $prod_id ) ) {
			array_push( $line, get_the_post_thumbnail( $loop->post->ID, array( $img_size, $img_size ) ) );
		} else {
			array_push( $line, '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="' . $img_size . 'px" height="'
			                   . $img_size . 'px" />' );
		}
		array_push( $line, the_title( '', '', false ) );
		if ( $sale ) {
			array_push( $line, gui_lable( "prc_" . $prod_id, $product->get_sale_price() ) );
			array_push( $line, gui_lable( "vpr_" . $prod_id, $product->get_regular_price() ) );
		} else {
			array_push( $line, gui_lable( "prc_" . $prod_id, $product->get_price() ) );
			$q_price = get_price_by_type( $prod_id, null, 8 );
//			if ( is_numeric( get_buy_price( $prod_id ) ) ) {
//				$q_price = min( round( get_buy_price( $prod_id ) * 1.25 ), $product->get_price() );
//			}
			array_push( $line, gui_lable( "vpr_" . $prod_id, $q_price, 1 ) );
		}
		array_push( $line, gui_input( "qua_" . $prod_id, "0", array( 'onchange="calc_line(this)"' ) ) );
		array_push( $line, gui_lable( "tot_" . $prod_id, '' ) );
		array_push( $table, $line );
	}

	if ( $text ) {
		return $result;
	} else {
		$result .= gui_table( $table, "table_" . $term_id );
	}

//	print "result = " . $result . "<br/>";
	return $result;
}
