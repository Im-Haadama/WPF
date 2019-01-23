<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/03/17
 * Time: 23:19
 */

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );

$sql = "UPDATE wp_posts 
SET post_status = 'wc-completed' 
WHERE post_status='wc-awaiting-shipment'";

print mysqli_query( $conn, $sql ) . ImMultiSite::LocalSiteName() . "<br/>";