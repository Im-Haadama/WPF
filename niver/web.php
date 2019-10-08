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

function add_param_to_url($url, $param_name, ...$param_value)
{
	# Todo: check if parameter is not there before.
	if (is_array($param_name))
	{
//		print "is array<br/>";
		$result = null;
		foreach ($param_name as $key => $value){
//			print "adding $key=$value<br/>";
			if (! $result)
				$result = add_to_url($key, $value);
			else
				$result .= "&$key=$value";
		}
		return $result;
	}
	if (is_array($param_value)) $param_value = $param_value[0];
	// print "handling non array $param_name=$param_value<br/>";
	if (strpos($url, '?')) return $url . '&' . $param_name . '=' . $param_value;
	return $url . '?' . $param_name . '=' . $param_value;

}

// Todo: check with more than one pair
function add_to_url($param_name, ...$param_value)
{
//	print "start<br/>";
//	var_dump($param_name); print "<br/>";
//	var_dump($param_value); print "<br/>";
	$url = get_url();

	if (isset($param_value[0]))
		return add_param_to_url($url, $param_name, $param_value[0]);

}