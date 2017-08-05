<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 10:31
 */
require_once( "pricelist-process.php" );
print header_text();

if ( isset( $_GET["file"] ) ) {
	// inbox manager activation
	$yb_file = "http://tabula.aglamaz.com/imap/attachment-manager/" . $_GET["file"];
	print "processing " . $yb_file . "<br/>";
} else {
	$date = date( "j.n.y", strtotime( 'tomorrow' ) );
	if ( date( 'w' ) >= 4 ) // Thursday
	{
		$date = date( "j.n.y", strtotime( 'next sunday' ) );
	}
	$yb_file = "http://tabula.aglamaz.com/imap/attachment/yb" . $date . ".csv";
}
print "reading yevuli bar<br/>";

pricelist_process_name( $yb_file, 'אמיר בן יהודה', false );
