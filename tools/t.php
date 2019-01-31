<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/01/19
 * Time: 09:40
 */

require_once( "im_tools.php" );
$arr_params = array(
	'foo' => 'bar',
	'baz' => 'tiny'
);
$u          = "http://fruity.co.il/order-finish/";
echo add_query_arg( $arr_params, $u ) . "<br />";