<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/05/18
 * Time: 07:30
 *
 * @param $msg
 * @param string $title
 * @param null $file - default is php_error.log
 */

function my_log( $msg, $title = '', $file = null ) {
	$error_file = ROOT_DIR . '/logs/' . ($file ? $file : 'php_error.log');
//    print $error_file;
	$date = date( 'd.m.Y h:i:s' );
	$msg  = print_r( $msg, true );
	$log  = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}

/**
 * @param bool $script_file
 *
 * @return string
 */
function load_scripts($script_file = false )
{
	$text = "";
	if ( $script_file ) {
		// print "Debug: " . $script_file . '<br/>';
		// var_dump($script_file);
		do {
			if ( $script_file === true ) {
				$text .= '<script type="text/javascript" src="/niver/gui/client_tools.js"></script>';
				break;
			}
			if ( is_string( $script_file ) ) {
				$text .= '<script type="text/javascript" src="' . $script_file . '"></script>';
				break;
			}
			if ( is_array( $script_file ) ) {
				foreach ( $script_file as $file ) {
					if (strstr($file, 'php')) $text .= curl_get($file);
					else $text .= '<script type="text/javascript" src="' . $file . '"></script>';
				}
				break;
			}
			print $script_file . " not added<br/>";
		} while ( 0 );
	}
//	print $text;

	return $text;

}

/**
 * @param null $args
 * rtl - right to left
 * print_logo - print the logo in the greeting block
 * script_files - java scripts to include in header
 * close_header - include </head> in the end of the header
 * greeting - greet the user - includes time, user_id and view_as
 * view_as - enables manager to see the worker's view (in greeting function)
 *
 * @return string
 * @throws Exception
 */

function HeaderText($args = null)
{
	global $business_info;
	global $logo_url;
	$style_file = GetArg($args, "css", null);

	$rtl = GetArg($args, "rtl", (function_exists("is_rtl") ? is_rtl() : false));
	$print_logo = GetArg($args, "print_logo", true);
	$script_files = GetArg($args, "script_files", false);
	$close_header = GetArg($args, "close_header", true);
	$greeting = GetArg($args, "greeting", false);

	$text = "";

	$text .= '<html';
	if ( $rtl ) {
		$text .= ' dir="rtl"';
	}
	$text .= '>';
	$text .= '<head>';
	$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
	$text .= '<title>';
	if ( defined( $business_info ) ) $text .= $business_info;
	$text .= '</title>';

	// print "loading..."; var_dump($script_files); print "<br/>";
	$text .= load_scripts($script_files);
	if ( isset( $style_file ) ) {
		$text .= load_style($style_file);
	}

	if ( $close_header ) $text .= '</head>';

	$table = array();
	$row = array();
	if ($greeting) $row [] = greeting( $args );
	if ($print_logo and $logo_url ) {
		$row [] = '<img src=' . $logo_url . '  style="height: 100px; width: auto;">'; // Todo: use GuiImage
		$table [] = $row;
		$args["align_table_cells"] = array(array(null, "left"));
		$text .= gui_table_args($table, "header", $args);
	}

	if (gui_type() == "html") return $text;
	return strip_tags($text);
}

/**
 * @return string
 */

/**
 * @param bool $print_logo
 * @param bool $close_header
 * @param bool $rtl
 * @param bool $script_file
 *
 * @return string
 * @throws Exception
 */
function header_text( $print_logo = true, $close_header = true, $rtl = true, $script_file = false ) {
	// $text .= '<p style="text-align:center;">';
	$args = [];
	$args["print_logo"] = $print_logo;
	$args["close_header"] = $close_header;
	$args["rtl"] = $rtl;
	$args["script_files"] = $script_file;

	return HeaderText($args);
}

/**
 * @return string
 */
function footer_text() {
	global $power_version;

	$text = gui_div( "footer", "Fresh store powered by Aglamaz.com 2015-2019 Version " . $power_version . " עם האדמה 2013", true );

	return $text;
}


/**
 * @param $style_file
 *
 * @return string
 */
function load_style($style_file)
{
	return '<link rel="stylesheet" type="text/css" href="' . $style_file . '">';

//	$text = "<style>";
//	$text .= file_get_contents( $style_file );
//	$text .= "</style>";
//
//	return $text;
}

/**
 * @param $key
 *
 * @param bool $mandatory
 * @param null $default
 *
 * @param string $delimiter
 *
 * @return array|null
 */
function get_param_array( $key, $mandatory = false, $default = null, $delimiter = ","  )
{
	if ( isset( $_GET[ $key ] ) )
	{
		$k = $_GET[ $key ];

		return explode( $delimiter, $k );
	}
	if ( $mandatory ) {
		die ("Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
	} else {
		return $default;
	}

	return null;
}

// Return reference is useful for summing table, e.g.

/**
 * @param $args
 * @param $key
 * @param $default
 *
 * @return mixed
 */
function &GetArg($args, $key, $default)
{
	if (! $args or ! isset($args[$key])) return $default;
	return $args[$key];
}

/**
 * @param $key
 * @param bool $mandory
 * @param null $default
 *
 * @return mixed|null
 */
function get_param( $key, $mandory = false, $default = null ) {
	if ( isset( $_GET[ $key ] ) ) {
		return $_GET[ $key ];
	}

	if ( $mandory ) {
		die ("Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
	} else {
		return $default;
	}
}

function quote_percent($text)
{
	return '"%' . $text . '%"';
}

/**
 * @param $num_or_text
 *
 * @return string
 */

function quote_text( $num_or_text ) {
	if ( is_null( $num_or_text ) ) {
		return '"NULL"';
	}

	if ( is_numeric( $num_or_text ) ) {
		return $num_or_text;
	}

	return "'" . $num_or_text . "'";
}

/**
 * @param $url
 * @param $addition
 *
 * @return string
 */
function append_url( $url, $addition ) {
	if ( strstr( $url, "?" ) ) {
		return $url . "&" . $addition;
	}

	return $url . "?" . $addition;
}

/**
 * @param $array
 * @param bool $quote
 *
 * @return array|string
 */
function comma_implode( $array, $quote = false ) {
	if ( is_null( $array ) ) {
		return "";
	}
	if ( is_bool( $array ) ) {
		return $array;
	}
	if ( ! is_array( $array ) ) {
		return $array;
	}
	if ( is_string( reset($array) ) ) {
		$result = "";
		foreach ( $array as $s ) {
			if ( $quote ) {
				$result .= quote_text( $s ) . ", ";
			} else {
				$result .= $s . ", ";
			}
		}

		return trim( $result, ", " );
	}
	$result = "";
	foreach ( $array as $var ) { // not string...
		if ( isset( $var->name ) ) {
			$result .= $var->name;
			$result .= ", ";
		} else
			$result .= $var . ", ";
	}

	return rtrim( $result, ", " );
}

/**
 * @param mixed ...$elems
 *
 * @return array|string
 */
function comma_implode_v( ... $elems ) {
	$array = array();
	foreach ( $elems as $elem ) {
		array_push( $array, $elem );
	}

	return comma_implode( $array );
}

/**
 * @param $array
 * @param $search_key
 *
 * @return int|string
 */
function get_next_array( $array, $search_key ) {
	$prev_key = null;

	foreach ( $array as $key => $value ) {
		if ( $search_key == $prev_key ) {
			return $key;
		}
		$prev_key = $key;
	}
}
//function comma_implode( $array, $space = " " ) {
//	$str = implode( "," . $space, $array );
//
//	return rtrim( $str, ", " );
//}

/**
 * @param $str
 */
function debug_time_output( $str ) {

	$micro_date = microtime();
	$date_array = explode( " ", $micro_date );
	$date       = date( "Y-m-d H:i:s", $date_array[1] );
	echo "$str $date:" . $date_array[0] . "<br>";
}

/**
 * @param $str
 */
function debug_time_log( $str ) {
	static $prev_time;
	if ( $str == "reset" || ! is_numeric($prev_time)){
		$prev_time = microtime();

		return;
	}
	$now         = microtime();
	$micro_delta = $now - $prev_time;
	$date_array  = explode( " ", $micro_delta );
	$date        = date( "s", $date_array[1] );
	if ( $micro_delta > 0.05 ) {
		my_log( "$str $date:" . $date_array[0] . "<br>", "performance" );
	}
	$prev_time = $now;
}

/**
 * @param $s
 * @param $a
 */
function sum_numbers( &$s, $a ) {
//	if (strstr($a, "<a")){
//		print strstr("'</a>", $a) . "<br/>";
//		print strstr("'>", $a) . "<br/>";
//		print "h";
//		 $n = substr($a, strstr("'>", $a), strstr("'</a>", $a) - strstr("'>", $a));
//		 print $n;
//	} else
	$n = floatval( $a );
//	var_dump($s); print "<br/>";
//	var_dump($a); print "<br/>";
	if ( is_numeric( $s ) and is_numeric( $n ) ) {
		$s = round( $s + $n, 2 );
//		 print "a=" . $a . " s=" . $s . " n=" . $n . "<br/>";
	}
}

// diffline and computeDiff are from https://stackoverflow.com/questions/321294/highlight-the-difference-between-two-strings-in-php
/**
 * @param $line1
 * @param $line2
 *
 * @return string
 */
function diffline($line1, $line2)
{
	$diff = computeDiff(str_split($line1), str_split($line2));
	$diffval = $diff['values'];
	$diffmask = $diff['mask'];

	$n = count($diffval);
	$pmc = 0;
	$result = '';
	for ($i = 0; $i < $n; $i++)
	{
		$mc = $diffmask[$i];
		if ($mc != $pmc)
		{
			switch ($pmc)
			{
				case -1: $result .= '</del>'; break;
				case 1: $result .= '</ins>'; break;
			}
			switch ($mc)
			{
				case -1: $result .= '<del>'; break;
				case 1: $result .= '<ins>'; break;
			}
		}
		$result .= $diffval[$i];

		$pmc = $mc;
	}
	switch ($pmc)
	{
		case -1: $result .= '</del>'; break;
		case 1: $result .= '</ins>'; break;
	}

	return $result;
}

/**
 * @param $from
 * @param $to
 *
 * @return array
 */
function computeDiff($from, $to)
{
	$diffValues = array();
	$diffMask = array();

	$dm = array();
	$n1 = count($from);
	$n2 = count($to);

	for ($j = -1; $j < $n2; $j++) $dm[-1][$j] = 0;
	for ($i = -1; $i < $n1; $i++) $dm[$i][-1] = 0;
	for ($i = 0; $i < $n1; $i++)
	{
		for ($j = 0; $j < $n2; $j++)
		{
			if ($from[$i] == $to[$j])
			{
				$ad = $dm[$i - 1][$j - 1];
				$dm[$i][$j] = $ad + 1;
			}
			else
			{
				$a1 = $dm[$i - 1][$j];
				$a2 = $dm[$i][$j - 1];
				$dm[$i][$j] = max($a1, $a2);
			}
		}
	}

	$i = $n1 - 1;
	$j = $n2 - 1;
	while (($i > -1) || ($j > -1))
	{
		if ($j > -1)
		{
			if ($dm[$i][$j - 1] == $dm[$i][$j])
			{
				$diffValues[] = $to[$j];
				$diffMask[] = 1;
				$j--;
				continue;
			}
		}
		if ($i > -1)
		{
			if ($dm[$i - 1][$j] == $dm[$i][$j])
			{
				$diffValues[] = $from[$i];
				$diffMask[] = -1;
				$i--;
				continue;
			}
		}
		{
			$diffValues[] = $from[$i];
			$diffMask[] = 0;
			$i--;
			$j--;
		}
	}

	$diffValues = array_reverse($diffValues);
	$diffMask = array_reverse($diffMask);

	return array('values' => $diffValues, 'mask' => $diffMask);
}

/**
 * @param $rows
 *
 * @return array
 * @throws Exception
 */
function array_transpose($rows)
{
	$target = array();
	foreach ($rows as $i => $row){
		if (isset($row[0])){ // sequential
			throw new Exception("row $i, send assoc array");
		}
		foreach ($row as $j => $cell){
			$target[$j][$i] = $cell;
		}
	}
	return $target;
}

/**
 * @param $array
 *
 * @return array|null
 */
function array_assoc($array)
{
	if (! $array) return null;
	if (! isset($array[0])) return $array;
	$new = array();

	foreach($array as $cell){
		// print "adding $cell<br/>";
		$new[$cell] = $cell;
	}
	// var_dump($new);
	return $new;
}

/**
 * @param $key
 *
 * @return false|string
 */
function mnemonic3($key)
{
	global $mn;
//	 var_dump($mn); print "<br/>";
	$chars = "abcdefghijklmnopqrstuvwxyz123456789";
	if (isset ($nm[$key])) return $mn[$key];

	$short_key = $key;
//	print "sk=$short_key<br/>";

	// For meta fields.
	if (($s = strpos($key, '/'))) {
		$short_key = substr ($key, $s + 1);
		// print "sk=$short_key<br/>";
	}

	// Try all 3 letters.
	$poss = substr($short_key, 0, 3);
//	print "poss=$poss<br>";
	if (! mn_used($poss) and (strlen($poss) == 3)) {
		$mn[$short_key] = $poss;
		return $poss;
	}

	// If already used, take 2 letters and the first that is available.
	for ($i = 0; $i < strlen($chars); $i ++){
		$poss = substr($short_key, 0, 2) . substr($chars, $i, 1);
//		print "poss=$poss<br/>";
		if (! mn_used($poss)) {
			$mn[$short_key] = $poss;
			return $poss;
		}
	}
//	print "not found";
	return "not";
}

/**
 * @param $string_array
 *
 * @return array|null
 */
function comma_array_explode($string_array)
{
	if (! $string_array) return null;
	$string_array = str_replace("::", ":", $string_array);
	$teams = array();
	$t = [];

	while (strlen($string_array) > 1) {
		$p = strpos($string_array, ":", 1);
		$team = substr($string_array, 1, $p - 1);
		// print "Team: $team<br/>";

		$t[] = $team;
//		print "p=$p<br/>";
		if ($team > 0) array_push($teams, $team);
		$string_array = substr($string_array, $p);
	}
	return $t;
}

/**
 * @param $var
 */
function debug_var($var)
{
	if (get_user_id() !== 1) return;
	$debug = debug_backtrace();
	for ($i = 0; $i < count($debug); $i++){
		if (isset($debug[$i]['file'])) $caller = basename($debug[$i]['file']) . " "; else $caller = "";
		if (isset($debug[ $i ]["line"])) $line = ":" . $debug[ $i ]["line"]; else $line = "";

		print $i . ")" . $caller . $debug[$i]["function"] . $line . ": <Br/>";
    }

	if (is_array($var)) var_dump($var);
	else
		if (is_string($var)) print $var;
		else var_dump($var);

	print "<br/>";
}

/**
 * @param $str
 *
 * @return string
 */
function encodeURIComponent($str) {
	$revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
	return strtr(rawurlencode($str), $revert);
}

/**
 * @param null $tz
 *
 * @return mysqli
 * @throws Exception
 */
function reconnect_db($tz = null)
{
	if (! defined("DB_HOST")) throw new Exception("DB configuration error = host");
	if (! defined ("DB_USER")) throw new Exception("DB configuration error = user");
	if (! defined ("DB_PASSWORD")) throw new Exception("DB configuration error = password");
	if (! defined ("DB_NAME")) throw new Exception("DB configuration error = name");
	// print "connecting" . __LINE__ . "<br/>";

	$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	get_sql_conn($conn);
	sql_set_time_offset();
	// print IM_CHARSET;
	$charset = 'utf8';
	if (defined('IM_CHARSET')) $charset = IM_CHARSET;
	if (! mysqli_set_charset( $conn, $charset )){
		my_log("encoding setting failed");
		die("encoding setting failed");
	}
	// Local and international staff...
	// Todo: get it from user profile
	if ($tz) date_default_timezone_set( $tz );

	return $conn;
}

/**
 * @param $plugin_name
 * @param $textdomain
 * @param string $tz
 *
 * @return mysqli|null
 * @throws Exception
 */
function boot_no_login($plugin_name, $textdomain, $tz = "Asia/Jerusalem")
{
	$conn = get_sql_conn();

	if (! $conn){
		$conn = reconnect_db($tz);
		$locale = get_locale();
		if ($locale != 'en_US'){
//			$mofile = ROOT_DIR . '/wp-content/languages/plugins/im_haadama-' . $locale . '.mo';
//			if (! load_textdomain('im-haadama', $mofile))
			$mofile = ROOT_DIR . '/wp-content/languages/plugins/' . $plugin_name . '-' . $locale . '.mo';
			if (! load_textdomain($textdomain, $mofile))

				print "load translation failed . $locale: $mofile";
		}
	}

	return $conn;
}

/**
 * @param $user
 * @param $password
 *
 * @return bool
 */
function check_password($user, $password)
{
	// For now hardcoded.
	if ($user != "im-haadama" or $password != "Wer95%pl")
		return false;

	return true;
}

/**
 * @return bool
 */
function developer()
{
	return (get_user_id() == 1);
}

/**
 * @return string
 */
function randomPassword() {
	$alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	$pass        = array(); //remember to declare $pass as an array
	$alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache
	for ( $i = 0; $i < 8; $i ++ ) {
		$n      = rand( 0, $alphaLength );
		$pass[] = $alphabet[ $n ];
	}

	return implode( $pass ); //turn the array into a string
}

/**
 * @param $url
 *
 * @return bool|string
 */
function curl_get( $url ) {
	$handle = curl_init();
	curl_setopt( $handle, CURLOPT_URL, $url );
	curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );

	$data = curl_exec( $handle );
	curl_close( $handle );

	return $data;
}

/**
 * @param $day_of_week
 *
 * @return mixed|string
 */
function day_name($day_of_week)
{
	return date_day_name('2019-09-' . ($day_of_week + 1));
}

/**
 * @param $date (string)
 *
 * @return mixed|string
 */
function date_day_name($date)
{
	$day_names = [];
	$day_names['he_IL'] = array("יום א'", "יום ב'", "יום ג'", "יום ד'", "יום ה'", "יום ו'", "שבת");

	$year = strtok($date, "-"); $month = strtok("-"); $day = strtok("-");
	if (! ($year > 0 and $month > 0 and $day > 0))
	{
		print $year. " " . $month . " " . $day . "<br/>";
		print sql_trace();
		die (__FUNCTION__ . ": invalid date $date");
	}
//	if (is_numeric($date )) {
//		print "numeric ";
//		$date = date('w', $date);
//	} else {
//		print "otherwise ";
//		$date = date('w', strtotime($date));
//	}

	$day = date('w', strtotime($date));
	$locale = get_locale();
	if (isset($day_names[$locale][$day])) return $day_names[$locale][$day];
	return strftime('%A', $date);
}
