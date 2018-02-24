<?php

require_once( '../r-shop_manager.php' );
require_once( "../gui/inputs.php" );
require_once( "account.php" );
require_once( "../multi-site/multi-site.php" );
require_once( "../delivery/delivery-common.php" );
require_once( '../../im-config.php' );
require_once( '../invoice4u/invoice.php' );
require_once( "../account/gui.php" );
// $start_time =  microtime(true);

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
if ( $manager and isset( $_GET["customer_id"] ) ) {
	print header_text( false );
	$customer_id = $_GET["customer_id"];
	// print "id: " . $customer_id;

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

        function disable_btn(id) {
            var btn = document.getElementById(id);
            if (btn) btn.disabled = true;
        }

        function enable_btn(id) {
            var btn = document.getElementById(id);
            if (btn) btn.disabled = false;
        }

        function updateDisplay() {
            var id = document.getElementById("invoice_client_id").innerHTML;
            if (id > 0) {
                document.getElementById("payment_table").style.visibility = "visible";
                document.getElementById("btn_invoice").style.visibility = "visible";
                document.getElementById("btn_create_user").style.visibility = "hidden";

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
                disable_btn("btn_invoice");
                disable_btn("btn_receipt");
                // disable_btn("btn_refund");
            } else {
                document.getElementById("payment_table").style.visibility = "hidden";
                document.getElementById("btn_invoice").style.visibility = "hidden";
            }
        }
        function create_user() {
            var request = "account-post.php?operation=create_invoice_user&id=" +<?php print $customer_id ?>;
            //  window.alert(request);
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    document.getElementById("invoice_client_id").innerHTML = xmlhttp.response;
                    updateDisplay();
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function calcRefund() {
            var table = document.getElementById('del_table');
            var total = 0;
            var total_vat = 0;
            var lines = table.rows.length;
            var quantity_discount = 0;
            var due_vat = 0;

            for (var i = 1; i < lines; i++)  // Skip the header. Skip last lines: total, vat, total-vat, discount
            {
                if (table.rows[i].cells[0].innerHTML == "סהכ חייבי מעם") break;
                if (table.rows[i].cells[0].innerHTML == "" ||
                    table.rows[i].cells[0].innerHTML == "הנחת כמות") continue; // Reserved line for discount
                if (table.rows[i].cells[0].innerHTML == "הנחת סל") {
                    continue;
                }
                var q = get_value(table.rows[i].cells[q_refund_id].firstChild);
                var p = get_value(table.rows[i].cells[p_id].firstChild);
                var line_refund = Math.round(p * q * 100) / 100;

                if ((q >= 8) && (table.rows[i].cells.length > 7)) {
                    var line_term_id = table.rows[i].cells[term_id].innerHTML;
                    // alert (line_term_id);
                    var terms = line_term_id.split(",");
                    var fresh = false;
                    for (var x = 0; x < terms.length; x++) {
                        if ([<?php print_fresh_category()?>].indexOf(parseInt(terms[x])) > -1) {
                            fresh = true;
                        }
                    }
                    if (fresh) Math.round(line_refund * 0.85 * 100) / 100;

                }
                table.rows[i].cells[refund_total_id].firstChild.nodeValue = line_refund;
                total += line_refund;

                // Vat
                var vat_percent = <?php global $global_vat; print $global_vat; ?>;
                var line_vat = 0;
                if (table.rows[i].cells[has_vat_id].firstChild.checked) {
                    line_vat = Math.round(100 * p * q / (100 + vat_percent) * vat_percent) / 100;
                    total_vat += line_vat;
                    due_vat += p * q;
                }
                table.rows[i].cells[line_vat_id].firstChild.nodeValue = line_vat;
            }

            // Show discount line or hide
            var line = table.rows.length - 4;
            quantity_discount = Math.round(quantity_discount);
            table.rows[line].cells[product_name_id].innerHTML = (quantity_discount > 0) ? "הנחת כמות" : "";
            table.rows[line].cells[q_supply_id].innerHTML = (quantity_discount > 0) ? -0.15 : "";
            table.rows[line].cells[p_id].innerHTML = (quantity_discount > 0) ? quantity_discount : "";
            var discount = -Math.round(quantity_discount * 15) / 100;
            total = total + discount;
            table.rows[line].cells[line_total_id].innerHTML = (quantity_discount > 0) ? discount : "";

            // Update totals
            total = Math.round(100 * total, 2) / 100;
            due_vat = Math.round(100 * due_vat, 2) / 100;
//    round_total = Math.round(total);
//    table.rows[table.rows.length - 4].cells[line_total_id].firstChild.nodeValue = Math.round((round_total-total) *100)/100;
            // Due VAT
            table.rows[table.rows.length - 3].cells[refund_total_id].firstChild.nodeValue = due_vat;
            // VAT
            table.rows[table.rows.length - 2].cells[refund_total_id].firstChild.nodeValue = Math.round(total_vat * 100) / 100;
            // Total
            table.rows[table.rows.length - 1].cells[refund_total_id].firstChild.nodeValue = total_refund;
        }

        function create_refund() {
            // var request = "account-post.php?operation=show_refund&id=";
            var request = "../delivery/create-delivery.php?refund&id=";
            var collection = document.getElementsByClassName("trans_checkbox");
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var del_id = table.rows[i + 1].cells[6].firstChild.innerHTML;
                    request = request + del_id;
                    break;
                }
            }

            //  window.alert(request);
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    // refund_area.innerHTML = xmlhttp.response;
                    refund_area.innerHTML = xmlhttp.response;

                    document.getElementById("btn_calc").onclick = function () {
                        calcRefund();
                    }

                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
</head>

<body onload="updateDisplay()">

<?php

// $now = debug_time("print", $start_time);

print "<center><h1>מצב חשבון ";
print get_customer_name( $customer_id );
print  "</h1> </center>";


if ( $manager ) {

	$client_id = get_invoice_user_id( $customer_id );
//	var_dump($client);

	$user_info = gui_table( array(
		array( "דואל", get_customer_email( $customer_id ) ),
		array( "טלפון", get_customer_phone( $customer_id ) ),
		array( "מספר מזהה", gui_lable( "invoice_client_id", $client_id ) ),
		array(
			"אמצעי תשלום",
			gui_select_payment( "payment", get_payment_method( $customer_id ), "onchange=\"save_payment_method()\"" )
		)
	) );
	$style     = "table.payment_table { border-collapse: collapse; } " .
	             " table.payment_table, td.change, th.change { border: 1px solid black; } ";
	$sums      = null;
	$new_tran  = gui_table( array(
		array(
			"תשלום",
			gui_button( "btn_receipt", "create_receipt()", "הפק חשבונית מס קבלה" )
		),
		array( "תאריך", gui_input_date( "pay_date", "" ) ),
		array( "מזומן", gui_input( "cash", "", array( 'onkeyup="update_sum()"' ) ) ),
		array( "אשראי", gui_input( "credit", "", array( 'onkeyup="update_sum()"' ) ) ),
		array( "העברה", gui_input( "bank", "", array( 'onkeyup="update_sum()"' ) ) ),
		array( "המחאה", gui_input( "check", "", array( 'onkeyup="update_sum()"' ) ) ),
		array( "עודף", " <div id=\"change\"></div>" )
	), "payment_table", true, true, $sums, $style, "payment_table" );

	print gui_table( array(
		array( gui_header( 2, "פרטי לקוח", true ), gui_header( 2, "קבלה", true ) ),
		array( $user_info, $new_tran )
	) );
}

?>
<br>

<script>
	<?php
	$filename = __DIR__ . "/../client_tools.js";
	$handle = fopen( $filename, "r" );
	$contents = fread( $handle, filesize( $filename ) );
	print $contents;
	?>

    function save_payment_method() {
        var method = get_value(document.getElementById("payment"));
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                //logging.innerHTML += "חשבונית מספר " + receipt_id;
                updateDisplay();
            }
        }
        var request = "account-post.php?operation=save_payment";
        request = request + "&user_id=" + <?php print $customer_id; ?>;
        request = request + "&method_id=" + method;

        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }
    function update_sum() {
        var collection = document.getElementsByClassName("trans_checkbox");
        var table = document.getElementById("transactions");
        var total = 0;
        var credit = parseFloat(get_value(document.getElementById("credit")));
        if (isNaN(credit)) credit = 0;
        var bank = parseFloat(get_value(document.getElementById("bank")));
        if (isNaN(bank)) bank = 0;
        var cash = parseFloat(get_value(document.getElementById("cash")));
        if (isNaN(cash)) cash = 0;
        var check = parseFloat(get_value(document.getElementById("check")));
        if (isNaN(check)) check = 0;

        var delivery_count = 0;
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                delivery_count++;
                total += parseFloat(get_value(table.rows[i + 1].cells[2]));

            }
        }
        total = Math.round(100 * total) / 100;
        // alert(total);
        if (total === 0) {
            logging.innerHTML = "יש לבחור משלוחים להפקת המסמך";

            document.getElementById('btn_invoice').disabled = true;
            document.getElementById('btn_receipt').disabled = true;
            //document.getElementById('btn_refund').disabled = true;
        } else {
            logging.innerHTML = "סכום השורות שנבחר " + total + "<br/>";
            logging.innerHTML += " סך תקבולים " + (credit + bank + cash + check) + "<br/>";
            var total_pay = (credit + bank + cash + check);
            var cash_delta = Math.round(100 * (total_pay - total)) / 100;
            change.innerHTML = cash_delta;

            if ((total_pay > 0) && Math.abs(cash_delta) <= 400) {
                document.getElementById('btn_invoice').disabled = true;
                document.getElementById('btn_receipt').disabled = false;
            } else {
                document.getElementById('btn_invoice').disabled = false;
                document.getElementById('btn_receipt').disabled = true;
            }
            // document.getElementById('btn_refund').disabled = (delivery_count != 1);
        }
    }
    //קבלה
    function create_receipt() {
        document.getElementById('btn_receipt').disabled = true;
        var collection = document.getElementsByClassName("trans_checkbox");
        var table = document.getElementById("transactions");
        var del_ids = new Array();

        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                var del_id = table.rows[i + 1].cells[6].firstChild.innerHTML;
                del_ids.push(del_id);
            }
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                receipt_id = xmlhttp.responseText.trim();
                logging.innerHTML += "חשבונית מספר " + receipt_id;
                updateDisplay();
            }
        }
        var credit = parseFloat(get_value(document.getElementById("credit")));
        if (isNaN(credit)) credit = 0;
        var bank = parseFloat(get_value(document.getElementById("bank")));
        if (isNaN(bank)) bank = 0;
        var cash = parseFloat(get_value(document.getElementById("cash")));
        if (isNaN(cash)) cash = 0;
        var check = parseFloat(get_value(document.getElementById("check")));
        if (isNaN(check)) check = 0;
        var date = get_value(document.getElementById("pay_date"));
        var request = "account-post.php?operation=create_receipt" +
            "&cash=" + cash +
            "&credit=" + credit +
            "&bank=" + bank +
            "&check=" + check +
            "&date=" + date +
            "&change=" + change.innerHTML +
            "&ids=" + del_ids.join() +
            "&user_id=" + <?php print $customer_id; ?>;
        // alert(request);
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
//        }
    }
    function create_invoice() {
        document.getElementById('btn_invoice').disabled = true;
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
                logging.innerHTML += "חשבונית מספר " + invoice_id + " נוצרה ";
                updateDisplay();
            }
        }
        var request = "account-post.php?operation=create_invoice" +
            "&ids=" + del_ids.join() +
            "&user_id=" + <?php print $customer_id; ?>;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
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
    <label id="total">יתרה לתשלום</label>
</h2>
<br>
<?php

if ( $manager ) {
	require_once( '../invoice4u/invoice.php' );
	// var_dump($client);



	// print gui_button( "btn_refund", "create_refund()", "הפק זיכוי" );
	// print "<button id=\"btn_invoice\" onclick=\"create_invoice()\">הפק חשבונית מס</button>";
	// print "<br/>";
	// print "<button id=\"btn_receipt\" onclick=\"create_receipt()\">הפק חשבונית מס קבלה</button>";
	print '<div id="logging"></div>';
	// print '<div id="refund_area"></div>';

	print gui_button( "btn_create_user", "create_user()", "צור משתמש" );
}

?>
<H2>תנועות</H2>
<table id="transactions"></table>

<?php
print "הנתונים הן יתרת חוב. זיכוי ותשלום ירשמו בסימן שלילי";
print "<br/>";
print gui_table( array(
	array( "סוג פעולה", "סכום", "תאריך", "מזהה" ),
	array(
		'<input type="text" id="transaction_type">',
		'<input type="text" id="transaction_amount">',
		'<input type="date" id="transaction_date">',
		'<input type="text" id="transaction_ref">'
	)
) );
print '<button id="btn_add" onclick="addTransaction()">הוסף תנועה</button>';
print gui_button( "btn_invoice", "create_invoice()", "הפק חשבונית מס" );

?>
</body>
</html>
