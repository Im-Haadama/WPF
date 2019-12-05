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

function create_missions()
{
    let param = get_selected('checkbox_im_paths');
    if (param.length < 1) { alert ("no path selected"); return; }
    let url = '/routes/routes-post.php?operation=create_missions&path_ids=' + param;
    execute_url(url, location_reload);
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

function delete_path_times(path_id)
{
    alert("not implemented");
    return false;
    // let table = document.getElementById("zone_times");
    // let params = new Array();
    // for (let i = 1; i < table.rows.length; i++){
    //     params.push(get_value(table.rows[i].cells[1]));
    //     params.push(get_value(table.rows[i].cells[3]));
    // }
    //
    // // alert(params);
    // let request = "/routes/routes-post.php?operation=save_path_times&params=" + params.join() + '&path_id=' + path_id;
    //
    // execute_url(request, location_reload);
}