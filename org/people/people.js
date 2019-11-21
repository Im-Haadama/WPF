function delete_lines()
{
    let params = get_selected("working_days");
    let request = "people-post.php?operation=delete&params=" + params;
    execute_url(request, location_reload);
}
