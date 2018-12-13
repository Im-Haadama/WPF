<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/03/17
 * Time: 23:29
 */
require_once( '../r-shop_manager.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/orders/orders-common.php' );
require_once( ROOT_DIR . '/tools/delivery/delivery-common.php' );

?>

    <script type="text/javascript" src="/agla/client_tools.js"></script>
    <script>
        function done() {
            var collection = document.getElementsByClassName("user_chk");
            var user_ids = new Array();
            var count = 0;
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var user_id = collection[i].id.substr(4);
                    user_ids.push(user_id);
                    count++;
                }
            }
            if (count === 0) {
                alert("יש לבחור לקוחות להוספה למסלול");
                return;
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var http_text = xmlhttp.responseText.trim();
                    document.getElementById("logging").innerHTML = http_text;

                    // if (http_text.indexOf("בהצלחה")) location.reload();

                }
            }
            var request = "legacy-post.php?operation=save_legacy&ids=" + user_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function clear_legacy() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var http_text = xmlhttp.responseText.trim();
                    document.getElementById("logging").innerHTML = http_text;
                }
            }
            var request = "delivery-post.php?operation=clear_legacy";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function create_ship() {
            document.getElementById('btn_create_ship').disabled = true;
            var collection = document.getElementsByClassName("deliveries");
            var del_ids = [];
            var count = 0;

            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var del_id = collection[i].id.substring(3); // table.rows[i + 1].cells[6].firstChild.innerHTML;
                    del_ids.push(del_id);
                    count++;
                }
            }
            if (count === 0) {
                alert("יש לבחור משלוחים ליצירת תעודות משלוח");
                document.getElementById('btn_create_ship').disabled = false;
                return;
            }

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    document.getElementById('btn_create_ship').disabled = false;

                    invoice_id = xmlhttp.responseText.trim();
                    logging.innerHTML += "תעודת משלוח מספר " + invoice_id + " נוצרה ";
                    location.reload();
                }
            }
            var request = "legacy-post.php?operation=create_ship" +
                "&ids=" + del_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }

    </script>
<?php
print header_text();

print gui_header( 1, "משלוחים פעילים" );
// $sql = "select post_id from wp_posts where post_status = 'wc-
delivery_table_header();
print_deliveries( true, "post_excerpt like '%משלוח המכולת%'", true );
print "</table>";

print gui_button( "btn_create_ship", "create_ship()", "צור תעודת משלוח" );

print gui_header( 1, "הוספת משלוחים" );

print "אנא בחר משלוחים לשבוע זה" . "<br/>";

//$sql = "SELECT DISTINCT user_id FROM wp_usermeta
//WHERE meta_key = 'legacy_user' AND meta_value=1";

$sql = 'SELECT user_id FROM wp_usermeta WHERE meta_key = "_client_type"
AND meta_value = "legacy"';

$result = mysqli_query( $conn, $sql );

print "<table>";
while ( $row = mysqli_fetch_row( $result ) ) {
	print user_checkbox( $row[0] );
}
print "</table>";

print gui_button( "btn_done", "done()", "בצע" );

print gui_button( "btn_clear", "clear_legacy()", "נקה" );

print '<div id="logging">';

function user_checkbox( $id ) {
	return gui_row( array(
		gui_checkbox( "chk_" . $id, "user_chk" ),
		get_user_name( $id )
	) );

}


