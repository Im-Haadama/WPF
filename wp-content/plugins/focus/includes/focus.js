// function filter_repeat_time()
// {
//     let repeat = get_value_by_name("select_repeat");
//     let url = window.location.href; // .split('?')[0];
//     execute_url(url + '?repeat=' + repeat);
// }

function add_member()
{
    let member = get_value_by_name("new_member");
    let team = get_value_by_name("team_id");
    execute_url(focus_post_url + "?operation=save_add_member&member=" + member + "&team=" + team, action_back);
}

function addSequenceTask(current)
{
    let next = current + 1;
    let table = document.getElementById("sequence_table");
    let row = table.insertRow();
    row.innerHTML = '<tr><td>task' + next + '</td><td><input id="task' + next + '" onchange=\'addSequenceTask(' + next + ')\'></td></tr>';
    document.getElementById("task" + next).focus();
}

function save_new_sequence()
{
    let project = get_value_by_name("project");
    let priority = get_value_by_name("priority");
    let request = focus_post_url + '?operation=new_sequence&project=' + project + "&priority=" + priority;
    for (let i = 1; i < document.getElementById("sequence_table").rows.length - 2; i ++){
        let text = get_value_by_name("task"+ i);
        request = request + '&task' + i + '=' + encodeURI(text);
    }
    execute_url(request, back_to_project);
}

function back_to_project(xmlhttp, project)
{
    if (check_result(xmlhttp))
        window.location = window.location.href + "&project=" + project;
}

function show_project(xmlhttp)
{
    let project = get_value_by_name("project_id");
    if (check_result(xmlhttp)) {
        let url = removeParam("operation", window.location.href);
        window.location = url + "&project=" + project;
    }
}

function add_project(table_name, url)
{
    let params = get_form_params(table_name, false);
    let new_loc = "?operation=show_new_project&next_page=" + encodeURIComponent(url + "&params=" + params);
    window.location = new_loc;
}

function project_add_worker(post_file, project_id)
{
    let worker_id = get_value_by_name("new_worker");
    execute_url(focus_post_url + "?operation=project_add_member&project_id=" + project_id +
                           "&user=" + worker_id, location_reload);
}

function project_remove_member(post_file, team_id)
{
    let ids = get_selected("workers");
    let operation = post_file + "?operation=project_remove_member&team_id=" + team_id +
        "&ids=" + ids;

    execute_url(operation, location_reload);
}

function add_to_company() {
    let email = get_value_by_name("email");
    let name = get_value_by_name("name");
    let company_id = get_value_by_name("company_id");
    let project_id = get_value_by_name("project_id");
    execute_url(focus_post_url + "?operation=add_to_company&email=" + encodeURI(email) + '&company_id=' + company_id
        + '&name=' + encodeURI(name) + '&project_id=' + project_id, action_back);
}

function team_remove_member(post_file, team_id)
{
    let ids = get_selected("workers");
    let operation = post_file + "?operation=team_remove_member&team_id=" + team_id +
        "&ids=" + ids;

    execute_url(operation, location_reload);
}

function team_add_member(post_file, team_id)
{
    let new_member = get_value_by_name("new_member");
    let operation = post_file + "?operation=team_add_member&team_id=" + team_id +
        "&new_member=" + new_member;

    execute_url(operation, location_reload);

}

function team_add_sender(post_file, team_id)
{
    let new_sender = get_value_by_name("new_sender");
    let operation = post_file + "?operation=team_add_sender&team_id=" + team_id +
        "&new_member=" + new_sender;

    execute_url(operation, location_reload);
}


function search_by_text()
{
    let search_box = document.getElementById("search_text");
    let value = get_value(search_box);
    if (value == "(search here)") {
        search_box.value = "";
        return;
    }
    if (value.length > 2) {
        document.getElementById("search_result").innerHTML = "Searching...";
        execute_url(focus_post_url + "?operation=search_by_text&text=" + value, update_search);
    }
}

function search_box_reset()
{
    let search_box = document.getElementById("search_text");
    let value = get_value(search_box);
    if (value == "") search_box.value = "(search here)";
}

function update_search(xmlhttp)
{
    let output = xmlhttp.response;

    let result_div = document.getElementById("search_result");
    result_div.innerHTML = output;
}

function company_add_worker(post, company)
{
    let worker = get_value_by_name("worker_email");
    let message = get_value_by_name("welcome_message");

    if (worker.length < 5 || worker.indexOf("@") === -1) {
        alert("enter valid worker email");
        return;
    }
    let url = post + "?operation=add_worker&company_id=" + company + "&worker_email=" + worker +
        "&message=" + encodeURI(message);
    execute_url(url, action_back);
}

function focus_create_user(post_file)
{
    let user_name = get_value_by_name('new_user_name');
    if (! (user_name.length > 4)) {
        alert("Please enter wanted user name longer than 4 letters");
        return;
    }
    let email = get_value_by_name('new_email');
    if (! email.length) {
        alert("Please enter your email");
        return;
    }

    let password = get_value_by_name("password");
    if (! (password.length > 7)) {
        alert ("Password must be longer the 7 characters" );
        return;
    }

    execute_url(post_file + '?operation=focus_create_user&user_name=' + encodeURI(user_name) + '&email=' + encodeURI(email) + '&password=' +
        encodeURI(password), navigate_to_login_page);
}

function navigate_to_login_page(xmlhttp)
{
    if (check_result(xmlhttp)) {
        location.href = "/wp-login.php?redirect_to=/focus";
    }
}