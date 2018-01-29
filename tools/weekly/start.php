<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/17
 * Time: 18:11
 */
require_once( '../r-shop_manager.php' );
require_once( '../multi-site/multi-site.php' );
require_once( '../orders/orders-common.php' );
require_once( '../supplies/supplies.php' );
require_once( '../pricelist/pricelist.php' );
require_once( '../gui/inputs.php' );
print header_text();

if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	switch ( $operation ) {
		case "reset_inventory":
			print gui_header( 1, "מאפס מלאי" );
			reset_inventory();
			if ( MultiSite::LocalSiteID() == 1 ) {
//				print gui_header( 2, "מאתחל רשימה של אמיר" );
//				$PL = new PriceList( 100004 );
//				$PL->RemoveLines( 1 );
//				$PL->RemoveLines( 2 );
				print gui_header( 2, "יוצר הזמנות למנויים" );
				orders_create_subs();
			}
			die ( 0 );
			break;
	}
} else {
	$open = display_active_supplies( array( 1 ) );
	if ( $open ) {
		print gui_header( 2, "אספקות לטיפול" );
		print $open;
	}
	$got = display_active_supplies( array( 3 ) );
	if ( $got ) {
		print gui_header( 2, "אספקות בדרך" );
		print $got;
	}
//	print "<br/><B>" . "יש לסגור הספקות לפני איפוס שבועי!" . "</B><br/>";
//	print "<br/><B>" . "איפוס שבועי מוחק את הרשימה של אמיר בן יהודה!" . "</B><br/>";
	print gui_hyperlink( "האם ברצונך לאפס את המלאי?", "start.php?operation=reset_inventory" );
}

function reset_inventory() {
	global $conn;
	$sql    = "UPDATE im_supplies SET status = 9 WHERE status IN (1, 3)";
	$result = mysqli_query( $conn, $sql );

	$sql    = "SELECT max(id) FROM im_supplies";
	$result = mysqli_query( $conn, $sql );
	$row    = mysqli_fetch_row( $result );

	$last_supply = $row[0];

	print "last supply: " . $last_supply . "<br/>";

	$sql    = "SELECT max(id) FROM im_delivery";
	$result = mysqli_query( $conn, $sql );
	$row    = mysqli_fetch_row( $result );

	$last_delivery = $row[0];

	print "last delivery: " . $last_delivery . "<br/>";

	$sql = "create or replace view i_in as " .
	       " select product_id, sum(quantity) as q_in " .
	       " from im_supplies_lines l " .
	       " join im_supplies s " .
	       " where supply_id > " . $last_supply . " and l.status < 8 " .
	       " and s.status < 9 " .
	       " and s.id = l.supply_id " .
	       " group by 1";

	$result = mysqli_query( $conn, $sql );
	if ( ! $result ) {
		sql_error( $sql );
	}
	$sql = "UPDATE im_info SET info_data = " . $last_supply . " WHERE info_key = 'inventory_in'";
	// print $sql;
	sql_query( $sql );
	if ( mysqli_affected_rows( $conn ) < 1 ) {
		sql_query( "insert into im_info  (info_key, info_data)
 					Values('inventory_in', $last_supply)" );
	}

	$sql = "create or replace view i_out as " .
	       " select prod_id, round(sum(dl.quantity),1) as q_out " .
	       " from im_delivery_lines dl" .
	       " where dl.delivery_id > " . $last_delivery .
	       " group by 1 ORDER BY  1";

	sql_query( $sql );

	sql_query( "UPDATE im_info SET info_data = " . $last_delivery . " where info_key = 'inventory_out'" );

}


