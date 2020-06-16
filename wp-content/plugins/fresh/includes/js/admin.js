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
    let request = post_file + "?operation=create_supplies&params=";
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

function pricelist_map_products(post_file)
{
    let table = document.getElementById("supplier_price_list");
    let collection = get_selected("checkbox_supplier_price_list");
    let map_ids = [];
    for (let i = 0; i < collection.length; i++) {
        let pricelist_id = collection[i];
        let product_id = get_value_by_name("prd" + pricelist_id);
        if (! product_id) {
            alert("לא נבחר מוצר עבור " + table.rows[i + 1].cells[2].innerHTML);
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
function moveNext(element)
{
    if (window.event.keyCode !== 13) return;
    let col_index = element.parentElement.cellIndex;
    let row_index = element.parentElement.parentElement.rowIndex;
    let table = element.parentElement.parentElement.parentElement;

    if (undefined != table.rows[row_index+1].cells[col_index].firstElementChild)
        table.rows[row_index+1].cells[col_index].firstElementChild.focus();
}

function account_add_transaction(post_file, customer_id) {
    let type = document.getElementById("transaction_type").value;
    let amount = document.getElementById("transaction_amount").value;
    let date = document.getElementById("transaction_date").value;
    let ref = document.getElementById("transaction_ref").value;
    let request = post_file + "?operation=account_add_trans&customer_id=" + customer_id +"&type=" + type + "&amount=" + amount + "&date=" + date + "&ref=" + ref;

    execute_url(request, location_reload);
}
