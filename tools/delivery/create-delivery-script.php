<?php
require_once( '../multi-site/multi-site.php' );
require_once( 'delivery-common.php' );

$id       = $_GET["id"];
$order_id = $_GET["order_id"];
$edit     = false;
if ( $id > 0 ) {
	$edit     = true;
	$order_id = get_order_id( $id );
}

?>

<script>

    function moveNextRow(my_row) {
        if (event.which == 13) {
            var current = document.getElementsByName("quantity" + (my_row));
            current[0].value = Math.round(current[0].value * 10) / 10;
            var objs = document.getElementsByName("quantity" + (my_row + 1));
            if (objs[0]) objs[0].focus();
        }
    }

    function addLine() {
        var table = document.getElementById('del_table');
        var lines = table.rows.length;
        var row = table.insertRow(lines - 4);
        row.insertCell().innerHTML = "<input type=\"text\">";
        row.insertCell().innerHTML = "0";
        row.insertCell().innerHTML = "<input type=\"text\">";
        row.insertCell().innerHTML = "<input id=\"has_vat\" type = \"checkbox\">";
        row.insertCell().innerHTML = "0";
        row.insertCell().innerHTML = "<input type=\"text\">";
        row.insertCell().innerHTML = "0";
    }

    function addDelivery() {

        calcDelivery();
        document.getElementById('btn_add').disabled = true;
	    <?php if ( isset( $order_id ) ) print "var order_id = " . $order_id . ";" ?>

        var table = document.getElementById('del_table');
        var lines = table.rows.length;
        var total = table.rows[table.rows.length - 1].cells[line_total_id].firstChild.nodeValue;
        var total_vat = table.rows[table.rows.length - 2].cells[line_total_id].firstChild.nodeValue;
        var logging = document.getElementById('logging');
        var line_number = 0;
        var is_edit = false;

	    <?php if ( $edit ) {
	    print "is_edit = true;";
    } ?>

        // Enter delivery note to db.
        var request = "create-delivery-post.php?operation=add_header&order_id=" + order_id
            + "&total=" + total
            + "&vat=" + total_vat;


	    <?php if ( $edit ) {
	    print "request = request + \"&edit&delivery_id=" . $id . "\"";
    } ?>

        var delivery_id = 0;
        var saved_lines = 0;
        var fee = 0;
        var i;

        // Check number of lines in the delivery
        for (i = 1; i < lines - 3; i++) {
            var quantity = get_value(table.rows[i].cells[q_supply_id].firstChild);
            saved_lines++;

            var product = get_value(table.rows[i].cells[product_name_id].firstChild);
            if (product == "משלוח") fee = get_value(table.rows[i].cells[line_total_id].firstChild);
        }
        request = request + "&lines=" + saved_lines;
        request = request + "&fee=" + fee;

        // Call the server to save the delivery
        server_header = new XMLHttpRequest();
        server_header.onreadystatechange = function () {
            // Wait to get delivery id.
            // 2) Save the lines.
            if (server_header.readyState == 4 && server_header.status == 200)  // Request finished
            {
                delivery_id = server_header.responseText.trim();
                logging.value += "תעודת משלוח מס " + delivery_id + "נשמרת " + "..";

                server_lines = new XMLHttpRequest();

                var line_request = "create-delivery-post.php?operation=add_lines&delivery_id=" + delivery_id;
                if (is_edit) line_request = line_request + "&edit";
                var line_args = new Array();

                // logging.value += response_text;
                // Enter delivery lines to db.
                for (i = 1; i < lines - 3; i++) {
                    var prod_id = document.getElementById('del_table').rows[i].cells[0].id;
                    if (!(prod_id > 0)) prod_id = 0; // New or unknown
                    var prod_name = get_value(table.rows[i].cells[product_name_id].firstChild);
//                if (prod_name == null) prod_name = "";

                    if (prod_name.length > 1) prod_name = prod_name.replace(/['"()%,]/g, "").substr(0, 20);

                    var quantity = get_value(table.rows[i].cells[q_supply_id].firstChild);
                    var quantity_ordered = get_value(table.rows[i].cells[q_quantity_ordered_id].firstChild);
                    if (quantity == "") quantity = 0;
                    var price = get_value(table.rows[i].cells[p_id].firstChild);
                    var vat = get_value(table.rows[i].cells[line_vat_id].firstChild);
                    // var prod_name = get_value(table.rows[i].cells[product_name_id].firstChild);
                    var line_total = get_value(table.rows[i].cells[line_total_id].firstChild);

                    if (prod_id > 0 || line_total != 0) {
                        line_args.push(prod_id);
                        line_args.push(prod_name);
                        line_args.push(quantity);
                        line_args.push(quantity_ordered);
                        line_args.push(vat);
                        line_args.push(price);
                        line_args.push(line_total);
                    }
                }
                server_lines = new XMLHttpRequest();
                server_lines.onreadystatechange = function () {
                    if (server_lines.readyState == 4 && server_lines.status == 200) {  // Request finished
                        logging.value += ". הסתיים";
//                    3) Send the delivery notes to the client
                        // Now call the server, to send the delivery. It waits few seconds for the save lines to finish
                        xmlhttp_send = new XMLHttpRequest();
                        xmlhttp_send.open("GET", "send-delivery.php?del_id=" + delivery_id);
                        xmlhttp_send.send();
                    }
                }

                line_request = line_request + "&lines=" + line_args.join();
                server_lines.open("GET", line_request, true);
                server_lines.send();
            }
        }

//	1) Send the header.
        server_header.open("GET", request, true);
        server_header.send();
    }

    function printDeliveryNotes() {
        document.getElementById('btn_calc').style.visibility = "hidden";
        document.getElementById('btn_print').style.visibility = "hidden";
        // Get the html
        var txt = document.documentElement.innerHTML;

        // Download the html
        var a = document.getElementById("a");
        var file = new Blob(txt, 'text/html');
        a.href = URL.createObjectURL(file);
        a.download = 're.html';

//	download(txt, 'myfilename.html', 'text/html')
//	window.open('data:text/html;charset=utf-8,<html dir="rtl" lang="he">' + txt + '</html>');

        document.getElementById('btn_calc').style.visibility = "visible";
        document.getElementById('btn_print').style.visibility = "visible";

    }

    function calcDelivery() {
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
                // Sum upper lines and compare to basket price
                var j = i - 1;
                var sum = 0;
                while (table.rows[j].cells[0].innerHTML.substr(0, 3) == "===") {
                    sum = sum + Number(table.rows[j].cells[line_total_id].innerHTML);
                    j = j - 1;
                }
                var basket_total = Number(table.rows[j].cells[q_quantity_ordered_id].innerHTML) * Number(table.rows[j].cells[p_id].innerHTML);
                if (sum > basket_total) {
                    diff = Math.round(100 * (basket_total - sum), 2) / 100;
                    table.rows[i].cells[q_supply_id].innerHTML = 1;
                    table.rows[i].cells[line_total_id].innerHTML = diff;
                    table.rows[i].cells[p_id].innerHTML = diff;
                    total += diff;
                } else {
                    table.rows[i].cells[q_supply_id].innerHTML = '';
                    table.rows[i].cells[line_total_id].innerHTML = '';
                    table.rows[i].cells[p_id].innerHTML = '';
                }

                continue;
            }
            var q = get_value(table.rows[i].cells[q_supply_id].firstChild);
            var p = get_value(table.rows[i].cells[p_id].firstChild);
            if (table.rows[i].cells[p_id].firstChild.tagName == "INPUT") { // delivery lines or new line
                p = table.rows[i].cells[p_id].firstChild.value;
            }
            var line_total = Math.round(p * q * 100) / 100;
            table.rows[i].cells[line_total_id].firstChild.nodeValue = line_total;
            total += line_total;

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
                if (fresh) quantity_discount += line_total;
            }

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
        table.rows[table.rows.length - 3].cells[line_total_id].firstChild.nodeValue = due_vat;
        // VAT
        table.rows[table.rows.length - 2].cells[line_total_id].firstChild.nodeValue = Math.round(total_vat * 100) / 100;
        // Total
        table.rows[table.rows.length - 1].cells[line_total_id].firstChild.nodeValue = total;
    }
</script>
