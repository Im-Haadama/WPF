<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once(FRESH_INCLUDES . '/wp-config.php');
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );


$operation = GetParam("operation", false, null);
if ($operation)
	handle_menu_operation($operation);

print header_text( false );
require_once( FRESH_INCLUDES . "/core/wp.php" );
require_once( FRESH_INCLUDES . "/core/gui/inputs.php" );
require_once( FRESH_INCLUDES . '/core/options.php' );

require_once( FRESH_INCLUDES . "/fresh/multi-site/imMulti-site.php" );
require_once( FRESH_INCLUDES . '/fresh/r-shop_manager.php' );

$test_site  = GetParam( "test_site" );
$test_limit = GetParam( "test_limit" );
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
	MyLog( __FILE__, $inclued_files[ count( $inclued_files ) - 2 ] );
	auth_redirect();
	var_dump( $user );
	exit();
}

?>


<?php
print Core_Html::gui_header(1, "ממשק לניהול החנות", true);
$table = array();

$max_row = 8;

print "שלום " . GetUserName( get_user_id() ) . "(" . get_user_id() . "). " . Date("G:i", strtotime("now"));
print " " . Core_Html::GuiHyperlink("התנתק/י", "/wp-login.php?loggedout=true&redirect_to=/fresh/menu_op.php");
function add_command( &$table, &$row, $col, $can, $text, $link, $target = "doc_frame" )
{
	global $test_site;

	if ( ! $can or user_can( get_user_id(), $can ) ) {
		if ( $test_site ) {
			$table[ $row ++ ][ $col ] = $link;
		} else {
			$table[ $row ++ ][ $col ] = Core_Html::GuiHyperlink( $text, $link, $target );
		}
	}
}

$row = 0;
$col = 0;

$table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "אריזה" );
add_command( $table, $row, $col, "edit_shop_orders", "הזמנות", "orders/orders-get.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "פריטים להזמנות", "orders/get-total-orders.php", "doc_frame" );
add_command( $table, $row, $col, "edit_shop_orders", "הדפסה", "/routes/print.php", "print" );
if ($manage_inventory){
    add_command( $table, $row, $col, "show_supplies", "אספקות", "supplies/supplies-page.php" );
    add_command( $table, $row, $col, "show_supplies", "מצב המלאי", "inventory/display.php" );
	add_command( $table, $row, $col, "show_supplies", "מוצרים לא זמינים", "inventory/display.php?not_available=1" );
}
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}
$col ++;
$row = 0;

$table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "משלוחים" );
add_command( $table, $row, $col, "edit_shop_orders", "Today routes", "/routes/routes-page.php?operation=show_today_routes" );
add_command( $table, $row, $col, "edit_shop_orders", "This week missions", "/routes/routes-page.php?operation=show_missions&week=" . date( "Y-m-d", strtotime( "last sunday" ) ));
add_command( $table, $row, $col, "edit_shop_orders", "תעודות משלוח", "delivery/delivery-page.php", "doc_frame" );
add_command( $table, $row, $col, "edit_missions", "ניהול מסלולים", "/routes/routes-page.php?operation=show_paths" );
add_command( $table, $row, $col, "edit_missions", "Driving missions", "/routes/routes-page.php?operation=show_missions" );
add_command( $table, $row, $col, "edit_shop_orders", "משלוחי המכולת", "/routes/legacy.php" );
add_command( $table, $row, $col, null, "דיווח חוסרים ללקוח", "delivery/missing.php" );

$m = new Core_Db_MultiSite();
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
$table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "לקוחות" );
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
$table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "מחירון" );
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

$table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "ספקים" );
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
    $table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "עובדים" );
    add_command( $table, $row, $col, null, "דיווח שעות", "https://store.im-haadama.co.il/org/people/worker.php", "doc_frame" );
    add_command( $table, $row, $col, "working_hours_all", "ניהול עובדים", "/org/people/people-page.php?operation=edit_workers" );
}
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = Core_Html::gui_header( 2, "ניהול" );
add_command( $table, $row, $col, "edit_pricelist", "תבנית", "tasklist/c-get-all-task_templates.php", "doc_frame" );
add_command( $table, $row, $col, "edit_pricelist", "משימות פעילות", $m->getSiteToolsURL( 1 ) . "/focus/focus-page.php", "tasks" );
    add_command( $table, $row, $col, "edit_pricelist", "חשבוניות", "/org/business/invoice_table.php" );
add_command( $table, $row, $col, "edit_pricelist", "פרויקטים", "people/project_admin.php" );
add_command( $table, $row, $col, "edit_pricelist", "תיבת דואר", "business/inbox-box.php" );
add_command( $table, $row, $col, "show_bank", "בנק", "/org/bank/bank-page.php" );
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

<div align="center">Fresh store powered by Aglamaz.com 2015-2019</div>
<div align="center">Version <?php print $power_version; ?> </div>
</body>
</html>

<?php

function handle_menu_operation($operation)
{
    switch ($operation) {
	    case "logout":
		    wp_logout();
		    $back = GetParam( "back", false, GetUrl( 1 ) );
		    header( "location: " . $back );

		    return;
		    break;
    }
}