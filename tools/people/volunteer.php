<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 19:48
 */

require_once( "../header.php" );
require_once( "../r-shop_manager.php" );
require_once( "../gui/inputs.php" );
?>

<html>
<header>
    <script type="text/javascript" src="/niver/gui/client_tools.js"></script>
    <script>

        function update_display() {
            table = document.getElementById("list");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table.innerHTML = xmlhttp.response;
                    // update_display();
                }
            }
            var request = "volunteer-post.php?operation=display";

			<?php
			// if (is_admin_user()) print "request = request + \"_all\";";
			if ( is_admin_user() ) {
				print "var user_id = get_value(document.getElementById(\"user_id\"));";
				print "request = request + \"&user_id=\" + user_id;";
			}
			?>
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function add_item() {
            var sel = document.getElementById("project");
            var id = sel.options[sel.selectedIndex].value;
            var start = get_value(document.getElementById("start_h"));
            var end = get_value(document.getElementById("end_h"));
            var date = get_value(document.getElementById("date"));

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    update_display();
                }
            }
            var request = "volunteer-post.php?operation=add_time&start=" + start + '&end=' + end +
                '&date=' + date + "&project=" + id + "&vol=1";
			<?php
			if ( is_admin_user() ) {
				print "var user_id = get_value(document.getElementById(\"user_id\"));";
				print "request = request + \"&user_id=\" + user_id;";
			}
			?>
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>

</header>
<body onload="update_display()">
<table id="list" border="1">

	<?php

	?>
	<?php
	print gui_header( 1, "נתוני התנדבות - עם האדמה", true );
	if ( is_admin_user() ) {
		print gui_select_table( "user_id", "i_people", 0, "onchange=\"update_display()\"", array(
			array(
				0,
				"כולם"
			)
		) );
	}
	print "<br/>";
	// "<input id=\"user_id\">"
	?>

    <div>הוסף שעות
        תאריך
        <input id="date" type="date" value="<?php echo date( 'Y-m-d' ); ?>">
        משעה
        <input id="start_h" type="time" value="09:00:00">
        עד שעה
        <input id="end_h" type="time" value="13:00:00">
        פרויקט
        <select id="project">
			<?php
			$sql = "SELECT id, project_name FROM im_projects";
			$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() . $sql );

			while ( $row = mysqli_fetch_row( $result ) ) {
				print "<option value=\"" . $row[0] . "\">" . $row[1] . "</option>";
			}
			?>
        </select>
        <button id="btn_add_time" onclick="add_item()">הוסף פעילות</button>
    </div>

</body>
</html>