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

