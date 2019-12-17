<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/01/19
 * Time: 16:15
 */

define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );

require_once( STORE_DIR . '/core/gui/inputs.php' );
require_once( STORE_DIR . "/core/data/Imap.php" );
require_once( STORE_DIR . "/core/fund.php" );
require_once( STORE_DIR . '/core/b-a/mail-config.php' );

// For debug:
//$operation = "download"; // get_param("operation");
//$id = 151; // get_param("id")

// For real...
$operation = get_param( "operation" );
$id        = get_param( "id" );

switch ( $operation ) {
	case "download":
		download_attachment( $id );
		break;
	default:
		die ( $operation . " not handled" );
}

function download_attachment( $id ) {
	global $hostname, $mail_user, $password;

	$m = new Imap();

	if ( ! $m->Connect( $hostname, $mail_user, $password ) ) {
		die( "can't connect to mail" );
	}

	$m->Read();

	$file = $m->SaveAttachment( $id );

	if ( strlen( $file ) < 3 ) {
		print "אין בהודעה קובץ מצורף";
		die ( 1 );
	}
	print "file: " . $file;
	die ( 1 );
	header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\"" );
	header( "Content-Type: application/force-download" );
	header( "Content-Length: " . filesize( $file ) );
	header( "Connection: close" );

}