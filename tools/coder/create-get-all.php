<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:29
 */

if ( ! defined( "STORE_DIR" ) ) {
	define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
//require_once( STORE_DIR . "/wp-config.php" );
//require( STORE_DIR . "/im-config.php" );
require_once( "../sql.php" );

if ( ! isset( $_GET["obj_name"] ) ) {
	print "Must sent obj name";
	die( 1 );
}
$obj_name = $_GET["obj_name"];
print "obj: " . $obj_name . "<br/>";

require_once( "coder-config-" . $obj_name . ".php" );
print "target folder: " . $target_folder . "<br/>";


if ( isset( $root_file ) ) {
	$root_file = STORE_DIR . '/tools/' . $root_file;
	print $root_file . "<br/>";
	require_once( $root_file );
} else {
	print "root file not set";
}

print "Creating code<br/>";

if ( $useMultiSite ) {
	require_once( "../multi-site/multi-site.php" );
}

if ( ! isset( $target_folder ) ) {
	$target_folder = "../$obj_name";
}

$filename = "$target_folder/c-get-all-$obj_name.php";
print "creating " . $filename . "<br/>";

$get_all = fopen( $filename, "w" );

fwrite( $get_all, "<?php
require_once ('$root_file');
// require_once('../header.php');
require_once(\"../translate.php\");
require_once('../gui/inputs.php');
print header_text(false, false);
if (isset(\$_GET[\"debug\"])) \$debug = true;
" );

$object_file = $target_folder . '/' . $obj_name . ".php";
print "object file: " . $object_file . "<br/>";
if ( file_exists( $object_file ) ) {
	fwrite( $get_all, "require_once('" . $object_file . "');" );
}

if ( isset ( $load_actions ) ) {
	print "writing load action code<br/>";
	fwrite( $get_all, "// Running load actions\n" );
	foreach ( $load_actions as $action ) {
		fwrite( $get_all, $action . "();\n" );
	}
}

fwrite( $get_all, "
print gui_header(2, '" . $header_text . "');
print gui_hyperlink(\"הוסף\", \"c-get-" . $obj_name . ".php\");
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
$field_list  = "";
while ( $row = mysqli_fetch_row( $result ) ) {
	$column_name[ $column_idx ++ ] = $row[0];
	$field_list                    .= $row[0] . ", ";
}
$field_list = rtrim( $field_list, ", " );

print $field_list . "<br/>";

$remote_sql .= $field_list . " from " . $table_name . " where  1 ";

if ( isset( $query ) ) {
	print "query = " . $query . "<br/>";
	$remote_sql .= " and " . $query;
	print "remote_sql= " . $remote_sql . "<br/>";
}

fwrite( $get_all, "
\$sql = \"" . $remote_sql . "\";

foreach(\$_GET as \$key => \$value)
{
    \$sql .= \" and \" . \$key . \" = '\" . \$value .\"'\";
}

\$sql = \$sql . \"" . $order . "\";
print \"<table dir='rtl' border=\'1\' id='table_" . $obj_name . "'>\";
print \"<tr>\";
" );

$result = mysqli_query( $conn, $sql ) or die ( "Sql error : " . mysqli_error( $conn ) . $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	if ( in_array( $row[0], $skip_in_horizontal ) ) {
		continue;
	}
	fwrite( $get_all, "print gui_cell(translate2heb(\"" . $row[0] . "\"));
	" );
}

$url        = "c-get-all-" . $obj_name . ".php";
$single_url = $target_folder . "/c-get-$obj_name.php";

fwrite( $get_all, "
print \"</tr>\";
\$seq = 1;
\$result = mysqli_query(\$conn, \$sql);
if (! \$result) { sql_error(\$conn); die(1); }
while (\$row = mysqli_fetch_row(\$result)) {
print_" . $obj_name . "(\"" . $url . "\", \$row[0], true, \"" . $single_url . "\");
}
" );

ob_start();
print "function print_" . $obj_name . "(\$url, \$id, \$horizontal, \$single_url)\n";
print "{\n";
print "\$sql = \"select " . $field_list . " from " . $table_name . " where id = \$id\";\n";
print "\$result = sql_query ( \$sql );\n";
print "\$row = mysqli_fetch_row( \$result ); \n";
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
	$transformer = null;

	if ( isset( $trans[ $column_name[ $col_idx ] ] ) ) {
		$transformer = $trans[ $column_name[ $col_idx ] ];
		if ( $transformer ) {
			print $transformer . "(";
		}
	}
	print "\$row[" . $col_idx . "]";
	if ( $transformer ) {
		print ", \$row[0] )";
	}
	print ";
	";
	// print "print \"<a href=\\\" \$url?" . $row[0] . "=\" . urlencode("."\$row[" . $col_idx ."]) . \">\";";
	if ( $row[0] == "id" ) {
		print 'print "<a href=\"" . $single_url . "?' . $row[0] . '=" . urlencode($row[' . $col_idx . ']) . "\">";';
	} else {
		print 'print "<a href=\"" . $url . "?' . $row[0] . '=" . urlencode($row[' . $col_idx . ']) . "\">";';
	}
	print 'print $value; ';
	print 'print "</a>";
';
	$col_idx ++;
	print "print \"</td>\";\n";
	print '}';
	print "if (!\$horizontal) print \"</tr>\";\n";
}
if ( isset( $actions ) ) {
	foreach ( $actions as $action ) {
		print 'print gui_cell(gui_hyperlink("' . $action[0] . '", "' . $action[1] . '". $id));';
	}
}
print "if (\$horizontal) print \"</tr>\";\n";

print "}\n";

$code = ob_get_contents();
ob_end_clean();

fwrite( $get_all, $code );

fclose( $get_all );

print "done<br/>";
