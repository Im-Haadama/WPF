<?php
/*
 * Created May 10 2021 20:00
 * Plugin Name: Random user
 * Plugin URI: https://e-fresh.co.il
 * Description: base class for wpf plugins
 * Version: 1.0
 * Author: agla
 * Author URI: https://e-fresh.co.il
 * Text Domain: im-haadama
 *
 * @package Fresh
 *
 */


add_action('init', 'init_random_users');

function init_random_users()
{
	add_shortcode('random_user', 'random_user'); // [random_user question="can I help you?"]
}

function random_user($atts)
{
	$data = file_get_contents("https://randomuser.me/api");
	if (! $data) {
		print "can't get random user data. Contact support!";
		return;
	}

	$user = json_decode($data);

	if ( ! is_array($user->results)) {
        print "Bad response from randomuser.me. Contact support!";
        return;
    }

	$user_info = $user->results[0];
//	var_dump($user_info);

	$email = $user_info->email;
	$name = $user_info->name->first . " " . $user_info->name->last;
//	dd($name);

	$question = "Can Appdome help me me with?";
	if (isset ($atts["question"])) $question = $atts["question"];

	?>

        <div>
<header>Ask an expert</header>
<table>
	<tr><td>Email</td>
		<td><input id="email" value="<?php print $email;?>"></td>
	</tr>
	<tr>
		<td>Name</td>
		<td><input id="name" value="<?php print $name; ?>"></td>
	</tr>
	<tr>
		<td>Question</td>
		<td><input id="question" value="<?php print $question; ?>"></td>
	</tr>
<div id="loader" class="loader" style="display: none"></div>
    <style>
        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</table>

    <button id="btn_send" onclick="random_user_submit()">Get the answer</button>
        </div>
    <script>
        function get_value_by_name(element_name) {
            let element = document.getElementById(element_name);
            if (element)
                return get_value(element);
            return null;
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

        function random_user_submit()
        {
            let email = get_value_by_name("email");
            let name = get_value_by_name("name");
            let question = get_value_by_name("question");

            let command = '<?php print plugin_dir_url(__FILE__) . 'post.php' ?>' +
            '?operation=save_random' +
                '&name=' + encodeURI(name) +
                '&email=' + encodeURI(email) +
                '&question=' + encodeURI(question);

            execute_url(command, success_message);
        }

        function check_result(xmlhttp)
        {
            let loader = document.getElementById("loader");

            loader.style = 'display: none';

            return ! report_error(xmlhttp.response);
        }

        function success_message(xmlhttp)
        {
            if (check_result(xmlhttp))
                alert("Success");
        }

        function report_error(response)
        {
            if (response.toLowerCase().indexOf("failed:") !== -1 ||
                (response.toLowerCase().indexOf("error:") !== -1)){
                alert (response);
                return true;
            }
            return false;
        }

        function execute_url(url, finish_action, obj, xml) {
            let xhp = new XMLHttpRequest();
            let loader = document.getElementById("loader");

            loader.style = 'display: block';

            if (xml) xhp.overrideMimeType('text/xml');

            xhp.onreadystatechange = function () {
                // Wait to get query result
                if (xhp.readyState == 4 && xhp.status == 200)  // Request finished
                {
                    if (finish_action)
                        return finish_action(xhp, obj);

                    else report_error(xhp.response);
                }
                if (xhp.readyState == 4 && xhp.status == 500)  // Request finished
                {
                    alert("Server error. Contact support");
                    return false;
                }
            }
            xhp.open("GET", url, true);
            xhp.send();
        }
    </script>
	<?php
}

