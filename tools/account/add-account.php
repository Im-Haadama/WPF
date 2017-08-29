<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/08/17
 * Time: 06:45
 */
require_once( "../gui/inputs.php" );
require_once( "../im_tools.php" );

?>
<script type="text/javascript" src="../client_tools.js"></script>
<script>
    function check_email() {
        var email = get_value(document.getElementById("email"));
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                if (xmlhttp.response.substr(0, 6) == "exists")
                    document.getElementById("user").innerHTML = "יוזר קיים!";
                else
                    document.getElementById("user").innerHTML = xmlhttp.response;
            }
        }
        var request = "account-post.php?operation=check_email&email=" + email;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }
    function add_user() {
        var name = get_value(document.getElementById("name"));
        if (name.length < 5) {
            alert("הכנס שם");
            return;
        }
        var email = get_value(document.getElementById("email"));
        if (email.length < 5) {
            alert("הכנס email");
            return;
        }
        check_email();
        var user = get_value(document.getElementById("user"));
        if (user.substr(0, 4) == "יוזר") {
            alert("יוזר עם email כזה קיים");
            return;
        }
        var address = get_value(document.getElementById("address"));
        if (address.length < 6) {
            alert("הכנס כתובת");
            return;
        }
        var city = get_value(document.getElementById("city"));
        if (city.length < 5) {
            alert("הכנס ישוב");
            return;
        }
        var phone = get_value(document.getElementById("phone"));
        if (phone.length < 10) {
            alert("הכנס טלפון");
            return;
        }
        var zip = get_value(document.getElementById("zip"));
        if (zip.length < 5) {
            alert("הכנס מיקוד");
            return;
        }
        var request = "account-post.php?operation=add_user&user=" + encodeURI(user) +
            '&name=' + name +
            '&email=' + encodeURI(email) +
            '&address=' + encodeURI(address) +
            '&city=' + city +
            '&phone=' + phone +
            '&zip=' + zip;
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                var http_text = xmlhttp.responseText.trim();
                document.getElementById("logging").innerHTML = http_text;
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
</script>

<?php
print header_text( true );
print gui_header( 2, "הוסף לקוח חדש" );

print gui_table(
	array(
		array( "שם משתמש", gui_lable( "user", "אוטומטי" ) ),
		array( "שם", gui_input( "name", "", "" ) ),
		array( "דואל", gui_input( "email", "", array( "onchange=\"check_email()\"" ) ) ),
		array( "כתובת", gui_input( "address", "", "" ) ),
		array( "ישוב", gui_input( "city", "", "" ) ),
		array( "טלפון", gui_input( "phone", "", "" ) ),
		array( "מיקוד", gui_input( "zip", "", "" ) )
	) );

print gui_button( "btn_add", "add_user()", "הוסף" );

?>

<div id="logging"></div>
