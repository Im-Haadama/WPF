function leaveQuantityFocus(my_row)
{
    let current = document.getElementsByName("quantity" + (my_row));
    current[0].value = Math.round(current[0].value * 10) / 10;
    calcDelivery();
}

function moveNextRow(my_row) {
    if (event.which === 13) {
        var i;
        for (i = my_row + 1; i < document.getElementById("del_table").rows.length; i++) {
            var next = document.getElementById("quantity" + i);
            if (next) {
                next.focus();
                return;
            }
        }

        var del = document.getElementById("delivery");
        if (del) del.focus();
    }
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
        let line_type = get_line_type(i);
        if (! table.rows[i].cells.length) continue; // Skip empty line

        switch (line_type)
        {
            case "bsk": // Start of basket;
                basket_sum = 0;
                basket_price = Number(get_value(document.getElementById("orq_" + i))) * Number(get_value(document.getElementById("prc_" + i)));
                break;
            case "dis":  // End of basket.
                let diff = Math.round(100 * (basket_price - basket_sum)) / 100;
                if (diff < 0) {
                    table.rows[i].cells[q_supply_id].innerHTML = 1;
                    table.rows[i].cells[line_total_id].innerHTML = diff;
                    table.rows[i].cells[price_id].innerHTML = diff;
                    table.rows[i].cells[line_vat_id].innerHTML = 0;
                    table.rows[i].hidden = false;
                } else {
                    table.rows[i].hidden = true;
                    table.rows[i].cells[price_id].innerHTML = 0;
                }
                // total += diff; Not needed. Will consdired by line_total
                break;
            default:
        }
        if (table.rows[i].cells[product_name_id].innerHTML === "סה\"כ חייבי מע\"מ") break;
        if (table.rows[i].cells[product_name_id].innerHTML === "" ||
            table.rows[i].cells[product_name_id].innerHTML === "הנחת כמות" ||
            table.rows[i].cells[product_name_id].innerHTML === "הנחת עובד") continue;  // Reserved line for discount

        if (table.rows[i].cells[line_select_id].innerHTML === "basket") {
            in_basket = true;
            continue;
        }

        var q = 0;
        var p = 0;
        var line_total = 0;
        var vat_percent = 17; // Todo: read from settings
        var line_vat = 0;
        var has_vat = true;
        var prfx = table.rows[i].cells[0].id.substr(4);
        if (prfx === "")
            prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);

        p = get_value(document.getElementById("prc_" + prfx));
        if (prfx === "del") {
            q = 1;
        } else {
            q = get_value(document.getElementById("deq_" + prfx));
            if (eval(q) !== q) {
                if (eval(q) > 0) {
                    q = eval(q);
                    document.getElementById("deq_" + prfx).value = q;
                }
            }
        }
        has_vat = get_value_by_name("hvt_" + prfx);
        if (has_vat) line_vat = Math.round(100 * p * q / (100 + vat_percent) * vat_percent) / 100;
        line_total = Math.round(p * q * 100) / 100;
        basket_sum += line_total;
        document.getElementById("del_" + prfx).innerHTML = line_total.toString();
        document.getElementById("lvt_" + prfx).innerHTML = line_vat.toString();

        if (line_vat) due_vat += line_total;
        total_vat += line_vat;
        total += line_total;
    }

    var employee_discount = false;
    <!--	    --><?php
//	    $customer_id = $O->getCustomerId();
//	    $wp_user = get_user_by( 'id', $customer_id );
//	    $roles = $wp_user->roles;
//	    if ( $roles and customer_type( $customer_id ) == 0 // Not owner or siton
//	         and count( array_intersect( array( "staff" ), $roles ) )
//	    ) {
//		    print "employee_discount = true;";
//	    }
//	    ?>
    // Show discount line or hide
    var line = table.rows.length - 4;
    var discount = 0;
    if (employee_discount) {
        var discount_gross = Math.round(total, 0); /// todo: get delivery_fee
        discount = -Math.round(discount_gross * 10) / 100;
        table.rows[line].cells[product_name_id].innerHTML = (discount_gross > 0) ? "הנחת עובד" : "";
        table.rows[line].cells[q_supply_id].innerHTML = (discount_gross > 0) ? -0.1 : "";
        table.rows[line].cells[price_id].innerHTML = discount_gross;
        table.rows[line].cells[line_vat_id].innerHTML = 0; // For now just for fresh. No VAT. (quantity_discount > 0) ? quantity_discount : "";

    } else {
        quantity_discount = Math.round(quantity_discount);
        table.rows[line].cells[product_name_id].innerHTML = (quantity_discount > 0) ? "הנחת כמות" : "";
        table.rows[line].cells[q_supply_id].innerHTML = (quantity_discount > 0) ? -0.15 : "";
        table.rows[line].cells[price_id].innerHTML = (quantity_discount > 0) ? quantity_discount : "";
        table.rows[line].cells[line_vat_id].innerHTML = 0; // For now just for fresh. No VAT. (quantity_discount > 0) ? quantity_discount : "";
        discount = -Math.round(quantity_discount * 15) / 100;
    }
    total = total + discount;
    table.rows[line].cells[line_total_id].innerHTML = (discount < 0) ? discount : "";

    // Update totals
    total = Math.round(100 * total) / 100;
    due_vat = Math.round(100 * due_vat) / 100;
//    round_total = Math.round(total);
//    table.rows[table.rows.length - 4].cells[line_total_id].firstChild.nodeValue = Math.round((round_total-total) *100)/100;
    // Due VAT
    document.getElementById("del_due").innerHTML = due_vat;
    // VAT
    document.getElementById("del_vat").innerHTML = Math.round(total_vat * 100) / 100;
    // Total
    document.getElementById("del_tot").innerHTML = total;
}

function deleteDelivery(id) {
    var request = "/wp-content/plugins/fresh.php?operation=delivery-delete&delivery_id=" + id;

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            if (window.history)
                window.history.back();
            else {
                alert("תעודה נמחקה. יש לסגור את החלון");
            }
        }
    }
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}
