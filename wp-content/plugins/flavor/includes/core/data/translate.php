<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/11/16
 * Time: 11:14
 */

// require_once("../config.php");

require_once( "sql.php" );


function im_translate_arr($texts, $arg = null)
{
//	print "translating $text<br/>";
}

function translate2heb( $w ) {
	return translate_word( $w, "en", "he" );
}

function translate_word( $w1, $src_lng, $dst_lng ) {
	$sql = "SELECT w2 FROM translate WHERE\n" .
	       "w1='" . $w1 . "' AND \n" .
	       "lang1='" . $src_lng . "' AND\n" .
	       "lang2='" . $dst_lng . "'";

	$row = sql_query_single_assoc( $sql );

	if ( is_null( $row ) ) {
		return $w1;
	}

	return $row["w2"];
}

//print translate2heb("aaa");


