/**
 * Created by agla on 26/02/19.
 */

finance_post_file = "/wp-content/plugins/finance/post.php";

function selected_client_index() {
    let client = document.getElementById("open_account");
    return get_value(client);
}

function selected_supplier_index() {
    var client = document.getElementById("supplier");
    return get_value(client);
}

function selected_supplier_id() {
    var item_id = selected_supplier_index();
    var list = document.getElementById("open_supplier");

    return list.options[item_id].getAttribute("data-supplier_id");
}

function selected_supplier_site_id() {
    var item_id = selected_supplier_index();
    var list = document.getElementById("open_supplier");

    return list.options[item_id].getAttribute("data-site_id");
}

function selected_client_id() {
    let item_id = selected_client_index();
    let list = document.getElementById("open_account").list;

    if (list)
        return list.options[item_id].getAttribute("data-client_id");
    return 0;
}

function selected_client_site_id() {
    var item_id = selected_client_index();
    var list = document.getElementById("open_account").list;

    if (list)
        return list.options[item_id].getAttribute("data-site_id");

    return 0;
}

function selected_bank_id() {
    return get_value_by_name("bank_id");
}

function client_selected() {
    let item_id = selected_client_index();

    if (item_id !== 0 && !(item_id > 0)) {
        document.getElementById("transactions").innerHTML = "";
        return;
    }
    let site_id = selected_client_site_id();
    let client_id = selected_client_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            document.getElementById("transactions").innerHTML = xmlhttp.response;
        }
    }
    let request = finance_post_file + "?operation=get_open_trans&client_id=" + client_id +
        "&site_id=" + site_id;
    // alert (request);
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function supplier_selected()
{
    var item_id = selected_supplier_index();

    if (item_id !== 0 && !(item_id > 0)) {
        document.getElementById("transactions").innerHTML = "";
        return;
    }
    var site_id = selected_supplier_site_id();
    var supplier_id = selected_supplier_id();

    var request = finance_post_file + "?operation=get_open_invoices&supplier_id=" + supplier_id +
        "&site_id=" + site_id;

    execute_url(request, update_transactions);
}

function update_transactions(xmlhttp, btn)
{
    if (xmlhttp.response.substr(0, 4) == "done")
        document.getElementById("transactions").innerHTML = xmlhttp.response.substr(5);
    else
        alert(xmlhttp.response);
}

function create_receipt_from_bank() {
    disable_btn('btn_receipt');

    var del_ids = account_get_row_ids();
    var site_id = selected_client_site_id();
    var client_id = selected_client_id();
    var bank_id = selected_bank_id();

    var bank_amount = parseFloat(get_value(document.getElementById("bank")));
    if (isNaN(bank_amount)) bank_amount = 0;
    var date = get_value(document.getElementById("pay_date"));
    var request = finance_post_file + "?operation=bank_create_receipt" +
        "&bank=" + bank_amount +
        "&date=" + date +
        "&ids=" + del_ids.join() +
        "&site_id=" + site_id +
        "&user_id=" + client_id +
        "&bank_id=" + bank_id;

    let change = get_value_by_name("change");
    if (change)
        request += "&change=" + change;

    execute_url(request, action_back);
}

function link_invoice_bank() {
    disable_btn('btn_receipt');

    var invoice_ids = account_get_row_ids();
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
    if (!(bank > 0)) {
        alert("שגיאה בסכום");
        return;
    }
    var request = finance_post_file + "?operation=bank_link_invoice" +
        "&ids=" + invoice_ids.join() +
        "&site_id=" + site_id +
        "&supplier_id=" + supplier_id +
        "&bank_id=" + bank_id +
        "&bank=" + bank;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function mark_refund_bank() {
    disable_btn('btn_refund');

    let invoice_ids = account_get_row_ids();
    let site_id = selected_supplier_site_id();
    let supplier_id = selected_supplier_id();
    let bank_id = selected_bank_id();

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
    if (!(bank > 0)) {
        alert("שגיאה בסכום");
        return;
    }
    var request = finance_post_file + "?operation=mark_refund_bank" +
        "&ids=" + invoice_ids.join() +
        "&site_id=" + site_id +
        "&supplier_id=" + supplier_id +
        "&bank_id=" + bank_id +
        "&bank=" + bank;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function update_display() {
    let collection = document.getElementsByClassName("trans_checkbox");
    let t = 0;
    let table = document.getElementById("table_invoices");

    for (let i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            let row_id = collection[i].id.substr(4);
            let amount = parseFloat(get_value_by_name("amount_" + row_id));
            t += amount;
        }
    }

    document.getElementById("total").innerHTML = t.toString();
}

function invoice_exists()
{
    let invoice = get_value_by_name("invoice_id");
    let bank_id = get_value_by_name("bank_id");
    execute_url(finance_post_file + "?operation=exists_invoice&bank_id=" + bank_id + "&invoice=" + invoice, action_back);
}