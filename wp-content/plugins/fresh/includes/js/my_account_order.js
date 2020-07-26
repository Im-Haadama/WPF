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
    if (check_result(xmlhttp))
        obj.style.display = 'none';
}

function order_remove_line(post_file, item_id, obj)
{
    let url = post_file + '?operation=order_remove_item&item_id=' + item_id;
    execute_url(url, action_hide_row, obj);
}

function order_add_product(post_file, order_id)
{
    let prod = get_value_by_name("new_prd");
    if (! (prod > 0)) {
        alert("יש לבחור מוצר להוסף");
        return;
    }
    execute_url(post_file + '?operation=order_add_product&order_id=' + order_id + "&prod=" + prod, location_reload);
}

function order_quantity_update(post_file, oid)
{
    let q= get_value_by_name('qty_' + oid);
    execute_url(post_file + '?operation=order_quantity_update&ooid=' + oid + '&quantity=' + q, fail_message);
}