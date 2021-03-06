function gem_fetch_page(post_file, table_id, page_number)
{
    let event = window.event;

    let url = add_param_to_url(post_file, "page", page_number);
    execute_url(url, gem_update_div, event.currentTarget);
}

function gem_next_page(post_file, table_id)
{
    let page_number = parseInt(get_value_by_name("gem_page_" + table_id));
    document.getElementById("gem_page_" + table_id).innerHTML = (page_number + 1);

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

function gem_show_import(post_file, table, div)
{
    let action = add_param_to_url(post_file, 'operation', 'gem_do_import&table=' + table);
    // execute_url(action, show_response, div);
    div.innerHTML = '<h1>Import to bank</h1>' +
        '<form action="' + action + '" name="upload_csv" method="post" enctype="multipart/form-data">Load from csv file' +
        '<input type="file" name="fileToUpload" id="fileToUpload">' +
        '<input type="submit" value="טען" name="submit">';

    // let frame_id = div.id + '_frame';
    //    div.innerHTML = '<iframe id="' + frame_id + '" width="100%" height="600" src="' + action + '"></iiframe>';
}