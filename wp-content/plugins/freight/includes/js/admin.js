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

function delivered(site, id, type) {
    let url = "/routes/routes-post.php?site_id=" + site + "&type=" + type +
        "&id=" + id + "&operation=delivered";

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            // alert (xmlhttp.response);
            if (xmlhttp.response === "delivered") {
                var row = document.getElementById("chk_" + id).parentElement.parentElement;
                var table = row.parentElement.parentElement;
                table.deleteRow(row.rowIndex);
            } else {
                alert(xmlhttp.response);
            }
            // window.location = window.location;
        }
    }

    xmlhttp.open("GET", url, true);
    xmlhttp.send();
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

function save_path_times(path_id)
{
    let table = document.getElementById("zone_times");
    let params = new Array();

    for (let i = 1; i < table.rows.length; i++){
        params.push(get_value(table.rows[i].cells[1]));
        params.push(get_value(table.rows[i].cells[3]));
    }

    // alert(params);
    let request = "/routes/routes-post.php?operation=save_path_times&params=" + params.join() + '&path_id=' + path_id;

    execute_url(request, location_reload);
}

function delete_path_times(path_id, post_file)
{
    let params = new Array();

    var collection = document.getElementsByClassName("checkbox_zone_times");
    for (var i = 0; i < collection.length; i++) {
        var zone_name = collection[i].id.substr(4);
        if (document.getElementById("chk_" + zone_name).checked)
            params.push(encodeURI(zone_name));
    }

    // for (let i = 1; i < table.rows.length; i++){
    //     if ()
    //     params.push(get_value(table.rows[i].cells[1]));
    //     params.push(get_value(table.rows[i].cells[3]));
    // }

    // alert(params);
    let request = post_file + "?operation=path_remove_times&params=" + params.join() + '&path_id=' + path_id;

    execute_url(request, location_reload);
}

function add_zone_times(path_id, post_file)
{
    let zones = get_value_by_name("zone_id");
    let times = get_value_by_name("zone_time");

    let request = add_param_to_url(post_file, "operation", "add_zone_times");
    request = add_param_to_url(request, "path_id", path_id);
    request = add_param_to_url(request, "time", times);
    request = add_param_to_url(request, "zones", zones)
    window.location.href = request;
}

function path_save_days(post_file, path)
{
    let day = get_value_by_name("path_days");

    execute_url(post_file + "?operation=path_save_days&path_id="+path+"&day=" + day, location_reload);
}

function update_shipment_instance(post_file, id)
{
    execute_url(post_file + "?operation=update_shipment_instance&id=" + id, fail_message);
}