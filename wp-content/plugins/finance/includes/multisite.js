function multisite_connect(post_file)
{
    let server = get_value_by_name("server");
    if (! server.length) {
        alert("Please provide server name");
        return;
    }

    let user = get_value_by_name("user");
    if (! user.length) {
        alert("Please provide user name");
        return;
    }

    let password = get_value_by_name("password");
    if (! password.length) {
        alert("Please provide password");
        return;
    }

    execute_url(post_file + '?operation=multisite_connect&server='+encodeURIComponent(server) + '&user=' + user + '&password=' + encodeURI(password),
        reload_location);
}