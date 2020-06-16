function change_import(action_file, form_name)
{
    let selected = get_value_by_name("import_select");
    let upcsv = document.getElementById(form_name);
    if (selected > 0){
        let forms = document.getElementsByName("submit");
        forms.forEach(element => element.disabled = false);
        upcsv.action = action_file + '&selection=' + selected;
    }
    else
        upcsv.action = select_import_first;
}

function select_import_first()
{
    alert ("select import target first");
}

function wait_for_selection()
{
    let forms = document.getElementsByName("submit");
    forms.forEach(element => element.disabled = true);
}

function makolet_create_invoice(post_file, year_month)
{
    let request = post_file + "?operation=makolet_create_invoice&month=" + year_month;

    execute_url(request, location_reload);
}
