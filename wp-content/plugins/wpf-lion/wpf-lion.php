<?php
/**
 * Plugin Name: wpf-lion
 * Plugin URI: https://e-fresh.co.il
 * Description: Lion's tools
 * Version: 1.0
 * Author: agla
 * Author URI: http://aglamaz.com
 * Text Domain: wpf
 *
 */

add_shortcode( "coupon", 'wpf_get_coupon' );
//add_filter('wpcf7_mail_components', 'wpf_add_coupon');
add_filter('wpcf7_mail_components', 'wpf_add_coupon', 11, 3);
add_action('init', 'lion_start');

function WPFGetSqlConn($new_conn = null)
{
	global $wpdb;

//	var_dump($wpdb);

	return $wpdb->dbh;
}

function WPFTableExists( $table ) {
	$db_prefix = '';
	$sql = 'SELECT 1 FROM ' . $db_prefix .$table . ' LIMIT 1';
//	print $sql;
	return WPFSqlQuery( $sql, false) != null;
}

function WPFMyLog( $msg, $title = '', $file = 'fresh.log' )
{
//		if (! (strlen($title) > 2)) $title = debug_trace(2);
	if (is_array($msg)) $msg = StringVar($msg);
	$error_file = WC_LOG_DIR . $file;
	$date = date( 'd.m.Y h:i:s' );
	$msg  = print_r( $msg, true );
	$log  = $date . ": " . $title . "  |  " . $msg . "\n";
	error_log( $log, 3, $error_file );
}

function WPFSqlError( $sql ) {
	try {
		$conn = WPFGetSqlConn();
	} catch ( Exception $e ) {
		print "Error (1): " . $e->getMessage() . "<br/>";
	}
	if ( is_string( $sql ) ) {
		$message = "Error: sql = `" . $sql;
		if ($conn) $message .= "`. Sql error : " . mysqli_error( $conn ) . "<br/>";
		else $message .= "not connected";
//		print debug_trace(6);
	} else {
		$message = $sql->error;
		// $message = "sql not string";
	}
	WPFMyLog( $message );
	print "<div style=\"direction: ltr;\">" . $message . "</div>";
}

function WPFSqlQuery( $sql, $report_error = true )
{
	try {
		$conn = WPFGetSqlConn();
	} catch ( Exception $e ) {
		print "Error (2): " . $e->getMessage() . "<br/>";
		die(1);
	}
	if ( ! $conn ) {
		WPFSqlError("Error (3): not connected");
//		print debug_trace(10);
		return null;
	}
	$prev_time         = microtime(true);
	if ( $result = mysqli_query( $conn, $sql ) ) {
		$now         = microtime(true);
		$micro_delta = $now - $prev_time;
		if ( $micro_delta > 0.1 ) {
			$report = debug_trace();
			$report .= "long executing: " . $sql . " " . $micro_delta . "<br>";
			WPFMyLog($report, "sql performance", "sql_performance" . date('m-j'));
		}
		return $result;
	}
	if ( $report_error ) WPFSqlError( $sql );
	return null;
}

function WPFGetClientIP()
{
	$ipaddress = 'UNKNOWN';
	$keys=array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR');
	foreach($keys as $k)
	{
		if (isset($_SERVER[$k]) && !empty($_SERVER[$k]) && filter_var($_SERVER[$k], FILTER_VALIDATE_IP))
		{
			$ipaddress = $_SERVER[$k];
			break;
		}
	}
	return $ipaddress;
}

function lion_start()
{
	if (! isset($_GET["coupon"])) return;

	if (! WPFTableExists("wpf_coupon"))
		WPFSqlQuery("create table wpf_coupon
(
	id int auto_increment
		primary key,
	ip varchar(20),
	 coupon varchar(100))
	charset = utf8");

	WPFSqlQuery("insert into wpf_coupon (ip, coupon) values ('" . WPFGetClientIP() . "', 
	'" . $_GET["coupon"] . "')");
//	WPFMyLog("init get coupon=" . StringVar($_GET));
//	WPFMyLog("init coupon=" . StringVar($_POST));
}

function WpfGetParam( $key, $mandory = false, $default = null ) {
	if ( isset( $_GET[ $key ] ) ) {
		return $_GET[ $key ];
	}

	if ( $mandory ) {
		die ( "Error: " . __FUNCTION__ . " key " . $key . " not supplied" );
	} else {
		return $default;
	}
}

function wpf_get_coupon() {
	$code = WPFGetCoupon();

	if ( $code ) {
		return "<p dir=rtl>קוד ההנחה שלך: $code</p>";
	}
}

function wpf_add_coupon($mail_components, $b = null, $c = null)
{
	WPFMyLog(WPFStringVar($mail_components));
	WPFMyLog(WPFStringVar($b));
	WPFMyLog(WPFStringVar($c));
	$coupon =  WPFGetCoupon();
	if (! $coupon) return $mail_components;

	$mail_components['body'] = str_replace("[coupon]", "הקופון שניתן:" . $coupon, $mail_components['body']);
	WPFMyLog($mail_components['body']);
	return $mail_components;

//	return
}

function WPFGetCoupon()
{
	return WPFSqlQuerySingleScalar("select coupon from wpf_coupon where ip = '" . WPFGetClientIP() . "' order by id desc limit 1");
}

function WPFSqlQuerySingleScalar( $sql, $report_error = true ) {
	$result = WPFSqlQuery( $sql, $report_error );
	if ( ! $result ) {
		if ( $report_error ) {
			SqlError( $sql );
		}

		return "Error";
	}
	// print gettype($result) . "<br/>";
	if ( gettype( $result ) != "object" ) {
		var_dump( $result );
		print "<br/>";
		print "bad result. sql= $sql<br/>";
		print "result = $result<br/>";
		die( 2 );
	}
	$arr = mysqli_fetch_row( $result );
	if (! $arr)
		return null;

	return $arr[0];
}

// For debug
if (1) {
	function WPFStringVar( $var ) {
		ob_start();
		var_dump( $var );
		$output = ob_get_contents();
		print "o=$output";
		ob_end_clean();

		return $output;
	}
}