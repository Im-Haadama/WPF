function med_new_case(post_file)
{
    let client = get_value_by_name('new_user');

    execute_url(post_file + '?operation=med_create_case&client=' + client, load_page);
}

function med_add_symptom(post_file, med_user)
{
    let sym = get_value_by_name("med_sym");
    execute_url(post_file + '?operation=med_add_symptom', med_user, sym);
}