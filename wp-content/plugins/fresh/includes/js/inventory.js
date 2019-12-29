let post = '/wp-content/plugins/fresh/post.php';

function save_inv(term) {
    let collection = document.getElementsByName("term_" + term);
    let prod_ids = new Array();

    for (let i = 0; i < collection.length; i++) {
        let prod_id = collection[i].id.substr(4);
        let q = get_value_by_name("inv_" + prod_id);
        prod_ids.push(prod_id, q);
    }
    let request = post + "?operation=save_inv&data=" + prod_ids.join();
    execute_url(request, location_reload);
}
