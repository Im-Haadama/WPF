<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 13/03/17
 * Time: 23:29
 */
require_once( '../tools.php' );
require_once( '../gui/inputs.php' );

?>

    <script type="text/javascript" src="../client_tools.js"></script>
    <script>
        function done() {
            var collection = document.getElementsByClassName("user_chk");
            var user_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var user_id = collection[i].id.substr(4);
                    user_ids.push(user_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var http_text = xmlhttp.responseText.trim();
                    document.getElementById("logging").innerHTML = http_text;
                }
            }
            var request = "delivery-post.php?operation=save_legacy&ids=" + user_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function add_chef() {
            var user = get_value(document.getElementById("user"));
            if (user.length < 4) {
                alert("הכנס שם משתמש");
                return;
            }
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
            var request = "delivery-post.php?operation=add_chef&user=" + encodeURI(user) +
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
print header_text();

print "אנא בחר משלוחים לשבוע זה" . "<br/>";

$sql = "SELECT DISTINCT  user_id FROM wp_usermeta 
WHERE meta_key = 'legacy_user' AND meta_value=1";

$result = mysqli_query( $conn, $sql );

print "<table>";
while ( $row = mysqli_fetch_row( $result ) ) {
	print user_checkbox( $row[0] );
}
print "</table>";

print gui_button( "btn_done", "done()", "בצע" );

print '<div id="logging">';

function user_checkbox( $id ) {
	return gui_row( array(
		gui_checkbox( "chk_" . $id, "user_chk" ),
		get_user_name( $id )
	) );

}


print gui_header( 2, "הוסף לקוח חדש" );

print gui_table(
	array(
		array( "שם משתמש", gui_input( "user", "", "" ) ),
		array( "שם", gui_input( "name", "", "" ) ),
		array( "דואל", gui_input( "email", "", "" ) ),
		array( "כתובת", gui_input( "address", "", "" ) ),
		array( "ישוב", gui_input( "city", "", "" ) ),
		array( "טלפון", gui_input( "phone", "", "" ) ),
		array( "מיקוד", gui_input( "zip", "", "" ) )
	) );

print gui_button( "btn_add", "add_chef()", "הוסף" );