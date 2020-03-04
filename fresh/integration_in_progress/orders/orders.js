
let orders_post = '/wp-content/plugins/fresh/post.php';

function mission_changed(order_id) {
    // "mis_"
    //var order_id = field.name.substr(4);
    var mis = document.getElementById("mis_" + order_id);
    var mission_id = get_value(mis);
    execute_url(orders_post + "?operation=order_set_mission&order_id=" + order_id + "&mission_id=" + mission_id);
}

function start_handle() {
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

    var request = "orders-post.php?operation=start_handle&ids=" + order_ids.join();
    execute_url(request, location_reload);
}

function cancel_order() {
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
    var request = "orders-post.php?operation=cancel_orders&ids=" + order_ids.join();
    execute_url(request, location_reload);
}

function select_orders(table_name) {
    var table = document.getElementById(table_name);
    for (var i = 1; i < table.rows.length; i++)
        table.rows[i].firstElementChild.firstElementChild.checked =
            table.rows[0].firstElementChild.firstElementChild.checked;

}

function complete_status() {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
            location.reload(true);
        }
    }
    var request = "orders_close_all_open.php";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function create_subs() {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
            location.reload(true);
        }
    }
    var request = "orders-create-subs.php";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function replace_baskets() {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
            location.reload(true);
        }
    }
    let request = "orders-post.php?operation=replace_baskets";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function update_email() {
    let email = get_value_by_name("email");

    let request = "/fresh/orders/orders-post.php?operation=check_email&email=" + email;

    execute_url(request, function(xmlhttp){
        if (xmlhttp.response === "u") {
            alert("משתמש לא ידוע. בדוק את כתובת המייל שלך");
        } else {
            document.getElementById("user_info").innerHTML = xmlhttp.response;
        }
    });

    request = "/fresh/orders/orders-post.php?operation=check_delivery&email=" + email;
    execute_url(request, function(xmlhttp)
    {
        document.getElementById("delivery_info").innerHTML = xmlhttp.response;
    });
}

function draft_products(collect_name)
{
    var collection = document.getElementsByClassName(collect_name);
    var ids = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            let prod_id = collection[i].id.substr(4);
            ids.push(prod_id);
        }
    }
    let request = "../catalog/catalog-update-post.php?operation=draft_items&update_ids=" + ids.join();
    execute_url(request, location_reload);
}
