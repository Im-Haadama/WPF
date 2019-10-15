<?php

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) ) ;
}

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

require_once( "im_tools.php" );
print header_text( false );
require_once( ROOT_DIR . "/niver/wp.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . '/niver/options.php' );

// print TOOLS_DIR . "/multi-site/imMulti-site.php";
require_once( ROOT_DIR . "/fresh/multi-site/imMulti-site.php" );
require_once( ROOT_DIR . '/fresh/r-shop_manager.php' );

$test_site  = get_param( "test_site" );
$test_limit = get_param( "test_limit" );
$manage_inventory = info_get("manage_inventory");
$manage_workers = info_get("manage_workers");
$manage_accounting = info_get("manage_accounting");

if ( ! $test_limit ) {
	$test_limit = 5;
}

$user = wp_get_current_user();
if ( $user->ID == "0" ) {
	// Force login
	$inclued_files = get_included_files();
	my_log( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_HOST ) . '/wp-login.php?redirect_to=' . $_SERVER['REQUEST_URI'] . '"';

	print '<script language="javascript">';
	print "window.location.href = '" . $url . "'";
	print '</script>';
	print $_SERVER['REMOTE_ADDR'] . "<br/>";
	var_dump( $user );
	exit();
}

?>


<?php
print gui_header(1, "ממשק לניהול החנות", true);
$table = array();

$max_row = 8;

print "שלום " . get_user_name( get_user_id() ) . "(" . get_user_id() . "). " . Date("G:i", strtotime("now"));
print " " . gui_hyperlink("התנתק/י", "/wp-login.php?loggedout=true&redirect_to=/fresh/menu_op.php");
function add_command( &$table, &$row, $col, $can, $text, $link, $target = "doc_frame" ) {
	global $test_site;
//    print "can: " . $can . " " . user_can( get_user_id(), $can ) . "<br/>";


	if ( ! $can or user_can( get_user_id(), $can ) ) {
		if ( $test_site ) {
			$table[ $row ++ ][ $col ] = $link;
		} else {
			$table[ $row ++ ][ $col ] = gui_hyperlink( $text, $link, $target );
		}
	}
}

$row = 0;
$col = 0;

$table[ $row ++ ][ $col ] = gui_header( 2, "אריזה" );
add_command( $table, $row, $col, "edit_shop_orders", "הזמנות", "orders/orders-get.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "פריטים להזמנות", "orders/get-total-orders.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "הדפסה", "delivery/print.php", "print" );
if ($manage_inventory){
    add_command( $table, $row, $col, "show_supplies", "אספקות", "supplies/supplies-page.php" );
    add_command( $table, $row, $col, "show_supplies", "מצב המלאי", "inventory/display.php" );
}
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}
$col ++;
$row = 0;

$table[ $row ++ ][ $col ] = gui_header( 2, "משלוחים" );
add_command( $table, $row, $col, "edit_shop_orders", "Today Routes", "/routes/routes-page.php?operation=show_routes" );
add_command( $table, $row, $col, "edit_shop_orders", "This week routes", "/routes/routes-page.php?operation=show_routes&week=" . date( "Y-m-d", strtotime( "last sunday" ) ));
add_command( $table, $row, $col, "edit_shop_orders", "תעודות משלוח", "delivery/delivery-page.php", "doc_frame" );
add_command( $table, $row, $col, "edit_missions", "ניהול מסלולים", "/routes/missions/missions-page.php" );
add_command( $table, $row, $col, "edit_shop_orders", "משלוחי המכולת", "delivery/legacy.php" );
add_command( $table, $row, $col, null, "דיווח חוסרים ללקוח", "delivery/missing.php" );

$m = new ImMultiSite();
// print MultiSite::LocalSiteID();
if ( ! $m->isMaster() ) {
//	add_command( $table, $row, $col, "edit_shop_orders", "סנכרן איזורים", "/fresh/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_locations&operation=update&source=4" );
//	add_command( $table, $row, $col, "edit_shop_orders", "סנכרן סוגי משלוח", "/fresh/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_methods&operation=update&source=4" );
	add_command( $table, $row, $col, "edit_shop_orders", "סנכרן מידע", "/fresh/multi-site/sync-from-master.php" );
}
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}


$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "לקוחות" );
add_command( $table, $row, $col, "edit_shop_orders", "אריזה", "orders/orders-get.php?order_type", "orders" );
add_command( $table, $row, $col, "edit_shop_orders", "מעקב תשלומים", "account/get-accounts-status.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "הוספת לקוח", "account/add-account.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "ניהול לקוחות", "customers/admin.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "קניה בחנות", "orders/order-pos.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "אמצעי תשלום", "account/payment-admin.php", "doc_frame" );
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "מחירון" );
add_command( $table, $row, $col, null, "סלי השבוע", "baskets/show_baskets.php" );
add_command( $table, $row, $col, "show_pricelist", "מחירון ספקים", "pricelist/pricelist-get.php" );
add_command( $table, $row, $col, "edit_shop_orders", "כל הפריטים", "catalog/cost-price-list.php", "doc_frame" );
// add_command( $table, $row, $col, "edit_shop_orders", "מחירון סיטונאי", "catalog/", "doc_frame" );
add_command( $table, $row, $col, "edit_pricelist", "מיפוי מחירון", "catalog/catalog-map.php" );
add_command( $table, $row, $col, "edit_pricelist", "עדכון מחירון", "catalog/catalog-get.php" );
add_command( $table, $row, $col, "edit_pricelist", "מחירון גרניט", "catalog/catalog-db-query.php?operation=pos" );
add_command( $table, $row, $col, "edit_pricelist", "מארזים", "catalog/bundles-get.php" );
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;

$table[ $row ++ ][ $col ] = gui_header( 2, "ספקים" );
add_command( $table, $row, $col, "edit_suppliers", "ספקים", "suppliers/admin.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "אספקות", "supplies/supplies-page.php", "doc_frame" );
if ($manage_inventory){
    add_command( $table, $row, $col, "edit_shop_orders", "מלאי 0", "catalog/catalog-db-query.php?operation=zero_inv", "doc_frame" );
    add_command( $table, $row, $col, "edit_suppliers", "יתרת ספקים", "suppliers/get-supplier-balance.php", "doc_frame" );
}

while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}


if ($manage_workers) {
    $col ++;
    $row                      = 0;
    $table[ $row ++ ][ $col ] = gui_header( 2, "עובדים" );
    add_command( $table, $row, $col, null, "דיווח שעות", "http://store.im-haadama.co.il/org/people/entry.php", "doc_frame" );
    add_command( $table, $row, $col, "working_hours_all", "ניהול עובדים", "people/c-get-all-working.php" );
}
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "ניהול" );
add_command( $table, $row, $col, "edit_pricelist", "תבנית", "tasklist/c-get-all-task_templates.php", "doc_frame" );
add_command( $table, $row, $col, "edit_pricelist", "משימות פעילות", $m->getSiteToolsURL( 1 ) . "/focus/focus-page.php", "tasks" );
    add_command( $table, $row, $col, "edit_pricelist", "חשבוניות", "business/invoice_table.php" );
add_command( $table, $row, $col, "edit_pricelist", "פרויקטים", "people/project_admin.php" );
add_command( $table, $row, $col, "edit_pricelist", "תיבת דואר", "business/inbox-box.php" );
add_command( $table, $row, $col, "show_bank", "בנק", "bank/bank-page.php" );
add_command( $table, $row, $col, "show_bank", "התאם תשלומים", "/org/business/business-post.php?operation=show_pay_to_link" );
add_command( $table, $row, $col, "show_bank", "ניתוח שבועי", "business/report.php" );

while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

if ( $test_site ) {
	print "מצב בדיקות. בודק " . $test_limit . "תפריטים";
	array_shift( $table ); // remove table headers.

	print '<script>

    function open_all(){';

	foreach ( $table as $row ) {
		foreach ( $row as $command ) {
			if ( strlen( $command ) and $test_limit > 0 ) // Skip blanks
			{
				print 'window.open(\'' . $command . '\', \'_blank\');
            ';
			}
			$test_limit --;
		}
	}
	print '
    };
    </script>
    ';
	print '<body onload="open_all()"></body>';

	return;
}
?>
<div style="text-align:center; ">
    <table style="margin-top: 0cm;">
        <tr>
            <td>
                <img style="padding:0;" src="<?php print get_logo_url(); ?>">
            </td>
            <td>
				<?php print gui_table_args( $table );
				?>
            </td>
        </tr>
    </table>

    <iframe name="doc_frame" width="100%" height="600">
    </iframe>

</div>

<br><br>

<?php
// require_once( TOOLS_DIR . "/weekly/run.php" );
?>
<div align="center">Fresh store powered by Aglamaz.com 2015-2019</div>
<div align="center">Version <?php print $power_version; ?> </div>
</body>
</html>


