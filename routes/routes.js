function update_path_preq(point)
{
    let mission_id = get_value_by_name("mission_id");
    let preq = get_value_by_name(point);
    let request = "/routes/routes-post.php?operation=update_mission_preq&id=" + mission_id +
        "&point=" + encodeURI(point) + "&preq=" + encodeURI(preq);
    execute_url(request, refresh);
}