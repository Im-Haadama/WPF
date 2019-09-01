
// Save entity from single row view - chk_ new fields.
function save_entity(table_name, id)
{
    if (!Number.isInteger(id)) {
        alert ("invalid id: " . id);
        return;
    }
    let operation = "/tools/admin/data.php?table_name=" + table_name + "&operation=update&id=" + id;
    let table = document.getElementById(table_name);
    let size = table.rows.length;
    for (let i = 0; i < size; i++){
        let chk_id = table.rows[i].cells[0].firstElementChild.id;
        if (get_value_by_name(chk_id)) {
            let name = chk_id.substr(4);
            operation += "&" + name + "=" + encodeURIComponent(get_value_by_name(name + "_" + id));
        }
    }
    // alert(operation);
    execute_url(operation, action_back);
}

function save_new(table_name)
{
    let operation = "/tools/admin/data.php?table_name=" + table_name + "&operation=new";
    let table = document.getElementById(table_name);
    if (! table || ! table.rows){
        alert("rows of table " + table_name + " not found");
        return false;
    }
    let size = table.rows.length;
    for (let i = 1; i < size; i++){
        let name = table.rows[i].cells[1].innerText;
        if (get_value_by_name("chk_" + name)){
            let val = get_value_by_name(name.substr(0, 3) + "_");
            operation += "&" + name + "=" + val;
        }
    }
    // alert(operation);
    execute_url(operation, action_back);
}

function check_update(xmlhttp)
{
    if (xmlhttp.response !== "done")
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
    let request = post_file + "?operation=update" +
        "&table_name=" + table_name +
        "&" + field_name + '=' +
        encodeURI(value) +
        "&id=" + id;

    execute_url(request, finish_action);
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
