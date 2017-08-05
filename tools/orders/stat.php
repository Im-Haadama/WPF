<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/05/17
 * Time: 09:14
 */
// require_once('../tools_wp_login.php');

function get_order_status() {

	$sql = "SELECT count(*) AS count, post_status AS status
    FROM wp_posts
      WHERE post_status LIKE 'wc%'
      AND post_status NOT IN ('wc-cancelled', 'wc-completed')
    GROUP BY 2";

	return sql_query_array( $sql );
}

