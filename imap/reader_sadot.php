<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/02/17
 * Time: 17:34
 */

require_once( "reader.php" );

//if (date('w') >= 5) {
//    print "no process on friday and saturday. <br/>";
//    die (0); // No processing needed
//}

$dates = [];
if ( date( 'w' ) >= 4 ) {
	array_push( $dates, date( "j.n.y", strtotime( 'last thursday' ) ) );
	array_push( $dates, date( "j.n.Y", strtotime( 'last thursday' ) ) );
	$result_date = date( "j.n.y", strtotime( 'next sunday' ) );
} else {
	array_push( $dates, date( "j.n.y", strtotime( 'today' ) ) );
	array_push( $dates, date( "j.n.Y", strtotime( 'today' ) ) );
	$result_date = date( "j.n.y", strtotime( 'today' ) );
}


// Handle SADOT
$search_strings = [];
foreach ( array( "Batya", "Limor" ) as $sender ) {
	foreach ( $dates as $date ) {
		$search_strings[0] = 'FROM ' . $sender . ' SUBJECT ' . $date;
	}
}
// $search_strings[1] = 'FROM Batya SUBJECT ' . $date;
// print "searching: " . $search_strings . "\n";
# If and error in the date string:
# $search_string = 'FROM Batya SUBJECT 12.4.19';
# print $search_string . "<br/>";

// $result_file = "/home/agla/store/imap/attachment/sadot" . $date . ".pdf";
// print $result_file;

$attachment_file = read_mail( $host, $user, $pass, $search_strings );

$ext = pathinfo( $attachment_file, PATHINFO_EXTENSION );

$date        = date( "j.n.y", strtotime( '' ) );
$result_file = "/home/agla/store/imap/attachment/sadot" . $result_date . "." . $ext;
rename( $attachment_file, $result_file );

print $result_file;
