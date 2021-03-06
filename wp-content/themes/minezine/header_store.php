<?php

return;

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
require_once( ROOT_DIR . '/core/data/sql.php' );

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

SqlQuery( $sql );
?>
