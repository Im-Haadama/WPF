<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/07/17
 * Time: 09:29
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
	print "ROOT_DIR: " . ROOT_DIR . "<br/>";
}

require_once( ROOT_DIR . '/niver/fund.php' );

$config_file = get_param( "config_file" );

if ( ! $config_file ) {
	if ( ! isset ( $obj_name ) ) {
		if ( ! isset( $_GET["obj_name"] ) ) {
			print "usage: obj_name=<obj>";
			die ( 1 );
		}
		$obj_name = $_GET["obj_name"];
	}
	print "obj: " . $obj_name . "<br/>";

	$req_file = "coder-config-" . $obj_name . ".php";
}

require_once( ROOT_DIR . "/niver/sql.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( "coder.php" );

if ( ! file_exists( $config_file ) ) {
	print "config not found: " . $config_file;
	die ( 1 );
}
print "reading config...";

require_once( $config_file );

print "done<br/>";

if ( isset( $root_file ) ) {
//	$root_file = ROOT_DIR . '/' . $root_file;
	print $root_file . "<br/>";
	require_once( $root_file );
} else {
	print "root file not set";
}

print "Creating code<br/>";

if ( $useMultiSite ) {
	require_once( ROOT_DIR . "/niver/MultiSite.php" );
}

if ( ! isset( $target_file ) ) {
	// print "root dir: " . ROOT_DIR . "<br/>";
	$target_file = ROOT_DIR . "/$obj_name";
	print "target: " . $target_folder . "<br/>";
}

$target_real_folder = realpath( $target_folder );

$filename = ROOT_DIR . "/$target_folder/c-get-all-$obj_name.php";
print "creating " . gui_hyperlink( $filename, "$target_folder/c-get-all-$obj_name.php" ) . "<br/>";

$get_all = fopen( $filename, "w" );

if ( ! is_writable( $filename ) ) {
	print "Can't write to $filename<br/>";
	die( 1 );
}
$get_all = fopen( $filename, "w" );

fwrite( $get_all, "<?php
require_once ('$root_file');
// require_once('../header.php');
require_once(ROOT_DIR . '/agla/fund.php');
require_once(ROOT_DIR . '/agla/translate.php');
require_once(ROOT_DIR . '/agla/gui/inputs.php');
require_once(ROOT_DIR . '/agla/fund.php');
print header_text(false, false);
if (isset(\$_GET[\"debug\"])) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );
	\$debug = true;
}
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

if ( isset( $page_actions ) ) {
	print "writing page actions<br/>";
	fwrite( $get_all, "// Page actions\n" );
	fwrite( $get_all, "?><div class='menu'><?php " );
	foreach ( $page_actions as $action ) {
		fwrite( $get_all, "print gui_hyperlink(" . quote_text( $action[0] ) . ", " . quote_text( $action[1] ) . ");\n" );
		fwrite( $get_all, "print ' '; " );
	}

}

fwrite( $get_all, '$debug = get_param("debug");' );

fwrite( $get_all, "
print gui_header(2, '" . $header_text . "');
print gui_hyperlink(\"הוסף\", \"c-get-" . $obj_name . ".php\");
print '<br/>';
print gui_hyperlink(\"יבא\", \"c-import-" . $obj_name . ".php\");
?>
<script>
" );

$client_tools = ROOT_DIR . "/niver/client_tools.js";
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
                if (xmlhttp.readyState==4 && xmlhttp.status==200)  // Request finhed
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
	print $row[0] . "<br/>";
	$field_list .= $row[0] . ", ";
}
$field_list = rtrim( $field_list, ", " );

// print $field_list . "<br/>";

$remote_sql .= $field_list . " from " . $table_name . " where  1 ";

if ( isset( $query ) ) {
	print "query = " . $query . "<br/>";
	$remote_sql .= " and " . $query;
	print "remote_sql= " . $remote_sql . "<br/>";
}

$url = "c-get-all-" . $obj_name . ".php?";
if ( ! isset( $order ) ) {
	$order = "";
}

fwrite( $get_all, "
\$sql = \"" . $remote_sql . "\";
\$remote_url = '$url';

foreach(\$_GET as \$key => \$value)
{
	if (! in_array(\$key, array(\"debug\"))){
	    \$sql .= \" and \" . \$key . \" = '\" . \$value .\"'\";
	    \$remote_url = append_url(\$remote_url, \"\$key=\" . urlencode(\$value) . \"&\");
	    }
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

if ( ! isset ( $single_url ) ) {
	if ( ! isset( $server_name ) ) {
		$server_name = $_SERVER["SERVER_NAME"];
	}
	$single_url = "http://" . $server_name . "/" . $target_folder . "/c-get-$obj_name.php";
	print "Single url: " . $single_url . "<br/>";
}

fwrite( $get_all, "
print \"</tr>\";
\$seq = 1;
\$result = mysqli_query(\$conn, \$sql);
if (! \$result) { sql_error(\$conn); die(1); }
if (\$debug) print \$sql;
while (\$row = mysqli_fetch_row(\$result)) {
print_" . $obj_name . "(\"\$remote_url\", \$row[0], true, \"" . $single_url . "\");
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
	do {
		if ( $row[0] == "id" ) {
			print 'print "<a href=\"" . $single_url . "?' . $row[0] . '=" . urlencode($row[' . $col_idx . ']) . "\">";';
			continue;
		}
		if ( isset ( $display_url[ $row[0] ] ) ) {
			print_href( $display_url[ $row[0] ], $row[0] );
			// print gui_hyperlink( $row[0], $aa );
			continue;
		}

		// Display item data with link to drill.
		//print_href("append_url(\$url, ))
		print 'print "<a href=\"" . $url . "&' . $row[0] . '=" . urlencode($row[' . $col_idx . ']) . "\">";';
	} while ( false );
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
