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
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( "../delivery/missions.php" );
print header_text();

if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	switch ( $operation ) {
		case "reset_inventory":
//			print gui_header( 1, "מאפס מלאי" );
//			reset_inventory();
//				print gui_header( 2, "מאתחל רשימה של אמיר" );
//				$PL = new PriceList( 100004 );
//				$PL->RemoveLines( 1 );
//				$PL->RemoveLines( 2 );
			print gui_header( 2, "יוצר הזמנות למנויים" );
			orders_create_subs();
			print gui_header( 1, "משימות" );
			if ( MultiSite::IsMaster() ) {
				print "יוצר חדשות<br/>";
				create_missions();
				print MultiSite::RunAll( "multi-site/sync-data.php?table=im_missions&operation=update&source=" . MultiSite::LocalSiteID() );
			} else {
				print "מעתיק ממסטר - עדיין לא פעיל<br/>";
			}
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

function create_missions() {
	$this_week = date( "Y-m-d", strtotime( "last sunday" ) );
	$sql       = "SELECT id FROM im_missions WHERE FIRST_DAY_OF_WEEK(date) = '" . $this_week . "' order by 1";
//	print $sql;

	$result = sql_query( $sql );
	while ( $row = sql_fetch_row( $result ) ) {
		$mission_id = $row[0];
		print "משכפל את משימה " . $mission_id . "<br/>";

		duplicate_mission( $mission_id );
	}
}

function reset_inventory() {
	global $conn;
	$sql    = "UPDATE im_supplies SET status = 9 WHERE status IN (1, 3)";
	sql_query( $sql );

	$sql    = "SELECT max(id) FROM im_supplies";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	$last_supply = $row[0];

	$sql    = "SELECT max(id) FROM im_delivery";
	$result = sql_query( $sql );
	$row    = mysqli_fetch_row( $result );

	$last_delivery = $row[0];

	sql_query( "delete from im_info where info_key = 'inventory_in'" );
	sql_query( "insert into im_info  (info_key, info_data)
 					Values('inventory_in', $last_supply)" );

	sql_query( "delete from im_info where info_key = 'inventory_out'" );
	sql_query( "insert into im_info  (info_key, info_data)
 					Values('inventory_out', $last_delivery)" );

	do_reset_inventory( $last_supply, $last_delivery );
}

function do_reset_inventory( $last_supply, $last_delivery ) {
	global $conn;

	print "last supply: " . $last_supply . "<br/>";


	print "last delivery: " . $last_delivery . "<br/>";

	$sql = "create or replace view i_in as " .
	       " select product_id, sum(quantity) as q_in " .
	       " from im_supplies_lines l " .
	       " join im_supplies s " .
	       " where supply_id > " . $last_supply . " and l.status < 8 " .
	       " and s.status < 9 " .
	       " and s.id = l.supply_id " .
	       " group by 1";

	sql_query( $sql );

	$sql = "create or replace view i_out as " .
	       " select prod_id, round(sum(dl.quantity),1) as q_out " .
	       " from im_delivery_lines dl" .
	       " where dl.delivery_id > " . $last_delivery .
	       " group by 1 ORDER BY  1";

	sql_query( $sql );
}


