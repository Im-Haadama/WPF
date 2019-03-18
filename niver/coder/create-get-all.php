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

require_once( ROOT_DIR . "/niver/data/sql.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( "coder.php" );

if ( ! file_exists( $config_file ) ) {
	print "config not found: " . $config_file;
	die ( 1 );
}
print "reading config...";

require_once( $config_file );

print "done<br/>";
print "Table: " . $table_name . '<br/>';

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

$filename        = ROOT_DIR . "/$target_folder/c-get-all-$obj_name.php";
$import_filename = ROOT_DIR . "/$target_folder/c-import-$obj_name.php";
print "creating " . gui_hyperlink( $filename, "$target_folder/c-get-all-$obj_name.php" ) . "<br/>";

$get_all = fopen( $filename, "w" );

if ( ! is_writable( $filename ) ) {
	print "Can't write to $filename<br/>";
	die( 1 );
}
$get_all = fopen( $filename, "w" );

write_header( $get_all );

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
	if ( is_string( $page_actions ) and function_exists( $page_actions ) ) {
		print "runtime<br/>";
		fwrite( $get_all, 'print ' . $page_actions . "();" );
	} else {
		print "writing page actions<br/>";
		fwrite( $get_all, "// Page actions\n" );
		fwrite( $get_all, "?><div class='menu'><?php " );
		foreach ( $page_actions as $action ) {
			fwrite( $get_all, "print gui_hyperlink(" . quote_text( $action[0] ) . ", " . quote_text( $action[1] ) . ");\n" );
			fwrite( $get_all, "print ' '; " );
		}
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

$client_tools = ROOT_DIR . "/niver/gui/client_tools.js";
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

//fwrite( $get_all, "
//<?php" );

$sql = "describe " . $table_name;

$remote_sql = "select ";
$result     = sql_query( $sql );
if ( ! $result ) {
	die( 1 );
}
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

fwrite( $get_all, "<?php 
\$sql = \"" . $remote_sql . "\";" );

if ( isset( $preset_query ) ) {
	if ( function_exists( "preset_query" ) ) {
		// fwrite ($get_all, "\$preset_query= " . $preset_query . "();");
		fwrite( $get_all, "\$preset = get_param(\"preset\", false, 2);
		\$sql .= preset_query(\$preset);" );
	} else {
		fwrite( $get_all, "\$preset_query = array(" .
		                  comma_implode( $preset_query, true ) . ");" );
		fwrite( $get_all, "
			\$ps = get_param(\"preset\");
			
			if (\$ps) \$sql .= \" and \" . \$preset_query[\$ps];" );
	}
}
fwrite( $get_all, "\$remote_url = '$url';

foreach(\$_GET as \$key => \$value)
{
	if (! in_array(\$key, array(\"debug\", \"preset\"))){
	    \$sql .= \" and \" . \$key . \" = '\" . \$value .\"'\";
	    \$remote_url = append_url(\$remote_url, \"\$key=\" . urlencode(\$value) . \"&\");
	    }
}

\$sql = \$sql . \" " . $order . "\";

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
\$result = sql_query(\$sql);
if (! \$result) { sql_error(\$conn); die(1); }
if (\$debug) print \$sql;
\$accum = null;" );
if ( isset( $accumulate ) ) {
	print "Accumulating<br/>";
	fwrite( $get_all, '$accum=array();' );
}
fwrite( $get_all, "
while (\$row = mysqli_fetch_row(\$result)) {
print_" . $obj_name . "(\"\$remote_url\", \$row[0], true, \"" . $single_url . "\", \$accum);
}
" );
if ( isset( $accumulate ) ) {
	fwrite( $get_all, 'print gui_row($accum);' );
}


ob_start();
print "function print_" . $obj_name . "(\$url, \$id, \$horizontal, \$single_url, &\$accum)\n";
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
	print "print \"</td>\";\n";
	print '}';
	print "if (!\$horizontal) print \"</tr>\";\n";
	if ( isset( $accumulate ) ) {

		if ( isset( $accumulate[ $column_name[ $col_idx ] ] ) ) {
			print 'if (! isset($accum["' . $column_name[ $col_idx ] . '"])) $accum["' . $column_name[ $col_idx ] . '"] = 0; ';
			print  $accumulate[ $column_name[ $col_idx ] ] .
			       '($accum["' . $column_name[ $col_idx ] . '"], $row[' . $col_idx . ']);';
		} else {
			print '$accum["' . $column_name[ $col_idx ] . '"] = ""; ';

		}
	}
	$col_idx ++;
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

// var_dump( $import_key );
if ( ! isset( $import_key ) ) {
	$import_key = null;
}

if ( isset( $import_csv ) ) {
	write_import_csv( $obj_name, $import_filename, $import_key );
}

fclose( $get_all );

//print gui_header(2, "import");
//$import = fopen($import_filename, "w");
//write_header($import);



print "done<br/>";

function write_header( $file ) {
	global $root_file;

	fwrite( $file, "<?php
require_once ('$root_file');
if ( ! defined( \"ROOT_DIR\" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}
// require_once('../header.php');
require_once(ROOT_DIR . '/niver/fund.php');
require_once(ROOT_DIR . '/niver/data/translate.php');
require_once(ROOT_DIR . '/niver/gui/inputs.php');
require_once(ROOT_DIR . '/niver/fund.php');
print header_text(false, true, true, true);
if (isset(\$_GET[\"debug\"])) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );
	\$debug = true;
}
" );

}

function write_import_csv( $obj_name, $import_csv, $import_key = null ) {
	global $table_name;
	print gui_header( 2, "import");

	if ( ! is_string( $import_csv ) ) {
		var_dump( $import_key );
		die( 1 );
	}
	$file        = fopen( $import_csv, "w" );
	$action_file = basename( $import_csv );

	write_header( $file );

	fwrite( $file, '
	$operation = get_param("operation");
	switch($operation)
	{
		case "import_from_file":
			require_once(ROOT_DIR . "/niver/data/Importer.php");
			$file_name  = $_FILES["fileToUpload"]["tmp_name"];
			print "Trying to import $file_name<br/>";
			$I = new Importer();
			$fields = null;' );

	if ( $import_key ) {
		fwrite( $file, '$fields = array(); $fields[' . quote_text( $import_key[0] ) . "] = get_param(" . quote_text( $import_key[0] ) . ");" );
	}

	fwrite( $file, '
	$count = 0;
			try {
				$result = $I->Import($file_name, "' . $table_name . '", $fields' );
	if ( isset( $import_key[2] ) ) {
		fwrite( $file, ", " . quote_text( $import_key[2] ) );
	}
	fwrite( $file, ');
			} catch (Exception $e) {
				print $e->getMessage();
				return;
			}
			print $result[0] . " rows imported<br/>";
			print $result[1] . " duplicate rows <br/>";
			print $result[2] . " failed rows <br/>";
			// import_from_file($obj_name, $import_key, $file_name);
			break;
	}' );
//	var_dump( $import_key );

	fwrite( $file, '?> <body onload="change_' . $obj_name . '()" >' );
	if ( $import_key ) {
		fwrite( $file, "<div>בחר ליבוא:</div><?php print " . $import_key[1] . "(\"" . $import_key[0] . "\"); ?>" );
	}
	fwrite( $file, '<form name="upload_csv" id="upcsv" method="post" enctype="multipart/form-data">
		                                                         טען מקובץ CSV
	    <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="טען" name="submit">

        <input type="hidden" name="post_type" value="product"/>
    </form>
    <script>
            function change_' . $obj_name . '() {
            var obj_name = get_value_by_name("' . $import_key[0] . '");
            var upcsv = document.getElementById("upcsv");
            upcsv.action = "' . $action_file . '?operation=import_from_file' );

	if ( $import_key ) {
		fwrite( $file, '&' . $import_key[0] . '=" + obj_name' );
	}

	fwrite( $file, ';
    }
</script>' );
	fwrite( $file, "<?php " );

	fwrite( $file, "?>" );

	fclose( $file);
}

