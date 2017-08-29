<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:29
 */

require_once( "config.php" );
require_once( "../tools.php" );
require_once( "../multi-site/multi-site.php" );

$get_all = fopen( "../$obj_name/get_all.php", "w" );

fwrite( $get_all, "<?php
require_once ('../tools.php');
require_once('../header.php');
require_once('../gui/inputs.php');
print header_text(false, false);
?>
<script>
" );

$client_tools = __DIR__ . "/../client_tools.js";
$handle       = fopen( $client_tools, "r" );
$contents     = fread( $handle, filesize( $client_tools ) );
fwrite( $get_all, $contents );

fwrite( $get_all, "function delete_item()
        {
            var t = document.getElementById(\"table_" . $obj_name . "\");
            var ids = new Array();
            var i;

            // Skip the header and the summary lines
            for (i = 1; i < t.rows.length - 1; i++){
                ids.push(get_value(t.rows[i].cells[1].firstChild));
            }

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function()
            {
                // Wait to get query result
                if (xmlhttp.readyState==4 && xmlhttp.status==200)  // Request finished
                {
                    window.location = window.location;
                }
            }
            var request = \"" . $obj_name . ".php?operation=delete_items&ids=\" + ids.join();
            // alert (request);
            xmlhttp.open(\"GET\", request, true);
            xmlhttp.send();
        }" );

fwrite( $get_all, "</script></header><body>" );

fwrite( $get_all, "
<?php" );

$sql = "describe " . $table_name;

$remote_sql = "select ";
$result = mysqli_query( $conn, $sql ) or die ( "Sql error : " . mysqli_error( $conn ) . $sql );
$column_name = array();
$column_idx  = 0;
while ( $row = mysqli_fetch_row( $result ) ) {
	$column_name[ $column_idx ++ ] = $row[0];
	$field_list                    .= $row[0] . ", ";
}
$field_list = rtrim( $field_list, ", " );

print $field_list;

$remote_sql .= $field_list . " from " . $table_name . " where  1 ";

fwrite( $get_all, "
\$sql = \"" . $remote_sql . "\";

foreach(\$_GET as \$key => \$value)
{
    \$sql .= \" and \" . \$key . \" = '\" . \$value .\"'\";
}

\$sql = \$sql . \"" . $order . "\";
print \"<table dir='rtl' id='table_" . $obj_name . "'>\";
print \"<tr>\";
" );

$result = mysqli_query( $conn, $sql ) or die ( "Sql error : " . mysqli_error( $conn ) . $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	if ( in_array( $row[0], $skip_in_horizontal ) ) {
		continue;
	}
	fwrite( $get_all, "print gui_cell(\"" . $row[0] . "\");
	" );
}

$url = MultiSite::LocalSiteTools() . "/" . $obj_name . "/get_all.php";
//    {
fwrite( $get_all, "
print \"</tr>\";
\$seq = 1;
\$result = mysqli_query(\$conn, \$sql);
while (\$row = mysqli_fetch_row(\$result)) {
print_" . $obj_name . "(\$url, \$row[0], true);
}
" );

ob_start();
print "function print_" . $obj_name . "(\$url, \$id, \$horizontal)\n";
print "{\n";
print "\$sql = \"select " . $field_list . " from " . $table_name . " where id = \$id\";\n";
print "\$export = mysql_query ( \$sql ) or die ( \"Sql error : \" . mysql_error( ) . \$sql );\n";
print "\$row = mysql_fetch_row( \$export ); \n";
print "if (!\$horizontal) print \"<table>\";";

// Read the headers.
$result = sql_query( $sql );
//$row = mysqli_fetch_row( $result );

print "if (\$horizontal) print \"<tr>\";\n";

// Read table rows;
$col_idx = 0;
while ( $row = mysqli_fetch_row( $result ) ) {
//	print "if (!\$horizontal and $row[0]
	if ( in_array( $row[0], $skip_in_horizontal ) ) {
		$include = 0;
	} else {
		$include = 1;
	}
	// If vertical open new row
	print "if (!\$horizontal) print \"<tr>\";\n";
	print "if (!\$horizontal) {
	";
	print "print \"<td>\";\n";
	print "print \"" . $row[0] . "\";\n";
	print "print \"</td>\";\n";
	print "}
	";

	print 'if (!$horizontal or ' . ( $include ) . ') {';
	print "print \"<td>\";\n";
//    print "print \"<a href=\\\"get-all.php?" . $row[0] . "=\" . urlencode("."\$row[" . $col_idx ."]) . \"\\\">\$row[" . $col_idx ."]</a>\";\n";
	print "\$value =";
	$transformer = $trans[ $column_name[ $col_idx ] ];
	if ( $transformer ) {
		print $transformer . "(";
	}
	print "\$row[" . $col_idx . "]";
	if ( $transformer ) {
		print ")";
	}
	print ";
	";
	// print "print \"<a href=\\\" \$url?" . $row[0] . "=\" . urlencode("."\$row[" . $col_idx ."]) . \">\";";
	print 'print "<a href=\"" . $url . "?' . $row[0] . '=" . urlencode($row[' . $col_idx . ']) . "\">";';
	print 'print $value; ';
	print 'print "</a>";
';
	$col_idx ++;
	print "print \"</td>\";\n";
	print '}';
	print "if (!\$horizontal) print \"</tr>\";\n";
}
print "if (\$horizontal) print \"</tr>\";\n";

print "}\n";

$code = ob_get_contents();
ob_end_clean();

fwrite( $get_all, $code );
fclose( $get_all );