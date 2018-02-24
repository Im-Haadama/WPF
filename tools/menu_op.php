<?php
require_once( "r-shop_manager.php" );
print header_text( false );
require_once( TOOLS_DIR . "/wp.php" );
require_once( TOOLS_DIR . "/gui/inputs.php" );
// print TOOLS_DIR . "/multi-site/multi-site.php";
require_once( TOOLS_DIR . "/multi-site/multi-site.php" );

?>

<h1 style="text-align:center; margin-bottom: 0cm;">ממשק לניהול החנות</h1>
<?php
$table = array();
$col   = 0;

$table[0][ $col ] = gui_header( 2, "אריזה" );
$table[1][ $col ] = gui_hyperlink( "הזמנות פעילות", "orders/orders-get.php", "doc_frame" );
$table[2][ $col ] = gui_hyperlink( "פריטים להזמנות", "orders/get-total-orders.php", "doc_frame" );
$table[3][ $col ] = gui_hyperlink( "הדפסה", "weekly/print.php" );
$table[4][ $col ] = gui_hyperlink( "אספקות", "supplies/supplies-get.php" );

$col ++;
$line                      = 0;
$table[ $line ++ ][ $col ] = gui_header( 2, "משלוחים" );
$table[ $line ++ ][ $col ] = gui_hyperlink( "משימות נהיגה", "delivery/get-driver-multi.php" );
// print MultiSite::LocalSiteID();
if ( MultiSite::LocalSiteID() != 4 ) {
	$table[ $line ++ ][ $col ] = gui_hyperlink( "סנכרן איזורים", "/tools/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_locations&operation=update&source=1" );
}
$table[ $line ++ ][ $col ] = gui_hyperlink( "סנכרן סוגי משלוח", "/tools/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_methods&operation=update&source=1" );
$table[ $line ++ ][ $col ] = gui_hyperlink( "סנכרן משימות", "/tools/multi-site/sync-data.php?table=im_missions&operation=update&source=1" );
$table[ $line ++ ][ $col ] = "";

$col ++;
$table[0][ $col ] = gui_header( 2, "לקוחות" );
$table[1][ $col ] = gui_hyperlink( "מעקב תשלומים", "account/get-accounts-status.php", "doc_frame" );
$table[2][ $col ] = gui_hyperlink( "הוספת לקוח", "account/add-account.php", "doc_frame" );
if ( current_user_can( "set_client_type" ) and MultiSite::LocalSiteID() == 4 )
	$table[3][ $col ] = gui_hyperlink( "ניהול לקוחות", "account/client-types.php", "doc_frame" );


$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "מחירון" );
$table[ $row ++ ][ $col ] = gui_hyperlink( "כל הפריטים", "catalog/cost-price-list.php", "doc_frame" );

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "ספקים" );
$table[ $row ++ ][ $col ] = gui_hyperlink( "אספקות", "supplies/supplies-get.php", "doc_frame" );

if ( MultiSite::LocalSiteID() == 2 ) {
	$table[ $row ++ ][ $col ] = gui_hyperlink( "עדכון מחירים מהשף", "pricelist/update-chef.php", "doc_frame" );
}
?>
<div style="text-align:center; ">
    <table style="margin-top: 0cm;">
        <tr>
            <td>
                <img style="padding:0;" src="<?php print get_logo_url(); ?>" height="100" width="100">
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

</body>
</html>
