<?php
// require_once( "r-shop_manager.php" );
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "im_tools.php" );
print header_text( false );
require_once( TOOLS_DIR . "/wp.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
// print TOOLS_DIR . "/multi-site/imMulti-site.php";
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );

$test_site  = get_param( "test_site" );
$test_limit = get_param( "test_limit" );
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


<h1 style="text-align:center; margin-bottom: 0cm;">ממשק לניהול החנות</h1>
<?php
$table = array();

$max_row = 8;

function add_command( &$row, $col, $can, $text, $link, $target = "doc_frame" ) {
	global $table;
	global $test_site;

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
add_command( $row, $col, "edit_shop_orders", "הזמנות", "orders/orders-get.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "פריטים להזמנות", "orders/get-total-orders.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "הדפסה", "weekly/print.php", "print" );
add_command( $row, $col, "show_supplies", "אספקות", "supplies/supplies-get.php" );
add_command( $row, $col, "show_supplies", "מצב המלאי", "inventory/display.php" );
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}
$col ++;
$row = 0;

$table[ $row ++ ][ $col ] = gui_header( 2, "משלוחים" );
add_command( $row, $col, "edit_shop_orders", "הצגת משימות נהיגה", "delivery/get-driver-multi.php" );
add_command( $row, $col, "edit_shop_orders", "תעודות משלוח", "business/get_all.php?week=" .
                                                             sunday( date( "Y-m-d" ) )->format( "Y-m-d" ), "doc_frame" );
add_command( $row, $col, "edit_missions", "ניהול מסלולים", "delivery/c-get-all-missions.php" );
add_command( $row, $col, "edit_shop_orders", "משלוחי המכולת", "delivery/legacy.php" );
add_command( $row, $col, null, "דיווח חוסרים ללקוח", "delivery/missing.php" );

$m = new ImMultiSite();
// print MultiSite::LocalSiteID();
if ( ! $m->isMaster() ) {
//	add_command( $row, $col, "edit_shop_orders", "סנכרן איזורים", "/tools/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_locations&operation=update&source=4" );
//	add_command( $row, $col, "edit_shop_orders", "סנכרן סוגי משלוח", "/tools/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_methods&operation=update&source=4" );
	add_command( $row, $col, "edit_shop_orders", "סנכרן מידע", "/tools/multi-site/sync-from-master.php" );
}
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}


$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "לקוחות" );
add_command( $row, $col, "edit_shop_orders", "הזנת תעודות משלוח", "orders/orders-get.php?order_type", "orders" );
add_command( $row, $col, "edit_shop_orders", "מעקב תשלומים", "account/get-accounts-status.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "הוספת לקוח", "account/add-account.php", "doc_frame" );
add_command( $row, $col, "set_client_type", "ניהול לקוחות", "account/client-types.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "קניה בחנות", "orders/order-pos.php", "doc_frame" );
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "מחירון" );
add_command( $row, $col, null, "סלי השבוע", "baskets/show_baskets.php" );
add_command( $row, $col, "show_pricelist", "מחירון ספקים", "pricelist/pricelist-get.php" );
add_command( $row, $col, "edit_shop_orders", "כל הפריטים", "catalog/cost-price-list.php", "doc_frame" );
// add_command( $row, $col, "edit_shop_orders", "מחירון סיטונאי", "catalog/", "doc_frame" );
add_command( $row, $col, "edit_pricelist", "מיפוי מחירון", "catalog/catalog-map.php" );
add_command( $row, $col, "edit_pricelist", "עדכון מחירון", "catalog/catalog-get.php" );
add_command( $row, $col, "edit_pricelist", "מחירון גרניט", "catalog/catalog-db-query.php?operation=pos" );
add_command( $row, $col, "edit_pricelist", "מארזים", "catalog/bundles-get.php" );
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "ספקים" );
add_command( $row, $col, "edit_suppliers", "ספקים", "suppliers/c-get-all-suppliers.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "אספקות", "supplies/supplies-get.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "מלאי 0", "catalog/catalog-db-query.php?operation=zero_inv", "doc_frame" );

while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "עובדים" );
add_command( $row, $col, null, "דיווח שעות", "http://store.im-haadama.co.il/tools/people/entry.php" );
add_command( $row, $col, "working_hours_all", "ניהול עובדים", "people/c-get-all-working.php" );
while ( $row < $max_row ) {
	$table[ $row ++ ][ $col ] = "";
}

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "ניהול" );
add_command( $row, $col, "edit_pricelist", "תבנית", "tasklist/c-get-all-task_templates.php", "doc_frame" );
add_command( $row, $col, "edit_pricelist", "משימות פעילות", "tasklist/c-get-all-tasklist.php", "tasks" );
add_command( $row, $col, "edit_pricelist", "חשבוניות", "business/c-get-all-business_info.php?document_type=4&is_active=1" );
add_command( $row, $col, "edit_pricelist", "פרויקטים", "people/c-get-all-projects.php" );
add_command( $row, $col, "edit_pricelist", "תיבת דואר", "business/manage-inbox.php" );

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
				<?php print gui_table( $table );
				?>
            </td>
        </tr>
    </table>

    <iframe name="doc_frame" width="1000" height="600">
    </iframe>

</div>

<br><br>

<?php
// require_once( TOOLS_DIR . "/weekly/run.php" );
?>
</body>
</html>


