<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 17/02/19
 * Time: 20:39
 */

require( '../r-shop_manager.php' );
print header_text( false );
// require_once( "account.php" );
?>
<html dir="rtl" lang="he">
<head>
    <script>

        //        function zero() {
        //            xmlhttp = new XMLHttpRequest();
        //            xmlhttp.onreadystatechange = function () {
        //                // Wait to get query result
        //                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        //                {
        //                    location.reload();
        //                }
        //            }
        //            var request = "../business/business-post.php?operation=zero_near_zero";
        //            xmlhttp.open("GET", request, true);
        //            xmlhttp.send();
        //        }

        //        function send_month_summary() {
        //            var collection = document.getElementsByClassName("user_chk");
        //            var user_ids = new Array();
        //            for (var i = 0; i < collection.length; i++) {
        //                if (collection[i].checked) {
        //                    var user_id = collection[i].id.substr(4);
        //                    user_ids.push(user_id);
        //                }
        //            }
        //            xmlhttp = new XMLHttpRequest();
        //            xmlhttp.onreadystatechange = function () {
        //                // Wait to get query result
        //                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
        //                {
        //                    var http_text = xmlhttp.responseText.trim();
        //                    document.getElementById("logging").innerHTML = http_text;
        //                }
        //            }
        //            var request = "account-post.php?operation=send_month_summary&ids=" + user_ids.join();
        //            xmlhttp.open("GET", request, true);
        //            xmlhttp.send();
        //
        //        }
    </script>
</head>
<body>
<?php

$include_zero = isset( $_GET["zero"] );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/08/15
 * Time: 12:39
 */

//require_once("../header.php");

//$sql = 'select round(sum(ia.transaction_amount),2), ia.client_id, wu.display_name, client_payment_method(ia.client_id), max(date) '
//       . ' from im_client_accounts ia'
//       . ' join wp_users wu'
//       . ' where wu.id=ia.client_id'
//       . ' group by client_id '
//       . ' order by 4,5';

$sql = "SELECT round(sum(amount), 0) as balance, part_id, supplier_displayname(part_id) \n"

       . "FROM `im_business_info`\n"

       . "where part_id > 10000\n"
       . " and is_active = 1\n"
       . " and document_type = " . ImDocumentType::invoice . "\n"
       . "group by part_id";

if ( ! $include_zero ) {
	$sql .= " having balance < 0";
}

//print $sql;

$result = sql_query( $sql );

$data = "<table>";
$data .= "<tr>";
$data .= gui_cell( "בחר" );

$data .= "<td>לקוח</td>";
$data .= "<td>יתרה לתשלום</td>";
$data .= "</tr>";

print "<a href=\"get-supplier-balance.php?zero=1\">הצג גם חשבונות מאופסים</a>";

$data_lines         = array();
$data_lines_credits = array();

while ( $row = sql_fetch_row( $result ) ) {
	// $line = '';
	$supplier_total = $row[0];
	$supplier_id    = $row[1];
	$supplier_name  = $row[2];

	// print $supplier_id . " " . $supplier_name . "<br/>";

	$line = gui_cell( gui_checkbox( "chk_" . $supplier_id, "supplier_chk" ) );
	// $line .= "<td><a href = \"get-supplier-account.php?supplier_id=" . $supplier_id . "\">" . $supplier_name . "</a></td>";
	$line .= gui_cell( gui_hyperlink( get_supplier_name( $supplier_id ),
		"../business/c-get-all-business_info.php?&document_type=4&&is_active=1&&part_id=" . $supplier_id ) );

	$line .= "<td>" . $supplier_total . "</td>";
//	print $line;
//	$payment_method = get_payment_method( $customer_id );
//	$line           .= "<td>" . $row[4] . "</td>";
//	$line           .= "<td>" . get_payment_method_name( $customer_id ) . "</td>";
//	if ( $include_zero || $supplier_total > 0 ) {
//		print $line;
	//array_push( $data_lines, array( - $customer_total, $line ) );
	array_push( $data_lines, $line );
//	} else if ( $supplier_total < 0 ) {
//		//array_push( $data_lines, array( - $customer_total, $line ) );
//		array_push( $data_lines_credits, array( $customer_name, $line ) );
//	}

}

// sort( $data_lines );

for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
	$line = $data_lines[ $i ];
	$data .= "<tr> " . trim( $line ) . "</tr>";
}

$data = str_replace( "\r", "", $data );

print "<center><h1>יתרות לתשלום</h1></center>";


$data .= "</table>";

print "$data";

?>
<button id="btn_zero" onclick="zero()">אפס קרובים לאפס</button>
<button id="btn_remind" onclick="send_month_summary()">שלח תזכורת</button>
<div id="logging"></div>
</body>
</html>
