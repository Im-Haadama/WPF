<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/11/16
 * Time: 11:14
 */

// require_once("../config.php");

require_once( "sql.php" );

function im_translate($text, $arg = null)
{
//	print "translating $text<br/>";
	if (is_array($text)){
		$result = "";
		foreach ($text as $text_part)
			$result .= im_translate($text_part, $arg);
		return $result;
	}

//	print "translating $text to " . get_locale() . "...";
	$t = translate($text, 'im_haadama');
//	print $t . "<br/>";
	// print $t . " " . strlen($t);
	if (strlen($t))
	{
		if (strstr($t, "%s")){
			return $arg ? sprintf($t, $arg) : $t;
		}
		return $t;
	} else {
		return $text;
	}
}

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


