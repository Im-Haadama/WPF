<?php



// This page is open to clients.
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

init();

require_once( "account.php" );
require_once( "../multi-site/imMulti-site.php" );
require_once( '../invoice4u/invoice.php' );
require_once( "../account/gui.php" );
require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );

// $start_time =  microtime(true);

// only if admin can select user. Otherwise get id from login info

$manager = is_manager();

if ( $manager and isset( $_GET["customer_id"] ) ) {
	print header_text( false, true, true, array( "account.js" ) );
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
        var site_url = <?php print '"' . get_site_url() . '"';?>;
        var key = '<?php print get_key(); ?>';

        function addTransaction() {
            var type = document.getElementById("transaction_type").value;
            var amount = document.getElementById("transaction_amount").value;
            var date = document.getElementById("transaction_date").value;
            var ref = document.getElementById("transaction_ref").value;
            var request = site_url + "/fresh/account/account-add-trans.php?customer_id=" + <?php print $customer_id ?>
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

        function updateDisplayTrans() {
            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    table = document.getElementById("transactions");
                    table.innerHTML = xmlhttp1.response;
                }
            }
            var request1 = site_url + "/fresh/account/account-post.php?operation=table&customer_id=" + <?php print $customer_id; ?>;
            xmlhttp1.open("GET", request1, true);
            xmlhttp1.send();
            xmlhttp1.onloadend = function () {
                if (xmlhttp1.status == 404 || xmlhttp1.status == 500)
                    updateDisplayTrans();
//                throw new Error(url + ' replied 404');
            }
        }
        function updateDisplayTotal() {
            xmlhttp2 = new XMLHttpRequest();
            xmlhttp2.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp2.readyState == 4 && xmlhttp2.status == 200)  // Request finished
                {
                    label = document.getElementById("total");
                    label.innerHTML = xmlhttp2.response;
                }
            }
            var request2 = site_url + "/fresh/account/account-post.php?key=" + key + "&operation=total&customer_id=" + <?php print $customer_id; ?>;
            xmlhttp2.open("GET", request2, true);
            xmlhttp2.send();
            xmlhttp2.onloadend = function () {
                if (xmlhttp2.status == 404)
                    updateDisplayTotal();
            }
        }

        function updateDisplay() {
            var id = document.getElementById("invoice_client_id").innerHTML;
            if (id > 0) {
                document.getElementById("payment_table").style.visibility = "visible";
                document.getElementById("btn_invoice").style.visibility = "visible";
                document.getElementById("btn_create_user").style.visibility = "hidden";
                updateDisplayTotal();
                updateDisplayTrans();
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

}

?>
<br>

<script type="text/javascript" src="/core/gui/client_tools.js"></script>

<script>
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
    //קבלה

    function create_invoice() {
        document.getElementById('btn_invoice').disabled = true;
        var collection = document.getElementsByClassName("trans_checkbox");
        var table = document.getElementById("transactions");
        var del_ids = new Array();
        var total = 0;

        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                let row_id = collection[i].id.substring(4); // table.rows[i + 1].cells[6].firstChild.innerHTML;
                let del_id = get_value_by_name("del_" + row_id);
                del_ids.push(del_id);
                total = total + parseInt(get_value_by_name("amo_" + row_id));
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

    function send_deliveries() {
        document.getElementById('btn_send').disabled = true;
        var collection = document.getElementsByClassName("trans_checkbox");
        var table = document.getElementById("transactions");
        var del_ids = new Array();
        var total = 0;

        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                var del_id = collection[i].id.substring(3); // table.rows[i + 1].cells[6].firstChild.innerHTML;
                del_ids.push(del_id);
                total = total + parseInt(table.rows[i + 1].cells[2].firstChild.data);
            }
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                response = xmlhttp.responseText;
                logging.innerHTML += response;
                document.getElementById('btn_send').disabled = false;

            }
        }
        var request = "account-post.php?operation=send" +
            "&del_ids=" + del_ids.join();
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function printDeliveryNotes() {
        // Get the html
        document.getElementById('btn_print').style.visibility = "hidden";
        window.open("//pdfcrowd.com/url_to_pdf/");
        document.getElementById('btn_print').style.visibility = "visible";
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
	print '<div id="logging"></div>';
	print Core_Html::GuiButton( "btn_create_user", "create_user()", "צור משתמש" );
}

?>
<H2>תנועות</H2>
<table id="transactions"></table>

<?php
print "הנתונים הן יתרת חוב. זיכוי ותשלום ירשמו בסימן שלילי";
print "<br/>";
print gui_table_args( array(
	array( "סוג פעולה", "סכום", "תאריך", "מזהה" ),
	array(
		'<input type="text" id="transaction_type">',
		'<input type="text" id="transaction_amount">',
		'<input type="date" id="transaction_date">',
		'<input type="text" id="transaction_ref">'
	)
) );
print '<button id="btn_add" onclick="addTransaction()">הוסף תנועה</button>';
print Core_Html::GuiButton( "btn_invoice", "create_invoice()", "הפק חשבונית מס" );
print Core_Html::GuiButton( "btn_send", "send_deliveries()", "שלח תעודות משלוח" );

function print_fresh_category() {
	$list = "";

	$option = sql_query_single_scalar( "SELECT option_value FROM wp_options WHERE option_name = 'im_discount_categories'" );
	if ( ! $option ) {
		return;
	}

	$fresh_categ = explode( ",", $option );
	foreach ( $fresh_categ as $categ ) {
		$list .= $categ . ",";
		foreach ( get_term_children( $categ, "product_cat" ) as $child_term_id ) {
			$list .= $child_term_id . ", ";
		}
	}
	print rtrim( $list, ", " );
}

?>
</body>
</html>