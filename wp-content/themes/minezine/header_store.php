<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/04/17
 * Time: 06:33
 */
// require_once('config.php');
if ( ! defined( __ROOT__ ) ) {
	define( '__ROOT__', dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) );
}
require_once( __ROOT__ . '/wp-load.php' );
require_once( __ROOT__ . '/im-config.php' );
require_once( __ROOT__ . '/niver/data/sql.php' );

if ( isset( $servername ) and strlen( $servername ) > 2 ) {
	$conn = new mysqli( $servername, $username, $password, $dbname );
	mysqli_set_charset( $conn, 'utf8' );
}

function tag_st( $str ) {
	return "'" . $str . "'";
}

$sql = "INSERT INTO im_activity (time, login, ip, url, ref, search) VALUES (" .
       " now(), " . tag_st( $current_user->user_login ) . ", "
       . tag_st( $_SERVER['REMOTE_ADDR'] ) . ", " . tag_st( urldecode( $post_slug ) ) .
       ", " . tag_st( $_SERVER['HTTP_REFERER'] ) .
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
