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
							$text .= curl_get( $file );
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

}