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

//$text = show_category_all( false, true, false, false);
//
//$rc = send_mail( "da." . date('h:mm'), 'info@im-haadama.co.il', $text );

ob_start();

// For now not working on fruity.co.il. Need to check if it's configuration or other problem.
// print gui_header( 1, "Handling auto orders" );
// auto_mail();

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
	if ( ! table_exists( "im_suppliers" ) ) {
		return;
	}
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


