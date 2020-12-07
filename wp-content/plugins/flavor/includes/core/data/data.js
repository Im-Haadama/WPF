// Save entity from single row view - chk_ new fields.
function data_save_entity(post_operation, table_name, id)
{
    if (!Number.isInteger(id)) {
        alert ("invalid id: " . id);
        return;
    }
    let operation = post_operation + "?table_name=" + table_name + "&operation=data_update&id=" + id;
    let operands = operation_arguments(table_name, id);;
    if (operands.length < 1) {
        alert ("no changes");
        return;
    }
    operation = operation + operands;
    // alert(operation);
    execute_url(operation, location_reload);
}

function data_delete_entity(post_operation, table_name, id)
{
    if (!Number.isInteger(id)) {
        alert ("invalid id: " . id);
        return;
    }
    let operation = post_operation + "?type=" + table_name + "&operation=data_delete&ids=" + id;
    // alert(operation);
    execute_url(operation, action_back);
}


function active_entity(active, post_operation, table_name, id)
{
    if (!Number.isInteger(id)) {
        alert ("invalid id: " . id);
        return;
    }
    let operation = post_operation + "?table_name=" + table_name + "&operation=data_active&id=" + id + "&active=" + active;
    
    //operation = operation + operation_arguments(table_name, id);
    // alert(operation);
    execute_url(operation, action_back);
}

// If we want to add custom action in the server we can send different post action.
function data_save_new(post_operation, table_name, page)
{
    let operation = post_operation;
    let table_id = table_name + "_new";
    let glue = '?';
    if (operation.indexOf('?') !== -1) glue = '&';

    operation = add_param_to_url(post_operation, "operation", "data_save_new");
    // if (post_operation.indexOf("operation=") === -1) operation += glue + 'operation=data_save_new';

    operation += '&table_name=' + table_name;
    let form_params = get_form_params(table_id, true);
    if (! form_params) {
        alert("form not found");
        return;
    }
    operation += form_params;

    if (typeof(page) === "string"){
        page = function(xmlhttp, obj) { next_page(xmlhttp, page); }
    }  else {
        if (typeof (page) !== 'function') page = action_back;
    }

    execute_url(operation, page);
}

function get_form_params(table_id, check_mandatory)
{
    let table = document.getElementById(table_id);
    let params = "";

    if (! table || ! table.rows){
        // alert("rows of table " + table_id + " not found");
        return "";
    }
    let size = table.rows.length;
    // Change i to start from 0 - in new row no header. id should be hidden
    for (let i = 0; i < size; i++){
        let chk_id = table.rows[i].cells[0].firstElementChild.id;
        let name = chk_id.substr(4);
        if (get_value_by_name(chk_id)) {
            let val = get_value_by_name(name);
            params += "&" + name + "=" + encodeURI(val);
        } else {
            if (check_mandatory && get_value_by_name(name + "_mandatory") === "1") {
                alert (name + " is mandatory ");
                return null;
            }
        }
    }
    return params;
}

// After operation completed successfully, send the result to the page before

function update_table_field(post_file, table_name, id, field_name, finish_action) {
    if (! (id > 0)) return;
    let value = get_value_by_name(field_name);
    if (! value) value = get_value_by_name(field_name + "_" + id);
    if (! value) {
        alert ("no value found");
        return;
    }
    let request = post_file + "?operation=data_update" +
        "&table_name=" + table_name +
        "&" + field_name + '=' +
        encodeURI(value) +
        "&id=" + id;

    execute_url(request, finish_action);
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
            if (document.getElementById(control_name + ".0"))
            {
                // Multiple values
                let i = 0;
                let params = [];
                do {
                    params.push(get_value_by_name(control_name + "." + i));
                    i = i + 1;
                } while (document.getElementById(control_name + "." + i));
                operation_args += "&" + name + "=" + params;
            } else
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
    if (! url) url = "/core/data/data.php?table_name=" + table_name + "&operation=data_search";
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

function update_list(post_file, list_name, obj)
{
    if (obj.value.length >= 2 && obj.value.length <= 7) {
        let url = add_param_to_url(post_file, "operation", "data_auto_list");
        url = add_param_to_url(url, "list", list_name);
        url = add_param_to_url(url, "prefix", obj.value);
        execute_url(url, do_update_list, obj);
    } else
        obj.dataset = null;
}

function do_update_list(xmlhttp, obj)
{
    if (xmlhttp.response.substr(0, 5) === "Error"){
        alert(xmlhttp.response);
        return;
    }
    // obj.list.innerHTML = xmlhttp.response;
    obj.list.innerHTML = xmlhttp.response;
}

function add_element(element_name, table_name, url)
{
    let table_id = table_name + "_new";
    let params = get_form_params(table_id, false);
    let new_loc = "?operation=show_new_" + element_name + "&next_page=" + encodeURIComponent(url + "&params=" + params);
    window.location = new_loc;
}

function delete_items(collection_name, post_file, action_name)
{
    if (undefined == action_name) action_name = 'data_delete';
    let ids = get_selected(collection_name);
    if (ids.length == 0) {
        alert ("first select items");
        return;
    }
    let type = collection_name.substr(9); // Remove checkbox_
    let glue = '?'; if (post_file.indexOf('?') > -1) glue = '&';
    let url = post_file + glue + "operation=" + action_name + "&type=" + type + "&ids=" + ids;
    execute_url(url, location_reload);
}