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

    let id_col = 0;
    if (table.rows[1].cells[1].id.indexOf("product") !== -1) id_col = 1;

    for (let i = 1; i < lines; i++)  // Skip the header. Skip last lines: total, vat, total-vat, discount
    {
        // let line_type = get_line_type(i);
        if (! table.rows[i].cells.length) continue; // Skip empty line
        if (table.rows[i].hidden) continue;


        var line_total = 0;
        var vat_percent = 17; // Todo: read from settings
        var has_vat = true;
        let id = table.rows[i].cells[id_col].id;
        id = id.substr(id.lastIndexOf("_") + 1);
        var line_vat = document.getElementById("vat_" + id);
        var order_line = id;
        // if (order_line === "")
        //     order_line = table.rows[i].cells[id].firstElementChild.id.substr(4);

        let p = get_value(document.getElementById("price_" + order_line));
        let q = get_value(document.getElementById("quantity_" + order_line));
        line_total = Math.round(p * q * 100) / 100;
        basket_sum += line_total;
        document.getElementById("line_price_" + order_line).innerHTML = line_total.toString();

        if (line_vat) {
            line_vat.innerHTML = Math.round(100 * line_total / (100 + vat_percent)* vat_percent) / 100;
            due_vat += line_total;
        }
        total_vat += line_vat;
        total = Math.round(100*(total + line_total)) / 100;
    }

    // total += parseFloat(get_value_by_name("fee"));

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

function delivery_send_mail(post_file, del_id)
{
    let url = post_file + '?operation=delivery_send_mail&id=' + del_id;
    execute_url(url, success_message);
}

function delivery_delete(post_file)
{
    let order_id = get_value_by_name("order_id");

    execute_url(post_file + '?operation=delivery_delete&order_id=' + order_id, action_back);
}

function delivery_save_or_edit(post_file, operation) {
    calcDelivery();
    document.getElementById('btn_do').disabled = true;

    var table = document.getElementById('del_table');
    var total = get_value_by_name("total");
    // var total_vat = table.rows[table.rows.length - 2].cells[line_total_id].firstChild.nodeValue;
    // var logging = document.getElementById('logging');
    var is_edit = false;
    let order_id = get_value_by_name("order_id");
    let send_email = get_value_by_name("chk_send_email");
    let data = [];
    if (! (order_id > 0)) {
        alert("Error - order id is missing");
        return false;
    }
    let vat = 0; // Calculate in server
    let fee = 0;

    let request = post_file + '?operation=' + operation + "&order_id=" + order_id + "&total=" + total + '&send_email=' + send_email;

    let id_col = 1;
    if (operation == "delivery_save") id_col = 0; // in save there is no checkbox.

    for (let line_number = 1; line_number < table.rows.length; line_number++)
    {
        if (table.rows[line_number].hidden) continue;
        let id = table.rows[line_number].cells[id_col].id;
        let order_line = id.substr(id.lastIndexOf("_") + 1);

        let prod_id = get_value_by_name("prod_id_" + order_line);
        let prod_name;
        if (null == prod_id) { // New line
            prod_name = encodeURI(document.getElementById("product_name_" + order_line).firstElementChild.value);
            prod_id = get_value_by_name("product_name_" + order_line);
        } else {
            prod_name = encodeURI(get_value_by_name("product_name_" + order_line));
        }

        // 1
        let q = get_value_by_name("quantity_" + order_line);
        // 2
        let price = get_value_by_name("price_"+ order_line);
        // 3
        let line_vat = Math.round((q * price * 100) / 1.17 * 0.17) / 100;
        let has_vat = get_value_by_name("has_vat_" + order_line);
        if (! has_vat) line_vat = 0;

        // 5
        let quantity_ordered = get_value_by_name("quantity_ordered_" + order_line);

        data.push([prod_name, q, price, line_vat, prod_id, quantity_ordered, has_vat]);
        // request += "&oid=" + oid + "&q=" + q + "&price="+price;

        if (prod_name.indexOf(encodeURI("משלוח")) !== -1) fee = fee + q * price;
    }
    data.unshift([order_id, total, vat, fee]);

    // alert (JSON.stringify(data));
    execute_url_post(request, JSON.stringify(data), action_reload);
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

function delivery_add_line(post_file, user_id, add_vat, has_checkbox)
{
    let  table = document.getElementById("del_table");
    let id_col = (has_checkbox ? 1 : 0);
    let id = table.rows[table.rows.length - 1].cells[id_col].id;
    let row_number = id.substr(id.lastIndexOf("_") + 1);
    row_number ++;
    if (! (row_number > 0)) row_number = 1;

    let new_row = table.insertRow();
    if (has_checkbox) new_row.insertCell(); // checkbox
    let new_cell = new_row.insertCell();
    new_cell.innerHTML = '<input type="text" id="product_name_' + row_number + '" list="products" onkeyup="update_list(\'' + post_file + '\', \'products\', this)" onchange="get_price(\'' + post_file + '\', ' + row_number + ', ' + user_id + ')"></input>';
    new_cell.id = "product_name_" + row_number;
    new_row.insertCell().innerHTML = '<label id="quantity_ordered_' + row_number + '">0</label>'; // ordered quantity
    new_row.insertCell().innerHTML = '<input type="text" id="quantity_' + row_number + '" size="4" onchange="quantity_changed()"></input>'; // supplied q
    new_row.insertCell().innerHTML = '<input type="text" id="price_' + row_number + '" size="4"></input>'; // price
    new_row.insertCell().innerHTML = '<lable id="line_price_' + row_number + '" size="4"></lable>'; // line_price
    if (add_vat)
        new_row.insertCell().innerHTML = '<input type="checkbox" id="has_vat_' + row_number + '" ></input>'; // vat checkbox

}

function quantity_changed()
{
    moveNextRow();
    calcDelivery();
}

function get_price(post_file, row_number, user_id)
{
    let prod_id = get_value_by_name("product_name_"+row_number);
    if (! (prod_id > 0)) return;
    let obj = document.getElementById('product_name_'+row_number);
    execute_url(post_file + '?operation=delivery_get_price&prod_id='+prod_id + '&user_id=' + user_id, delivery_update_price, obj);
}

function delivery_update_price(xmlhttp, obj)
{
    let my_row = obj.id.substr(obj.id.lastIndexOf("_") + 1);
    if (xmlhttp.response.indexOf(",")) { // price, vat
        var response = xmlhttp.response.split(",");
        var price = response[0];
        if (!(price > 0)) {
            alert(response);
            return false;
        }
        var vat = response[1] > 0;

        if (price > 0) {
            document.getElementById("price_" + my_row).value = price;
            document.getElementById("quantity_" + my_row).focus();
        }

        document.getElementById("has_vat_" + my_row).checked = vat;
    }
    // Just price
    document.getElementById("price_" + my_row).value = price;
}

function delivery_done(post)
{
    let url = post + '?operation=delivered&id=' + get_selected("deliveries");
    execute_url(url, location_reload);
}
