<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/01/19
 * Time: 20:55
 */
if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . "/tools/supplies/Supply.php" );
require_once( ROOT_DIR . "/tools/multi-site/imMulti-site.php" );

$m = ImMultiSite::getInstance();

ob_start();

backup_database();

print gui_header( 1, "Handling auto orders" );
auto_mail();

print gui_header( 1, "Handling auto supply" );
auto_supply();

require_once( ROOT_DIR . "/tools/tasklist/Tasklist.php" );
print gui_header( 1, "Creating tasks from templates into tasklist" );
create_tasks( null, true );

// Local scripts - active from task_template
//$site_name = $m->getLocalSiteName();
//$local_dir = ROOT_DIR . '/tools/' . $site_name;
//if (file_exists($local_dir))
//{
//	print gui_header( 1, "Running site specific" );
//
//	$scripts = scandir($local_dir);
//
//	foreach ($scripts as $script)
//	{
//		if (strstr($script, ".php")){
//			print "running $script<br/>";
//			require_once ($script);
//
//		}
//	}
//}

// Create local database backup

print "done";

$buffer = ob_get_contents();
ob_end_clean();

print $buffer;
$log_file = ROOT_DIR . "/logs/run-" . date( 'd' ) . ".html";
$log      = fopen( $log_file, "w" );
fwrite( $log, $buffer );
fclose( $log );
return;

function auto_supply() {
	//	Run once a week, but considered daily because each supplier has it's day.
	$sql = "SELECT id FROM im_suppliers WHERE  auto_order_day = " . date( "w" );

	// print $sql;
	$suppliers = sql_query_array_scalar( $sql );
	$created   = false;

	foreach ( $suppliers as $supplier_id ) {
		print "create auto order for " . get_supplier_name( $supplier_id ) . "\n";

		// $s = new Supply($supplier_id);
		$last_order = sql_query_single_scalar( "SELECT max(date) FROM im_supplies WHERE supplier = " . $supplier_id );

		print "last: " . $last_order . "\n";
		$sold         = supplier_report_data( $supplier_id, $last_order, date( 'y-m-d' ) );
		$supply_lines = array();
		$total        = 0;
		foreach ( $sold as $k => $product ) {
			$prod_id  = $sold[ $k ][0];
			$quantity = $sold[ $k ][1];
			$price    = get_buy_price( $prod_id, $supplier_id );
			if ( $quantity > 0 ) {
				print get_product_name( $prod_id ) . " " . $quantity . "\n";
				array_push( $supply_lines, array( $prod_id, $quantity ) );
				$total += $quantity * $price;
			}
		}
		if ( $total > sql_query_single_scalar( "SELECT min_order FROM im_suppliers WHERE id = " . $supplier_id ) ) {
			$supply = Supply::CreateSupply( $supplier_id );
			foreach ( $supply_lines as $line ) {
				$supply->AddLine( $line[0], $line[1], get_buy_price( $line[0] ) );
			}
			// Manual control!
			// $supply->Send();
		} else {
			print "not enough for an order\n";
		}
		$created = true;
//		var_dump($sold);
	}
	if ( ! $created ) {
		print "Done<br/>";
	}
}

function auto_mail() {
	require_once( TOOLS_DIR . "/orders/form.php" );
	require_once( TOOLS_DIR . "/orders/orders-common.php" );
	require_once( TOOLS_DIR . "/mail.php" );

	global $business_name;
	global $support_email;

	$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key = 'auto_mail'";

	$auto_list = sql_query_array_scalar( $sql );

	print "Auto mail...<br/>";
	print "Today " . date( "w" ) . "<br/>";

	foreach ( $auto_list as $client_id ) {
		print get_customer_name( $client_id ) . "<br/>";
		$last = get_user_meta( $client_id, "last_email", true );
		if ( $last == date( 'Y-m-d' ) ) {
			print "already sent";
			continue;
		}
		$setting = get_user_meta( $client_id, 'auto_mail', true );
		$day     = strtok( $setting, ":" );
		$categ   = strtok( ":" );
		print "day: " . $day . "<br/>";
		print "categ: " . $categ . "<br/>";
		$customer_type = customer_type( $client_id );

		if ( $day == date( 'w' ) ) {
			print "שולח...<br/>";
			$subject = "מוצרי השבוע ב-" . $business_name;
			$mail    = "שלום " . get_customer_name( $client_id ) .
			           " להלן רשימת מוצרי פרוטי ";
			do {
				if ( $categ == 0 ) {
					$mail = show_category_all( false, true, false, false, $customer_type );
					break;
				}
				if ( $categ == "f" ) {
					$mail = show_category_all( false, true, true, false, $customer_type );
					break;
				}
				foreach ( explode( ",", $categ ) as $categ ) {
					$mail .= show_category_by_id( $categ, false, true, $customer_type );
				}
			} while ( 0 );
			$user_info = get_userdata( $client_id );
			$to        = $user_info->user_email . ", " . $support_email;

			$rc = send_mail( $subject, $to, $mail );
			print "subject: " . $subject . "<br/>";
			print "mail: " . $mail . "<br/>";
			print "to: " . $to . "<br/>";
			print "rc: " . $rc . "<br/>";

			update_user_meta( $client_id, "last_email", date( 'Y-m-d' ) );
		}
	}

	// Todo: remove this
}

function backup_database() {
	if ( defined( 'IM_CRON_BACKUP' ) ) {
		print "backup using cron";

		return;
	}

	if ( ! defined( 'IM_BACKUP_FOLDER' ) ) {
		print "define IM_BACKUP_FOLDER";
		die( 1 );
	}

	if ( strlen( IM_DB_NAME ) < 3 ) {
		print "define IM_DB_NAME";
		die( 2 );
	}

	$folder  = IM_BACKUP_FOLDER;
	$success = '-- Dump completed on ' . date( 'Y-m-d' );

	// print "folder: " . $folder . "<br/>";

	if ( ! file_exists( $folder ) ) {
		print "creating folder... ";
		if ( ! mkdir( $folder, 0777, true ) ) {
			print "create folder " . $folder . "<br/>";
			die ( 1 );
		}
		print "<br/>";
	}
	print "last date: " . info_get( "backup_date" ) . "<br/>";
	print "last result: " . info_get( "backup_result" ) . "<br/>";

	if ( info_get( "backup_date" ) == date( 'z' ) &&
	     info_get( "backup_result" ) == $success
	) {
		print "has successful backup<br/>";

		return;
	}

	print "running backup<br/>";

	$param_file = $folder . "/." . IM_DB_NAME;
	if ( ! file_exists( $param_file ) ) {
		$file = fopen( $param_file, "w" );
		if ( ! $file ) {
			die( "can't write" );
		}

		fwrite( $file, "[mysqldump]\n" );
		// global $conn;
		fwrite( $file, "user=" . IM_DB_NAME . "\n" );
		fwrite( $file, "password=" . IM_DB_PASSWORD . "\n" );
		fwrite( $file, "single-transaction\n" );

		fclose( $file );
	}

	$backup_file = $folder . "/" . IM_DB_NAME . '-' . date( 'Y-m-d' ) . ".sql";

	$command = "cd " . $folder . " &&  mysqldump --defaults-extra-file=" . $param_file . " " . IM_DB_NAME . " > " . $backup_file;
	// print $command . "<br/>";
	exec( $command );

	$result = exec( "tail -1 " . $backup_file );

	exec( "gzip " . $backup_file );

	// Server might gone because of the backup.
	global $conn;
	$conn = new mysqli( IM_DB_HOST, IM_DB_NAME, IM_DB_PASSWORD, IM_DB_NAME );

	if ( substr( $result, 0, 31 ) == $success ) {
		print "success<br/>";
		info_update( "backup_date", date( 'z' ) );
//		print "s=" . $success ."<br/>";
		info_update( "backup_result", $success );
	} else {
		print $result;
	}

	// print $result;

	print "done\n";

//	print "folder: " . $folder;
}
