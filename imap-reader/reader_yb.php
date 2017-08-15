<?php
require_once( "reader.php" );
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 20/02/17
 * Time: 17:34
 */

$dates = [];
array_push( $dates, date( "j.n.y", strtotime( 'tomorrow' ) ) );
if ( date( 'w' ) >= 4 ) // Thursday
{
	array_push( $date, date( "j.n.y", strtotime( 'next sunday' ) ) );
}

// Handle YB
// $search_string = 'FROM Office SUBJECT ' . $date;
# print $search_string;

$search_strings = [];
foreach ( array( "Office" ) as $sender ) {
	foreach ( $dates as $date ) {
		$search_strings[0] = 'FROM ' . $sender . ' SUBJECT ' . $date;
	}
}


$result_file = "/home/agla/store/imap-reader/attachment/yb" . $date . ".pdf";

$attach_file = read_mail( $host, $user, $pass, $search_strings );
rename( $attach_file, $result_file );
print $result_file;

// $attachment_file = read_mail($host, $user, $pass, $search_strings);

