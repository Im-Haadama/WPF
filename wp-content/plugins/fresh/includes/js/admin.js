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