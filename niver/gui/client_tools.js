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
    var element = document.getElementById(element_name);
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
                    if (element.value.indexOf(")") > -1)
                        return element.value.substring(0, element.value.indexOf(")"));
                    else
                        return element.value;
                }
                return element.value;

            case "TEXTAREA":
                // alert (element.value);
                return element.value;
            case "SELECT":
                var idx = element.selectedIndex;
                // alert(idx);
                return element.options[idx].value;
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

function select_all_toggle(selector, collection) {
    var is_on = document.getElementById(selector).checked;
    var collection = document.getElementsByClassName(collection);
    for (var i = 0; i < collection.length; i++) {
        collection[i].checked = is_on;
    }
}

// Limited use. The internal variable is not scoped correctly.
// Use only if one call at a time
function execute_url(url, finish_action) {
    xmlhttp3 = new XMLHttpRequest();
    xmlhttp3.onreadystatechange = function () {
        // Wait to get query result
        if (xmlhttp3.readyState == 4 && xmlhttp3.status == 200)  // Request finished
        {
            if (finish_action)
                finish_action(xmlhttp3);
        }
    }
    xmlhttp3.open("GET", url, true);
    xmlhttp3.send();
}

function disable_btn(id) {
    var btn = document.getElementById(id);
    if (btn) btn.disabled = true;
}

function enable_btn(id) {
    var btn = document.getElementById(id);
    if (btn) btn.disabled = false;
}
