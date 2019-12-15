<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/02/18
 * Time: 23:23
 */

require_once( "../r-shop_manager.php" );
require_once( FRESH_INCLUDES . '/niver/gui/inputs.php' );
require_once( FRESH_INCLUDES . "/niver/gui/sql_table.php" );
require_once( "gui.php" );

?>
    <script type="text/javascript" src="/niver/gui/client_tools.js"></script>
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
            var request = "account-post.php?operation=set_client_type" +
                "&id=" + id +
                "&type=" + type;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        function add_client_type() {
            document.getElementById('btn_save').disabled = true;

            var user_id = get_value_by_name("client_select");
            user_id = user_id.substr(0, user_id.indexOf(")"));

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
            var request = "account-post.php?operation=set_client_type" +
                "&id=" + user_id +
                "&type=" + type;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

<?php
print header_text( false, true );

print gui_header( 1, "שיוך לקוחות למחירון" );

$sql = "SELECT user_id, meta_value FROM wp_usermeta WHERE meta_key = '_client_type'";

$result = sql_query( $sql );

$table = array( array( "מזהה", "לקוח", "מחירון" ) );

while ( $row = sql_fetch_row( $result ) ) {
//    print $row[0] . " " . $row[1] . "<br/>";
	$user_id = $row[0];

	$client_type_id = sql_query_single_scalar( "SELECT id FROM im_client_types WHERE type = '" . $row[1] . "'" );
	array_push( $table, array(
		$user_id,
		get_user_name( $user_id ),
		gui_select_client_type( "select_type_" . $user_id,
			$client_type_id, "onchange=update_client_type(" . $user_id . ")" )
	) );
}

print gui_table_args( $table );

print gui_header( 2, "הוסף שיוך" );

print gui_table_args( array(
	array( "בחר לקוח", gui_select_client() ),
	array(
		"בחר מחירון",
		gui_select_client_type( "select_type_new", 1 )
	)
) );

print gui_button( "btn_save", "add_client_type()", "שמור" );
?>
<?php
print gui_header( 2, "מחירונים" );

print GuiTableContent("table", "SELECT rate, dry_rate AS מרווח, type AS 'שם מחירון' FROM im_client_types");
