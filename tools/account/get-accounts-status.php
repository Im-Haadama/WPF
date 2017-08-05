<?php require_once( '../tools_wp_login.php' );
print header_text( false );
require_once( "../gui/inputs.php" );
require_once( "account.php" );
?>
<html dir="rtl" lang="he">
<head>
    <script>

        function zero() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    location.reload();
                }
            }
            var request = "account-post.php?operation=zero_near_zero";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function send_month_summary() {
            var collection = document.getElementsByClassName("user_chk");
            var user_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var user_id = collection[i].id.substr(4);
                    user_ids.push(user_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    var http_text = xmlhttp.responseText.trim();
                    document.getElementById("logging").innerHTML = http_text;
                }
            }
            var request = "account-post.php?operation=send_month_summary&ids=" + user_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>
</head>
<body>
<?php

if ( $_GET["zero"] === null ) {
	$include_zero = false;
} else {
	$include_zero = true;
}

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/08/15
 * Time: 12:39
 */

//require_once("../header.php");

$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name'
       . ' from im_client_accounts ia'
       . ' join wp_users wu'
       . ' where wu.id=ia.client_id'
       . ' group by client_id';


$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

$data = "<table>";
$data .= "<tr>";
$data .= gui_cell( "בחר" );

$data .= "<td>לקוח</td>";
$data .= "<td>יתרה לתשלום</td>";
$data .= "</tr>";

$data_lines = array();

while ( $row = mysql_fetch_row( $export ) ) {
	// $line = '';
	$customer_total = $row[0];
	$customer_id    = $row[1];
	$customer_name  = $row[2];

	$line = gui_cell( gui_checkbox( "chk_" . $customer_id, "user_chk" ) );
	$line .= "<td><a href = \"get-customer-account.php?customer_id=" . $customer_id . "\">" . $customer_name . "</a></td>";

	$line .= "<td>" . $customer_total . "</td>";
	$line .= "<td>" . get_payment_method_name( $customer_id ) . "</td>";
	if ( $include_zero || $customer_total <> 0 ) {
		array_push( $data_lines, array( - $customer_total, $line ) );
	}
}

sort( $data_lines );

for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
	$line = $data_lines[ $i ][1];
	$data .= "<tr> " . trim( $line ) . "</tr>";
}

$data = str_replace( "\r", "", $data );

if ( $data == "" ) {
	$data = "\n(0) Records Found!\n";
}

print "<center><h1>יתרת לקוחות</h1></center>";

print "<a href=\"get-accounts-status.php?zero\">הצג גם חשבונות מאופסים</a>";

$data .= "</table>";

print "$data";

?>
<button id="btn_zero" onclick="zero()">אפס קרובים לאפס</button>
<button id="btn_remind" onclick="send_month_summary()">שלח תזכורת</button>
<div id="logging"></div>
</body>
</html>
