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

    const product_name_id = <? print DeliveryFields::product_name; ?>;
    const q_quantity_ordered_id = <? print DeliveryFields::order_q; ?>;
    const q_units = <? print DeliveryFields::order_q_units; ?>;
    const q_supply_id = <? print DeliveryFields::delivery_q; ?>;
    const has_vat_id = <? print DeliveryFields::has_vat; ?>;
    const line_vat_id = <?print DeliveryFields::line_vat; ?>;
    const price_id = <? print DeliveryFields::price; ?>;
    const line_total_id = <? print DeliveryFields::delivery_line; ?>;
    const term_id = <? print DeliveryFields::term; ?>;
    const q_refund_id = <? print DeliveryFields::refund_q ?>;
    const refund_total_id = <? print DeliveryFields::refund_line; ?>;

    function moveNextRow(my_row) {
        if (event.which == 13) {
            var current = document.getElementsByName("quantity" + (my_row));
            current[0].value = Math.round(current[0].value * 10) / 10;
            var objs = document.getElementsByName("quantity" + (my_row + 1));
            calcDelivery();
            if (objs[0]) objs[0].focus();
            else {
                var del = document.getElementById("delivery");
                if (del) del.focus();
            }
        }
    }

    function addLine() {
        var table = document.getElementById('del_table');
//        var hidden = [];
//        for (var i = 0; i < table.rows.length; i++) {
//            for (var j = 0; j < table.rows[0].cells.length; j++)
//                table.rows[i].cells[j].style.display = 'visible';
////             hidden[i] = table.rows[0].cells[i].style.display;
//
//        }
//        return;

        var lines = table.rows.length;
        var line_id = lines - 4;
        var row = table.insertRow(lines - 4);
//        row.insertCell(0).style.visibility = false;              // 0 - select
        row.insertCell(-1).innerHTML = "<input id=\"nam_" + line_id + "\" type=\"text\">";   // 1 - product name
        row.insertCell(-1).innerHTML = "0";                       // 2 - quantity ordered
        row.insertCell(-1).innerHTML = "";                        // 3 - unit ordered
        row.insertCell(-1).innerHTML = "<input id=\"prc_" + line_id + "\" type=\"text\">";   // 5 - price
        row.insertCell(-1).innerHTML = ""; // order total
        row.insertCell(-1).innerHTML = "<input id=\"deq_" + line_id + "\" type=\"text\">";   // 4 - supplied
        row.insertCell(-1).innerHTML = "<input id=\"hvt_" + line_id + "\"  type = \"checkbox\" checked>"; // 6 - has vat
        row.insertCell(-1).id = "lvt_" + line_id;                       // 7 - line vat
        row.insertCell(-1).id = "del_" + line_id;   // 8 - total_line

        calcDelivery();
//        row.insertCell(9).style.visibility = false;              // 9 - categ
//        row.insertCell(10).style.visibility = false;              // 10 - refund q
//        row.insertCell(11).style.visibility = false;              // 11 - refund total
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
        fee = get_value(document.getElementById("del_del"));
        for (i = 1; i < lines - 3; i++) {
            var prfx = table.rows[i].cells[0].id.substr(4);
            if (prfx === "")
                prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);

            var quantity = get_value(document.getElementById("deq_" + prfx));
            if (quantity > 0 || quantity < 0) saved_lines++;
            var prod_name = get_value(document.getElementById("nam_" + prfx));
            if (prod_name === "דמי משלוח") fee = get_value(document.getElementById("del_" + prfx))


            // var product = get_value(table.rows[i].cells[product_name_id].firstChild);
            // if (product == "דמי משלוח") fee = get_value(table.rows[i].cells[line_total_id].firstChild);
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
                    var prfx = table.rows[i].cells[0].id.substr(4);
                    if (prfx === "")
                        prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);
                    var prod_id;
                    var prod_name;
                    //if (parseInt(prfx) > 0) { // Regular line
                    prod_id = get_value(document.getElementById("pid_" + prfx));
                    prod_name = get_value(document.getElementById("nam_" + prfx));
//                    } else {
//                        // Special line:
//                        if (prfx === "dis") {
//                            prod_id = 0;
//                            prod_name = "הנחת כמות";
//                        }
//                    }
                    if (!(prod_id > 0)) prod_id = 0; // New or unknown
                    if (prod_name.length > 1) prod_name = prod_name.replace(/['"()%,]/g, "").substr(0, 20);

                    var quantity = get_value(document.getElementById("deq_" + prfx));
                    if (quantity === "") quantity = 0;

                    var quantity_ordered = get_value(document.getElementById("orq_" + prfx));
                    if (quantity_ordered === "") quantity_ordered = 0;

                    var unit_ordered = get_value(document.getElementById("oru_" + prfx));
                    if (unit_ordered.length < 1) unit_ordered = 0;

                    var price = get_value(document.getElementById("prc_" + prfx));
                    var vat = get_value(document.getElementById("lvt_" + prfx));
                    // var prod_name = get_value(table.rows[i].cells[product_name_id].firstChild);
                    var line_total = get_value(document.getElementById("del_" + prfx));
//                    if (table.rows[i].cells[0].children.length === 1) { // delivery lines or new line
//                        prod_id = 0;
//                        prod_name = get_value(table.rows[i].cells[0]);
//                        quantity = get_value(table.rows[i].cells[3]);
//                        quantity_ordered = 0;
//                        price = get_value(table.rows[i].cells[4]);
//                        vat = get_value(table.rows[i].cells[6]);
//                        line_total = get_value(table.rows[i].cells[7]);
//                    } else {
//                        line_total = get_value(table.rows[i].cells[line_total_id].firstChild);
//                    }

                    if (prod_id > 0 || line_total > 0 || line_total < 0) {
                        push(line_args, prod_id);
                        push(line_args, encodeURIComponent(prod_name));
                        push(line_args, quantity);
                        push(line_args, quantity_ordered);
                        push(line_args, unit_ordered);
                        push(line_args, vat);
                        push(line_args, price);
                        push(line_args, line_total);
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

    function push(array, item) {
        if (item === null) {
            alert(null);
            item = '';
        }

        array.push(item);
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
            if (table.rows[i].cells[0].id.substr(4, 3) == "bsk") {
                // Sum upper lines and compare to basket price
                var j = i - 1;
                var sum = 0;
                // while (table.rows[j].cells[product_name_id].innerHTML.substr(0, 3) == "===") {
                while (get_value(document.getElementById("nam_" + j)).substr(0, 3) == "===") {
                    // alert(table.rows[j].cells[product_name_id].innerHTML);
                    sum = sum + Number(document.getElementById("del_" + j).innerHTML);
                    j = j - 1;
                }
                var basket_total = Number(get_value(document.getElementById("orq_" + j))) * Number(get_value(document.getElementById("prc_" + j)));
                if (sum > basket_total) {
                    diff = Math.round(100 * (basket_total - sum), 2) / 100;
                    table.rows[i].cells[q_supply_id].innerHTML = 1;
                    table.rows[i].cells[line_total_id].innerHTML = diff;
                    table.rows[i].cells[price_id].innerHTML = diff;
                    table.rows[i].cells[line_vat_id].innerHTML = 0;
                    total += diff;
                } else {
                    table.rows[i].cells[q_supply_id].innerHTML = '';
                    table.rows[i].cells[line_total_id].innerHTML = '';
                    table.rows[i].cells[price_id].innerHTML = '';
                }

                continue;
            }
            if (table.rows[i].cells[product_name_id].innerHTML == "סה\"כ חייבי מע\"מ") break;
            if (table.rows[i].cells[product_name_id].innerHTML == "" ||
                table.rows[i].cells[product_name_id].innerHTML == "הנחת כמות") continue; // Reserved line for discount

            var q = 0;
            var p = 0;
            var line_total = 0;
            var vat_percent = <?php global $global_vat; print $global_vat; ?>;
            var line_vat = 0;
            var has_vat = true;
            var prfx = table.rows[i].cells[0].id.substr(4);
            if (prfx === "")
                prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);

            p = get_value(document.getElementById("prc_" + prfx));
            if (prfx === "del") {
                q = 1;
            } else
                q = get_value(document.getElementById("deq_" + prfx));
            has_vat = get_value(document.getElementById("hvt_" + prfx));
            if (has_vat) line_vat = Math.round(100 * p * q / (100 + vat_percent) * vat_percent) / 100;
            line_total = Math.round(p * q * 100) / 100;
            document.getElementById("del_" + prfx).innerHTML = line_total.toString();
            document.getElementById("lvt_" + prfx).innerHTML = line_vat.toString();

            //            if (table.rows[i].cells[price_id].firstChild.tagName == "INPUT") { // delivery lines or new line
//                var prfx = table.rows[i].cells[0].substr(4);
//                p = get_value(document.getElementById("deq_" + prfx));
//                has_vat = get_value(document.getElementById("hvt_" + prfx));
//                if (table.rows[i].cells[0].id == "delivery_fee_0") {
//                    p = get_value(document.getElementById("delivery_fee_5").firstElementChild);
//                    if (! document.getElementById("delivery_fee_6").firstElementChild.checked) has_vat = false;
//                    q = 1;
//                } else {
//                    p = get_value(document.getElementById(""))
//                    alert ("XXXXXX");
//                }
//                if (has_vat) line_vat = Math.round(100 * p * q / (100 + vat_percent) * vat_percent) / 100;
//
//                line_total = Math.round(p * q * 100) / 100;
//                if (table.rows[i].cells[5].firstChild.checked) {
//                    table.rows[i].cells[6].innerHTML = line_vat;
//                }
//                table.rows[i].cells[7].innerHTML = line_total;
//            } else {
//                q = get_value(table.rows[i].cells[q_supply_id].firstChild);
//                p = get_value(table.rows[i].cells[price_id].firstChild);
//                line_total = Math.round(p * q * 100) / 100;
//                table.rows[i].cells[line_total_id].innerHTML = line_total;
//                if (table.rows[i].cells[has_vat_id].firstChild.checked) {
//                    line_vat = Math.round(100 * p * q / (100 + vat_percent) * vat_percent) / 100;
//                    table.rows[i].cells[line_vat_id].innerHTML = line_vat;
//                }
//            }
            if (line_vat) due_vat += line_total;
            total_vat += line_vat;
            total += line_total;

            if (<?php print ( MultiSite::LocalSiteID() == 1 ) ? 1 : 0; ?> &&
            (q >= 8) && (table.rows[i].cells.length > 7)
        )
            {
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
        }

        // Show discount line or hide
        var line = table.rows.length - 4;
        quantity_discount = Math.round(quantity_discount);
        table.rows[line].cells[product_name_id].innerHTML = (quantity_discount > 0) ? "הנחת כמות" : "";
        table.rows[line].cells[q_supply_id].innerHTML = (quantity_discount > 0) ? -0.15 : "";
        table.rows[line].cells[price_id].innerHTML = (quantity_discount > 0) ? quantity_discount : "";
        table.rows[line].cells[line_vat_id].innerHTML = 0; // For now just for fresh. No VAT. (quantity_discount > 0) ? quantity_discount : "";
        var discount = -Math.round(quantity_discount * 15) / 100;
        total = total + discount;
        table.rows[line].cells[line_total_id].innerHTML = (quantity_discount > 0) ? discount : "";

        // Update totals
        total = Math.round(100 * total, 2) / 100;
        due_vat = Math.round(100 * due_vat, 2) / 100;
//    round_total = Math.round(total);
//    table.rows[table.rows.length - 4].cells[line_total_id].firstChild.nodeValue = Math.round((round_total-total) *100)/100;
        // Due VAT
        document.getElementById("del_due").innerHTML = due_vat;
        // VAT
        document.getElementById("del_vat").innerHTML = Math.round(total_vat * 100) / 100;
        // Total
        document.getElementById("del_tot").innerHTML = total;
    }
</script>

