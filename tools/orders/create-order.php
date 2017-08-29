<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/17
 * Time: 00:26
 */

require_once "../im_tools.php";

$user_id    = $_GET["user_id"];
$prod_ids   = $_GET["prod_ids"];
$quantities = $_GET["quantities"];
$comments   = $_GET["comment"];

if ( ! ( count( $prod_ids ) > 0 ) or ! ( $user_id > 0 ) ) {
	print "wrong usage";
	die( 1 );
}

$ids = explode( ',', $prod_ids );

create_order( $user_id, $ids, $quantities );

