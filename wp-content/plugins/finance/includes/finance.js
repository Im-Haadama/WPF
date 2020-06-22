function change_import(action_file, form_name)
{
    let selected = get_value_by_name("import_select");
    let upcsv = document.getElementById(form_name);
    if (selected > 0){
        let forms = document.getElementsByName("submit");
        forms.forEach(element => element.disabled = false);
        upcsv.action = action_file + '&selection=' + selected;
    }
    else
        upcsv.action = select_import_first;
}

function select_import_first()
{
    alert ("select import target first");
}

function wait_for_selection()
{
    let forms = document.getElementsByName("submit");
    forms.forEach(element => element.disabled = true);
}

function makolet_create_invoice(post_file, year_month)
{
    let request = post_file + "?operation=makolet_create_invoice&month=" + year_month;

    execute_url(request, location_reload);
}

function delete_lines(post_file)
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

function salary_del_items(post_file) {
    document.getElementById("btn_delete").disabled = true;

    var collection = document.getElementsByClassName("working_days");
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            var id = collection[i].id.substr(4);
            params.push(id);
        }
    }
    execute_url(post_file + "?operation=salary_delete&params=" + params, action_hide_rows, "working_days");

}
function salary_add_item(post_file, user_id)
{
    document.getElementById("btn_add_time").disabled = true;

    var sel = document.getElementById("project");
    if (! sel) {
        alert("first define project for this worker");
        return false;
    }
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