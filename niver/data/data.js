
// Save entity from single row view - chk_ new fields.
function save_entity(post_operation, table_name, id)
{
    if (!Number.isInteger(id)) {
        alert ("invalid id: " . id);
        return;
    }
    let operation = post_operation + "?table_name=" + table_name + "&operation=update&id=" + id;
    operation = operation + operation_arguments(table_name, id);
    // alert(operation);
    execute_url(operation, action_back);
}

// If we want to add custom action in the server we can send different post action.
function save_new_custom(post_operation, table_name, action)
{
    let operation = post_operation + '&table_name=' + table_name;
    if (! post_operation.indexOf("operation="))
        operation += '?operation=save_new';
    let table = document.getElementById(table_name);
    if (! table || ! table.rows){
        alert("rows of table " + table_name + " not found");
        return false;
    }
    let size = table.rows.length;
    // Change i to start from 0 - in new row no header. id should be hidden
    for (let i = 0; i < size; i++){
        let chk_id = table.rows[i].cells[0].firstElementChild.id;
        let name = chk_id.substr(4);
        if (get_value_by_name(chk_id)) {
            let val = get_value_by_name(name);
            operation += "&" + name + "=" + encodeURI(val);
        } else {
            if (get_value_by_name("mandatory_" + name)) {
                alert (name + " is mandatory ");
                return false;
            }
        }
    }
    // alert(operation);
    if (action)
        execute_url(operation, action);
    else
        execute_url(operation, action_back);

}
// function save_new(table_name, action = null)
// {
//     save_new_custom("/niver/data/data.php?operation=new", table_name, action);
// }

function check_update(xmlhttp)
{
    if (xmlhttp.response !== "done")
        alert (xmlhttp.response);

}

function action_hide_row(xmlhttp, btn)
{
    if (xmlhttp.response === "done")
        btn.parentElement.parentElement.style.display = 'none';
    else
        alert (xmlhttp.response);
}

function action_back(xmlhttp)
{
    if (xmlhttp.response === "done")
        window.history.back();
    else
        alert (xmlhttp.response);
}

function update_table_field(post_file, table_name, id, field_name, finish_action) {
    let value = get_value_by_name(field_name);
    if (! value) value = get_value_by_name(field_name + "_" + id);
    if (! value) {
        alert ("no value found");
        return;
    }
    let request = post_file + "?operation=update" +
        "&table_name=" + table_name +
        "&" + field_name + '=' +
        encodeURI(value) +
        "&id=" + id;

    execute_url(request, finish_action);
}

function location_reload(xmlhttp)
{
    if (xmlhttp.response === "done")
        location.reload();
    else
        alert (xmlhttp.response);
}

function operation_arguments(table_name, id = null)
{
    let operation_args = "";
    let table = document.getElementById(table_name);
    let size = table.rows.length;
    for (let i = 0; i < size; i++){
        let chk_id = table.rows[i].cells[0].firstElementChild.id;
        if (get_value_by_name(chk_id)) {
            let name = chk_id.substr(4);
            let control_name = name;
            if (id) control_name = name + "_" + id;
            operation_args += "&" + name + "=" + encodeURIComponent(get_value_by_name(control_name));
        }
    }
    return operation_args;
}

function search_table(table_name, url = null)
{
    // alert(operation);
    let args = operation_arguments(table_name);
    if (args.length < 3){
        alert("Select fields to search with");
        return;
    }
    if (! url) url = "/niver/data/data.php?table_name=" + table_name + "&operation=search";
    window.location =  url + args;
}
// function update_field(post_file, id, field_name, finish_action) {
//     let value = get_value_by_name(field_name);
//     let request = post_file + "?operation=update_field" +
//         "&field_name=" + field_name +
//         "&value=" + encodeURI(value) +
//         "&id=" + id;
//
//     execute_url(request, finish_action);
// }
