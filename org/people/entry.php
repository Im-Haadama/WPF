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
require_once( ROOT_DIR . "/org/business/business.php" );
require_once( "people.php" );
require_once( TOOLS_DIR . "/gui.php" );

require_once( ROOT_DIR . "/init.php" );

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
	$args = array();
	$args["header_f.poields"] = array("id", "worker", "date", "Start time", "End time", "Traveling expense", "Other expense", "Expense details", "Comments");
	// $args["id_field"] = "worker";
	print GuiTableContent( "table", "select id, client_displayname(user_id) as worker, date, start_time, end_time, " .
	                              " traveling, expense, expense_text, comment " .
	                              " from im_working_hours " .
	                              " order by id desc " .
	                              " limit 10", $args );
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
                let date = get_value_by_name("date");
                let _date = new Date(date);
                let now = new Date();
                if (date.length > 4 && (_date <= now)){
                    enable_btn("btn_add_time");
                    enable_btn("btn_add_sick_leave");
                }
                else {
                    disable_btn("btn_add_time");
                    disable_btn("btn_add_sick_leave");
                }

            }
        }
        request = "entry.php?operation=get_last";
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }

    function get_worker() {
        return get_value_by_name("worker_select");
    }

    function add_sick_leave()
    {
        disable_btn("btn_add_time");
        disable_btn("btn_add_sick_time");
        let sel = document.getElementById("project");
        let project_id = sel.options[sel.selectedIndex].value;
        let date = get_value(document.getElementById("date"));
	    <?php if ( isset( $_GET["worker"] ) ) {
	    print 'var id = ' . $_GET["workder"] . '\n';
    } else {
	    print 'var id = get_worker();';
    }
	    ?>
        let request = "people-post.php?operation=add_sick_leave" +
            '&date=' + date + "&project=" + project_id +
            "&user_id=" + id;
        let xmlhttp = new XMLHttpRequest();

        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                if (xmlhttp.response.length) // Failed
                    alert(xmlhttp.response);
                update_display();
            }
        }


        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
    function add_item() {
        disable_btn("btn_add_time");
        disable_btn("btn_add_sick_time");

        let sel = document.getElementById("project");
        let project_id = sel.options[sel.selectedIndex].value;
        let start = get_value(document.getElementById("start_h"));
        let end = get_value(document.getElementById("end_h"));
        let date = get_value(document.getElementById("date"));
        let traveling = get_value(document.getElementById("traveling"));
        let extra_text = get_value(document.getElementById("extra_text"));
        let extra = get_value(document.getElementById("extra"));
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

        let xmlhttp = new XMLHttpRequest();
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

print gui_header( 1, "הזנת נתוני שכר עם האדמה" );

$table = array();
$args = array("events" => "onchange=worker_changed()");
$args["companies"] = array(1);
$args["worker"] = 1;
array_push( $table, array( "בחר עובד", gui_select_worker( "worker_select", get_user_id(), $args)));
$date = date( 'Y-m-d' );
array_push( $table,  array( "תאריך", gui_input_date( "date",  null, $date, 'onchange=update_display()') )  );
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

print gui_button("btn_add_time", "add_item()", "Add activity", true);

print gui_button("btn_add_sick_leave", "add_sick_leave()", "Add sick leave", true);

?>



<table id="last_info">

</table>

</body>