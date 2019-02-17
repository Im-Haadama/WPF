<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/04/17
 * Time: 06:33
 */
// require_once('config.php');
if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
}
require_once( ROOT_DIR . '/wp-load.php' );
require_once( ROOT_DIR . '/im-config.php' );
require_once( ROOT_DIR . '/niver/data/sql.php' );

if ( isset( $servername ) and strlen( $servername ) > 2 ) {
	$conn = new mysqli( $servername, $username, $password, $dbname );
	mysqli_set_charset( $conn, 'utf8' );
}

function tag_st( $str ) {
	return "'" . $str . "'";
}

$current_user = wp_get_current_user();
$post_slug    = get_post_field( 'post_name', get_post() );

$ref = null;
if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
	$ref = $_SERVER['HTTP_REFERER'];
}

$sql = "INSERT INTO im_activity (time, login, ip, url, ref, search) VALUES (" .
       " now(), " . tag_st( $current_user->user_login ) . ", "
       . tag_st( $_SERVER['REMOTE_ADDR'] ) . ", " . tag_st( urldecode( $post_slug ) ) .
       ", " . tag_st( $ref ) .
       ", " . tag_st( $_SERVER['QUERY_STRING'] )
       . ")";

//print "<br/>" . $sql . "<br/>";
// my_log($sql);

sql_query( $sql );
?>
<script>
    (function (i, s, o, g, r, a, m) {
        i['GoogleAnalyticsObject'] = r;
        i[r] = i[r] || function () {
                (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
        a = s.createElement(o),
            m = s.getElementsByTagName(o)[0];
        a.async = 1;
        a.src = g;
        m.parentNode.insertBefore(a, m)
    })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');

    ga('create', '<?php print $google_code; ?>', 'auto');
    ga('send', 'pageview');

</script>
