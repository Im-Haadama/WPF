<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once("../r-shop_manager.php");

require_once ("../../niver/gui/sql_table.php");

im_init();

/// $url = "http://store.im-haadama.co.il/tools/admin/data-post.php?table_name=im_tasklist&operation=update&id=2641&task_description=%D7%A7%D7%A8%D7%9F%20%D7%A4%D7%A0%D7%A1%D7%99%D7%94%20-%20%D7%98%D7%95%D7%A4%D7%A1%20%D7%9E%D7%94%D7%A1%D7%95%D7%9B%D7%9F%20%D7%91%D7%99%D7%98%D7%95%D7%97%20%D7%91%D7%A9%D7%9D%20%22%D7%A4%D7%A8%D7%98%D7%99%20%D7%94%D7%A7%D7%95%D7%A4%D7%94%22.%0A%0A%D7%95%D7%91%D7%9E%D7%99%D7%93%D7%94%20%D7%95%D7%90%D7%99%D7%9F%20%D7%9C%D7%99%20%D7%A1%D7%95%D7%9B%D7%9F%20%D7%91%D7%99%D7%98%D7%95%D7%97%20%D7%90%D7%96%20%D7%9E%D7%94%20%D7%94%D7%9C%D7%90%D7%94%3F%0A%D7%99%D7%A9%20%D7%A9%D7%AA%D7%99%20%D7%90%D7%A4%D7%A9%D7%A8%D7%95%D7%99%D7%95%D7%AA%3A%0A1.%20%D7%90%D7%A0%D7%99%20%D7%A4%D7%95%D7%AA%D7%97%20%D7%9E%D7%94%20%D7%A9%D7%A0%D7%A7%D7%A8%D7%90%20%D7%A7%D7%A8%D7%9F%20%D7%A4%D7%A0%D7%A1%D7%99%D7%94%20%D7%91%D7%A8%D7%99%D7%A8%D7%AA%20%D7%9E%D7%97%D7%93%D7%9C%20%D7%91%D7%93%22%D7%A9.%0A2.%20%D7%90%D7%AA%20%D7%9E%D7%95%D7%A6%D7%90%D7%AA%20%D7%A1%D7%95%D7%9B%D7%9F%20%D7%91%D7%94%D7%A7%D7%93%D7%9D%20%3A)%20%D7%90%D7%A0%D7%99%20%D7%99%D7%9B%D7%95%D7%9C%20%D7%9C%D7%94%D7%9E%D7%9C%D7%99%D7%A5%20%D7%A2%D7%9C%20%D7%A9%D7%9C%D7%99.";

// print urldecode($url);

//$cell =escape_string($cell);
//
//$description =str_replace('/\"/', '""', $cell);
//
//print "<table><tr><td>" . $description . "</td></tr/><table>";

$text = sql_query_single_scalar("select task_description from im_tasklist where id = 2641" );
// $text=str_replace('\n','<br>',$text);

function str_split_unicode($str, $l = 0) {
	if ($l > 0) {
		$ret = array();
		$len = mb_strlen($str, "UTF-8");
		for ($i = 0; $i < $len; $i += $l) {
			$ret[] = mb_substr($str, $i, $l, "UTF-8");
		}
		return $ret;
	}
	return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}
$arr = str_split_unicode($text);
foreach ($arr as $char)
	print $char . "<br/>";

print gui_textarea("test", $text);

print "<br/>";

print gui_table(array(array($text)));

print gui_textarea("test1", "man\n\nwomen\n\n");

print "<br/>XXXX<br/>";

print remove_br1($text);

function remove_br1($value)
{
	$to_replace = array("<br/>", "<br>");
	foreach ($to_replace as $rep)
		$value = str_replace($rep, '\n', $value);
	return $value;
}
