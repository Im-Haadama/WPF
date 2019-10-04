<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 10/09/17
 * Time: 18:07
 */

define( STORE_DIR, dirname( dirname( __FILE__ ) ) );

require_once( STORE_DIR . '/wp-config.php' );
require_once( STORE_DIR . '/im-config.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
require_once( STORE_DIR . '/fresh/multi-site/multi-site.php' );

print header_text();

// print MultiSite::LocalSiteID();

////$token = get_usermeta($user_ID, "cc_token");
//
//?>
    <script type="text/javascript" src="/agla/client_tools.js"></script>

    <script>
        function pay() {
            var card = get_value(document.getElementById("card"));
            if (card.length < 8) {
                alert("הכנס מספר כרטיס");
                return;
            }
            var id = get_value(document.getElementById("id"));
            if (id.length < 8) {
                alert("הכנס מספר ת\"ז");
                return;
            }
            var amount = get_value(document.getElementById("amount"));
            if (amount.length < 2) {
                alert("הכנס סכום");
                return;
            }
            var cvv = get_value(document.getElementById("cvv"));
            if (cvv.length < 3) {
                alert("הכנס 3 ספרות");
                return;
            }
            xmlhttp = new XMLHttpRequest();

            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    //             update_display();
                }
            }
            var request = "pay-post.php?operation=pay&card=" + card + '&id=' + id +
                '&date=' + date + "&amount=" + amount + "&";

            // document.getElementById("debug").innerHTML = request;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();


        }
        function check_num(event) {
            if (event.shiftKey) {
                event.preventDefault();
                return;
            }
            switch (event.keyCode) {
                case 8: // backspace
                case 48: // 0
                case 49:
                case 50:
                case 51:
                case 52:
                case 53:
                case 54:
                case 55:
                case 56:
                case 57: // 9
                    break;
                default:
                    event.preventDefault();
            }
//	    if (! (event.keyCode >= 48 && event.keyCode <= 57))
            // alert(event.keyCode);
            // alert (event.shiftKey);
        }
    </script>
<?php
if ( ImMultiSite::LocalSiteID() == 3 ) {
	$cc     = "4580000000000000";
	$cvv    = "144";
	$id     = "000000000";
	$amount = 1433;
} else {
	$cc     = "";
	$cvv    = "";
	$id     = "";
	$amount = 0;
}

print gui_header( 1, "ביצוע תשלום" );
print gui_table_args( array(
	array( "מספר כרטיס", gui_input( "card", $cc, array( "onkeydown=\"check_num(event)\"" ), "card" ) ),
	array( "תוקף", gui_input_month( "valid", "", "", null ) ),
	array( "id", gui_input( "מספר תז", $id ) ),
	array( "3 ספרות בגב הכרטיס", gui_input( "cvv", $cvv ) ),
	array( "סכום לתשלום", gui_input( "amount", $amount ) )
) );

print gui_button( "btn_pay", "pay()", "בצע תשלום" );
