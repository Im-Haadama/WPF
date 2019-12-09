function add_medicine()
{
    let user_id = get_value_by_name("user_id");

    if (! (user_id > 0)) {
        alert ("no user selected");
        return;
    }

    let url = '/medicine.php?operation=add_user&id=' + user_id;
    execute_url(url, action_back);
}