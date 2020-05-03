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

    let changes_allowed = document.getElementById("changes_allowed_" + item_id);
    let c = get_value(changes_allowed);
    if (0 === get_value(changes_allowed)) // Should be avoided by hiding the X.
        return;

    if (c === 1) {
        // the last changes. Remove all X.
    }
// add_to_basket_27170_3
    let id = "add_to_basket_" + item_id + "_" + c.toString();
    let add = document.getElementById(id);
    add.style.display = "block";

    c = c - 1;
    // if (0 == c) changes_allowed.hidden = true;
    changes_allowed.innerText = c.toString();

    execute_url(post_file + '?operation=order_remove_from_basket&item_id=' + item_id + '&prod_id=' + prod_id, order_remove_from_display, button.parentElement.parentElement);
}

function order_add_to_basket(post_file, item_id, basket_prod_id, new_index)
{
    // Hide the row.
    // let button_id = "remove_" + item_id.toString() + "_" + prod_id.toString();
    // let button = document.getElementById(button_id);
    // button.disabled = true;
    // parentElement.style.display = 'none';

    // let changes_allowed = document.getElementById("changes_allowed_" + item_id);
    // let c = get_value(changes_allowed);
    // if (0 === get_value(changes_allowed)) // Should be avoided by hiding the X.
    //     return;
    //
    // if (c === 1) {
    //     the last changes. Remove all X.
    // }
// add_to_basket_27170_3
//     let id = "add_to_basket_" + item_id + "_" + c.toString();
//     let add = document.getElementById(id);
//     add.style.display = "block";

    // c = c - 1;
    // if (0 == c) changes_allowed.hidden = true;
    // changes_allowed.innerText = c.toString();

    let new_product = get_value_by_name("new_prod_" + item_id + "_" + new_index);

    execute_url(post_file + '?operation=order_add_to_basket&item_id=' + item_id + '&new_prod_id=' + new_product, success_message);
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