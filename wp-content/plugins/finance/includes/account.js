/**
 * Created by agla on 26/02/19.
 */

function update_sum() {
    var collection = document.getElementsByClassName("trans_checkbox");
    var total = 0;
    if (isNaN(total)) total = 0;
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
            total += parseFloat(get_value_by_name("transaction_amount_" + collection[i].id.substring(4)));
        }
    }
    total = Math.round(100 * total) / 100;
    // alert(total);
    if (total === 0) {
        logging.innerHTML = "יש לבחור משלוחים להפקת המסמך";

        disable_btn('btn_invoice');
        document.getElementById('btn_receipt').disabled = true;
        //document.getElementById('btn_refund').disabled = true;
    } else {
        logging.innerHTML = "סכום השורות שנבחר " + total + "<br/>";
        logging.innerHTML += " סך תקבולים " + (credit + bank + cash + check) + "<br/>";
        var total_pay = (credit + bank + cash + check);
        var cash_delta = Math.round(100 * (total_pay - total)) / 100;
        var change = document.getElementById("change");

        if (change)
            change.innerHTML = cash_delta;

        if ((total_pay > 0) && Math.abs(cash_delta) <= 1400) {
            disable_btn('btn_invoice');
            enable_btn('btn_receipt');
        } else {
            enable_btn('btn_invoice');
            disable_btn('btn_receipt');
        }
        // document.getElementById('btn_refund').disabled = (delivery_count != 1);
    }
}

function account_get_row_ids() {
    var collection = document.getElementsByClassName("trans_checkbox");
    var row_ids = new Array();

    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            var row_id = collection[i].id.substring(4); // table.rows[i + 1].cells[6].firstChild.innerHTML;
            row_ids.push(row_id);
        }
    }

    return row_ids;
}

function create_receipt(post_file, customer_id) {
    disable_btn('btn_receipt');

    let row_ids = account_get_row_ids();

    let credit = parseFloat(get_value(document.getElementById("credit")));
    if (isNaN(credit)) credit = 0;
    let bank = parseFloat(get_value(document.getElementById("bank")));
    if (isNaN(bank)) bank = 0;
    let cash = parseFloat(get_value(document.getElementById("cash")));
    if (isNaN(cash)) cash = 0;
    let check = parseFloat(get_value(document.getElementById("check")));
    if (isNaN(check)) check = 0;
    let date = get_value(document.getElementById("pay_date"));
    let request = post_file + "?operation=create_receipt" +
    "&cash=" + cash +
    "&credit=" + credit +
    "&bank=" + bank +
    "&check=" + check +
    "&date=" + date +
    "&change=" + change.innerHTML +
    "&row_ids=" + row_ids.join() +
    "&user_id=" + customer_id;

    execute_url(request, location_reload);
}

function pay_credit(post_file)
{
    disable_btn("btn_pay");
    let users = get_selected("user_chk");

    let request = post_file + '?operation=pay_credit&users='+users;
    execute_url(request, location_reload);
}

function pay_credit_client(post_file, user)
{
    disable_btn("btn_pay");

    let request = post_file + '?operation=pay_credit&users='+user;

    let number = get_value_by_name("payment_number");
    if (number > 0) request += '&number=' + number;

    execute_url(request, success_message());
}


function save_payment_method(post_file, customer_id) {
    var method = get_value(document.getElementById("payment"));
   let request = post_file + '?operation=update_payment_method&user_id=' + customer_id + '&method_id=' + method;
   execute_url(request, fail_message);
}
