<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/05/19
 * Time: 21:49
 */

function get_url($only_base = false)
{
	if (isset($_SERVER['REQUEST_URI'])) {
		$url = $_SERVER['REQUEST_URI'];
		if ( $only_base ) {
			$r = parse_url( $url, PHP_URL_PATH );
			if ( ! $r ) {
				return "error";
			}

			return $r;
		}
		return $url;
	}
	return "unknown";
}

/**
 * @param $url
 * @param $param_name
 * @param $param_value
 *
 * Usage add_param_to_url($url, $param, $value) or ($url, array(param1 => value1, param2 => value2 ...)
 *
 * @return string|null
 */
function add_param_to_url($url, $param_name, $param_value = null)
{
	$ignore_list = array("page"); // Would be removed from base url.
	$query_parts = [];
	if (is_null($param_value) and ! is_array($param_name)) die (__FUNCTION__ . ": bad usage");
	if ($s = strpos($url,'?')) { // Have previous query
		// Remove url part
		$result = substr($url, 0, $s); // not including the ?
		$query = substr($url, $s + 1); // not including the ?
		while (strlen($query)) {
			$next_amp = strpos($query, '&');
			$param = substr($query, 0, $e = strpos($query, '='));
			$value = $next_amp ? substr($query, $e + 1, $next_amp - $e - 1) : substr($query, $e + 1);

			if (! in_array($param, $ignore_list)) $query_parts[$param] = $value;
			if ($next_amp)
				$query = substr($query, $next_amp + 1); // Not including previous &
			else
				$query = "";
		}
	}  else
		$result = $url;

	if (is_array($param_name))
	{
//		print "is array<br/>";
		$result = null;
		foreach ($param_name as $key => $value){
			$query_parts[$key] = $value;
		}
	} else {
		$query_parts[$param_name] = $param_value;
	}
	// var_dump($query_parts);

	// Build the url
	$glue = '?';
	// $result .= "?";
	foreach ($query_parts as $param => $value) {
		$result .=  $glue . $param . '=' . $value;
		$glue = "&";
	}

//	var_dump($query_parts);
	return $result;
}

// Todo: check with more than one pair
/**
 * @param $param_name
 * @param mixed ...$param_value
 *
 * @return string|null
 */
function add_to_url($param_name, $param_value = null)
{
	return add_param_to_url(get_url(), $param_name, $param_value);
}