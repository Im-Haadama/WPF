/**
 * Created by agla on 07/05/16.
 */

function changed(field) {
    var subject = field.id;
    document.getElementById("chk_" + subject).checked = true;
}

function update_field(post_file, id, field_name, finish_action) {
    var value = get_value_by_name(field_name);
    var request = post_file + "?operation=update_field" +
        "&field_name=" + field_name +
        "&value=" + encodeURI(value) +
        "&id=" + id;

    execute_url(request, finish_action);
}

function get_select_text(element_name) {
    var element = document.getElementById(element_name);
    var idx = element.selectedIndex;
    // alert(idx);
    return element.options[idx].innerText;
}

function get_value_by_name(element_name) {
    var element = document.getElementById(element_name);
    return get_value(element);

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
