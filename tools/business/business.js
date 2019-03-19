/**
 * Created by agla on 26/02/19.
 */


function selected_client_index() {
    var client = document.getElementById("client");
    var i = get_value(client);
    return i.substr(0, i.indexOf(")"));
}

function selected_supplier_index() {
    var client = document.getElementById("supplier");
    var i = get_value(client);
    return i.substr(0, i.indexOf(")"));
}

function selected_supplier_id() {
    var item_id = selected_supplier_index();
    var list = document.getElementById("supplier_items");

    return list.options[item_id].getAttribute("data-supplier_id");
}

function selected_supplier_site_id() {
    var item_id = selected_supplier_index();
    var list = document.getElementById("supplier_items");

    return list.options[item_id].getAttribute("data-site_id");
}

function selected_client_id() {
    var item_id = selected_client_index();
    var list = document.getElementById("client_items");

    return list.options[item_id].getAttribute("data-client_id");
}

function selected_client_site_id() {
    var item_id = selected_client_index();
    var list = document.getElementById("client_items");

    return list.options[item_id].getAttribute("data-site_id");
}


function selected_bank_id() {
    return get_value_by_name("bank_id");
}

function client_selected() {
    var item_id = selected_client_index();

    if (item_id !== 0 && !(item_id > 0)) {
        document.getElementById("transactions").innerHTML = "";
        return;
    }
    var site_id = selected_client_site_id();
    var client_id = selected_client_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            document.getElementById("transactions").innerHTML = xmlhttp.response;
        }
    }
    var request = "business-post.php?operation=get_trans&client_id=" + client_id +
        "&site_id=" + site_id;
    // alert (request);
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function supplier_selected() {
    var item_id = selected_supplier_index();

    if (item_id !== 0 && !(item_id > 0)) {
        document.getElementById("transactions").innerHTML = "";
        return;
    }
    var site_id = selected_supplier_site_id();
    var supplier_id = selected_supplier_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            document.getElementById("transactions").innerHTML = xmlhttp.response;
        }
    }
    var request = "business-post.php?operation=get_open_invoices&supplier_id=" + supplier_id +
        "&site_id=" + site_id;
    // alert (request);
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function create_receipt_from_bank() {
    disable_btn('btn_receipt');

    var del_ids = account_get_del_ids();
    var site_id = selected_client_site_id();
    var client_id = selected_client_id();
    var bank_id = selected_bank_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
        {
            logging.innerHTML = xmlhttp.response;
            // receipt_id = xmlhttp.responseText.trim();
            // logging.innerHTML += "חשבונית מספר " + receipt_id;
            // updateDisplay();
        }
    }
    var bank = parseFloat(get_value(document.getElementById("bank")));
    if (isNaN(bank)) bank = 0;
    var date = get_value(document.getElementById("pay_date"));
    var request = "business-post.php?operation=create_receipt" +
        "&bank=" + bank +
        "&date=" + date +
        "&change=" + change.innerHTML +
        "&ids=" + del_ids.join() +
        "&site_id=" + site_id +
        "&user_id=" + client_id +
        "&bank_id=" + bank_id;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function link_invoice_bank() {
    disable_btn('btn_receipt');

    var invoice_ids = account_get_del_ids();
    var site_id = selected_supplier_site_id();
    var supplier_id = selected_supplier_id();
    var bank_id = selected_bank_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
        {
            add_message(xmlhttp.response);
            // receipt_id = xmlhttp.responseText.trim();
            // logging.innerHTML += "חשבונית מספר " + receipt_id;
            // updateDisplay();
        }
    }
    var bank = parseFloat(get_value(document.getElementById("bank")));
    if (isNaN(bank)) bank = 0;
    var date = get_value(document.getElementById("pay_date"));
    var request = "business-post.php?operation=link_invoice" +
        "&ids=" + invoice_ids.join() +
        "&site_id=" + site_id +
        "&supplier_id=" + supplier_id +
        "&bank_id=" + bank_id;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}
