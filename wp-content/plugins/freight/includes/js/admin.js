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
    if (xmlhttp.response.substr(0, 4) === "done") return;

    alert (xmlhttp.response);
}

function mission_update_type(post_file, mission_id)
{
    let type = get_value_by_name("mission_type_" + mission_id);
    execute_url(post_file + '?operation=mission_update_type&mission=' + mission_id + '&type=' + type, fail_message1);
}

function freight_add_delivery(post_file, mission_id)
{
    let client = get_value_by_name("delivery_client");
    let fee = get_value_by_name("delivery_price");

    let request = post_file + "?operation=freight_do_add_delivery&client=" + client + '&mission_id=' + mission_id + '&fee=' + fee;

    execute_url(request, success_message);
}
