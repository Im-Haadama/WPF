/**
 * Created by agla on 01/02/19.
 */


function mission_changed(supply_id) {
    let mis = document.getElementById("mis_" + supply_id);
    let mission_id = get_value(mis);
    execute_url("supplies-post.php?operation=set_mission&supply_id=" + get_supply_id() + "&mission_id=" + mission_id);
}

function add_item() {
    let supply_id = get_supply_id();
    let request_url = "supplies-post.php?operation=add_item&supply_id=" + supply_id;
    let prod_id = get_value_by_name("itm_");
    request_url = request_url + "&prod_id=" + prod_id;
    let _q = 1; // encodeURI(get_value(document . getElementById("qua_")));
    request_url = request_url + "&quantity=" + _q;

    execute_url(request_url, location_reload);
}

function save_mission() {
    var mission = get_value(document.getElementById("mission_select"));
    var request = "supplies-post.php?operation=set_mission&mission_id=" + mission + "&supply_id= " + get_supply_id();

    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            location.reload();
        }
    }
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function update_comment() {
    var text = get_value(document.getElementById("comment"));

    execute_url("supplies-post.php?operation=save_comment&text=" + encodeURI(text)
        + "&id=" + get_supply_id(), "update_display()");
}

function updateItems() {
    let supply_id = get_supply_id();
    let collection = document.getElementsByClassName("supply_checkbox");
    let params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            // var name = get_value(table.rows[i+1].cells[0].firstChild);
            var line_id = collection[i].id.substr(4);

            params.push(line_id);
            params.push(get_value_by_name("quantity_" +line_id));
            params.push(get_value_by_name("$buy_" +line_id));
        }
    }
    let request = "supplies-post.php?operation=update_lines&supply_id=" + supply_id + "&params=" + params;
    execute_url(request, location_reload);
}

function del_line(supply_line_id) {
    var btn = document.getElementById("del_" + supply_line_id);
    btn.parentElement.parentElement.style.display = 'none';
    execute_url("supplies-post.php?operation=delete_lines&params=" + supply_line_id);
}

function deleteItems() {
    var table = document.getElementById('del_table');

    var collection = document.getElementsByClassName("supply_checkbox");
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            // var name = get_value(table.rows[i+1].cells[0].firstChild);
            var line_id = collection[i].id.substr(4);

            params.push(line_id);
        }
    }
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
        {
            update_display();
        }
    }
    var request = "supplies-post.php?operation=delete_lines&params=" + params;
    xmlhttp.open("GET", request, true);
    xmlhttp.send();
}

function get_supply_id()
{
    return get_value_by_name("supply_number");
}