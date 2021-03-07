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

function pricelist_update_price(post_file, pricelist_id)
{
    let price = get_value_by_name("price_" + pricelist_id);
    let url = post_file + '?operation=pricelist_update_price&pricelist_id=' + pricelist_id + '&price=' + price;
    execute_url(url, fail_message);
}

function pricelist_filter()
{
    let table = document.getElementById("supplier_price_list");
    for (let i = 1; i < table.rows.length; i++)
    {
        let colored = false;
        for (let j=1; j < table.rows[i].cells.length; j++) {
            if ((null != table.rows[i].cells[j].firstElementChild) &&
                (table.rows[i].cells[j].firstElementChild.style.backgroundColor != '') &&
                (table.rows[i].cells[j].firstElementChild.style.backgroundColor != 'white')){
                colored = true;
                continue;
            }
        }
        if (! colored)
            table.rows[i].style.display = 'none';

    }
}