<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/02/18
 * Time: 23:23
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-config.php');

//require_once( "../r-shop_manager.php" );
//require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );
//require_once( FRESH_INCLUDES . "/core/gui/sql_table.php" );
//require_once( "gui.php" );

?>
    <script type="text/javascript" src="/wp-content/plugins/flavor/includes/core/gui/client_tools.js"></script>
    <script type="text/javascript" src="/wp-content/plugins/flavor/includes/core/data/data.js"></script>
    <script>
        function update_client_type(id) {
            var type = get_value_by_name("select_type_" + id);

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    document.getElementById('btn_save').disabled = false;
                    location.reload();
                }
            }
            var request = "/wp-content/plugins/fresh/post.php?operation=set_client_type" +
                "&id=" + id +
                "&type=" + type;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        function add_client_type() {
            document.getElementById('btn_save').disabled = true;

            var user_id = get_value_by_name("client_select");

            var type = get_value_by_name("select_type_new");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    document.getElementById('btn_save').disabled = false;
                    location.reload();
                }
            }
            var request = "/wp-content/plugins/fresh/post.php?operation=set_client_type" +
                "&id=" + user_id +
                "&type=" + type;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

<?php
//print header_text( false, true );

print Core_Html::gui_header( 1, "שיוך לקוחות למחירון" );

$sql = "SELECT user_id, meta_value FROM wp_usermeta WHERE meta_key = '_client_type'";

$result = SqlQuery( $sql );

$table = array( array( "מזהה", "לקוח", "מחירון" ) );

while ( $row = SqlFetchRow( $result ) ) {
//    print $row[0] . " " . $row[1] . "<br/>";
	$user_id = $row[0];

	$client_type_id = SqlQuerySingleScalar( "SELECT id FROM im_client_types WHERE type = '" . $row[1] . "'" );
	array_push( $table, array(
		$user_id,
		GetUserName( $user_id ),
		gui_select_client_type( "select_type_" . $user_id,
			$client_type_id, "onchange=update_client_type(" . $user_id . ")" )
	) );
}

print Core_Html::gui_table_args( $table );

print Core_Html::gui_header( 2, "הוסף שיוך" );

$args = [];
$args["post_file"] = Fresh::getPost();
print Core_Html::gui_table_args( array(
	array( "בחר לקוח", gui_select_client("client_select", null, $args) ),
	array(
		"בחר מחירון",
		gui_select_client_type( "select_type_new", 1 )
	)
) );

print Core_Html::GuiButton( "btn_save", "שמור", array("action"=>"add_client_type()") );
print Core_Html::gui_header( 2, "מחירונים" );

print Core_Html::GuiTableContent("table", "SELECT id, rate, dry_rate AS מרווח, type AS 'שם מחירון' FROM im_client_types");

function gui_select_client_type( $id, $value, $events = null ) {
	$none = array( "id" => 0, "type" => "רגיל" );

	return Core_Html::gui_select_table( $id, "im_client_types", $value, $events, array( $none ), "type",
		null, true );
//		$sql_where );
}
