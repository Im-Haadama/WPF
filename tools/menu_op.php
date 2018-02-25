<?php
// require_once( "r-shop_manager.php" );
require_once( "im_tools.php" );
print header_text( false );
require_once( TOOLS_DIR . "/wp.php" );
require_once( TOOLS_DIR . "/gui/inputs.php" );
// print TOOLS_DIR . "/multi-site/multi-site.php";
require_once( TOOLS_DIR . "/multi-site/multi-site.php" );

?>

<h1 style="text-align:center; margin-bottom: 0cm;">ממשק לניהול החנות</h1>
<?php
$table = array();

$max_row = 1;

function add_command( &$row, $col, $can, $text, $link, $target = "doc_frame" ) {
	global $table, $max_row;

	if ( user_can( get_user_id(), $can ) ) {
		$table[ $row ++ ][ $col ] = gui_hyperlink( $text, $link, $target );
	}
	if ( $row > $max_row ) {
		$max_row = $row;
	}
}

$row = 0;
$col = 0;

$table[ $row ++ ][ $col ] = gui_header( 2, "אריזה" );
add_command( $row, $col, "edit_shop_orders", "הזמנות", "orders/orders-get.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "פריטים להזמנות", "orders/get-total-orders.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "הדפסה", "weekly/print.php" );
add_command( $row, $col, "show_supplies", "אספקות", "supplies/supplies-get.php" );

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "משלוחים" );
add_command( $row, $col, "edit_shop_orders", "משימות נהיגה", "delivery/get-driver-multi.php" );
// print MultiSite::LocalSiteID();
if ( MultiSite::LocalSiteID() != 4 ) {
	add_command( $row, $col, "edit_shop_orders", "סנכרן איזורים", "/tools/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_locations&operation=update&source=1" );
}
add_command( $row, $col, "edit_shop_orders", "סנכרן סוגי משלוח", "/tools/multi-site/sync-data.php?table=wp_woocommerce_shipping_zone_methods&operation=update&source=1" );
add_command( $row, $col, "edit_shop_orders", "סנכרן משימות", "/tools/multi-site/sync-data.php?table=im_missions&operation=update&source=1" );


$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "לקוחות" );
add_command( $row, $col, "edit_shop_orders", "מעקב תשלומים", "account/get-accounts-status.php", "doc_frame" );
add_command( $row, $col, "edit_shop_orders", "הוספת לקוח", "account/add-account.php", "doc_frame" );
add_command( $row, $col, "set_client_type", "ניהול לקוחות", "account/client-types.php", "doc_frame" );

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "מחירון" );
add_command( $row, $col, "edit_shop_orders", "כל הפריטים", "catalog/cost-price-list.php", "doc_frame" );

$col ++;
$row                      = 0;
$table[ $row ++ ][ $col ] = gui_header( 2, "ספקים" );
add_command( $row, $col, "edit_shop_orders", "אספקות", "supplies/supplies-get.php", "doc_frame" );

//print "max row: " . $max_row . "<br/>";

for ( $i = 0; $i <= $col; $i ++ )
	for ( $j = 0; $j < $max_row; $j ++ )
		if ( is_null( $table[ $j ][ $i ] ) ) {
//    print $i . " " . $j . "<br/>";
			$table[ $j ][ $i ] = "";
		}

if ( MultiSite::LocalSiteID() == 2 ) {
	$table[ $col ++ ][ $row ] = gui_hyperlink( "עדכון מחירים מהשף", "pricelist/update-chef.php", "doc_frame" );
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
