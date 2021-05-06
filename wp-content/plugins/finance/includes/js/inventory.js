/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

let post = '/wp-content/plugins/wpf_flavor/post.php';

function save_inv(collection_name) {
    let collection = document.getElementsByName(collection_name);
    let prod_ids = new Array();

    for (let i = 0; i < collection.length; i++) {
        let prod_id = collection[i].id.substr(4);
        let q = get_value_by_name("inv_" + prod_id);
        prod_ids.push(prod_id, q);
    }
    let request = post + "?operation=save_inv&data=" + prod_ids.join();
    execute_url(request, location_reload);
}

function inventory_save_count(supplier_id)
{
    // save_inv("term_" +  supplier_id);
//    let supplier_id = get_value_by_name("supplier_id");
    execute_url(post + "?operation=inv_save_count&supplier_id=" + supplier_id, location_reload);
}