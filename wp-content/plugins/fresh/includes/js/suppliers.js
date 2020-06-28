function supplier_add_pricelist_item(post_file, supplier_id)
{
    let product_name = get_value_by_name("product_name_new_row");
    if (! (product_name.length > 2)) {
        alert ("Enter product name");
        return;
    }

    let price = get_value_by_name("price_new_row");
    if (! (price > 0)) {
        alert("print price");
        return;
    }

    execute_url(post_file + '?operation=add_pricelist_item' +
        '&product_name=' + encodeURI(product_name) +
        '&price=' + price +
        '&supplier_id=' + supplier_id, location_reload);
}