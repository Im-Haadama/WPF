
function gem_next_page(post_file, table_id)
{
    let event = window.event;

    let page_number = parseInt(get_value_by_name("gem_page_" + table_id)) + 1;
    let url = add_param_to_url(post_file, "page", page_number+1);
    execute_url(url, gem_update_div, event.currentTarget);
}

function gem_update_div(xmlhttp, btn)
{
    let table_id = btn.id.substr(13); // remove btn_gem_next_
    if (xmlhttp.response.length > 10) { // has content
        // advance page number
        document.getElementById("gem_page_" + table_id).innerHTML = get_value_by_name("gem_page_" + table_id);

        // update content
        document.getElementById("gem_div_" + table_id).innerHTML = xmlhttp.response;
    }
}
