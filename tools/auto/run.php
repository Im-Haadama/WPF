<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/08/18
 * Time: 04:37
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

return; // Not used
require_once( TOOLS_DIR . "/im_tools.php" );
require_once( TOOLS_DIR . "/options.php" );
require_once( TOOLS_DIR . "/delivery/missions.php" );
// require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/supplies/supplies.php" );
require_once( TOOLS_DIR . "/pricelist/pricelist-process.php" );

// DEBUG = 1. output on screen
$debug = 1;
//$gap_key = "run_gap";
//$run_gap = info_get( $gap_key );
//if ( $debug ) {
//	$run_gap = 5;
//} // debug mode
//$date_format = "h:m:s";
//
//if ( ! $run_gap ) {
//	// Set default
//	$run_gap = 600;
//	print "setting default run_gap to " . $run_gap . "<br/>";
//	info_update( "run_gap", $run_gap );
//}
//
ob_start();
$this_run_time = time();
print "run started " . date( $date_format ) . "\n";

// Check last run
$key      = "weekly_run";
$last_run = info_get( $key );

print "last_run: " . $last_run . "\n";
print "this_run_time: " . $this_run_time . "\n";
if ( $this_run_time - $last_run < $run_gap and $debug == 0 ) {
	print "no need to run\n";
	close_file( $debug );

	return;
}
// print $run . " " . $create_time . "<br/>";

auto_mail();

// TODO: Check permission
//if ( ImMultiSite::isMaster() ) {
//	duplicate_week();
//} else {
//	//require_once( TOOLS_DIR . "/delivery/sync-from-master.php" );
//}

update_remotes();

//if ( MultiSite::LocalSiteID() == 4 ) {
//	print "im haadama not proceesed<br/>";
//	// $results = "";
//	// print $results;
//}

auto_supply();

info_update( $key, $this_run_time );
close_file( $debug );

function close_file( $debug ) {

	global $date_format;
	print "run ended " . date( $date_format ) . "\n";

	$log = ob_get_clean();
//	print "log: " . $log . "<br/>";

	$file_name = ROOT_DIR . "/logs/run-" . date( 'd' ) . ".txt";
	// print "results saved to " . $file_name . "<br/>";
	$file = fopen( $file_name, "a" );
	fwrite( $file, $log );

	if ( $debug == 1 ) // debug
	{
		print nl2br( $log );
	}
}

function auto_mail() {
	require_once( TOOLS_DIR . "/orders/form.php" );
	require_once( TOOLS_DIR . "/mail.php" );

	global $business_name;
	global $support_email;

	$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key = 'auto_mail'";

	$auto_list = sql_query_array_scalar( $sql );

	print "Auto mail<br/>";

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

		if ( $day == date( 'w' ) ) {
			$subject = "מוצרי השבוע ב-" . $business_name;
			$mail    = "שלום " . get_customer_name( $client_id ) .
			           " להלן רשימת מוצרי פרוטי ";

			do {
				if ( $categ == 0 ) {
					$mail = show_category_all( false, true );
					break;
				}
				if ( $categ == "f" ) {
					$mail = show_category_all( false, true, true );
					break;
				}
				foreach ( explode( ",", $categ ) as $categ ) {
					$mail .= show_category_by_id( $categ, false, true );
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
function auto_supply() {
//	print "auto supply<br/>";
	$sql = "SELECT id FROM im_suppliers WHERE  auto_order_day = " . date( "w");

	// print $sql;
	$suppliers = sql_query_array_scalar( $sql );

	foreach ( $suppliers as $supplier_id ) {
		print "create auto order for " . get_supplier_name( $supplier_id ) . "\n";

		// $s = new Supply($supplier_id);
		$last_order = sql_query_single_scalar( "select max(date) from im_supplies where supplier = " . $supplier_id );

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
		if ( $total > sql_query_single_scalar( "select min_order from im_suppliers where id = " . $supplier_id ) ) {
			$supply = Supply::CreateSupply( $supplier_id );
			foreach ( $supply_lines as $line ) {
				$supply->AddLine( $line[0], $line[1], get_buy_price( $line[0] ) );
			}
			$supply->Send();
		} else {
			print "not enough for an order\n";
		}
//		var_dump($sold);
	}
}

function update_remotes() {
	// Update remote pricelist.
	$sql = "select id, site_id from im_suppliers where site_id is not null";

	$suppliers = sql_query( $sql );

	while ( $row = sql_fetch_row( $suppliers ) ) {
		$supplier_id = $row[0];
		pricelist_remote_site_process( $supplier_id, $results, false );

		// print $row[0] . " " . $row[1] . "<br/>";
	}
}