<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/02/17
 * Time: 09:16
 */
require_once( "pricelist-process.php" );
print header_text();

if ( isset( $_GET["file"] ) ) {
	// inbox manager activation
	$sadot_file = "http://tabula.aglamaz.com/imap/attachment-manager/" . $_GET["file"];
} else {
	$date = date( "j.n.y", strtotime( 'today' ) );
	if ( date( 'w' ) >= 4 ) { // Thursday
		$date  = date( "j.n.y", strtotime( 'last thursday' ) );
		$date2 = date( "j.n.y", strtotime( 'next sunday' ) );
	}
	$sadot_file  = "http://tabula.aglamaz.com/imap/attachment/sadot" . $date . ".csv";
	$sadot_file2 = "http://tabula.aglamaz.com/imap/attachment/sadot" . $date2 . ".csv";
}
print $sadot_file . "<br/>";

$sql = "SELECT id FROM im_suppliers WHERE supplier_name = 'שדות'";
$sid = sql_query_single_scalar( $sql );
print "מעדכן מחירון של ספק " . get_supplier_name( $sid ) . "<br/>";

if ( ! pricelist_process( $sadot_file, $sid, false ) ) {
	if ( isset( $sadot_file2 ) ) {
		pricelist_process( $sadot_file2, $sid, false );
	}
}
?>

</html>