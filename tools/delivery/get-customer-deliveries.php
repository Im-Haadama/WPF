<?php
require "../header.php";
require '../tools_wp_login.php';
?>

<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">

</head>
<body>
<center><img src="http://store.im-haadama.co.il/wp-content/uploads/2014/11/cropped-imadama-logo-7x170.jpg"></center>

<?php

// only if admin can select user. Otherwise get id from login info
$user    = new WP_User( $user_ID );
$manager = false;
if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
	foreach ( $user->roles as $role ) {
		if ( $role == 'administrator' ) {
			$manager = true;
		}
	}
}
if ( $manager ) {
	$customer_id = $_GET["customer_id"];
} else {
	$customer_id = $user_ID;
}


// display form for creating invoice
print "<center><h1>משלוחים ללקוח ";
print get_customer_name( $customer_id );
print  "</h1> </center>";

$sql = 'SELECT id FROM im_delivery'
       . ' WHERE order_id IN ('
       . ' SELECT post_id '
       . ' FROM wp_postmeta WHERE '
       . ' meta_key = \'_customer_user\''
       . ' AND meta_value = ' . $customer_id . ')';

$result = sql_query( $sql );

$data = "<table id=\"del_table\" border=\"0\"><tr><td>משלוח</td><td>תאריך</td><td>מחיר</td><td>מעם</td></tr>";

while ( $row = mysqli_fetch_row( $result ) ) {
	$line_number = $line_number + 1;
	$line        = "<tr>";
	$delivery_id = $row[0];

	// Display item name
	$line .= "<td><a href=\"get-delivery.php?id=" . $delivery_id . "\">" . $delivery_id . '</a></td>';
	$line .= delivery_info( $delivery_id );
	$line .= "</tr>";

	$data .= "<tr> " . trim( $line ) . "</tr>";
}

$data = str_replace( "\r", "", $data );

$data .= "</table>";

print "$data";

print "</form>";

function delivery_info( $delivery_id ) {
	$sql = 'select date, total, vat '
	       . ' from im_delivery where id = ' . $delivery_id;

	$row  = sql_query_single( $sql );
	$line = "<td>" . $row[0] . '</td>';
	$line .= "<td>" . $row[1] . "</td>";
	$line .= "<td>" . $row[2] . "</td>";

	return $line;
}

?>

<button id="btn_print" onclick="printDeliveryNotes()">שמור כרטיסית לקוח</button>
<script>

    function printDeliveryNotes() {
        // Get the html
        document.getElementById('btn_print').style.visibility = "hidden";
        window.open("//pdfcrowd.com/url_to_pdf/");
        document.getElementById('btn_print').style.visibility = "visible";
//	var txt = document.documentElement.innerHTML;

        // Download the html
// 	var a = document.getElementById("a");
//	var file = new Blob(txt, 'text/html');
// 	a.href = URL.createObjectURL(file);
        // a.download = 're.html';

//	download(txt, 'myfilename.html', 'text/html')
//	window.open('data:text/html;charset=utf-8,<html dir="rtl" lang="he">' + txt + '</html>');

//

        // To Do: upload the file

        document.getElementById('btn_calc').style.visibility = "visible";
        document.getElementById('btn_print').style.visibility = "visible";
    }

</script>
</body>
</html>
