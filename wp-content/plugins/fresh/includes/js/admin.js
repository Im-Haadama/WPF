// baskets
// use wp_localize_script to set admin_post
function admin_post()
{
    return fresh_admin_params['admin_post'];
}

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
