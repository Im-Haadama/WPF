<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/10/17
 * Time: 08:03
 */

if ( ! isset( $_GET["input"] ) ) {
	print "bad usage";
	die( 1 );
}
$input = $_GET["input"];
//print $input . "<br/>";

$input_content = file_get_contents( $input );
$name          = parse_url( $input, PHP_URL_PATH );
while ( strstr( $name, "/" ) ) {
	$name = basename( $name );
	// print $name ."<br/>";
}

$local_file_name = "/home/agla/tmp/" . $name;

$local_file = fopen( $local_file_name, "w" );
fwrite( $local_file, $input_content );
fclose( $local_file );

//print "local - " . $local_file . "<br/>";

$new_file = "/home/agla/tmp/" . basename( $name ) . ".csv";
//print "new - " . $new_file . "<br/>";

$result = shell_exec( "ssconvert " . $local_file_name . " " . $new_file );
//print $result;
header( "Content-disposition: attachment;filename=" . basename( $new_file ) );
readfile( $new_file );
