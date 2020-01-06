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
                            if (val === list[i].value) return list[i].dataset.id;
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

// Limited use. The internal variable is not scoped correctly.
// Use only if one call at a time
function execute_url(url, finish_action, obj) {
    let xmlhttp3 = new XMLHttpRequest();
    xmlhttp3.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp3.readyState == 4 && xmlhttp3.status == 200)  // Request finished
        {
            if (finish_action)
                return finish_action(xmlhttp3, obj);

            else if (xmlhttp3.response.substr(0, 4) !== "done") alert (xmlhttp3.response);
        }
    }
    // xmlhttp3.onloadend = function(){
    //     alert("Can't load " + url);
    // }
    xmlhttp3.open("GET", url, true);
    xmlhttp3.send();
}

// Use this when the delete button is outside the table and the lines are indicated by the checkbox class
function action_hide_rows(xmlhttp, checkbox_class)
{
    if (xmlhttp.response === "done"){
        var collection = document.getElementsByClassName(checkbox_class);
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                collection[i].parentElement.parentElement.style.display = 'none';
            }
        }
            // btn.parentElement.parentElement.style.display = 'none';
    }

    else
        alert (xmlhttp.response);
}

// Use this when the delete button is inline.
function action_hide_row(xmlhttp, btn)
{
    if (xmlhttp.response === "done"){
        if (btn[0] != 'undefined')
            for (let i=0; i < btn.length; i++)
                btn[i].parentElement.parentElement.style.display = 'none';
        else
            btn.parentElement.parentElement.style.display = 'none';
    }
    else
        alert (xmlhttp.response);
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
    if (xmlhttp.response.substr(0, 4) === "done")
        location.reload();
    else
        alert (xmlhttp.response);
}

// Good for start task. If URL received as response, the URL will be loaded.
// Otherwise the page will be reloaded.
function load_page(xmlhttp)
{
    if (xmlhttp.response.substr(0, 4) !== "done") {
        alert (xmlhttp.response);
        return false;
    }
    let url = xmlhttp.response.substr(5);
    if (url.length)
        window.location = url;
    else
        location.reload();
}

function get_selected(collection_name)
{
    let param = new Array();
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
    if (xmlhttp.response.substr(0, 4) === "done") {
        let operation = document.referrer;
        let data = xmlhttp.response.substr(5); // After .
        if (data.length) {
            if (operation.indexOf('?') === -1) operation += "?"; // Make sure this is ?
            else operation += '&';

            operation += data; // The data coming back from post action will be added to the url of the calling.
        }
        location.replace(operation );
    }
    else
        alert (xmlhttp.response);
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

function selectTab(event, selected, tab_class)
{
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName(tab_class);
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(selected).style.display = "block";
    event.currentTarget.className += " active";
}