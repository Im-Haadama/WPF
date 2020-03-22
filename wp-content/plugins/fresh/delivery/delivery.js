function delivered_table() {
    var collection = document.getElementsByClassName("select_order_wc-awaiting-shipment");
    var order_ids = new Array();
    var table = document.getElementById("wc-awaiting-shipment");

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            window.location = window.location;
        }
    }

    for (var i = 0; i < collection.length; i++) {
        var order_id = collection[i].id.substr(4);
        if (document.getElementById("chk_" + order_id).checked)
            order_ids.push(order_id);
    }
    var request = "/fresh/orders/orders-post.php?operation=delivered&ids=" + order_ids.join();
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}
