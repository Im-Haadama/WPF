<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/05/19
 * Time: 21:49
 */

function get_url($only_base = false)
{
	$url = $_SERVER['REQUEST_URI'];
	if ($only_base)
	{
		$r = parse_url($url, PHP_URL_PATH);
		if (! $r) return "error";

		return $r;
	}

	return $url;
}