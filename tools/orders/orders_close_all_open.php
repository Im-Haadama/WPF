<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/07/15
 * Time: 21:18
 */
require '../tools_wp_login.php';

$sql = "UPDATE wp_posts SET post_status = 'wc-completed' WHERE post_status = 'wc-processing'";

sql_query( $sql );

?>
