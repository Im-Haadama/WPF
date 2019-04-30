<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/05/18
 * Time: 07:30
 */

function my_log( $msg, $title = '' ) {
	$error_file = ROOT_DIR . '/logs/php_error.log';
//    print $error_file;
	$date = date( 'd.m.Y h:i:s' );
	$msg  = print_r( $msg, true );
	$log  = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}

function header_text( $print_logo = true, $close_header = true, $rtl = true, $script_file = false ) {
	global $business_info;
	global $logo_url;

	global $style_file;

	$text = '<html';
	if ( $rtl ) {
		$text .= ' dir="rtl"';
	}
	$text .= '>';
	$text .= '<head>';
	$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
	$text .= '<title>';
	if ( defined( $business_info ) ) {
		$text .= $business_info;
	}
	$text .= '</title>';
	// $text .= '<p style="text-align:center;">';
	if ( $print_logo ) {
		if ( ( $logo_url ) ) {
			$text .= '<img src=' . $logo_url . '>';
		}
	}
	$text .= '</p>';
	if ( isset( $style_file ) ) {
		$text .= "<style>";
		$text .= file_get_contents( $style_file );
		$text .= "</style>";
	}
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
					$text .= '<script type="text/javascript" src="' . $file . '"></script>';
				}
				break;
			}
			print $script_file . " not added<br/>";
		} while ( 0 );
	}
	if ( $close_header ) {
		$text .= '</head>';
	}

//	print $text;

	return $text;
}

function footer_text() {
	$text = gui_div( "footer", "Fresh store powered by Niver Dri Sol 2015-2019 Version 1.6.2. עם האדמה 2013", true );

	return $text;
}

function get_param_array( $key ) {
	if ( isset( $_GET[ $key ] ) ) {
		$k = $_GET[ $key ];

		return explode( ",", $k );
	}

	return null;
}

function get_param( $key, $mandory = false, $default = null ) {
	if ( isset( $_GET[ $key ] ) ) {
		return $_GET[ $key ];
	}

	if ( $mandory ) {
		die ( "key " . $key . " not supplied" );
	} else {
		return $default;
	}
}

function quote_text( $num_or_text ) {
	if ( is_null( $num_or_text ) ) {
		return "NULL";
	}

	if ( is_numeric( $num_or_text ) ) {
		return $num_or_text;
	}

	return "'" . $num_or_text . "'";
}

function append_url( $url, $addition ) {
	if ( strstr( $url, "?" ) ) {
		return $url . "&" . $addition;
	}

	return $url . "?" . $addition;
}

function comma_implode( $array, $quote = false ) {
//	print "<p dir=\"ltr\">";
//	var_dump($array);
//	print "</p>";
	if ( is_null( $array ) ) {
		return "";
	}
	if ( is_bool( $array ) ) {
		return $array;
	}
	if ( ! is_array( $array ) ) {
		var_dump( $array );
		return "not array!";
	}
	if ( is_string( $array[0] ) ) {
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
		}
	}

	return rtrim( $result, ", " );
}

function comma_implode_v( ... $elems ) {
	$array = array();
	foreach ( $elems as $elem ) {
		array_push( $array, $elem );
	}

	return comma_implode( $array );
}

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

function debug_time2( $str ) {
	$micro_date = microtime();
	$date_array = explode( " ", $micro_date );
	$date       = date( "Y-m-d H:i:s", $date_array[1] );
	echo "$str $date:" . $date_array[0] . "<br>";
}

function debug_time1( $str ) {
	return;
	static $prev_time;
	if ( $str == "reset" ) {
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
