function company_add(post_file, company)
{
    let url = add_param_to_url(post_file, "operation", "company_add_worker");
    url = add_param_to_url(url, "company", company);
    url = add_param_to_url(url, "user", get_value_by_name("user_to_add"));
    execute_url(url, success_message);
}