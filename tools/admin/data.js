
// Save entity from single row view - chk_ new fields.
function save_entity(table_name, id)
{
    if (!Number.isInteger(id)) {
        alert ("invalid id: " . id);
        return;
    }
    var operation = "/tools/admin/data.php?table_name=" + table_name + "&operation=update&id=" + id;
    var table = document.getElementById(table_name);
    var size = table.rows.length;
    for (var i = 0; i < size; i++){
        var name = table.rows[i].cells[1].innerText;
        if (get_value_by_name("chk_" + name)) {
            operation += "&" + name + "=" + get_value_by_name(name);
        }
    }
    // alert(operation);
    execute_url(operation, action_back);
}

function save_new(table_name)
{
    var operation = "/tools/admin/data.php?table_name=" + table_name + "&operation=new";
    var table = document.getElementById(table_name);
    if (! table || ! table.rows){
        alert("rows of table " + table_name + " not found");
        return false;
    }
    var size = table.rows.length;
    for (var i = 0; i < size; i++){
        var name = table.rows[i].cells[1].innerText;
        var val = get_value_by_name(name);
        if (get_value_by_name("chk_" + name)) {
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
