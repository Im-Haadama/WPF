<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/10/16
 * Time: 21:20
 */


function print_field( $field_name, $field_value ) {
	global $display_name;

	$func = $display_name[ $field_name ];
	if ( $func ) {
		print "print " . $func . "(" . $field_value . ");";
	} else {
		print "print " . $field_value . ";";
	}
}

function print_href( $display_url, $field_value ) {

	print "print \"<a href=\\";
	print "\"" . $display_url . "\".\$value;";

	print "print \"\\\">\";";
}

?>

