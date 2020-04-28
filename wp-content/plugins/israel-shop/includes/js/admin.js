function vat_add_category(post_file)
{
    let new_category = get_value_by_name("new_categ");

    let request = post_file + "?operation=vat_add_category&new_categ=" + new_category;

    execute_url(request, location_reload);
}

