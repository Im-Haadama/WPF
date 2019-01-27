<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/01/17
 * Time: 18:11
 */
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
require_once( '../multi-site/imMulti-site.php' );
require_once( '../orders/orders-common.php' );
require_once( '../supplies/supplies.php' );
require_once( '../pricelist/pricelist.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( "../delivery/missions.php" );
print header_text();

print "aa";
if ( isset( $_GET["operation"] ) ) {
	$operation = $_GET["operation"];
	print "op = " . $operation . "<br/>";
	switch ( $operation ) {
		case "create_inventory_view":
			do_create_inventory_views();
//			print gui_header( 1, "מאפס מלאי" );
//			reset_inventory();
//				print gui_header( 2, "מאתחל רשימה של אמיר" );
//				$PL = new PriceList( 100004 );
//				$PL->RemoveLines( 1 );
//				$PL->RemoveLines( 2 );
//			print gui_header( 2, "יוצר הזמנות למנויים" );
//			orders_create_subs();
//			print gui_header( 1, "משימות" );
//			if ( MultiSite::IsMaster() ) {
//				print "יוצר חדשות<br/>";
//				create_missions();
//				print MultiSite::RunAll( "multi-site/sync-data.php?table=im_missions&operation=update&source=" . MultiSite::LocalSiteID() );
//			} else {
//				print "מעתיק ממסטר - עדיין לא פעיל<br/>";
//			}
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


function do_create_inventory_views() {

	$sql = "create or replace view i_in as " .
	       " select product_id, sum(quantity) as q_in " .
	       " from im_supplies_lines l " .
	       " join im_supplies s " .
	       //	       " where supply_id > " . $last_supply . " and l.status < 8 " .
	       " where l.status < 8 " .
	       " and s.status < 9 " .
	       " and s.id = l.supply_id " .
	       " group by 1";

	print $sql . "<br/>";
	sql_query( $sql );

	$sql = "create or replace view i_out as " .
	       " select prod_id, round(sum(dl.quantity),1) as q_out " .
	       " from im_delivery_lines dl" .
	       //	       " where dl.delivery_id > " . $last_delivery .
	       " group by 1 ORDER BY  1";

	sql_query( $sql );
	print $sql . "<br/>";

//	$sql = "cerate or replace VIEW `i_total` AS " .
//	  "AS select `i_out`.`prod_id` AS `prod_id`,`wp_posts`.`post_title` AS `product_name`, " .
//	       "(`i_in`.`q_in` - `i_out`.`q_out`) AS `q` from ((`i_out` join `i_in`) join `wp_posts`) " .
//	       " where ((`i_in`.`product_id` = `i_out`.`prod_id`) and (`wp_posts`.`ID` = `i_in`.`product_id`))";
//	  sql_query($sql);
}


