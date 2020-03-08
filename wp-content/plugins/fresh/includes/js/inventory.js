let post = '/wp-content/plugins/fresh/post.php';

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
    save_inv("term_" +  supplier_id);
//    let supplier_id = get_value_by_name("supplier_id");
    execute_url(post + "?operation=inv_save_count&supplier_id=" + supplier_id);
}