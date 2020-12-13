/**
/**
 * Created by agla on 07/05/16.
 */

function changed(field) {
    var subject = field.id;

    // in suppliy-get, the subject is quantity_id
    let s = subject.indexOf("_"); if (s) subject = subject.substr(s + 1);

    document.getElementById("chk_" + subject).checked = true;
}

// In grid of rows, the pass argument should be the row ID.
// In single row edit the passed argument should be the fieid id.
function changed_field(id)
{
    var f = document.getElementById("chk_" + id);
    if (f)
        f.checked = true;
    else
        f = 'aaa'; // hook for breatpoint
}

function get_select_text(element_name) {
    var element = document.getElementById(element_name);
    var idx = element.selectedIndex;
    // alert(idx);
    return element.options[idx].innerText;
}

function get_value_by_name(element_name) {
    let element = document.getElementById(element_name);
    if (element)
        return get_value(element);
    return null;
}

function reset_message(message) {
    var log = document.getElementById("log");

    log.innerHTML = "";
    // alert(message);
}

function add_message(message) {
    var log = document.getElementById("log");

    if (log)
        log.innerHTML += message;
    else
        alert(message);
    // alert(message);
}

function get_value(element) {
    if (element === null) {
        return 0;
    }
    if (element.tagName)
        switch (element.tagName) {
            case "INPUT":
                if (element.type == "checkbox") {
                    if (element.checked) return 1;
                    return 0;
                    // if (element.checke == "on") return 1;
                    // return 0;
                }
                if (element.list)
                {
                    // if (element.value.indexOf(")") > -1)
                    //     return element.value.substring(0, element.value.indexOf(")"));
                    // else {
                    let val = element.value;
                    let list = element.list.options;
                    if (list === undefined) list = element.list.firstElementChild.options; // for auto list. the post datalist is child of the datalist.
                    if (list){
                        for (let i = 0; i < list.length; i++)
                            if (val === list[i].value)
                                return list[i].dataset.id;
                        return element.value;
                    }
                }
                return element.value;

            case "TEXTAREA":
                // alert (element.value);
                return element.value;
            case "SELECT":
                if (element.multiple) {
                    let result = "";
                    for ( let i = 0, l = element.options.length, o; i < l; i++ ) {
                        if (element.options[i].selected) {
                            if (result.length) result += ":";
                            result += element.options[i].value;
                        }
                    }

                    return result;
                }
                // alert(idx);
                return element.options[element.selectedIndex].value;
            case "LABEL":
                return element.textContent;
            case "TD":
                var e = element.firstElementChild;
                if (e)
                    return get_value(e);
                else // text
                    return element.innerHTML;
            case "DIV":
                return element.innerHTML;

            case "A":
                return element.text;
        }
    else
        return element;
    return element.nodeValue;
}

function select_all_toggle(selector, collection_name) {
    // let is_on = document.getElementById(selector).checked;
    let is_on = selector.checked;
    let collection = document.getElementsByClassName(collection_name);
    for (let i = 0; i < collection.length; i++) {
        collection[i].checked = is_on;
    }
}

function data_set_active(post_file, id)
{
    let value = get_value_by_name(id);
    let url = add_param_to_url(post_file, "operation", "data_set_active");
    url = add_param_to_url(url, "value", value);
    execute_url(url, load_page);
}

function execute_url(url, finish_action, obj) {
    let xhp = new XMLHttpRequest();
    xhp.onreadystatechange = function () {
        // Wait to get query result
        if (xhp.readyState == 4 && xhp.status == 200)  // Request finished
        {
            if (finish_action)
                return finish_action(xhp, obj);

            else report_error(xhp.response);
        }
    }
    xhp.open("GET", url, true);
    xhp.send();
}

function execute_url_post(url, post, finish_action)
{
    let xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(post);
    xhr.onload = function() {
        if (finish_action)
            return finish_action(xhr);

        else report_error(xhr.response);
    }
}
// Use this when the delete button is outside the table and the lines are indicated by the checkbox class
function action_hide_rows(xmlhttp, checkbox_class)
{
    if (check_result(xmlhttp)) {
        var collection = document.getElementsByClassName(checkbox_class);
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                collection[i].parentElement.parentElement.style.display = 'none';
            }
        }
    }
}

function check_result(xmlhttp)
{
    return ! report_error(xmlhttp.response);
}

function report_error(response)
{
    if (response.indexOf("failed") !== -1 ||
        (response.indexOf("Error") !== -1)){
        alert (response);
        return true;
    }
    return false;
}

// Use this when the delete button is inline.
function action_hide_row(xmlhttp, btn)
{
    if (check_result(xmlhttp)){
        let the_button;
        if (typeof (btn[0]) != 'undefined') {
            the_button = btn[0];
        } else {
            the_button = btn;
        }
        if (the_button.parentElement.parentElement.parentElement.rows.length === 2) location.reload();
        else
            btn.parentElement.parentElement.style.display = 'none';
    }
}

function disable_btn(id) {
    var btn = document.getElementById(id);
    if (btn) btn.disabled = true;
}

function enable_btn(id) {
    var btn = document.getElementById(id);
    if (btn) btn.disabled = false;
}

function location_reload(xmlhttp)
{
    if (check_result(xmlhttp))
        location.reload();
}

// Good for start task. If URL received as response, the URL will be loaded.
// Otherwise the page will be reloaded.
function load_page(xmlhttp)
{
    if (check_result(xmlhttp)) {
        let url = xmlhttp.response;
        if (url.length)
            window.location = url;
        else
            location.reload();
    }
}

function get_selected(collection_name)
{
    let param = [];
    let collection = document.getElementsByClassName(collection_name);
    for (let i = 0; i < collection.length; i++) {
        if (collection[i].checked) param.push(collection[i].id.substr(4));
    }
    return param;
}

function removeParam(key, sourceURL) {
    var rtn = sourceURL.split("?")[0],
        param,
        params_arr = [],
        queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
    if (queryString !== "") {
        params_arr = queryString.split("&");
        for (var i = params_arr.length - 1; i >= 0; i -= 1) {
            param = params_arr[i].split("=")[0];
            if (param === key) {
                params_arr.splice(i, 1);
            }
        }
        rtn = rtn + "?" + params_arr.join("&");
    }
    return rtn;
}

function action_back(xmlhttp)
{
    if (check_result(xmlhttp)) {
        let operation = document.referrer;
        let data = xmlhttp.response.substr(5); // After .
        if (data.length) {
            if (operation.indexOf('?') === -1) operation += "?"; // Make sure this is ?
            else operation += '&';

            operation += data; // The data coming back from post action will be added to the url of the calling.
        }
        location.replace(operation );
    }
}

function show_menu(menu_name)
{
    document.getElementById(menu_name).classList.toggle("show");
    // window.onclick = function(event) {
    //     if (!event.target.matches(menu_name)) {
    //         let dropdowns = document.getElementsByClassName("dropdown-content");
    // //         let i;
    // //         for (i = 0; i < dropdowns.length; i++) {
    // //             var openDropdown = dropdowns[i];
    // //             if (openDropdown.classList.contains('show')) {
    // //                 openDropdown.classList.remove('show');
    // //             }
    // //         }
    //     }
    // }
}

function selectTab(event, selected, tab_class, tab_links_class)
{
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName(tab_class);
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName(tab_links_class);
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(selected).style.display = "block";
    event.currentTarget.className += " active";
}

function success_message(xmlhttp)
{
    if (check_result(xmlhttp))
        alert("Success");
}

function fail_message(xmlhttp)
{
    check_result(xmlhttp);
}

function log_message(xmlhttp)
{
    log = document.getElementById("log");
    if (log) log += xmlhttp.response;
}


function add_param_to_url(url, param, value)
{
    let base = url;
    // Lets search &param = or ?param=
    let has = url.indexOf("&" + param);
    if (! (has > 0))
        has = url.indexOf("?" + param);
    if (has > 0) { // if found, remove them
        base = url.substr(0, has); // Take before part
        // If there more params after, add them
        if (url.length > (has + param.length))
            base += url.substr(has + url.substr(has+1).indexOf("&") + 1);
    }
    if (base.indexOf('?') === -1) base += '?';
    else base += '&';
    return base + param + "=" + value;
}

function import_set_action(action)
{
    document.upload_csv.action = action;
}

function show_response(xmlhttp, obj)
{
    if (check_result(xmlhttp)) {
        obj.innerHTML += xmlhttp.response;
    } else {
        alert(xmlhttp.response + ':' + xmlhttp.responseURL);
    }
}

function show_modal(div, blur_div)
{
    if (typeof(div) == 'string')
        div = document.getElementById(div);
    div.style.display = "block";
    // Blur the body
    // document.getElementsByTagName("body").className = "is-blurred";
    if (undefined != blur_div)
        blur_div.className = "is-blurred";
}
function moveNextRow() {
    let me = document.getElementById(event.target.id);
    if (event.which === 13) {
        let next_row = me.parentElement.nextElementSibling;
        if (null === next_row) return;
        if (undefined !== next_row) {
            let td = next_row.cells[me.cellIndex];
            if (undefined !== td) {
                let input = td.firstElementChild;
                if (undefined !== input) {
                    input.focus();
                    input.select();
                }

            }
            // event.stopPropagation();
        }
    }
}

function next_page(xmlhttp, page) {
    if (xmlhttp.response.indexOf("failed") === -1 ) {
        let new_id = xmlhttp.response;
        window.location = add_param_to_url(page, 'new', new_id);
    }  else alert(xmlhttp.response);
}
