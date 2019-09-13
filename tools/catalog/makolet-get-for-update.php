<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/08/17
 * Time: 11:12
 */

require_once( "catalog-db-query.php" );

//$sql = "select term_id, name from wp_terms";

$categs = array(
	34,
	10,
	100,
	25,
	21,
	23,
	26,
	33,
	98,
	30,
	44,
	45,
	96,
	146,
	145,
	82,
	88,
	90,
	92,
	102,
	104,
	106,
	108,
	110,
	112,
	114,
	116,
	118,
	120,
	122,
	124,
	126,
	128,
	136,
	134,
	140,
	148,
	147,
	149,
	150,
	151,
	152,
	153,
	154,
	155,
	156,
	157,
	158,
	159,
	160,
	161,
	162,
	163,
	164,
	165
);

show_catalog( false, null, false, true, false, false, $categs, false );

