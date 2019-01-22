<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 09/05/16
 * Time: 10:32
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
            var sel = document.getElementById("sender_id");
            var sender = sel.options[sel.selectedIndex].value;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    location.reload();
                }
            }
            var request = "driver-post.php?operation=add_item&date=" + date + "&quantity=" + quantity +
                "&sender=" + sender;
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
$data .= "<tr><td>תאריך</td><td>כמות</td><td>משלוח</td></tr>";

$sql = "SELECT date, quantity, sender FROM im_driver_deliveries WHERE user_id = " . get_user_id() . " ORDER BY 1 DESC";

$result = sql_query( $sql );

while ( $row = mysqli_fetch_row( $result ) ) {

	$line = "<tr>";

	$line .= "<td>" . $row[0] . "</td>";
	$line .= "<td>" . $row[1] . "</td>";
	$line .= "<td>" . sender_name( $row[2] ) . "</td>";

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
    <select id="sender_id"" >
    <option value="1">עם האדמה</option>
    <option value="2">מכולת</option>
    </select>


    <button id="btn_add_item" onclick="add_item()">הוסף שילוחים</button>
</div>

</body>
</html>