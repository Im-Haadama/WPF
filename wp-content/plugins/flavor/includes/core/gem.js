
function gem_next_page(post_file, table_id)
{
    let page_number = get_value_by_name("gem_page_" + table_id) + 1;
    let url = add_param_to_url(post_file, "page", page_number+1);
    execute_url(url, gem_update_div);
}

function gem_update_div(xmlhttp, btn)
{
    let table_id = btn.id.substr(8);
    if (xmlhttp.response.length > 10) { // has content
        // advance page number
        document.getElementById("gem_page_" + table_id).innerHTML = get_value_by_name("gem_page_" + table_id);

        // update content
        document.getElementById("gem_div_" + table_id).innerHTML = xmlhttp.response;
    }
}
