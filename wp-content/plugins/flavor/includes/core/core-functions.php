<?php

/**
 * @param bool $force_login - only good in post actions (no header are sent prior to action).
 * inside shortcode processing, add action to check login - unlogged_guest_posts_redirect.
 *
 * @return int
 */
	function get_user_id( $force_login = false ) {
		if ( function_exists( 'wp_get_current_user' ) ) {
			$current_user = wp_get_current_user();
			if ( $current_user->ID ) {
				return $current_user->ID;
			}
			if ( $force_login ) {
				auth_redirect(); // Redirects to login form.
			}

			return 0;
		}
//	print "configuration error. Contact support";
//	print trace();
		return 0;
	}

	function user_has_role( $user_id, $role ) {
		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;

		if ( in_array( $role, $user_roles, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	function trace() {
		$result = "";
		$debug  = debug_backtrace();
		for ( $i = 1; $i < 10 && $i < count( $debug ); $i ++ ) {
			if ( isset( $debug[ $i ]['file'] ) ) {
				$caller = "called from " . $debug[ $i ]['file'] . " ";
			} else {
				$caller = "";
			}
			if ( isset( $debug[ $i ]["line"] ) ) {
				$line = ":" . $debug[ $i ]["line"];
			} else {
				$line = "";
			}
			$result .= '#' . $i . ' ' . ( $caller . $debug[ $i ]["function"] . $line . "<br/>" );
		}

		return $result;
	}

	function unset_by_value(&$array, $del_val)
	{
		if (($key = array_search($del_val, $array)) !== false) {
			unset($array[$key]);
		}
	}

	function GetParams($ignore_list = array())
	{
		$atts = [];
		foreach ($_GET as $param => $value)
		{
			if (!in_array($param, $ignore_list)) $atts[$param] = $value;
		}
		return $atts;
	}
	/**
	 * @param $args
	 * @param $key
	 * @param $default
	 *
	 * @return mixed
	 */
	if (! function_exists('GetArg')){
		function &GetArg( $args, $key, $default ) {
			if ( ! $args or ! isset( $args[ $key ] ) ) {
				return $default;
			}

			return $args[ $key ];
		}

	/**
	 * @param $key
	 * @param bool $mandory
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	function GetParam( $key, $mandory = false, $default = null ) {
		if ( isset( $_GET[ $key ] ) ) {
			return $_GET[ $key ];
		}

		if ( $mandory ) {
			die ( "Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
		} else {
			return $default;
		}
	}

		function GetParamArray( $key, $mandatory = false, $default = null, $delimiter = "," ) {
			if ( isset( $_GET[ $key ] ) ) {
				$k = $_GET[ $key ];

				return explode( $delimiter, $k );
			}
			if ( $mandatory ) {
				die ( "Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
			} else {
				return $default;
			}

			return null;
		}

		/**
	 * @param $text
	 *
	 * @return string
	 */
	function QuotePercent( $text ) {
		return '"%' . $text . '%"';
	}

		function QuoteText( $num_or_text, $sql_escape = false ) {
			if ( is_null( $num_or_text ) ) return '"NULL"';

			if ( is_numeric( $num_or_text ) ) return $num_or_text;

			if ($sql_escape) return "'" . escape_string($num_or_text) . "'";

			return "'" . $num_or_text . "'";
		}

		function QuoteDate($date, $format = 'Y-m-d')
		{
			return "'" . date($format, $date) . "'";
		}

		/**
	 *
	 */
	function DisableTranslate() {
		ImTranslate( "DisableTranslate" );
	}

	/**
	 * @param $text
	 * @param null $arg
	 *
	 * @return string|void
	 */
	function ImTranslate( $text, $arg = null ) {
		static $translate_enabled = true; // Had problems with global variable changed somehow.

		if ( $text === "DisableTranslate" ) {
			$translate_enabled = false;
			return;
		}

		if ( ! $translate_enabled ) return $text;

		$textdomain = GetArg( $arg, "textdomain", 'wpf' );

		if ( is_array( $text ) ) {
			$result = "";
			foreach ( $text as $text_part )  $result .= ImTranslate( $text_part, $arg );

			return $result;
		}

		if ( function_exists( 'translate' ) ) {
			$t = translate( $text, $textdomain );
		} else {
			print "no translate function";
			$t = $text;
		}
		if ( strlen( $t ) ) {
			if ( strstr( $t, "%s" ) ) {
				return $arg ? sprintf( $t, $arg ) : $t;
			}

			return $t;
		} else {
			return $text;
		}
	}

	/**
	 * @param $time float
	 *
	 * @return string
	 */
	function FloatToTime( $time ) {
//	print $time . ", ";
		if ( $time > 0 ) {
			$time += 0.00001; // 5:20 -> 5.33333333 -> 5:19.

			return sprintf( '%02d:%02d', $time, fmod( $time, 1 ) * 60 );
		}

		return "";
	}
	function InfoGet( $key, $create = false, $default = null ) {
		$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

//      print $sql ."<br/>";

		$result = sql_query_single_scalar( $sql );

		if ( is_null( $result ) ) {
			if ( $create ) {
				InfoUpdate( $key, $default );

				return $default;
			}
		}

		return $result;
	}

	function InfoUpdate( $key, $data ) {
		$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";
//      print "s1=" . $sql . "<br/>";

		$result = sql_query_single_scalar( $sql );
		if ( ! $result ) {
			$sql = "insert into im_info (info_key, info_data) VALUE ('$key', '$data')";
//              print $sql;
			sql_query( $sql );

			return;
		}
		$sql = "UPDATE im_info SET info_data = '" . $data . "' WHERE info_key = '" . $key . "'";
//              print $sql;
		sql_query( $sql );
	}
	}

	function AppendUrl( $url, $addition ) {
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
	function CommaImplode( $array, $quote = false )
	{
		if ( is_null( $array ) ) {
			return "";
		}
		if ( is_bool( $array ) ) {
			return $array;
		}
		if ( ! is_array( $array ) ) {
			return $array;
		}
		if ( is_string( reset( $array ) ) ) {
			$result = "";
			foreach ( $array as $s ) {
				if ( $quote ) {
					$result .= QuoteText( $s ) . ", ";
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
			} else {
				if (is_array($var)){
					print debug_trace(5);
					exit;
				}
				$result .= $var . ", ";
			}
		}

		return rtrim( $result, ", " );
	}

	/**
	 * @param $url
	 *
	 * @return bool|string
	 */
	function GetContent( $url ) {
		print "url: $url<br/>";
//		$handle = curl_init();
//		curl_setopt( $handle, CURLOPT_URL, $url );
//		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
//
//		var_dump($handle);
//		$data = curl_exec( $handle );
//
//		curl_close( $handle );

		$data = file_get_contents($url);
		var_dump($data);
		return $data;
	}

	function ReconnectDb( $tz = null ) {
		if ( ! defined( "DB_HOST" ) ) {
			throw new Exception( "DB configuration error = host" );
		}
		if ( ! defined( "DB_USER" ) ) {
			throw new Exception( "DB configuration error = user" );
		}
		if ( ! defined( "DB_PASSWORD" ) ) {
			throw new Exception( "DB configuration error = password" );
		}
		if ( ! defined( "DB_NAME" ) ) {
			throw new Exception( "DB configuration error = name" );
		}
		// print "connecting" . __LINE__ . "<br/>";

		$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
		get_sql_conn( $conn );
		sql_set_time_offset();
		// print IM_CHARSET;
		$charset = 'utf8';
		if ( defined( 'IM_CHARSET' ) ) {
			$charset = IM_CHARSET;
		}
		if ( ! mysqli_set_charset( $conn, $charset ) ) {
			MyLog( "encoding setting failed" );
			die( "encoding setting failed" );
		}
		// Local and international staff...
		// Todo: get it from user profile
		if ( $tz ) {
			date_default_timezone_set( $tz );
		}

		return $conn;
	}

	/**
	 * @param $str
	 */
	function DebugTimeOutput( $str ) {

		$micro_date = microtime();
		$date_array = explode( " ", $micro_date );
		$date       = date( "Y-m-d H:i:s", $date_array[1] );
		echo "$str $date:" . $date_array[0] . "<br>";
	}

	/**
	 * @param $str
	 */
	function DebugTimeLog( $str ) {
		static $prev_time;
		if ( $str == "reset" || ! is_numeric( $prev_time ) ) {
			$prev_time = microtime();

			return;
		}
		$now         = microtime();
		$micro_delta = $now - $prev_time;
		$date_array  = explode( " ", $micro_delta );
		$date        = date( "s", $date_array[1] );
		if ( $micro_delta > 0.05 ) {
			MyLog( "$str $date:" . $date_array[0] . "<br>", "performance" );
		}
		$prev_time = $now;
	}

	/**
	 * @param $s
	 * @param $a
	 */
	function SumNumbers( &$s, $a ) {
		$n = floatval( $a );
		if ( is_numeric( $s ) and is_numeric( $n ) ) {
			$s = round( $s + $n, 2 );
		}
	}

	/**
	 * @param $day_of_week
	 *
	 * @return mixed|string
	 */
	function DayName( $day_of_week ) {
		return DateDayName( '2019-09-' . ( $day_of_week + 1 ) );
	}

	/**
	 * @param $date (string)
	 *
	 * @return mixed|string
	 */
	function DateDayName( $date ) {
		$day_names          = [];
		$day_names['he_IL'] = array( "יום א'", "יום ב'", "יום ג'", "יום ד'", "יום ה'", "יום ו'", "שבת" );

		$year  = strtok( $date, "-" );
		$month = strtok( "-" );
		$day   = strtok( "-" );
		if ( ! ( $year > 0 and $month > 0 and $day > 0 ) ) {
			print $year . " " . $month . " " . $day . "<br/>";
			print sql_trace();
			die ( __FUNCTION__ . ": invalid date $date" );
		}
//	if (is_numeric($date )) {
//		print "numeric ";
//		$date = date('w', $date);
//	} else {
//		print "otherwise ";
//		$date = date('w', strtotime($date));
//	}

		$day    = date( 'w', strtotime( $date ) );
		$locale = get_locale();
//		print "locale= " . get_locale() . "<br/>";
		if ( isset( $day_names[ $locale ][ $day ] ) ) {
			return $day_names[ $locale ][ $day ];
		}

		return strftime( '%A', $date );
	}

	function GetUrl($only_base = false)
	{
		if (isset($_SERVER['REQUEST_URI'])) {
			$url = $_SERVER['REQUEST_URI'];
			if ( $only_base ) {
				$r = parse_url( $url, PHP_URL_PATH );
				if ( ! $r ) return "error";
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
	 * @param bool $encode
	 *
	 * @return string|null
	 */
	function AddParamToUrl($url, $param_name, $param_value = null) {
		$ignore_list = array( "page_number" ); // Would be removed from base url.
		$query_parts = [];
		if ( is_null( $param_value ) and ! is_array( $param_name ) ) {
			die ( __FUNCTION__ . ": bad usage" );
		}
		if ( $s = strpos( $url, '?' ) ) { // Have previous query
			// Remove url part
			$result = substr( $url, 0, $s ); // not including the ?
			$query  = substr( $url, $s + 1 ); // not including the ?
			while ( strlen( $query ) ) {
				$next_amp = strpos( $query, '&' );
				$param    = substr( $query, 0, $e = strpos( $query, '=' ) );
				$value    = $next_amp ? substr( $query, $e + 1, $next_amp - $e - 1 ) : substr( $query, $e + 1 );

				if ( ! in_array( $param, $ignore_list ) ) {
					$query_parts[ $param ] = $value;
				}
				if ( $next_amp ) {
					$query = substr( $query, $next_amp + 1 );
				} // Not including previous &
				else {
					$query = "";
				}
			}
		} else {
			$result = $url;
		}

		if ( is_array( $param_name ) ) {
//              print "is array<br/>";
			// $result = null;
			foreach ( $param_name as $key => $value ) {
				$query_parts[ $key ] = $value;
			}
		} else {
			$query_parts[ $param_name ] = $param_value;
		}

		// Build the url
		$glue = '?';
		// $result .= "?";
		foreach ( $query_parts as $param => $value ) {
			if (is_array($param)) { print __FUNCTION__ . ":param is array<br/>"; var_dump($param); die(1); }
			if (is_array($value)) { print __FUNCTION__ . ":value is array<br/>"; var_dump($value); die(1); }
			$result .= $glue . $param . '=' . $value;
			$glue   = "&";
		}

		return $result;
	}

	/**
	 * @param $param_name
	 * @param mixed ...$param_value
	 *
	 * @return string|null
	 */
	function AddToUrl($param_name, $param_value = null)
	{
		return AddParamToUrl(GetUrl(), $param_name, $param_value);
	}

	function execute_url($post, $after = null)
	{
		return "execute_url('". $post . "'" . ($after ? ", " . $after : "") . ")";
	}

	function AddAction($tag, $function_to_add, int $priority = 10, int $accepted_args = 1, $debug = 0)
	{
		if ($debug)	print "adding $tag<br/>";

		return add_action($tag, $function_to_add, $priority, $accepted_args);
	}

function info_get( $key, $create = false, $default = null ) {
	$sql = "SELECT info_data FROM im_info WHERE info_key = '" . $key . "'";

//	print $sql ."<br/>";

	$result = sql_query_single_scalar( $sql );

	if ( is_null( $result ) ) {
		if ( $create ) {
			info_update( $key, $default );

			return $default;
		}
	}

	return $result;
}
