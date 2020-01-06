let post_file = '/wp-content/plugins/focus/post.php';

function delete_lines()
{
    let params = get_selected("working_days");
    let request = post_file + "?operation=salary_delete&params=" + params;
    execute_url(request, location_reload);
}

function do_update(xmlhttp)
{
    let table = document.getElementById("list");
    table.innerHTML = xmlhttp.response;
    document.getElementById("btn_add_time").disabled = false;
    document.getElementById("btn_delete").disabled = false;
}

function salary_del_items() {
    document.getElementById("btn_delete").disabled = true;

    var collection = document.getElementsByClassName("working_days");
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            var id = collection[i].id.substr(4);
//                var name = get_value(table.rows[i+1].cells[1].firstChild);
//                var sel = document.getElementById("supplier_id");
//                var supplier_id = sel.options[sel.selectedIndex].value;

            params.push(id);
            //        alert(id);
        }
    }
//     document.getElementById("debug").innerHTML = "people-post.php?operation=delete&params=" + params;
    execute_url(post_file + "?operation=salary_delete&params=" + params, action_hide_rows, "working_days");

}
function salary_add_item(user_id)
{
    document.getElementById("btn_add_time").disabled = true;

    var sel = document.getElementById("project");
    var id = sel.options[sel.selectedIndex].value;
    var start = get_value(document.getElementById("start_h"));
    var end = get_value(document.getElementById("end_h"));
    var date = get_value(document.getElementById("date"));
    var traveling = get_value(document.getElementById("traveling"));
    var extra_text = get_value(document.getElementById("extra_text"));
    var extra = get_value(document.getElementById("extra"));

    if (traveling.length > 0 && !(parseInt(traveling) > 0)) {
        document.getElementById("btn_add_time").disabled = false;
        alert("רשום סכום הוצאות נסיעה");
        return;
    }

    let request = post_file + "?operation=salary_add_time&start=" + start + '&end=' + end +
        '&date=' + date + "&project=" + id + "&vol=0" + "&traveling=" + traveling +
        "&extra_text=" + encodeURI(extra_text) +
        "&extra=" + extra +
    '&user_id=' + user_id;

    execute_url(request, location_reload);
}
