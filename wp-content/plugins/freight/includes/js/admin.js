function update_path_preq(point)
{
    let mission_id = get_value_by_name("mission_id");
    let preq = get_value_by_name(point);
    let request = "/routes/routes-post.php?operation=update_mission_preq&id=" + mission_id +
        "&point=" + encodeURI(point) + "&preq=" + encodeURI(preq);
    execute_url(request, location_reload);
}

function update()
{
    let mission_id = get_value_by_name("mission_id");
    let start = get_value_by_name("start_time");
    let start_point = get_value_by_name("start_location");
    let url = "/routes/routes-post.php?operation=update_mission&id=" + mission_id +
        "&start=" + encodeURI(start) +
        "&start_point=" + encodeURI(start_point);

    execute_url(url, update_display);
    // alert("update" + mission_id + start + start_point);
}

function update_display(xmlhttp)
{
    document.getElementById("route_div").innerHTML = xmlhttp.response;
}

function delivered(post_file, site, id, type) {
    let url = post_file + "?site_id=" + site + "&type=" + type + "&id=" + id + "&operation=delivered";
    let obj = document.getElementById("chk_" + id);
    execute_url(url, action_hide_row, obj);
}

function create_missions(post_file)
{
    let param = get_selected('checkbox_paths');
    if (param.length < 1) { alert ("no path selected"); return; }
    let url = post_file + '?operation=create_missions&path_ids=' + param;
    execute_url(url, location_reload);
}

function save_paths()
{
    let selected = get_selected("checkbox_paths");
    if (selected.length == 0) {
        alert ("first select items");
        return;
    }
    let params = [];
    for (let i = 0; i < selected.length; i++)
    {
        params.push(selected[i]);
        params.push(get_value_by_name("zones_" + selected[i]));
    }
    alert(params);
}

function update_shipment_instance(post_file, id)
{
    execute_url(post_file + "?operation=update_shipment_instance&id=" + id, fail_message);
}

function update_order_pri(post_file, obj)
{
    let new_pri = obj.value;
    do_update_order_pri(post_file, obj, new_pri);
    doMoveNextRow();
}

function do_update_order_pri(post_file, obj, new_pri)
{
    let order_id = obj.id.substr(3, obj.id.indexOf('_') - 3);
    let site_id = obj.id.substr(obj.id.indexOf('_') + 1);
    execute_url(post_file + "?operation=order_save_pri&order_id=" + order_id + "&site_id=" +site_id + "&pri="+ new_pri);
}

function reset_path(post_file, row_number)
{
    let pri_col = 6;
    let table = document.getElementById("path");
    let pri = get_value(table.rows[row_number].cells[pri_col]);

    for (let i = row_number; i< table.rows.length; i++)
    {
        let p = table.rows[i].cells[pri_col].firstElementChild;
        p.value = pri;
        do_update_order_pri(post_file, p, pri)
    }
}

function toggle_shipment_enable(post_file, instance_id)
{
    let enable = get_value_by_name("chk_shipment_" + instance_id);
    execute_url(post_file + "?operation=toggle_shipment_enable&instance=" + instance_id + '&enable=' + enable);
}

function shipment_delete(post_file, instance_id)
{
    execute_url(post_file + "?operation=shipment_delete&instance=" + instance_id, location_reload);
}

function shipment_update_mc(post_file, instance_id)
{
    let mc = get_value_by_name("mis_"+instance_id);
    execute_url(post_file + "?operation=shipment_update_mc&instance=" + instance_id + '&mc=' + mc, fail_message1);
}

function fail_message1(xmlhttp)
{
    check_result(xmlhttp);
}

function mission_update_type(post_file, mission_id)
{
    let type = get_value_by_name("mission_type_" + mission_id);
    execute_url(post_file + '?operation=mission_update_type&mission=' + mission_id + '&type=' + type, fail_message1);
}

function freight_add_delivery(post_file, mission_id)
{
    let client = get_value_by_name("delivery_client");
    if (! (client > 0)) {
        alert("יש לבחור לקוח מהרשימה או להוסיף תחילה משתמש וורדפרס");
        return;
    }
    let fee = get_value_by_name("delivery_price");
    if (! (fee > 0)) {
        alert("יש לבחור את עלות המשלוח לפני מע\"מ");
        return;
    }

    let request = post_file + "?operation=freight_do_add_delivery&client=" + client + '&mission_id=' + mission_id + '&fee=' + fee;

    execute_url(request, success_message);
}

function order_update_driver_comment(post_file, order_id)
{
    let comments = get_value_by_name("comments_" + order_id);
    let url = post_file + '?operation=order_update_driver_comment&order_id=' + order_id + '&comments=' + encodeURI(comments);
    execute_url(url, fail_message1);
}

function order_update_field(post_file, order_id, field)
{
    let field_value = get_value_by_name(field + "_" + order_id);
    let url = post_file + '?operation=order_update_field&order_id=' + order_id + '&field=' + field + '&field_value=' + encodeURI(field_value);
    execute_url(url, fail_message1);
}


function freight_import(post_file, div)
{
    let action = add_param_to_url(post_file, 'operation', 'freight_do_import');
    let action_baldar = add_param_to_url(post_file, 'operation', 'freight_do_import_baldar');

    // execute_url(action, show_response, div);
    div.innerHTML = '<h1>Import deliveries</h1>' +
        '<div style="border: thin solid black"><form action="' + action + '" name="import_csv" method="post" enctype="multipart/form-data"><h1>Import from csv file</h1>' +
        '<p>Create and edit sprreadsheet with columns:</p>' +
        '<li>A: Order number</li>' +
        '<li>B: Client name (optional)</li>' +
        '<li>C: Address 2 (optional)</li>' +
        '<li>D: Address 1</li>' +
        '<li>E: City</li>' +
        '<li>F: Comments (optional)</li>' +
        '<li>G: Phone (optional)</li>' +
        '<input type="file" name="fileToUpload" id="fileToUpload">' +
        '<input type="submit" value="טען" name="submit"></form></div>' +

        '<div style="border: thin solid black"><form action="' + action_baldar + '" name="import_html" method="post" enctype="multipart/form-data"><h1>Import from baldar html file</h1>' +
        '<p>Save baldar file using ctrl-s</p>' +
        '<p>Press choose file to select it</p>' +
        '<p>Press Load button</p>' +
        '<input type="file" name="fileToUpload" id="fileToUpload">' +
        '<input type="submit" value="טען" name="submit"></form></div>';

}

