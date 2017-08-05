<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/05/16
 * Time: 11:25
 */

require_once( "../header.php" );
require_once( "people.php" );
?>

<html>

<header>

    <script>

        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }


        function add_item() {
            var quantity = get_value(document.getElementById("quantity"));
            var date = get_value(document.getElementById("date"));
            var driver_id = get_value(document.getElementById("driver_id"));
            var sel = document.getElementById("sender_id");
            var sender_id = sel.options[sel.selectedIndex].value;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    // update_display();
                }
            }
            var request = "driver-post.php?operation=add_item&driver_id=" + driver_id + "&date=" + date + "&quantity=" + quantity +
                "&sender=" + sender_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function set_defaults() {
        }
    </script>

</header>
<body onload="set_defaults()">
<?php


$data = "<table>";
$data .= "<tr><td>תאריך</td><td>כמות</td><td>נהג</td><td>משלוח</td></tr>";

$sql = "SELECT date, quantity, user_id, sender FROM im_driver_deliveries ORDER BY 1 DESC";

$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

while ( $row = mysql_fetch_row( $export ) ) {

	$line = "<tr>";

	$line .= "<td>" . $row[0] . "</td>";
	$line .= "<td>" . $row[1] . "</td>";
	$line .= "<td>" . get_customer_name( $row[2] ) . "</td>";
	$line .= "<td>";

	$line .= sender_name( $row[2] );
	$line .= "</td>";

	$line .= "</tr>";

	$data .= $line;
}

$data .= "</table>";

print $data;
?>

<div>הוסף שילוחים
    תאריך
    <input id="date" type="date" value="<?php echo date( 'Y-m-d' ); ?>">
    כמות
    <input id="quantity">
    נהג
    <input id="driver_id">
    משלח
    <select id="sender_id">
        <option value="1">עם האדמה</option>
        <option value="2">המכולת האורגנית</option>
    </select>

    <button id="btn_add_item" onclick="add_item()">הוסף שילוחים</button>
</div>

</body>
</html>