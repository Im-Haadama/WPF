function create_subcontract_delivery_invoice(post_file) {
    document.getElementById('id_legacy_invoice').disabled = true;
    let selected = get_selected("delivery_note");

    if (! selected.length) {
        alert("יש לבחור תעודות משלוח");
        return;
    }

    var request = post_file + "?operation=create_subcontract_delivery_invoice&ids=" + selected;

    execute_url(request, location_reload);
}

function clear_legacy() {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            var http_text = xmlhttp.responseText.trim();
            document.getElementById("logging").innerHTML = http_text;
        }
    }
    var request = "delivery-post.php?operation=clear_legacy";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function create_ship(post_file) {
    document.getElementById('btn_create_ship').disabled = true;
    var collection = document.getElementsByClassName("deliveries");
    var del_ids = [];
    var count = 0;

    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            var del_id = collection[i].id.substring(3); // table.rows[i + 1].cells[6].firstChild.innerHTML;
            del_ids.push(del_id);
            count++;
        }
    }
    if (count === 0) {
        alert("יש לבחור משלוחים ליצירת תעודות משלוח");
        document.getElementById('btn_create_ship').disabled = false;
        return;
    }

    // xmlhttp = new XMLHttpRequest();
    // xmlhttp.onreadystatechange = function () {
    //     // Wait to get query result
    //     if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
    //     {
    //         document.getElementById('btn_create_ship').disabled = false;
    //
    //         var text = xmlhttp.responseText.trim();
    //         if (Number.isInteger(text))
    //             document.getElementById("logging").innerHTML = "תעודת משלוח מספר " + invoice_id + " נוצרה ";
    //         else
    //             document.getElementById("logging").innerHTML = text;
    //
    //         // location.reload();
    //     }
    // }
    var request = post_file + "?operation=create_ship" +
        "&ids=" + del_ids.join();
    execute_url(request, location_reload);
    // xmlhttp.open("GET", request, true);
    // xmlhttp.send();
}
