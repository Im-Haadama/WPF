/**
 * Created by agla on 26/02/19.
 */

function update_sum() {
    var collection = document.getElementsByClassName("trans_checkbox");
    var total = 0;
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

