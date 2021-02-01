// baskets
function add_to_basket(post_file, basket_id)
{
    let prod_id = get_value_by_name("new_product");
    execute_url(post_file + '?operation=add_to_basket&basket_id=' + basket_id + '&new_product=' + prod_id, location_reload);
}

function remove_from_basket(post_file, basket_id)
{
    let param = get_selected("product_checkbox");
    execute_url(post_file + '?operation=remove_from_basket&basket_id=' + basket_id + '&products=' + param, location_reload);
}

function basket_create_new(post_file)
{
    let basket_name = get_value_by_name("basket_name");
    let price = get_value_by_name("basket_price");
    let categ = get_value_by_name("basket_categ");

    let url = post_file + '?operation=basket_create&basket_name=' + encodeURI(basket_name) + '&basket_price=' + price;

    if (categ) url += '&basket_categ=' + categ;
    execute_url(url, action_back);
}

function basket_delete(post_file, basket_id)
{
    let url = post_file + "?operation=basket_delete&basket_id=" + basket_id;
    let btn = document.getElementById("btn_delete_" + basket_id);

    execute_url(url, action_hide_row, btn);
}

function needed_create_supplies(post_file, supplier_id) {
    document.getElementById("btn_create_supply_" + supplier_id).disabled = true;
    // let table = document.getElementById('needed_' + supplier_id);
    // var lines = table.rows.length;
    let collection = document.getElementsByClassName("product_checkbox" + supplier_id);

    // Request header
    let request = post_file + "?operation=create_supply&params=";
    let params = new Array();

    // Add the data.
    for (let i = 0; i < collection.length; i++) {
        if (collection[i].checked) { // Add to suppplies
            var prod_id = collection[i].id.substring(3);
            params.push(prod_id);

            var quantity = get_value_by_name("qua_" + prod_id);
            params.push(quantity);
        }
    }
    // Call the server to save the supply

    if (params.length < 1) {
        alert("select products");
        return;
    }
    request = request + params.join() + '&supplier_id=' + supplier_id;

    execute_url(request, location_reload);
}

function create_product(post_file, supplier_id, pl_id)
{
    disable_btn("cre_" + pl_id);

    let categ = get_value_by_name("categ_" + pl_id);

    // let request = "/fresh/about.php?x=1," + pl_id;
    // NOTICE: hide_row depend on one colon in url. If you add parameter, change hide_row.
    let request = post_file + "?operation=create_products&category_name=" + encodeURI(categ) +
        "&create_info=" + supplier_id + "," + pl_id;

    execute_url(request, action_hide_row);
    // alert (categ);
}

function pricelist_option_selected(sel) {
    var pricelist_id = sel.id.substr(3);
    document.getElementById("chk_" + pricelist_id).checked = true;
}

function pricelist_delete(post_file, line_id)
{
    let btn = document.getElementById("del_" + line_id);
    execute_url(post_file + '?operation=pricelist_delete&id=' + line_id, action_hide_row, btn);
}

function pricelist_map_remove(post_file)
{
    let table = document.getElementById("supplier_price_list");
    let collection = get_selected("checkbox_supplier_price_list");
    let map_ids = [];
    for (let i = 0; i < collection.length; i++) {
        map_ids.push(collection[i]);
    }

    let request = post_file + '?operation=suppliers_map_remove&ids=' + map_ids.join();
    execute_url(request, location_reload);
}

function pricelist_map_products(post_file)
{
    let table = document.getElementById("supplier_price_list");
    let collection = get_selected("checkbox_supplier_price_list");
    let map_ids = [];
    for (let i = 0; i < collection.length; i++) {
        let pricelist_id = collection[i];
        let product_id = get_value_by_name("prd" + pricelist_id);
        if (! product_id) {
            alert("לא נבחר מוצר עבור " + pricelist_id);
            return;
        }
        // Handle local
        map_ids.push(product_id);
        map_ids.push(pricelist_id);
    }

    let request = post_file + '?operation=suppliers_map_products&ids=' + map_ids.join();
    execute_url(request, location_reload);
}

function product_change_regularprice(post_file, pl_id, prod_id)
{
    let price = get_value_by_name("prc_" + prod_id);
    let buy_price = get_value_by_name("price_" + pl_id);
    if (parseFloat(price) < parseFloat(buy_price))
        document.getElementById("prc_" + prod_id).style.backgroundColor = '#EC7063';
    else
        document.getElementById("prc_" + prod_id).style.backgroundColor = 'white';
    if (! (price > 0)) {
        alert ("Enter valid price");
        return;
    }
    let request = post_file + "?operation=product_change_regularprice&prod_id=" + prod_id + "&price=" + price;
    execute_url(request, fail_message);
}

function product_change_saleprice (post_file, prod_id)
{
    let price = get_value_by_name("sal_" + prod_id);
    if (! (price > 0) && price !== '') {
        alert ("Enter valid saleprice");
        return;
    }
    let request = post_file + "?operation=product_change_saleprice&prod_id=" + prod_id + "&price=" + price;
    execute_url(request, fail_message);
}

function product_publish(post_file, product_id)
{
    let publish = get_value_by_name("pub_"+ product_id);
    let url = post_file + '?operation=product_publish&product_id='+product_id+'&status='+publish;
    execute_url(url, fail_message);
}
// Have general function in client_tools. 19/10/2020
// function moveNext(element)
// {
//     if (window.event.keyCode !== 13) return;
//     let col_index = element.parentElement.cellIndex;
//     let row_index = element.parentElement.parentElement.rowIndex;
//     let table = element.parentElement.parentElement.parentElement;
//
//     if (null != table.rows[row_index+1])
//         if (undefined != table.rows[row_index+1].cells[col_index].firstElementChild)
//             table.rows[row_index+1].cells[col_index].firstElementChild.focus();
// }

function account_add_transaction(post_file, customer_id) {
    let type = document.getElementById("transaction_type").value;
    if (type.length < 1) {
        alert("Enter type");
        return;
    }

    let amount = document.getElementById("transaction_amount").value;
    if (amount.length < 1) {
        alert("Enter amount");
        return;
    }

    let date = document.getElementById("transaction_date").value;
    let ref = document.getElementById("transaction_ref").value;
    if (ref.length < 1) ref = '0';

    let request = post_file + "?operation=account_add_trans&customer_id=" + customer_id +"&type=" + type + "&amount=" + amount + "&date=" + date + "&ref=" + ref;

    execute_url(request, location_reload);
}

//////////////////////////// Bundles /////////////////////////////
function bundle_get_price() {
    var product_name = get_value(document.getElementById("item_name"));
    var request = "../delivery/delivery-post.php?operation=get_price&name=" + encodeURI(product_name);

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            var price = xmlhttp.response;

            if (price > 0) {
                document.getElementById("unit_price").innerHTML = price;
            }
        }
    }
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function bundle_calc(post_file) {
    let product_id = get_value_by_name("product_name");
    // var product_id = product_name.substr(0, product_name.indexOf(")"));
    var q = get_value(document.getElementById("quantity"));
    var margin = get_value(document.getElementById("margin"));

    var request = post_file + "?operation=calculate&product_id=" + product_id +
        "&quantity=" + q + "&margin=" + margin;

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get delivery id.
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            var response = xmlhttp.response.split(",");
            var buy_price = response[0];
            var price = response[1];
            var bundle_price = response[2];

            document.getElementById("buy_price").innerHTML = buy_price;
            document.getElementById("price").innerHTML = price;
            document.getElementById("bundle_price").innerHTML = bundle_price;
            document.getElementById("regular_price").innerHTML = price * q;

        }
    }
    xmlhttp.open("GET", request, true);
    xmlhttp.send();

}

function bundle_show_create_new() {
    var new_item = document.getElementById("new_item");
    new_item.style.display = 'block';
    // add_line();
    // document.getElementById("client_select").focus();

}

// function selected(sel) {
//     var pricelist_id = sel.id.substr(4);
//     document.getElementById("chk_" + pricelist_id).checked = true;
// }

function bundle_create(post_url) {
    var product_id = get_value_by_name("product_name");
    // var product_id = product_name.substr(0, product_name.indexOf(")"));
    var quantity = get_value(document.getElementById("quantity"));
    var margin = get_value(document.getElementById("margin"));

    var request = post_url + "?operation=bundle_add_item&product_id=" + product_id + "&quantity=" + quantity +
        "&margin=" + encodeURI(margin);

    execute_url(request, location_reload);
}

function bundle_save_items(post_url) {
    var table = document.getElementById(table_name);

    var collection = document.getElementsByClassName(class_name);
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            var bundle_id = table.rows[i + 1].cells[0].firstChild.id.substr(4);
            var quantity = get_value_by_name("qty_" + bundle_id);
            var margin = get_value_by_name("mar_" + bundle_id);

            params.push(bundle_id);
            params.push(quantity);
            params.push(encodeURI(margin));
        }
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
        {
            updateDisplay();
        }
    }
    var request = post_url + "?operation=update&params=" + params;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function bundle_del_items(post_url) {
    var table = document.getElementById(table_name);

    var collection = document.getElementsByClassName(class_name);
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            var id = collection[i].id.substr(4);

            params.push(id);
        }
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            updateDisplay();
        }
    }
    var request = post_url + "?operation=delete_item" + "&params=" + params;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function bundle_update_display(post_url) {
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            table = document.getElementById(table_name);
            table.innerHTML = xmlhttp.response;
        }
    }
    var request = post_url + "?operation=get_bundles";
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function bundle_add_item(post_url) {
    var prod_name = get_value(document.getElementById("item_id"));
    var quantity = get_value(document.getElementById("quantity"));
    var margin = get_value(document.getElementById("margin"));
    var bundle_prod_name = get_value(document.getElementById("bundle_prod_id"));

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            updateDisplay();
        }
    }
    var request = post_url + "?operation=add_item&product_name=" + encodeURI(prod_name) + '&quantity=' + quantity +
        '&margin=' + margin + '&bundle_prod_name=' + encodeURI(bundle_prod_name);
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function bundle_update_quantity(post_file, id)
{
    let q = get_value_by_name("quantity_" + id);
    execute_url(post_file + '?operation=bundle_change_quantity&id=' + id + '&q=' + q, location_reload);
}

function inventory_change(post_file, prod_id)
{
    let data = new Array();
    let q = get_value_by_name("inv_" + prod_id);
    data.push(prod_id, q);
    let request = post_file + "?operation=inventory_save&data=" + data.join();
    execute_url(request, fail_message);
}