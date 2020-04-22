// General
function admin_post()
{
    return fresh_admin_params['admin_post'];
}

// baskets
// use wp_localize_script to set admin_post
function add_to_basket(basket_id)
{
    let prod_id = get_value_by_name("new_product");
    execute_url(admin_post() + '?operation=add_to_basket&basket_id=' + basket_id + '&new_product=' + prod_id, location_reload);
}

function remove_from_basket(basket_id)
{
    let param = get_selected("product_checkbox");
    execute_url(admin_post() + '?operation=remove_from_basket&basket_id=' + basket_id + '&products=' + param, location_reload);
}

function basket_create_new()
{
    let basket_name = get_value_by_name("basket_name");
    let price = get_value_by_name("basket_price");
    let categ = get_value_by_name("basket_categ");

    let url = admin_post() + '?operation=basket_create&basket_name=' + encodeURI(basket_name) + '&basket_price=' + price;

    if (categ) url += '&basket_categ=' + categ;
    execute_url(url, action_back);
}

function basket_delete(basket_id)
{
    let url = admin_post() + "?operation=basket_delete&basket_id=" + basket_id;
    let btn = document.getElementById("btn_delete_" + basket_id);

    execute_url(url, action_hide_row, btn);
}

function needed_create_supplies(supplier_id) {
    document.getElementById("btn_create_supply_" + supplier_id).disabled = true;
    // let table = document.getElementById('needed_' + supplier_id);
    // var lines = table.rows.length;
    let collection = document.getElementsByClassName("product_checkbox" + supplier_id);

    // Request header
    let request = admin_post() + "?operation=create_supplies&params=";
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
