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
    execute_url("/focus/focus-post.php?operation=save_add_member&member=" + member + "&team=" + team, action_back);
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
    let request = '/focus/focus-post.php?operation=new_sequence&project=' + project + "&priority=" + priority;
    for (let i = 1; i < document.getElementById("sequence_table").rows.length - 2; i ++){
        let text = get_value_by_name("task"+ i);
        request = request + '&task' + i + '=' + encodeURI(text);
    }
    execute_url(request, back_to_project);
}

function back_to_project(xmlhttp, project)
{
    if (xmlhttp.response === "done")
        window.location = window.location.href + "&project=" + project;
    else
        alert (xmlhttp.response);
}

function show_project(xmlhttp)
{
    let project = get_value_by_name("project_id");
    if (xmlhttp.response === "done" || xmlhttp.response > 0) {
        let url = removeParam("operation", window.location.href);
        window.location = url + "&project=" + project;
    }
    else
    alert (xmlhttp.response);
}

function add_project(table_name, url)
{
    let params = get_form_params(table_name, false);
    let new_loc = "?operation=show_new_project&next_page=" + encodeURIComponent(url + "&params=" + params);
    window.location = new_loc;
}

function add_to_company() {
    let email = get_value_by_name("email");
    let name = get_value_by_name("name");
    let company_id = get_value_by_name("company_id");
    let project_id = get_value_by_name("project_id");
    execute_url("/focus/focus-post.php?operation=add_to_company&email=" + encodeURI(email) + '&company_id=' + company_id
        + '&name=' + encodeURI(name) + '&project_id=' + project_id, action_back);
}