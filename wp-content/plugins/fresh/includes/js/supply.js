/**
 * Created by agla on 01/02/19.
 */

function mission_changed(post_file, supply_id) {
    let mis = document.getElementById("mis_" + supply_id);
    let mission_id = get_value(mis);
    execute_url(post_file + "?operation=set_mission&supply_id=" + supply_id + "&mission_id=" + mission_id);
}

function save_mission() {
    var mission = get_value(document.getElementById("mission_select"));
    var request = "supplies-post.php?operation=set_mission&mission_id=" + mission + "&supply_id= " + get_supply_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            location.reload();
        }
    }
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function supply_save_comment(post_file, supply_id) {
    let text = get_value(document.getElementById("comment"));

    execute_url(post_file + "?operation=supply_save_comment&text=" + encodeURI(text)
        + "&id=" + supply_id, location_reload);
}

function supply_update_items(post_file, supply_id) {
    let collection = document.getElementsByClassName("supply_checkbox");
    let params = new Array();
    for (let i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            // var name = get_value(table.rows[i+1].cells[0].firstChild);
            var line_id = collection[i].id.substr(4);

            params.push(line_id);
            params.push(get_value_by_name("quantity_" +line_id));
            params.push(get_value_by_name("$buy_" +line_id));
        }
    }
    let request = post_file + "?operation=supply_update_items&supply_id=" + supply_id + "&params=" + params;
    execute_url(request, location_reload);
}

function del_line(supply_line_id) {
    var btn = document.getElementById("del_" + supply_line_id);
    btn.parentElement.parentElement.style.display = 'none';
    execute_url("supplies-post.php?operation=delete_lines&params=" + supply_line_id);
}

function supply_delete_items(post_file, supply_id)
{
    if (! (supply_id > 0)) { alert ("bad supply_id"); return }
    let params = get_selected("supply_checkbox");

    let request_url = post_file + "?operation=supply_delete_items&supply_id=" + supply_id + '&params=' + params;
    execute_url(request_url, location_reload);
}

function closeItems(collection_name)
{
    let request = "/fresh/supplies/supplies-post.php?operation=supplied&ids=" + get_selected(collection_name);
    execute_url(request, location_reload);
}
function get_supply_id()
{
    return get_value_by_name("supply_id");
}

function send_supplies()
{
    let ids = get_selected("new");
    if (! ids.length) {
        alert ("no supply selected");
        return;
    }
    do_send_supplies(ids);
}
function do_send_supplies(ids) {
    let request = "supplies-post.php?operation=send&id=" + ids;

    // execute_url(url, finish_action, obj)

    execute_url(request, update_message);
}

// finish_action(xmlhttp3, obj);
function update_message(xmlhttp, obj)
{
    document.getElementById("results").innerText = xmlhttp.response;
}

function supply_pay(id) {
    var date = get_value_by_name("pay_date");

    var request_url = "supplies-post.php?operation=supply_pay&date=" + date +
    "&id=" + id;

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
        if (request.readyState === 4 && request.status === 200) {
            // window.location = window.location;
            update_display();
        }
    }

    request.open("GET", request_url, true);
    request.send();
}

function printDeliveryNotes() {
    document.getElementById('head_print').innerHTML = get_value(document.getElementById("comment"));
    var elements = ["buttons", "supply_arrived", "add_items", "head_edit"];
    elements.forEach(function (element) {
        document.getElementById(element).style.display = "none";
    });
    document.getElementById("head_print").style.display = "block";

    window.print();

    document.getElementById("head_print").style.display = "none";
    elements.forEach(function (element) {
        document.getElementById(element).style.display = "block";
    });
}

function got_supply() {
    disable_btn("btn_got_supply");
    var supply_number = get_value(document.getElementById("supply_number"));
    var supply_total = get_value(document.getElementById("supply_total"));
    var net_amount = get_value(document.getElementById("net_amount"));
    var is_invoice = get_value(document.getElementById("is_invoice"));
    var date = get_value_by_name("document_date");
    let supply_id = get_value_by_name("supply_id");

    if (!supply_number) {
        alert("יש לרשום את מספר תעודת המשלוח");
        enable_btn("btn_got_supply");
        return;
    }

    if (!supply_total) {
        alert("יש לרשום סכום תעודת המשלוח");
        enable_btn("btn_got_supply");
        return;
    }

    if (!net_amount) {
        alert("יש לרשום סכום תעודת המשלוח ללא מע\"מ");
        enable_btn("btn_got_supply");
        return;
    }

    var request_url = "supplies-post.php?operation=got_supply" +
        "&supply_id=" + supply_id +
        "&supply_total=" + supply_total + "&supply_number=" + supply_number +
        "&net_amount=" + net_amount +
        "&is_invoice=" + is_invoice;

    if (date)
        request_url = request_url + "&document_date=" + date;

    execute_url(request_url, location_reload);
}

function new_supply_change(post_file)
{
    let supplier_id = get_value_by_name("supplier_select");
    let upcsv = document.getElementById("upcsv");
    let date = get_value_by_name("date");
    upcsv.action = add_param_to_url(add_param_to_url(add_param_to_url(post_file, "operation", "create_supply_from_file"), "supplier_id", supplier_id), "date", date);
}

function supply_add_item(post_file, supply_id) {
    if (! (supply_id > 0)) { alert ("bad supply_id"); return }
    let request_url = post_file + "?operation=supply_add_item&supply_id=" + supply_id;
    let prod_id = get_value_by_name("itm_");
    if (! (prod_id > 0)) { alert ("bad product"); return }
    request_url = request_url + "&prod_id=" + prod_id;
    let _q = 1; // encodeURI(get_value(document . getElementById("qua_")));
    request_url = request_url + "&quantity=" + _q;
    execute_url(request_url, location_reload, this);
}

function supply_add(post_file)
{
    disable_btn('btn_add_item');

    let supplier_id = get_value_by_name("supplier_select");
//            var supplier_id = supplier_name.substr(0, supplier_name.indexOf(")"));
    if (!(supplier_id > 0)) {
        alert("יש לבחור ספק מהרשימה");
        enable_btn('btn_add_item');
        return;
    }
    let ids = [];

    let item_table = document.getElementById("supply_items");
    let line_number = 0;

    for (let i = 1; i < item_table.rows.length; i++) {
        let prod_id = get_value_by_name("itm_" + i);
        let q = get_value_by_name("qua_" + i);
        let u = get_value_by_name("uni_" + i);
        if (!u > 0) u = 0;
//                $prod_id  = $ids[ $pos ];
//                $quantity = $ids[ $pos + 1 ];
//                $units    = $ids[ $pos + 2 ];
        if (q > 0) {
            ids.push(prod_id);
            ids.push(q);
            ids.push(u);

            line_number++;
        }
        // ids.push(get_value(item_table.rows[i].cells[0].innerHTML));
    }
    if (line_number === 0) {
        alert("יש לבחור מוצרים, כולל כמויות");
        enable_btn('btn_add_item');

        return;
    }

    let date = get_value(document.getElementById("date"));

    let request = post_file + "?operation=create_supply" +
        "&supplier_id=" + supplier_id +
        "&params=" + ids.join() +
        "&date=" + date;

    reset_message();
    execute_url(request, action_back);
    // xmlhttp = new XMLHttpRequest();
    // xmlhttp.onreadystatechange = function () {
    //     // Wait to get delivery id.
    //     if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
    //         add_message(xmlhttp.responseText);
    //         let id = xmlhttp.responseText.match(/\d+/)[0];
    //         disable_btn("btn_add_item");
    //         if (id > 0) {
    //             window.location.href = "/fresh/supplies/supplies-page.php?operation=get&id=" + id;
    //             // location.reload();
    //         }
    //     }
    // }
    // xmlhttp.open("GET", request, true);
    // xmlhttp.send();
}

function supply_new_add_line(post_file)
{
    let item_table = document.getElementById("supply_items");
    let line_idx = item_table.rows.length;
    let new_row = item_table.insertRow(-1);
    let product = new_row.insertCell(0);
    // product.innerHTML = "<input id=\"itm_" + line_idx + "\" list=\"items\" \">";
    let list_name = "product_list_" + item_table.rows.length;
    product.innerHTML = "<input id=\"itm_" + line_idx + "\" list=\"" + list_name + "\" onkeyup=\"update_list('" +post_file + "', 'products', this)\"><datalist id=\"" + list_name + "\"></datalist>";

    let quantity = new_row.insertCell(1);
    quantity.innerHTML = "<input id = \"qua_" + line_idx + "\" >"; // onkeypress=\"select_unit(" + line_idx + ")\"
//    let units = new_row.insertCell(2);
//    units.innerHTML = "<input id=\"uni_" + line_idx + "\" list=\"units\", onkeypress=\"add_line(" + line_idx + ")\">";
    product.firstElementChild.focus();
}

function supply_delete(post_file, status) {
    let params = get_selected(status);
    if (! params.length) {
        alert ("select supplies for delete");
        return
    }
    let request = post_file + "?operation=delete_supplies&params=" + params;
    execute_url(request, location_reload);
}
