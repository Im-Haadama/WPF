<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/02/17
 * Time: 20:54
 */

require_once "../r-shop_manager.php";
require_once( "../gui/inputs.php" );
$user_id = $_GET["user_id"];

print header_text( false, true );
?>
    <header>
        <script type="text/javascript" src="/core/gui/client_tools.js"></script>
        <script>

            function send_order() {
                var collection = document.getElementsByClassName("prod_checkbox");
                var prods = new Array();
                var comment = get_value(document.getElementById("comments"));
                var sum = 0;
                for (var i = 0; i < collection.length; i++) {
                    if (collection[i].checked) {
                        var id = collection[i].id.substr(4);
                        sum += parseInt(document.getElementById("prods").rows[i + 1].cells[2].innerHTML);

                        prods.push(id);
                    }
                }
                if (prods.length < 1) {
                    alert("יש לבחור לפתחות מוצר אחד");
                    return;
                }

                if (sum < 80) {
                    alert("הזמנה מינימום 80 שקלים");
                    return;
                }
                var request = "create-order.php?user_id=<?print $user_id;?>&prod_ids=" + prods.join() +
                    "&comment=" + encodeURI(comment);

                xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
                    // Wait to get query result
                    if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                    {
                        document.getElementById("result").innerHTML = xmlhttp.response;
                    }
                }
                xmlhttp.open("GET", request, true);
                xmlhttp.send();
            }
        </script>
    </header>
<?php
require_once( "../header_no_login.php" );


if ( ! ( $user_id > 0 ) ) {
	print "קיצור שגוי. אנא בקש סיוע מ'עם האדמה'" . "<br/>";
	die( 1 );
}

$user_info = get_userdata( $user_id );
print "שלום " . $user_info->display_name . "<br/>";

print "להלן פריטים שהזמנת בחודש האחרון, להזמנה חוזרת בחר אותם ולחץ/י הזמן." . "<br/>";

$info = array( array( "סמן", "מוצר", "מחיר" ) );

$sql = "SELECT DISTINCT woim.meta_value
FROM wp_woocommerce_order_items woi JOIN wp_woocommerce_order_itemmeta woim
 WHERE order_id IN (SELECT post_id FROM wp_postmeta pm JOIN wp_posts p
 WHERE meta_key = '_customer_user'
AND meta_value = " . $user_id . "
AND pm.post_id = p.id
AND p.post_date >= DATE_SUB(NOW(), INTERVAL 31 DAY))
 AND woi.order_item_id = woim.order_item_id AND woim.`meta_key` = '_product_id'";

$result = sql_query( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	array_push( $info, prod_line( $row[0] ) );
}

print gui_table_args( $info, "prods" );


function prod_line( $prod_id ) {
	if ( get_post_status( $prod_id ) == "publish" ) {
		return array(
			gui_checkbox( "chk_" . $prod_id, "prod_checkbox", false ),
			get_product_name( $prod_id ),
			client_price( $prod_id )
		);
	}

	return null;
}

print "<br/>";
print gui_textarea( "comments", "הערות", "", 5, 80 );
print "<br/>";
print gui_button( "order", "send_order()", "הזמן" );

print gui_label( "result", "" );
