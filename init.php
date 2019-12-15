<?php

//require_once(ABSPATH . 'niver/data/sql.php');
//
//global $script_files;
//init($script_files);
//
//function init($script_files = null)
//{
////	print "uid=" . get_user_id() . "<br/>";
//	// Singleton connection for the application.
//	if (0 and get_user_id() == 2) $debug = 1;
//	else $debug = 0;
//
//	$conn = get_sql_conn();
//
//	if (! $conn){
//		if ($debug) print "connecting...";
//		if (! defined("DB_HOST")) throw new Exception("DB configuration error = host");
//		if (! defined ("DB_USER")) throw new Exception("DB configuration error = user");
//		if (! defined ("DB_PASSWORD")) throw new Exception("DB configuration error = password");
//		if (! defined ("DB_NAME")) throw new Exception("DB configuration error = name");
//		// print "connecting" . __LINE__ . "<br/>";
//
//		$conn = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
//		get_sql_conn($conn);
//		sql_set_time_offset();
//		// print IM_CHARSET;
//		$charset = 'utf8';
//		if (defined('IM_CHARSET')) $charset = IM_CHARSET;
//		if (! mysqli_set_charset( $conn, $charset )){
//			my_log("encoding setting failed");
//			die("encoding setting failed");
//		}
//		if ($debug) print "done<br/>";
//		// Local and international staff...
//		// Todo: get it from user profile
//		date_default_timezone_set( "Asia/Jerusalem" );
//
//		if ($debug) print "loading translation<br/>";
//		$locale = get_locale();
//		if ($locale != 'en_US'){
//			$mofile = ROOT_DIR . '/wp-content/languages/plugins/im_haadama-' . $locale . '.mo';
//			if (! load_textdomain('im-haadama', $mofile))
//				print "load translation failed . $locale: $mofile";
//		}
//	}
//
//	if ($script_files)
//		print load_scripts( $script_files );
//
//	if ($debug){
//		print "getting...<Br/>";
//		$c = get_sql_conn();
//		var_dump($c);
//	}
//	return $conn;
//}
