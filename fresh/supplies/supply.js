/**
 * Created by agla on 01/02/19.
 */

function mission_changed(supply_id) {
    let mis = document.getElementById("mis_" + supply_id);
    let mission_id = get_value(mis);
    execute_url("supplies-post.php?operation=set_mission&supply_id=" + get_supply_id() + "&mission_id=" + mission_id);
}

function add_item() {
    let supply_id = get_supply_id();
    let request_url = "supplies-post.php?operation=add_item&supply_id=" + supply_id;
    let prod_id = get_value_by_name("itm_");
    request_url = request_url + "&prod_id=" + prod_id;
    let _q = 1; // encodeURI(get_value(document . getElementById("qua_")));
    request_url = request_url + "&quantity=" + _q;

    execute_url(request_url, location_reload);
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

function update_comment() {
    var text = get_value(document.getElementById("comment"));

    execute_url("supplies-post.php?operation=save_comment&text=" + encodeURI(text)
        + "&id=" + get_supply_id(), location_reload);
}

function updateItems() {
    let supply_id = get_supply_id();
    let collection = document.getElementsByClassName("supply_checkbox");
    let params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            // var name = get_value(table.rows[i+1].cells[0].firstChild);
            var line_id = collection[i].id.substr(4);

            params.push(line_id);
            params.push(get_value_by_name("quantity_" +line_id));
            params.push(get_value_by_name("$buy_" +line_id));
        }
    }
    let request = "supplies-post.php?operation=update_lines&supply_id=" + supply_id + "&params=" + params;
    execute_url(request, location_reload);
}

function del_line(supply_line_id) {
    var btn = document.getElementById("del_" + supply_line_id);
    btn.parentElement.parentElement.style.display = 'none';
    execute_url("supplies-post.php?operation=delete_lines&params=" + supply_line_id);
}

function deleteItems() {
    var table = document.getElementById('del_table');

    var collection = document.getElementsByClassName("supply_checkbox");
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            // var name = get_value(table.rows[i+1].cells[0].firstChild);
            var line_id = collection[i].id.substr(4);

            params.push(line_id);
        }
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            update_display();
        }
    }
    var request = "supplies-post.php?operation=delete_lines&params=" + params;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function closeItems(collection_name)
{
    let request = "/fresh/supplies/supplies-post.php?operation=supplied&ids=" + get_selected(collection_name);
    execute_url(request, location_reload);
}
function get_supply_id()
{
    return get_value_by_name("supply_number");
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

    var request_url = "supplies-post.php?operation=got_supply&supply_id=<?php print $id; ?>" +
        "&supply_total=" + supply_total + "&supply_number=" + supply_number +
        "&net_amount=" + net_amount +
        "&is_invoice=" + is_invoice;

    if (date)
        request_url = request_url + "&document_date=" + date;

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
        if (request.readyState === 4 && request.status === 200) {
            if (request.response.indexOf("fail") !== -1) {
                add_message("הפעולה נכשלה" + request.response);
                enable_btn("btn_got_supply");
                return;
            }
            // window.location = window.location;
            update_display();
        }
    }

    request.open("GET", request_url, true);
    request.send();
    // alert (request_url);
}

function new_supply_change()
{
    let supplier_id = get_value_by_name("supplier_select");
    let upcsv = document.getElementById("upcsv");
    let date = get_value_by_name("date");
    upcsv.action = "/fresh/supplies/supplies-post.php?operation=create_from_file&supplier_id=" + supplier_id + "&date=" + date;
}