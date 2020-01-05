<?php

/**
 * @param bool $force_login
 *
 * @return int
 */
if (! function_exists('get_user_id')) {
	function get_user_id( $force_login = false ) {
		if ( function_exists( 'wp_get_current_user' ) ) {
			$current_user = wp_get_current_user();
			if ( $current_user->ID ) {
				return $current_user->ID;
			}
			if ( $force_login ) {
				force_login(); // Redirects to login form.
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
	function get_param( $key, $mandory = false, $default = null ) {
		if ( isset( $_GET[ $key ] ) ) {
			return $_GET[ $key ];
		}

		if ( $mandory ) {
			die ( "Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
		} else {
			return $default;
		}
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	function quote_percent( $text ) {
		return '"%' . $text . '%"';
	}

	/**
	 *
	 */
	function disable_translate() {
		im_translate( "DisableTranslate" );
	}

	/**
	 * @param $text
	 * @param null $arg
	 *
	 * @return string|void
	 */
	function im_translate( $text, $arg = null ) {
		static $translate_enabled = true; // Had problems with global variable changed somehow.

		if ( $text === "DisableTranslate" ) {
			$translate_enabled = false;
			return;
		}

		if ( ! $translate_enabled ) return $text;

		$textdomain = GetArg( $arg, "textdomain", 'wpf' );

		if ( is_array( $text ) ) {
			$result = "";
			foreach ( $text as $text_part )  $result .= im_translate( $text_part, $arg );

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
	function float_to_time( $time ) {
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

}