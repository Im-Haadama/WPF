<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 19:48
 */

require_once( "../tools_wp_login.php" );
require_once( "../header.php" );
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
            var request = "volunteer-post.php?operation=display_all";
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
                '&date=' + date + "&project=" + id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }


    </script>

</header>
<body onload="update_display()">
<table id="list" border="1">

	<?php

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
            <option value="1">עולש</option>
            <option value="2">כפר הס</option>
            <option value="3">בית אריזה</option>
        </select>
        <button id="btn_add_time" onclick="add_item()">הוסף פעילות</button>
    </div>

</body>
</html>