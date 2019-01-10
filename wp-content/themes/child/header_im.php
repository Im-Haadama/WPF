<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/06/16
 * Time: 15:42
 */
?>
<div align="center">
    תוצרת אורגנית בפיקוח או מ
    <a href="http://im-haadama.co.il/about/">
        חקלאות בת קיימא.
    </a>
    לפרטים אודות ה
    <a href="http://store.im-haadama.co.il/about/">שירות והמשלוחים</a>
</div>

<div align="center">
	<?php
	date_default_timezone_set( "Asia/Jerusalem" );

	// 6 - Saturday, 7 - Sunday, 1 - Monday, 2 - Tuesday
	//if (date("N") == 6 || date("N") == 7 || (date("N") == 1  && date("G") <= 18 )) {
	//    echo date("הזמנה זאת תסופק ביום שלישי j/n/Y", mktime(0, 0, 0, date("m"), date("d")+(9 - date("N")) % 7 , date("Y")));
	//} else {
	//    echo date("הזמנה זאת תסופק ביום שלישי הבא j/n/Y", mktime(0, 0, 0, date("m"), 9-date("N")+date("d"), date("Y")));
	//}
	// האספקה בימים שני-עד רביעי.
	?>
    <!--    <h2> לרגל החג, המשלוחים יבצעו ביום ב'. הזמנות עד השעה 18.-->
    <h2> המשלוחים בימים ב' עד ה', בהתאם
        <a href="http://store.im-haadama.co.il/about">לאיזורים</a></h2>
    <script>
        function save_basket() {
            var sel = document.getElementById("basket");
//        var supplier_id = sel.options[sel.selectedIndex].getAttribute("data-supplier")

            var id = sel.options[sel.selectedIndex].getAttribute("data-basket_id");
            window.location.href = "../../../tools/baskets/basket.php?op=save&basket_id=" + id;
        }

        function load_basket() {
            var sel = document.getElementById("basket");

            var id = sel.options[sel.selectedIndex].getAttribute("data-basket_id");
            window.location.href = "../../../tools/baskets/basket.php?op=load&basket_id=" + id;
        }

        function empty_basket() {
            var sel = document.getElementById("basket");

            window.location.href = "../../../tools/baskets/basket.php?op=empty";
        }

    </script>
	<?php
	$user    = new WP_User( $user_ID );
	$manager = false;
	if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
		foreach ( $user->roles as $role ) {
			if ( $role == 'administrator' or $role == 'shop_manager' ) {
				$manager = true;

			}
		}
	}

	function get_basket_name( $basket_id ) {
		$sql = 'SELECT post_title FROM wp_posts WHERE id = ' . $basket_id;
		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . "COMMAND=" . $sql );

		$row = mysql_fetch_row( $export );

		return $row[0];
	}

	// define('__ROOT__', dirname(dirname(dirname(dirname(__FILE__)))));

	// require( '../../../config.php');

	if ( $manager ) {
		$config = STORE_DIR . '/im-config.php';
		if ( file_exists( $config ) ) {
			require_once( $config );
		} else {

			print '<button id="btn_save_basket" onclick="save_basket()">שמור כסל שבועי</button>';

			// Basket selector
			global $servername, $username, $password;
			// print $servername;
			$conn = mysqli_connect( $servername, $username, $password );
			if ( ! $conn ) {
				die ( 1 );
			}
			mysqli_set_charset( $conn, 'utf8' );
			mysqli_select_db( $conn, $dbname );

			$select_box = '<select id="basket">';

			$sql = "SELECT DISTINCT basket_id FROM im_baskets";

			$export = mysql_query( $sql );
			print mysql_error();
			while ( $row = mysql_fetch_row( $export ) ) {
				$basket_id   = $row[0];
				$basket_name = get_basket_name( $basket_id );
				$line        = '<option value="' . $basket_id . '" data-basket_id = ' . $basket_id . '>' . $basket_name . ' ' . $basket_id . '</option>';
				$select_box  .= $line;
			}
			$select_box .= '</select>';

			print $select_box;

			print '<button id="btn_load_basket" onclick="load_basket()">טען סל שבועי</button>';
			print '<button id="btn_empty_basket" onclick="empty_basket()">רוקן סל</button>';
		}
	}

	// Audit
	$error_dir = STORE_DIR . '/audit.log';
	$date      = date( 'd.m h:i' );
	$msg       = print_r( $msg, true );

	global $current_user;
	get_currentuserinfo();

	global $post;
	$post_slug = $_SERVER[ REQUEST_URI ];

	require_once( 'header_store.php' );

	//$log = $date . ": "  . "IP: " . $_SERVER['REMOTE_ADDR'] . ", search: " . $_SERVER[QUERY_STRING] .", user: " .
	//    $current_user->user_login . ", HTTP_REFERER: " . urldecode($_SERVER['HTTP_REFERER']) . ", page: " . urldecode($post_slug) . "\n";
	//error_log( $log, 3, $error_dir );

	?>
</div>
