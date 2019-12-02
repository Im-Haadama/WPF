<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/05/16
 * Time: 19:48
 */
// ini_set( 'display_errors', 'on' );


if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once(ROOT_DIR . '/im-config.php');
require_once( ROOT_DIR . "/init.php" );

init();

$user_id = get_user_id();

if ($user_id == 1){
    error_reporting( E_ALL );
    ini_set( 'display_errors', 'on' );
} else {
    print "under construction...";
    return;
}

$user_id = 369; // get_user_id(true);

$wp_user = get_user_by( 'id', $user_id );
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
$args["script_files"] = "/niver/gui/client_tools.js";
// $project_list = sql_query_array_scalar("select project_id from im_working where user_id = " . $user_id);
print HeaderText($args);

print gui_header( 1, "הוספת פעילות" );

$table = array();
if ( $role == 'hr' ) {
	array_push( $table, array( "בחר עובד", gui_select_worker() ) );
}
array_push( $table, ( array( "תאריך", gui_input_date( "date", date( 'Y-m-d' ) ) ) ) );
array_push( $table, ( array(	"משעה",
	'<input id="start_h" type="time" value="09:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
) ) );
array_push( $table, ( array(
	"עד שעה",
	'<input id="end_h" type="time" value="13:00" pattern="([1]?[0-9]|2[0-3]):[0-5][0-9]">'
) ) );
$args["worker_id"] = $user_id;
array_push( $table, ( array( "פרויקט", gui_select_project("project", null, $args))));

print gui_table_args( $table );
?>
<div>
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
$month = get_param("month", false, date('Y-m'));
print gui_header( 1, "Entered data for month", true, true );
print  " " . $month . gui_br();
$a = explode( "-", $month ); $y = $a[0]; $m = $a[1];
print show_entry($user_id, $m, $y, $args);
print GuiHyperlink("previous month", add_to_url("month", date('Y-m', strtotime($month . '-1 -1 month'))));

?>
