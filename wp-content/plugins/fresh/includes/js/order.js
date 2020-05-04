// Functions for my account, order change
function order_show_basket(item_id)
{
    let basket = document.getElementById("basket_" + item_id);
    basket.style.display = "block";
}

function order_remove_from_basket(post_file, item_id, prod_id)
{
    // Hide the row.
    let button_id = "remove_" + item_id.toString() + "_" + prod_id.toString();
    let button = document.getElementById(button_id);
    button.disabled = true;
        // parentElement.style.display = 'none';

    execute_url(post_file + '?operation=order_remove_from_basket&item_id=' + item_id + '&prod_id=' + prod_id, location_reload);
}

function order_add_to_basket(post_file, item_id, basket_prod_id, new_index)
{
    let new_product = get_value_by_name("new_prod_" + item_id + "_" + new_index);

    execute_url(post_file + '?operation=order_add_to_basket&item_id=' + item_id + '&new_prod_id=' + new_product, location_reload);
}

function order_remove_from_display(xmlhttp, obj)
{
    if (xmlhttp.response.substr(0, 4) === "done") {
        obj.style.display = 'none';
    }
}

function order_remove_line(post_file, item_id, obj)
{
    let url = post_file + '?operation=order_remove_item&item_id=' + item_id;
    execute_url(url, action_hide_row, obj);
}