<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 13:15
 */

define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );

require_once( STORE_DIR . '/niver/data/im_simple_html_dom.php' );
require_once( STORE_DIR . '/niver/data/attachment_folder.php' );

$filename = STORE_DIR . '/niver/b-a/mail-config.php';

if ( ! file_exists( $filename ) ) {
	print "config file " . $filename . " not found<br/>";
	die ( 1 );
}
require_once( $filename );

if ( ! isset( $attach_folder ) ) {
	print "Warning: \$attach_folder should be set<br/>";
}
if ( ! isset( $folder_url ) ) {
	print "Warning: \$folder_url should be set<br/>";
}
if ( ! isset( $site_tools ) ) {
	print "Warning: \$site_tools should be set<br/>";
}
if ( ! isset( $mail_user ) ) {
	die ( "Fatal: \$mail_user should be set<br/>" );
}
if ( ! isset( $password ) ) {
	die( "Fatal: \$password should be set<br/>" );
}
if ( ! isset( $hostname ) ) {
	die( "Fatal: \$hostname should be set<br/>" );
}
// print __FILE__;
require_once( STORE_DIR . '/niver/gui/inputs.php' );

$site_tools = array(
	"", // 0
	"http://store.im-haadama.co.il/tools", // 1
	"http://super-organi.co.il/tools/", // 2
	"", // 3
	"http://fruity.co.il/tools" // 4
);

// header_text
$text = '<html>';
$text .= '<head>';
$text .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
$text .= '</head>';

print $text;

print gui_table_args( inbox_files( $hostname, $mail_user, $password, $attach_folder, $folder_url ) );