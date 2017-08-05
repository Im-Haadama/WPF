<?php

require_once( '../tools_wp_login.php' );

// only if admin can select user. Otherwise get id from login info
$user    = new WP_User( $user_ID );
$manager = false;
if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
	foreach ( $user->roles as $role ) {
		if ( $role == 'administrator' or $role == 'shop_manager' ) {
			$manager = true;
		}
	}
}
if ( $manager ) {
	$customer_id = $_GET["customer_id"];
} else {
	$customer_id = $user_ID;
}

if ( ! $manager ) {
	require_once( "../header.php" );
}

?>

<html dir="rtl" lang="he">
<meta charset="UTF-8">
<head>
    <script>
        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }

        function addTransaction() {
            var type = document.getElementById("transaction_type").value;
            var amount = document.getElementById("transaction_amount").value;
            var date = document.getElementById("transaction_date").value;
            var ref = document.getElementById("transaction_ref").value;
            var request = "account-add-trans.php?customer_id=" + <?php print $customer_id ?>
                +"&type=" + type + "&amount=" + amount + "&date=" + date + "&ref=" + ref;
            // window.alert(request);
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function updateDisplay() {
            xmlhttp2 = new XMLHttpRequest();
            xmlhttp2.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp2.readyState == 4 && xmlhttp2.status == 200)  // Request finished
                {
                    label = document.getElementById("total");
                    label.innerHTML = xmlhttp2.response;
                }
            }
            var request2 = "get-customer-account-post.php?operation=total&customer_id=" + <? print $customer_id; ?>;
            xmlhttp2.open("GET", request2, true);
            xmlhttp2.send();

            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    table = document.getElementById("transactions");
                    table.innerHTML = xmlhttp1.response;
                }
            }
            var request1 = "get-customer-account-post.php?operation=table&customer_id=" + <? print $customer_id; ?>;
            xmlhttp1.open("GET", request1, true);
            xmlhttp1.send();
        }

    </script>
</head>

<body onload="updateDisplay()">

<?php

print "<center><h1>מצב חשבון ";
print get_customer_name( $customer_id );
print  "</h1> </center>";

if ( $manager ) {
	print "<br>";
	print "email: " . get_customer_email( $customer_id );
	print "<br>";
	print "הנתונים הן יתרת חוב. זיכוי ותשלום ירשמו בסימן שלילי";
	print "<br>";
	print "סוג פעולה";
	print '<input type="text" id="transaction_type">';
	print "סכום";
	print '<input type="text" id="transaction_amount">';
	print "תאריך";
	print '<input type="date" id="transaction_date">';
	print "מזהה";
	print '<input type="text" id="transaction_ref">';
	print '<button id="btn_add" onclick="addTransaction()">הוסף תנועה</button>';
	print '<textarea id="logging" rows="2" cols="50"></textarea>';
}

?>
<br>

<script>
    function create_invoice() {
        var collection = document.getElementsByClassName("trans_checkbox");
        var table = document.getElementById("transactions");
        var del_ids = new Array();
        var total = 0;

        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                var del_id = table.rows[i + 1].cells[6].firstChild.innerHTML;
                del_ids.push(del_id);
                total = total + parseInt(table.rows[i + 1].cells[2].firstChild.data);
            }
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                invoice_id = xmlhttp.responseText.trim();
                logging.value += "חשבונית מספר " + invoice_id + " נוצרה ";
                updateDisplay();
            }
        }
        var credit = parseInt(get_value(document.getElementById("credit")));
        if (isNaN(credit)) credit = 0;
        var bank = parseInt(get_value(document.getElementById("bank")));
        if (isNaN(bank)) bank = 0;
        var cash = parseInt(get_value(document.getElementById("cash")));
        if (isNaN(cash)) cash = 0;
        if (credit + bank + cash != total) {
            window.alert("סכום לא תואם לתנועות שנבחרו");
        } else {
            var request = "account-post.php?operation=create_invoice" +
                "&cash=" + cash +
                "&credit=" + credit +
                "&bank=" + bank +
                "&ids=" + del_ids.join() +
                "&user_id=" + <?php print $customer_id; ?>;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
    }
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
<h2>
    <label id="total">יתרה</label>
</h2>
<br>
<H2>תנועות</H2>

<?php

if ( $manager ) {
	require_once( '../invoice4u/invoice.php' );
	$invoice = new Invoice4u();
	$invoice->Login();

	if ( is_null( $invoice->token ) ) {
		die ( "can't login" );
	}

	// print "client name " . $client_name . "<br/>";
	$client_name = get_customer_name( $customer_id );
	$client      = $invoice->GetCustomerByName( $client_name );
	// var_dump($client);

	if ( is_null( $client->ID ) ) {
		print "<B>יש להקים לקוח ב Invoice4u</B>";
	} else {
		print "<button id=\"btn_invoice\" onclick=\"create_invoice()\">הפק חשבונית מס קבלה</button>";
		print "מזומן";
		print "<input id=\"cash\">";
		print "אשראי";
		print "<input id=\"credit\">";
		print "העברה";
		print "<input id=\"bank\">";
	}
}

?>
<table id="transactions"></table>

</body>
</html>
