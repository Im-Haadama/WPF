function order_mission_changed(post_file, order_id) {
    // "mis_"
    //var order_id = field.name.substr(4);
    var mis = document.getElementById("mis_" + order_id);
    var mission_id = get_value(mis);
    execute_url(post_file + "?operation=order_set_mission&order_id=" + order_id + "&mission_id=" + mission_id);
}

// Todo: change calling
function start_handle(post_file) {
    var collection = document.getElementsByClassName("select_order_wc-pending");
    var order_ids = new Array();

    for (var i = 0; i < collection.length; i++) {
        var order_id = collection[i].id.substr(4);
        if (document.getElementById("chk_" + order_id).checked)
            order_ids.push(order_id);
    }
    collection = document.getElementsByClassName("select_order_wc-on-hold");
    for (var i = 0; i < collection.length; i++) {
        order_id = collection[i].id.substr(4);
        if (document.getElementById("chk_" + order_id).checked)
            order_ids.push(order_id);
    }

    var request = post_file + "?operation=order_start_handle&ids=" + order_ids.join();
    execute_url(request, location_reload);
}

// Todo: change calling
function cancel_order(post_file) {
    var classes = ["select_order_wc-pending", "select_order_wc-processing", "select_order_wc-on-hold"];
    var order_ids = new Array();

    for (var c = 0; c < classes.length; c++) {
        var collection = document.getElementsByClassName(classes[c]);
        for (var i = 0; i < collection.length; i++) {
            var order_id = collection[i].id.substr(4);
            if (document.getElementById("chk_" + order_id).checked)
                order_ids.push(order_id);
        }
    }
    var request = post_file + "?operation=order_cancel_orders&ids=" + order_ids.join();
    execute_url(request, location_reload);
}

function select_orders(table_name) {
    var table = document.getElementById(table_name);
    for (var i = 1; i < table.rows.length; i++)
        table.rows[i].firstElementChild.firstElementChild.checked =
            table.rows[0].firstElementChild.firstElementChild.checked;

}

// Todo: change calling
function complete_status(post_file) {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
            location.reload(true);
        }
    }
    var request = post_file + "?operation=orders_close_all_open";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function create_subs(post_file) {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
            location.reload(true);
        }
    }
    var request = post_file + "?operation=orders-create-subs";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function replace_baskets(post_file) {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
            location.reload(true);
        }
    }
    let request = post_file + "?operation=order_replace_baskets";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

// Todo: change calling
function update_email(post_file) {
    let email = get_value_by_name("email");

    let request = post_file + "?operation=order_check_email&email=" + email;

    execute_url(request, function(xmlhttp){
        if (xmlhttp.response === "u") {
            alert("משתמש לא ידוע. בדוק את כתובת המייל שלך");
        } else {
            document.getElementById("user_info").innerHTML = xmlhttp.response;
        }
    });

    request = post_file + "?operation=order_check_delivery&email=" + email;
    execute_url(request, function(xmlhttp)
    {
        document.getElementById("delivery_info").innerHTML = xmlhttp.response;
    });
}

// Todo: change calling
function draft_products(post_file, collect_name)
{
    var collection = document.getElementsByClassName(collect_name);
    var ids = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            let prod_id = collection[i].id.substr(4);
            ids.push(prod_id);
        }
    }
    let request = post_file + "?operation=order_draft_items&update_ids=" + ids.join();
    execute_url(request, location_reload);
}
