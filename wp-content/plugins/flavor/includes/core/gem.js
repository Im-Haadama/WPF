function gem_fetch_page(post_file, table_id, page_number)
{
    let event = window.event;

    let url = add_param_to_url(post_file, "page", page_number);
    execute_url(url, gem_update_div, event.currentTarget);

}

function gem_next_page(post_file, table_id)
{
    let page_number = parseInt(get_value_by_name("gem_page_" + table_id));

    gem_fetch_page(post_file, table_id, page_number+1);
}

function gem_previous_page(post_file, table_id)
{
    let page_number = parseInt(get_value_by_name("gem_page_" + table_id));
    if (page_number > 0)
        gem_fetch_page(post_file, table_id, page_number-1);
}

function gem_all_page(post_file, table_id)
{
    gem_fetch_page(post_file, table_id, -1);
}

function gem_update_div(xmlhttp, btn)
{
    let table_id = btn.id.substr(13); // remove btn_gem_next_
    if (xmlhttp.response.length > 10) { // has content
         // update content
        document.getElementById("gem_div_" + table_id).innerHTML = xmlhttp.response.substr(5); // Substr to remove done.
    }
}
