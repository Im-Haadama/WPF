<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 28/02/18
 * Time: 11:42
 */

//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( "../im_tools.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( ROOT_DIR . "/niver/gui/sql_table.php" );
require_once( "../business/business.php" );
require_once( "people.php" );

$role = "";

$operation = get_param( "operation" );


if ( isset( $operation ) ) {
	switch ( $operation ) {
		case "get_projects":
//            print "aa";
			$worker_id = get_param( "id" );
//            print "w=" . $worker_id . "<br/>";
        $args = array("edit" => true, "worker" => $worker_id);
			print gui_select_project( "select_project", 3, $args );

			return;
	}
	print table_content( "table", "select client_displayname(user_id) as עובד, date as תאריך, start_time as התחלה, end_time as סיום, " .
	                              " traveling as 'הוצאות נסיעה', expense as 'הוצאות', expense_text as 'תיאור הוצאה' " .
	                              " from im_working_hours " .
	                              " order by id desc " .
	                              " limit 10" );
	exit( 0 );
}

print header_text( false, true, true );

?>

<script type="text/javascript" src="/niver/gui/client_tools.js"></script>

<script>

    function worker_changed() {
        var worker_id = get_worker();
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                document.getElementById("project").innerHTML = xmlhttp.response;
            }
        }
        request = "entry.php?operation=get_projects&id=" + worker_id;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }

    function update_display() {
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                document.getElementById("last_info").innerHTML = xmlhttp.response;
                document.getElementById("btn_add_time").disabled = false;
            }
        }
        request = "entry.php?operation=get_last";
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }

    function get_worker() {
        return get_value_by_name("worker_select");
    }

    function add_item() {
        document.getElementById("btn_add_time").disabled = true;

        var sel = document.getElementById("project");
        var project_id = sel.options[sel.selectedIndex].value;
        var start = get_value(document.getElementById("start_h"));
        var end = get_value(document.getElementById("end_h"));
        var date = get_value(document.getElementById("date"));
        var traveling = get_value(document.getElementById("traveling"));
        var extra_text = get_value(document.getElementById("extra_text"));
        var extra = get_value(document.getElementById("extra"));
	    <?php if ( isset( $_GET["worker"] ) ) {
	    print 'var id = ' . $_GET["workder"] . '\n';
    } else {
	    print 'var id = get_worker();';
    }
	    ?>
        //    var worker_id = id.substr(0, id.indexOf(")"));

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
                if (xmlhttp.response.length) // Failed
                    alert(xmlhttp.response);
                update_display();
            }
        }
        var request = "people-post.php?operation=add_time&start=" + start + '&end=' + end +
            '&date=' + date + "&project=" + project_id + "&vol=0" + "&traveling=" + traveling +
            "&extra_text=" + encodeURI(extra_text) +
            "&user_id=" + id +
            "&extra=" + extra;

		<? if ( $role == 'hr' ) {
		print 'var user_name = get_value(document.getElementById("worker_select"));
;';
		print 'var worker_id = user_name.substr(0, user_name.indexOf(")"));
';
	    print 'request = request + "&user_id=" + worker_id;';
	}
		?>
        // document.getElementById("debug").innerHTML = request;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

</script>
<body onload="update_display()">
<?php
print header_text(false, true, true);

print gui_header( 1, "הזנת נתוני שכר" );

$table = array();
array_push( $table, array( "בחר עובד", gui_select_worker( "worker_select", null, "onchange=worker_changed()" ) ) );
array_push( $table, ( array( "תאריך", gui_input_date( "date", date( 'Y-m-d' ) ) ) ) );
array_push( $table, ( array(
"משעה",
'<input id="start_h" type="time" value="09:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
) ) );
array_push( $table, ( array(
"עד שעה",
'<input id="end_h" type="time" value="13:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
) ) );
// array_push( $table, ( array( "פרויקט", gui_select_table( "project", "im_projects", "3", "", "", "project_name" ) ) ) );
$args["edit"] = 1;
// TODO: Get default project from user history or something...
array_push( $table, ( array( "פרויקט", gui_select_project( "project",3, $args ) ) ) );

print gui_table_args( $table );


print gui_header( 2, "הוצאות נסיעה" );
print gui_input( "traveling", "" ) . "<br/>";
print gui_header( 2, "הוצאות נוספות/משלוחים" );
print "תיאור";
print gui_input( "extra_text", "" ) . "<br/>";
print "סכום";
print gui_input( "extra", "" ) . "<br/>";

?>

<button id="btn_add_time" onclick="add_item()">הוסף פעילות</button>

<table id="last_info">

</table>

</body>