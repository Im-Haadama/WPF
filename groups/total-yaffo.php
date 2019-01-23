<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/03/18
 * Time: 15:44
 */


require_once( "../tools/im_tools.php" );
require_once( "../tools/orders/orders-common.php" );
require_once( "../tools/gui/inputs.php" );
print header_text( true, true, true );

$user_id = 381;

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
    <script>
        function start_handle() {
            var collection = document.getElementsByClassName("select_order");
            var order_ids = new Array();
            var table = document.getElementById("wc-pending");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location = window.location;
                }
            }

            for (var i = 0; i < collection.length; i++) {
                var order_id = collection[i].id.substr(4);
                if (document.getElementById("chk_" + order_id).checked)
                    order_ids.push(order_id);
            }
            var request = "http://fruity.co.il/tools/orders/orders-post.php?operation=start_handle&ids=" + order_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
<?php
print gui_header( 2, "הזמנות" );

print gui_button( "btn_start", "start_handle()", "התחל טיפול" ) . "<br/>";

print orders_table( array( "wc-pending", "wc-on-hold", "wc-processing", "wc-awaiting-shipment" ), false, $user_id );

print gui_header( 1, "סך פריטים לשבוע זה" );

print total_order( $user_id );

