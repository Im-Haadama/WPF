<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/06/16
 * Time: 15:42
 */
?>
<div align="center">
</div>

<div align="center">
<script>
    function save_basket()
    {
        var sel = document.getElementById("basket");
//        var supplier_id = sel.options[sel.selectedIndex].getAttribute("data-supplier")

        var id = sel.options[sel.selectedIndex].getAttribute("data-basket_id");
        window.location.href = "../../../tools/baskets/basket.php?op=save&basket_id=" + id;
    }

    function load_basket()
    {
        var sel = document.getElementById("basket");

        var id = sel.options[sel.selectedIndex].getAttribute("data-basket_id");
        window.location.href = "../../../tools/baskets/basket.php?op=load&basket_id=" + id;
    }

    function empty_basket()
    {
        var sel = document.getElementById("basket");

        window.location.href = "../../../tools/baskets/basket.php?op=empty";
    }

</script>
<?php
$user = new WP_User( $user_ID );
$manager = false;
if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
    foreach ($user->roles as $role)
        if ($role == 'administrator' or $role == 'shop_manager') $manager = true;
}

function get_basket_name($basket_id)
{
    $sql = 'SELECT post_title FROM wp_posts where id = ' . $basket_id;
    $export = mysql_query($sql) or die ("Sql error : " . mysql_error() . "COMMAND=" . $sql);

    $row = mysql_fetch_row($export);

    return $row[0];
}

if ($manager) {

    print '<button id="btn_save_basket" onclick="save_basket()">שמור כסל שבועי</button>';

    // Basket selector
    $servername = "ihstore.db.10279689.hostedresource.com";
    $username = "ihstore";
    $password = "Nopass1!";
    $dbname = "ihstore";
    $link = mysql_connect($servername, $username, $password);
    mysql_set_charset('utf8', $link);
    mysql_select_db($dbname);

    $select_box = '<select id="basket">';

    $sql = "select distinct basket_id from im_baskets";

    $export = mysql_query($sql);
    print mysql_error();
    while ($row = mysql_fetch_row($export)) {
        $basket_id = $row[0];
        $basket_name = get_basket_name($basket_id);
        $line = '<option value="' . $basket_id . '" data-basket_id = ' . $basket_id . '>' . $basket_name . ' ' . $basket_id . '</option>';
        $select_box .= $line;
    }
    $select_box .= '</select>';

    print $select_box;

    print '<button id="btn_load_basket" onclick="load_basket()">טען סל שבועי</button>';
    print '<button id="btn_empty_basket" onclick="empty_basket()">רוקן סל</button>';
}

// Audit
$error_dir = '/home/content/89/10279689/html/store/tools/audit.log';
$date = date( 'd.m h:i' );
$msg = print_r( $msg, true );

global $current_user;
get_currentuserinfo();

global $post;
$post_slug=$_SERVER[REQUEST_URI];

$log = $date . ": "  . "IP: " . $_SERVER['REMOTE_ADDR'] . ", search: " . $_SERVER[QUERY_STRING] .", user: " .
    $current_user->user_login . ", HTTP_REFERER: " . urldecode($_SERVER['HTTP_REFERER']) . ", page: " . urldecode($post_slug) . "\n";
error_log( $log, 3, $error_dir );

?>
</div>
