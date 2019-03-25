/**
 * Created by agla on 03/06/18.
 */

function save_excerpt(order_id) {
    var excerpt = get_value_by_name("order_excerpt");
    // alert(excerpt);

    execute_url("orders-post.php?operation=save_order_excerpt&excerpt=" + encodeURI(excerpt) + "&order_id=" + order_id);
}

function update_address(f, client_id, order_id) {
    var address = get_value_by_name(f);

    var request = "/tools/orders/orders-post.php?operation=update_address&f=" + f + "&address=" + encodeURI(address) +
        "&client_id=" + client_id +
        "&order_id=" + order_id;

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
        {
            // document.getElementById("invoice_client_id").innerHTML = xmlhttp.response;
            if (get_value_by_name("invoice_client_id").length > 1)
            // location.reload();
                add_message(xmlhttp.response);
        }
    }
    xmlhttp.open("GET", request, true);
    xmlhttp.send();

}

