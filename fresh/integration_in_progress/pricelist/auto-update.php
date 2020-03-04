<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/17
 * Time: 09:16
 */

require_once( "pricelist-process.php" );

$date = date( "d.n.y", strtotime( 'tomorrow' ) );
if ( date( 'w' ) >= 4 ) // Thursday
{
	$date = date( "d.n.y", strtotime( 'next sunday' ) );
}

$sadot_file = "http://tabula.aglamaz.com/imap/attachment/sadot" . $date . ".csv";

print "reading sadot<br/>";

pricelist_process( $sadot_file, 100016 );

$yb_file = "http://tabula.aglamaz.com/imap/attachment/yb" . $date . ".csv";

print "reading yevuli bar<br/>";

pricelist_process( $yb_file, 100016 );

