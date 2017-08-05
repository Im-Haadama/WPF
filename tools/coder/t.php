<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 14:51
 */
require_once( '../tools.php' );
$s = "2017-07-27";
// $d = DateTime::createFromFormat("Y-m-j", $s);
print get_week( $s );
// var_dump($d);