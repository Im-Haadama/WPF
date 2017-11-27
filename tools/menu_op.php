<?php
include "r-shop_manager.php";
require_once( "gui/inputs.php" );
print header_text( false );
?>

<h1 style="text-align:center; margin-bottom: 0cm;">ממשק לניהול החנות</h1>
<?php
$table = array();
$col   = 0;

$table[0][ $col ] = gui_header( 2, "אריזה" );
$table[1][ $col ] = gui_hyperlink( "הזמנות פעילות", "orders/orders-get.php", "doc_frame" );
$table[2][ $col ] = gui_hyperlink( "פריטים להזמנות", "orders/get-total-orders.php", "doc_frame" );
$table[3][ $col ] = gui_hyperlink( "הדפסה", "weekly/print.php" );

$col ++;
$table[0][ $col ] = gui_header( 2, "משלוחים" );
$table[1][ $col ] = gui_hyperlink( "משימות נהיגה", "delivery/get-driver-multi.php" );
$table[2][ $col ] = "";

$col ++;
$table[0][ $col ] = gui_header( 2, "לקוחות" );
$table[1][ $col ] = gui_hyperlink( "מעקב תשלומים", "account/get-accounts-status.php", "doc_frame" );
$table[2][ $col ] = gui_hyperlink( "הוספת לקוח", "account/add-account.php", "doc_frame" );

$col ++;
$table[0][ $col ] = gui_header( 2, "מחירון" );
$table[1][ $col ] = gui_hyperlink( "כל הפריטים", "catalog/cost-price-list.php", "doc_frame" );

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
