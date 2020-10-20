function leaveQuantityFocus()
{

}

function calcDelivery() {
    let table = document.getElementById('del_table');
    let total = 0;
    let total_vat = 0;
    let lines = table.rows.length;
    let quantity_discount = 0;
    let due_vat = 0;
    let basket_sum = 0, basket_price = 0;

    for (let i = 1; i < lines; i++)  // Skip the header. Skip last lines: total, vat, total-vat, discount
    {
        // let line_type = get_line_type(i);
        if (! table.rows[i].cells.length) continue; // Skip empty line

        var line_total = 0;
        var vat_percent = 17; // Todo: read from settings
        var line_vat = 0;
        var has_vat = true;
        let id = table.rows[i].cells[0].id;
        var order_line = id.substr(id.lastIndexOf("_") + 1);
        if (order_line === "")
            order_line = table.rows[i].cells[0].firstElementChild.id.substr(4);

        let p = get_value(document.getElementById("price_" + order_line));
        let q = get_value(document.getElementById("quantity_" + order_line));
        line_total = Math.round(p * q * 100) / 100;
        basket_sum += line_total;
        document.getElementById("line_price_" + order_line).innerHTML = line_total.toString();

        if (line_vat) due_vat += line_total;
        total_vat += line_vat;
        total += line_total;
    }

    total += parseFloat(get_value_by_name("fee"));

    document.getElementById("total").innerHTML = total;

//     table.rows[line].cells[line_total_id].innerHTML = (discount < 0) ? discount : "";
//
//     // Update totals
//     total = Math.round(100 * total) / 100;
//     due_vat = Math.round(100 * due_vat) / 100;
// //    round_total = Math.round(total);
// //    table.rows[table.rows.length - 4].cells[line_total_id].firstChild.nodeValue = Math.round((round_total-total) *100)/100;
//     // Due VAT
//     document.getElementById("del_due").innerHTML = due_vat;
//     // Vat 0
//     document.getElementById("del_va0").innerHTML = Math.round(100 *(total-due_vat)) / 100 ;
//     // VAT
//     document.getElementById("del_vat").innerHTML = Math.round(total_vat * 100) / 100;
//     // Total
//     document.getElementById("del_tot").innerHTML = total;
}

function delivery_delete(post_file)
{
    let order_id = get_value_by_name("order_id");

    execute_url(post_file + '?operation=delivery_delete&order_id=' + order_id);
}

function delivery_save_or_edit(post_file, operation) {
    // document.getElementById('btn_add').disabled = true;

    var table = document.getElementById('del_table');
    var total = get_value_by_name("total");
    // var total_vat = table.rows[table.rows.length - 2].cells[line_total_id].firstChild.nodeValue;
    // var logging = document.getElementById('logging');
    var is_edit = false;
    let order_id = get_value_by_name("order_id");
    let vat = 0; // Calculate in server

    let request = "order_id=" + order_id + "&total=" + total;

    let data = [[order_id, total, vat]];

    for (let line_number = 1; line_number < table.rows.length; line_number++)
    {
        let id = table.rows[line_number].cells[0].id;
        let order_line = id.substr(id.lastIndexOf("_") + 1);
        // 0
        let prod_name = encodeURI(get_value_by_name("product_name_" + order_line));
        // 1
        let q = get_value_by_name("quantity_" + order_line);
        // 2
        let price = get_value_by_name("price_"+ order_line);
        // 3
        let live_vat = 0;
        // 4
        let prod_id = 0;
        // 5
        let quantity_ordered = get_value_by_name("quantity_ordered_" + order_line);

        data.push([prod_name, q, price, live_vat, prod_id, quantity_ordered]);
        // request += "&oid=" + oid + "&q=" + q + "&price="+price;
    }
    // alert (JSON.stringify(data));
    execute_url_post(post_file + '?operation=' + operation, JSON.stringify(data), action_back);
}
// 	    <?php if ( $edit ) {  print "is_edit = true;"; } ?>
//
//         // Enter delivery note to db.
//         var request = "delivery-post.php?operation=add_header&order_id=" + order_id
//             + "&total=" + total
//             + "&vat=" + total_vat;
//
// 	    <?php if ( $edit ) {
// 	    print "request = request + \"&edit&delivery_id=" . $id . "\"";
//     } ?>
//
//         var delivery_id = 0;
//         var saved_lines = 0;
//         var fee = 0;
//         var i;
//
//         // Check number of lines in the delivery
//         fee = get_value(document.getElementById("del_del"));
//         for (i = 1; i < lines - 3; i++) {
//             var prfx = table.rows[i].cells[0].id.substr(4);
//             if (prfx === "")
//                 prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);
//
//             var quantity = get_value(document.getElementById("deq_" + prfx));
//             if (quantity > 0 || quantity < 0) saved_lines++;
//             var prod_name = get_value(document.getElementById("nam_" + prfx));
//             if (prod_name === "דמי משלוח"
//                 || prod_name === "משלוח") fee = get_value(document.getElementById("del_" + prfx))
//
//             // var product = get_value(table.rows[i].cells[product_name_id].firstChild);
//             // if (product == "דמי משלוח") fee = get_value(table.rows[i].cells[line_total_id].firstChild);
//         }
//         request = request + "&lines=" + saved_lines;
//         request = request + "&fee=" + fee;
//         if (draft) {
//             request += "&draft";
//             var reason = ""; // get_select_text("draft_reason");
//             // alert(reason);
//
//             request += "&reason=" + encodeURI(reason);
//         }
//
//         // Call the server to save the delivery
//         server_header = new XMLHttpRequest();
//         server_header.onreadystatechange = function () {
//             // Wait to get delivery id.
//             // 2) Save the lines.
//             if (server_header.readyState == 4 && server_header.status == 200)  // Request finished
//             {
//                 delivery_id = server_header.responseText.trim();
//                 logging.value += "תעודת משלוח מס " + delivery_id + "נשמרת " + "..";
//
//                 server_lines = new XMLHttpRequest();
//
//                 var line_request = "delivery-post.php?operation=add_lines&delivery_id=" + delivery_id;
//                 if (is_edit) line_request = line_request + "&edit";
//                 var line_args = new Array();
//
//                 // logging.value += response_text;
//                 // Enter delivery lines to db.
//                 for (i = 1; i < lines - 3; i++) {
//                     var prfx = table.rows[i].cells[0].id.substr(4);
//                     if (prfx === "")
//                         prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);
//                     var prod_id;
//                     var prod_name;
//                     let line_type = get_line_type(i);
//                     let part_of_basket = 0;
//
//                     //if (parseInt(prfx) > 0) { // Regular line
//                     prod_id = get_value(document.getElementById("pid_" + prfx));
//                     prod_name = get_value(document.getElementById("nam_" + prfx));
// //                    } else {
// //                        // Special line:
// //                        if (prfx === "dis") {
// //                            prod_id = 0;
// //                            prod_name = "הנחת כמות";
// //                        }
// //                    }
// //                     if (!prod_id && (prod_name.indexOf(")") > 0)) {
// //                         prod_id = prod_name.substr(0, prod_name.indexOf(")"));
// //                         prod_name = prod_name.substr(prod_name.indexOf(")"));
// //                     }
//                     if ((prod_id != -1) && !(prod_id > 0))
//                         prod_id = 0; // New or unknown
//
//                     if (prod_name.substr(0, 6) === "===&gt")
//                         part_of_basket = 1;
//                     else
//                         part_of_basket = 0;
//
//                     if (prod_name.length > 1) prod_name = prod_name.replace(/['"()%,]/g, "").substr(0, 40);
//
//                     var quantity = get_value(document.getElementById("deq_" + prfx));
//                     if (quantity === "") quantity = 0;
//
//                     var quantity_ordered = get_value(document.getElementById("orq_" + prfx));
//                     if (quantity_ordered === "") quantity_ordered = 0;
//
//                     var unit_ordered = get_value(document.getElementById("oru_" + prfx));
//                     if (unit_ordered.length < 1) unit_ordered = 0;
//
//                     var price = get_value(document.getElementById("prc_" + prfx));
//                     var vat = get_value(document.getElementById("lvt_" + prfx));
//                     // var prod_name = get_value(table.rows[i].cells[product_name_id].firstChild);
//                     var line_total = get_value(document.getElementById("del_" + prfx));
// //                    if (table.rows[i].cells[0].children.length === 1) { // delivery lines or new line
// //                        prod_id = 0;
// //                        prod_name = get_value(table.rows[i].cells[0]);
// //                        quantity = get_value(table.rows[i].cells[3]);
// //                        quantity_ordered = 0;
// //                        price = get_value(table.rows[i].cells[4]);
// //                        vat = get_value(table.rows[i].cells[6]);
// //                        line_total = get_value(table.rows[i].cells[7]);
// //                    } else {
// //                        line_total = get_value(table.rows[i].cells[line_total_id].firstChild);
// //                    }
//
//                     if (prod_id === -1 || prod_id > 0 || line_total > 0 || line_total < 0 || line_type === "bsk" || line_type === "dis") { // Line to be saved.
//                         if (prod_id > 0 || prod_id == -1) // -1 is basket discount.
//                             push(line_args, prod_id);
//                         else
//                             push(line_args, encodeURIComponent(prod_name));
//                         push(line_args, quantity);
//                         push(line_args, quantity_ordered);
//                         push(line_args, unit_ordered);
//                         push(line_args, vat);
//                         push(line_args, price);
//                         push(line_args, line_total);
//                         push(line_args, part_of_basket);
//                     }
//                 }
//                 server_lines = new XMLHttpRequest();
//                 server_lines.onreadystatechange = function () {
//                     if (server_lines.readyState === 4 && server_lines.status === 200) {  // Request finished
//                         logging.value += "הסתיים.\n";
//                         location.replace(document.referrer);
//                     }
//                 }
//
//                 line_request = line_request + "&lines=" + line_args.join();
//                 server_lines.open("GET", line_request, true);
//                 server_lines.send();
//             }
//         }
//
// //	1) Send the header.
//         server_header.open("GET", request, true);
//         server_header.send();
//     }

function quantity_changed()
{
    moveNextRow();
    calcDelivery();
}