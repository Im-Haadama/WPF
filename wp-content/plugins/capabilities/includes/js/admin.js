function toggle_role(post_file, user_id, role)
{
    execute_url(post_file + '?operation=toggle_role&user=' + user_id + '&role=' + role + '&set=' + get_value_by_name("chk_" + user_id + "_" + role));
}
