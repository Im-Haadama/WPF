/*
 * Copyright (c) 2021. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

function company_add_worker(post_file, company)
{
    let url = add_param_to_url(post_file, "operation", "company_add_worker");
    url = add_param_to_url(url, "company", company);
    url = add_param_to_url(url, "worker_email", get_value_by_name("worker_email"));
    // url = add_param_to_url(url, "user", get_value_by_name("user_to_add"));
    execute_url(url, success_message);
}

function company_remove_worker(post_file, company)
{
    let url = add_param_to_url(post_file, "operation", "company_remove_worker");
    url = add_param_to_url(url, "company", company);
    let to_remove = get_selected("checkbox_company_workers");
    url = add_param_to_url(url, "users", to_remove.toString());
    execute_url(url, location_reload);
}