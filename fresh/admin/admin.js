function add_member()
{
    let member = get_value_by_name("new_member");
    let team = get_value_by_name("team_id");
    execute_url("admin-post.php?operation=do_add_member&member=" + member + "&team=" + team, check_update);
}