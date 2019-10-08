function delete_line()
{
    var collection = document.getElementsByClassName("hours_checkbox");
    var params = new Array();
    for (var i = 0; i < collection.length; i++) {
        if (collection[i].checked) {
            // var name = get_value(table.rows[i+1].cells[0].firstChild);
            var line_id = collection[i].id.substr(3);

            params.push(line_id);
        }
    }
    let request = "people-post.php?operation=delete&params=" + params;
    execute_url(request, location_reload);
}
