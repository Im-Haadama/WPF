<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 19:48
 */
// ini_set( 'display_errors', 'on' );

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

init();


if (! get_user_id())
{
    print im_translate("login first") . "<br/>";
//	print do_shortcode('[miniorange_social_login shape="longbuttonwithtext" theme="default" space="8" width="180" height="35" color="000000"]');
    print do_shortcode("[miniorange_social_login shape=\"square\" theme=\"default\" space=\"4\" size=\"35\"]");
    return;
}
$wp_user = get_user_by( 'id', get_user_id() );
$roles = $wp_user->roles;
//var_dump($roles);
if ( isset( $roles ) and count( array_intersect( array( "hr" ), $roles ) ) >= 1 ) {
	$role = 'hr';
} else {
	$role = 'staff';
}

require_once( ROOT_DIR . "/niver/gui/inputs.php" );
$args = [];
$args["greeting"] = true;
print HeaderText($args);

?>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>

<script>

    function do_update(xmlhttp)
    {
        let table = document.getElementById("list");
        table.innerHTML = xmlhttp.response;
        document.getElementById("btn_add_time").disabled = false;
        document.getElementById("btn_delete").disabled = false;
    }

    function update_display()
    {
        let request = "people-post.php?operation=show_all";
	    <? if ( isset( $_GET["month"] ) ) {	    print "request = request + \"&month=" . $_GET["month"] . "\";";    }?>
        execute_url(request, do_update);
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

        let request = "people-post.php?operation=add_time&start=" + start + '&end=' + end +
            '&date=' + date + "&project=" + id + "&vol=0" + "&traveling=" + traveling +
            "&extra_text=" + encodeURI(extra_text) +
            "&extra=" + extra;

        execute_url(request, update_display);
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

print gui_table_args( $table );
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