/**
 * Created by agla on 07/05/16.
 */

function changed(field) {
    var subject = field.id;
    document.getElementById("chk_" + subject).checked = true;
}

// TODO:
// function check_input(element)
// {
//     return false;
// }
//
// function check_inputs(elements)
// {
//     elements.forEach(function(element) {
//         if (!check_input(element)) return false;
//     });
//     return true;
// }

function get_value(element) {
    if (element === null) {
        return 0;
    }
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
            return element.options[idx].value;
        case "LABEL":
            return element.textContent;
    }
    return element.nodeValue;
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