<?php


class Core_Fund {
	function load_scripts( $script_file = false ) {
		$text = "";
		if ( $script_file ) {
			// print "Debug: " . $script_file . '<br/>';
			// var_dump($script_file);
			do {
				if ( $script_file === true ) {
					$text .= '<script type="text/javascript" src="/core/gui/client_tools.js"></script>';
					break;
				}
				if ( is_string( $script_file ) ) {
					$text .= '<script type="text/javascript" src="' . $script_file . '"></script>';
					break;
				}
				if ( is_array( $script_file ) ) {
					foreach ( $script_file as $file ) {
						if ( strstr( $file, 'php' ) ) {
							$text .= GetContent( $file );
						} else {
							$text .= '<script type="text/javascript" src="' . $file . '"></script>';
						}
					}
					break;
				}
				print $script_file . " not added<br/>";
			} while ( 0 );
		}
		return $text;
	}
	function footer_text() {
		global $power_version;

		$text = gui_div( "footer", "Fresh store powered by Aglamaz.com 2015-2019 Version " . $power_version . " עם האדמה 2013", true );

		return $text;
	}

// diffline and computeDiff are from https://stackoverflow.com/questions/321294/highlight-the-difference-between-two-strings-in-php
	/**
	 * @param $line1
	 * @param $line2
	 *
	 * @return string
	 */
	function diffline( $line1, $line2 ) {
		$diff     = computeDiff( str_split( $line1 ), str_split( $line2 ) );
		$diffval  = $diff['values'];
		$diffmask = $diff['mask'];

		$n      = count( $diffval );
		$pmc    = 0;
		$result = '';
		for ( $i = 0; $i < $n; $i ++ ) {
			$mc = $diffmask[ $i ];
			if ( $mc != $pmc ) {
				switch ( $pmc ) {
					case - 1:
						$result .= '</del>';
						break;
					case 1:
						$result .= '</ins>';
						break;
				}
				switch ( $mc ) {
					case - 1:
						$result .= '<del>';
						break;
					case 1:
						$result .= '<ins>';
						break;
				}
			}
			$result .= $diffval[ $i ];

			$pmc = $mc;
		}
		switch ( $pmc ) {
			case - 1:
				$result .= '</del>';
				break;
			case 1:
				$result .= '</ins>';
				break;
		}

		return $result;
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return array
	 */
	function computeDiff( $from, $to ) {
		$diffValues = array();
		$diffMask   = array();

		$dm = array();
		$n1 = count( $from );
		$n2 = count( $to );

		for ( $j = - 1; $j < $n2; $j ++ ) {
			$dm[ - 1 ][ $j ] = 0;
		}
		for ( $i = - 1; $i < $n1; $i ++ ) {
			$dm[ $i ][ - 1 ] = 0;
		}
		for ( $i = 0; $i < $n1; $i ++ ) {
			for ( $j = 0; $j < $n2; $j ++ ) {
				if ( $from[ $i ] == $to[ $j ] ) {
					$ad             = $dm[ $i - 1 ][ $j - 1 ];
					$dm[ $i ][ $j ] = $ad + 1;
				} else {
					$a1             = $dm[ $i - 1 ][ $j ];
					$a2             = $dm[ $i ][ $j - 1 ];
					$dm[ $i ][ $j ] = max( $a1, $a2 );
				}
			}
		}

		$i = $n1 - 1;
		$j = $n2 - 1;
		while ( ( $i > - 1 ) || ( $j > - 1 ) ) {
			if ( $j > - 1 ) {
				if ( $dm[ $i ][ $j - 1 ] == $dm[ $i ][ $j ] ) {
					$diffValues[] = $to[ $j ];
					$diffMask[]   = 1;
					$j --;
					continue;
				}
			}
			if ( $i > - 1 ) {
				if ( $dm[ $i - 1 ][ $j ] == $dm[ $i ][ $j ] ) {
					$diffValues[] = $from[ $i ];
					$diffMask[]   = - 1;
					$i --;
					continue;
				}
			}
			{
				$diffValues[] = $from[ $i ];
				$diffMask[]   = 0;
				$i --;
				$j --;
			}
		}

		$diffValues = array_reverse( $diffValues );
		$diffMask   = array_reverse( $diffMask );

		return array( 'values' => $diffValues, 'mask' => $diffMask );
	}

	/**
	 * @param $rows
	 *
	 * @return array
	 * @throws Exception
	 */
	static function array_transpose( $rows ) {
		$target = array();
		foreach ( $rows as $i => $row ) {
			if ( isset( $row[0] ) ) { // sequential
				throw new Exception( "row $i, send assoc array" );
			}
			foreach ( $row as $j => $cell ) {
				$target[ $j ][ $i ] = $cell;
			}
		}

		return $target;
	}

	/**
	 * @param $array
	 *
	 * @return array|null
	 */
	static function array_assoc( $array ) {
		if ( ! $array ) return null;
		if ( ! isset( $array[0] ) ) return $array;

		$new = array();

		if (! is_array($array)) return $array;

		foreach ( $array as $cell ) {
			// print "adding $cell<br/>";
			$new[ $cell ] = $cell;
		}

		// var_dump($new);
		return $new;
	}

	/**
	 * @param $key
	 *
	 * @return false|string
	 */
	function mnemonic3( $key ) {
		global $mn;
//	 var_dump($mn); print "<br/>";
		$chars = "abcdefghijklmnopqrstuvwxyz123456789";
		if ( isset ( $nm[ $key ] ) ) {
			return $mn[ $key ];
		}

		$short_key = $key;
//	print "sk=$short_key<br/>";

		// For meta fields.
		if ( ( $s = strpos( $key, '/' ) ) ) {
			$short_key = substr( $key, $s + 1 );
			// print "sk=$short_key<br/>";
		}

		// Try all 3 letters.
		$poss = substr( $short_key, 0, 3 );
//	print "poss=$poss<br>";
		if ( ! mn_used( $poss ) and ( strlen( $poss ) == 3 ) ) {
			$mn[ $short_key ] = $poss;

			return $poss;
		}

		// If already used, take 2 letters and the first that is available.
		for ( $i = 0; $i < strlen( $chars ); $i ++ ) {
			$poss = substr( $short_key, 0, 2 ) . substr( $chars, $i, 1 );
//		print "poss=$poss<br/>";
			if ( ! mn_used( $poss ) ) {
				$mn[ $short_key ] = $poss;

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

	/**
	 * @param $var
	 */
	function debug_var( $var ) {
		print "debug";
		if ( get_user_id() !== 1 ) {
			return;
		}
		$debug = debug_backtrace();
		for ( $i = count($debug) -2 ; $i < count( $debug ); $i ++ ) {
			if ( isset( $debug[ $i ]['file'] ) ) {
				$caller = basename( $debug[ $i ]['file'] ) . " ";
			} else {
				$caller = "";
			}
			if ( isset( $debug[ $i ]["line"] ) ) {
				$line = ":" . $debug[ $i ]["line"];
			} else {
				$line = "";
			}

			print $i . ")" . $caller . $debug[ $i ]["function"] . $line . ": <Br/>";
		}

		if ( is_array( $var ) ) {
			var_dump( $var );
		} else if ( is_string( $var ) ) {
			print $var;
		} else {
			var_dump( $var );
		}

		print "<br/>";
	}

	/**
	 * @param $str
	 *
	 * @return string
	 */
	function encodeURIComponent( $str ) {
		$revert = array( '%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')' );

		return strtr( rawurlencode( $str ), $revert );
	}

	/**
	 * @param null $tz
	 *
	 * @return mysqli
	 * @throws Exception
	 */

	/**
	 * @param $plugin_name
	 * @param $textdomain
	 * @param string $tz
	 *
	 * @return mysqli|null
	 * @throws Exception
	 */
	function boot_no_login( $plugin_name, $textdomain, $tz = "Asia/Jerusalem" ) {
		$conn = get_sql_conn();

		if ( ! $conn ) {
			$conn   = ReconnectDb( $tz );
			$locale = get_locale();
			if ( $locale != 'en_US' ) {
//			$mofile = FRESH_INCLUDES . '/wp-content/languages/plugins/im_haadama-' . $locale . '.mo';
//			if (! load_textdomain('im-haadama', $mofile))
				$mofile = FRESH_INCLUDES . '/wp-content/languages/plugins/' . $plugin_name . '-' . $locale . '.mo';
				if ( ! load_textdomain( $textdomain, $mofile ) ) {
					print "load translation failed . $locale: $mofile";
				}
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
	function check_password( $user, $password ) {
		// For now hardcoded.
		if ( $user != "im-haadama" or $password != "Wer95%pl" ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	function developer() {
		return ( get_user_id() == 1 );
	}

	/**
	 * @return string
	 */
	function randomPassword() {
		$alphabet    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789`10';
		$pass        = array(); //remember to declare $pass as an array
		$alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache
		for ( $i = 0; $i < 8; $i ++ ) {
			$n      = rand( 0, $alphaLength );
			$pass[] = $alphabet[ $n ];
		}

		return implode( $pass ); //turn the array into a string
	}

	function load_style( $style_file ) {
		return '<link rel="stylesheet" type="text/css" href="' . $style_file . '">';

//	$text = "<style>";
//	$text .= file_get_contents( $style_file );
//	$text .= "</style>";
//
//	return $text;
	}

}

