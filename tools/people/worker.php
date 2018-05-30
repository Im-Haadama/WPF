<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 19:48
 */
require_once( '../r-staff.php' );
require_once( "../gui/inputs.php" );
print header_text();

?>
<script type="text/javascript" src="/agla/client_tools.js"></script>

<script>

    function update_display() {
        table = document.getElementById("list");

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                table.innerHTML = xmlhttp.response;
                document.getElementById("btn_add_time").disabled = false;
                document.getElementById("btn_delete").disabled = false;
            }
        }
        var request = "people-post.php?operation=display_all";
	    <? if ( isset( $_GET["month"] ) ) {
	    print "request = request + \"&month=" . $_GET["month"] . "\";";
    }?>
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function del_items() {
        document.getElementById("btn_delete").disabled = true;

        var collection = document.getElementsByClassName("hours_checkbox");
        var params = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                var id = collection[i].id.substr(3);
//                var name = get_value(table.rows[i+1].cells[1].firstChild);
//                var sel = document.getElementById("supplier_id");
//                var supplier_id = sel.options[sel.selectedIndex].value;

                params.push(id);
                //        alert(id);
            }
        }
//     document.getElementById("debug").innerHTML = "people-post.php?operation=delete&params=" + params;
        execute_url("people-post.php?operation=delete&params=" + params, update_display);

    }
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

</header>
<body onload="update_display()">

<?php

print gui_header( 1, "הוספת פעילות" );

$table = array();
if ( $role == 'hr' ) {
	array_push( $table, array( "בחר עובד", gui_select_worker() ) );
}
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
<div>
    <!--    פרויקט-->
    <!--    <select id="project">-->
    <!--		--><?php
	//		$sql    = "SELECT id, project_name FROM im_projects";
	//		$result = sql_query( $sql );
	//
	//		while ( $row = mysqli_fetch_row( $result ) ) {
	//			print "<option value=\"" . $row[0] . "\">" . $row[1] . "</option>";
	//		}
	//		?>
    <!--    </select><br/>-->
	<?php
	print gui_header( 2, "הוצאות נסיעה" );
	print gui_input( "traveling", "" ) . "<br/>";
	print gui_header( 2, "הוצאות נוספות/משלוחים" );
	print "תיאור";
	print gui_input( "extra_text", "" ) . "<br/>";
	print "סכום";
	print gui_input( "extra", "" ) . "<br/>";
	?>
    <br/>
    <button id="btn_add_time" onclick="add_item()">הוסף פעילות</button>
    <button id="btn_delete" onclick="del_items()">מחק פעילות</button>
</div>
<div id="debug"></div>
<?php
print gui_header( 1, "נתונים שהוזנו" );
?>

<table id="list" border="1">

</body>
</html>