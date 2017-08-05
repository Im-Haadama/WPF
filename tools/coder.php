<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/10/16
 * Time: 21:20
 */

require_once( 'tools.php' );
$table_prefix = "im_";
$obj_name     = "business";
$table_suffix = "_info";

$table_name              = $table_prefix . $obj_name . $table_suffix;
$display_name            = array();
$display_name["part_id"] = "get_name";

$display_url        = array();
$display_url["ref"] = "../delivery/get-delivery.php?id=";

$sql = "describe " . $table_name;
$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );

if ( 0 ) {
	print "<table>";
	while ( $row = mysql_fetch_row( $export ) ) {
		print "<tr><td>" . $row[0] . "</td><td>" . $row[1] . "</td>" . "</tr>";
	}
	print "</table>";
}
$sql = "describe " . $table_name;
$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
$row        = mysql_fetch_row( $export );
$field_list = $row[0];
while ( $row = mysql_fetch_row( $export ) ) {
	$field_list .= ", " . $row[0];
}

function print_field( $field_name, $field_value ) {
	global $display_name;

	$func = $display_name[ $field_name ];
	if ( $func ) {
		print "print " . $func . "(" . $field_value . ");";
	} else {
		print "print " . $field_value . ";";
	}
}

function print_href( $field_name, $field_value ) {
	global $display_url;

	print "print \"&#60a href=\\";
	$url = $display_url[ $field_name ];
	if ( $url ) {
		print "\"" . $url . "\"." . $field_value . ";";
	} else {
		print "\"get_all.php?" . $field_name . "=\" . urlencode(";
		print $field_value; // "\$row[" . $col_idx ."]";
		print ");";
	}
	print "print \"\\\">\";";
}

// scripts
print "<header>";
print "<script>";

print "</script>";
print "</header>";

// Delete
print "function delete_" . $obj_name . "(\$id)<br/>";
print "{<br/>";
print "\$sql = \"delete from " . $table_name . " where id = \$id\";<br/>";
print "\$export = mysql_query ( \$sql ) or die ( \"Sql error : \" . mysql_error( ) . \$sql );<br/>";
print "}<br/>";


// Print
print "function print_" . $obj_name . "(\$id, \$horizontal, \$seq)<br/>";
print "{<br/>";
print "\$sql = \"select " . $field_list . " from " . $table_name . " where id = \$id\";<br/>";
print "\$export = mysql_query ( \$sql ) or die ( \"Sql error : \" . mysql_error( ) . \$sql );<br/>";
print "\$row = mysql_fetch_row( \$export ); <br/>";
print "if (!\$horizontal) print \"&#60table>\";</br>";
// Read the headers.
$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() . $sql );
//$row = mysql_fetch_row( $export );

print "if (\$horizontal) print \"&#60tr>&#60td>\$seq&#60/td>\";<br/>";

// Read table rows;
$col_idx = 0;
while ( $row = mysql_fetch_row( $export ) ) {
	$field_name = $row[0];
	print "if (!\$horizontal) print \"&#60tr>\";<br/>";
	print "if (!\$horizontal) {";
	print "print \"&#60td>\";<br/>";
	print "print \"" . $field_name . "\";<br/>";
	print "print \"&#60/td>\";<br/>";
	print "}";

	print "print \"&#60td>\";<br/>";

	print_href( $field_name, "\$row[" . $col_idx . "]" );

	print_field( $field_name, "\$row[" . $col_idx . "]" );
	print "print \"&#60/a>\";<br/>";
//    print $display_name[$row[0]] . "(" . $row[0].")";
	$col_idx ++;
	print "print \"&#60/td>\";<br/>";
	print "if (!\$horizontal) print \"&#60/tr>\";<br/>";
}
print "if (\$horizontal) print \"&#60/tr>\";<br/>";

print "}<br/>";

print "print \"&#60/table>\";</br>";

// Print input fields
print "?>";

$conn->query( $sql );
$result = mysqli_query( $conn, $sql );
while ( $row = mysqli_fetch_assoc( $result ) ) {
	$name = $row[]

}


?>
