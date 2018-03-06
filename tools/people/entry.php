<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/02/18
 * Time: 11:42
 */

require_once( "../im_tools.php" );
require_once( "../gui/inputs.php" );

?>
<script>

    function add_item() {
        document.getElementById("btn_add_time").disabled = true;

        var sel = document.getElementById("project");
        var id = sel.options[sel.selectedIndex].value;
        var start = get_value(document.getElementById("start_h"));
        var end = get_value(document.getElementById("end_h"));
        var date = get_value(document.getElementById("date"));
        var traveling = get_value(document.getElementById("traveling"));
        var extra_text = get_value(document.getElementById("extra_text"));
        var extra = get_value(document.getElementById("extra"));

        if (traveling.length > 0 && !(parseInt(traveling) > 0)) {
            document.getElementById("btn_add_time").disabled = false;
            alert("רשום סכום הוצאות נסיעה");
            return;
        }

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                update_display();
            }
        }
        var request = "people-post.php?operation=add_time&start=" + start + '&end=' + end +
            '&date=' + date + "&project=" + id + "&vol=0" + "&traveling=" + traveling +
            "&extra_text=" + encodeURI(extra_text) +
            "&extra=" + extra;

		<? if ( $role == 'hr' ) {
		print 'var user_name = get_value(document.getElementById("worker_select"));
;';
		print 'var worker_id = user_name.substr(0, user_name.indexOf(")"));
';
		print 'request = request + "&worker_id=" + worker_id;';
	}
		?>
        // document.getElementById("debug").innerHTML = request;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

</script>

print header_text(false, true, true);

$table = array();
array_push( $table, array( "בחר עובד", gui_select_worker() ) );
array_push( $table, ( array( "תאריך", gui_input_date( "date", date( 'Y-m-d' ) ) ) ) );
array_push( $table, ( array(
"משעה",
'<input id="start_h" type="time" value="09:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
) ) );
array_push( $table, ( array(
"עד שעה",
'<input id="end_h" type="time" value="13:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
) ) );
array_push( $table, ( array( "פרויקט", gui_select_table( "project", "im_projects", "3", "", "", "project_name" ) ) ) );

print gui_table( $table );

?>

<button id="btn_add_time" onclick="add_item()">הוסף פעילות</button>

