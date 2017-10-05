<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 12:15
 */
// require_once( 'tools_wp_login.php' );
$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';
print '<script language="javascript">';
print "window.location.href = '" . $url . "'";

print '</script>';
exit();

//$args = array(
//    'posts_per_page' => -1,
//    'relation' => 'AND',
//    array(
//    'post_status' => 'draft', 'publish',
//    'post_type' => 'product',
//    'post_title' => 'מלפפון')
//);
//$query = new WP_Query( $args );
//
//while ( $query->have_posts() ) {
//    $query->the_post();
//    echo '<li>' . get_the_title() . '</li>';
//}

