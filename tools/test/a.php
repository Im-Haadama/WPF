<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/04/19
 * Time: 07:03
 */

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );

$args = array(
	'post_type'      => 'product',
	'posts_per_page' => 10000,
	'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => 62 ) ),
	'orderby'        => 'name',
	'order'          => 'ASC'
);

$args['post_status'] = array( 'draft', 'publish' );

$loop = new WP_Query( $args );
while ( $loop->have_posts() ) {
	$loop->the_post();
	global $product;
	$prod_id = $loop->post->ID;
	print $prod_id . " " . get_product_name( $prod_id ) . "<br/>";
}
