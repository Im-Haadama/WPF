function delivered(site, id, type) {
    let url = "delivery-post.php?site_id=" + site + "&type=" + type +
        "&id=" + id + "&operation=delivered";

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            // alert (xmlhttp.response);
            if (xmlhttp.response === "delivered") {
                var row = document.getElementById("chk_" + id).parentElement.parentElement;
                var table = row.parentElement.parentElement;
                table.deleteRow(row.rowIndex);
            } else {
                alert(xmlhttp.response);
            }
            // window.location = window.location;
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

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
