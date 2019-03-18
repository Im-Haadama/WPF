<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/11/16
 * Time: 10:40
 */

// TODO: in c-<obj>
// 1) watch headers. Don't print header.
// 2) f (sql_query($sql)) print "התווסף בהצלחה"; remove print $sql;
// 3) define ROOT_DIR

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
	print "ROOT_DIR: " . ROOT_DIR . "<br/>";
}

require_once( ROOT_DIR . '/niver/fund.php' );
require_once( ROOT_DIR . '/niver/data/sql.php' );
require_once( "common.php" );

$config_file = get_param( "config_file" );

if ( ! $config_file ) {
	if ( ! isset ( $obj_name ) ) {
		if ( ! isset( $_GET["obj_name"] ) ) {
			print "usage: obj_name=<obj> or config_file=<file>";
			die ( 1 );
		}
		$obj_name = $_GET["obj_name"];
	}
	print "obj: " . $obj_name . "<br/>";

	$config_file = "coder-config-" . $obj_name . ".php";
}

if ( ! file_exists( $config_file ) ) {
	print $config_file . " not found <br/>";
	die( 1 );
} else {
	print "reading " . $config_file . " ";
}

require_once( $config_file );

if ( ! isset( $conn ) or ! $conn ) {
	die ( "<br/>NOT connected to DB<br/>" );
}

if ( isset( $root_file ) ) {
//	$root_file = ROOT_DIR . '/' . $root_file;
	print $root_file . "<br/>";
	//require_once( $root_file );
} else {
	print "root file not set";
}

print "done<br/>";

print "root file: " . $root_file;
//require_once ($root_file);
print "done<br/>";

require_once( ROOT_DIR . "/niver/data/translate.php" );


$filename      = ROOT_DIR . "/$target_folder/c-get-$obj_name.php";
$post_filename = ROOT_DIR . "/$target_folder/c-$obj_name.php";

print "creating " . $filename . "<br/>";

$output = fopen( $filename, "w" );

if ( ! $output ) {
	print "can't open target . " . $filename . "<br/>";
	die ( 1 );
}

fwrite( $output, "<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once ('$root_file');
// require_once('../header.php');
require_once(ROOT_DIR . '/niver/fund.php');
require_once(ROOT_DIR . '/niver/data/translate.php');
require_once(ROOT_DIR . '/niver/gui/inputs.php');
" );

$object_file = ROOT_DIR . "/$target_folder/$obj_name.php";
print "object file: " . $object_file . "<br/>";
if ( file_exists( $object_file ) ) {
	fwrite( $output, "require_once('" . $object_file . "');" );
}

fwrite( $output, "
print header_text(false, false);
if (isset(\$_GET[\"debug\"])) \$debug = true;
if (isset(\$_GET[\"id\"])) {
	\$edit = true;
	\$id = \$_GET[\"id\"];
	\$operation = 'update';
} else {
	\$operation = 'insert';
	\$id = 0; // New record
}
print_entry(\$id);

?>
<script>
" );

$client_tools = ROOT_DIR . '/niver/gui/client_tools.js';
// if (isset($datalist)) print "dl=" . $datalist . "<br/>";

$handle   = fopen( $client_tools, "r" );
$contents = fread( $handle, filesize( $client_tools ) );
fwrite( $output, $contents );

fwrite( $output, "function changed(field){
        var subject = field.id;
        document.getElementById(\"chk_\" + subject).checked = true;
    }
    " );
fwrite( $output, "function save_new() {\n" );
$sql = "describe " . $table_name;
if ( ! ( $result = sql_query( $sql ) ) ) {
	print "Table " . $table_name . " not found <br/>";
	die ( 1 );
}

fwrite( $output, "var request_url = \"c-" . $obj_name . ".php?operation=<?php print \$operation;?>\";\n" );

while ( $row = mysqli_fetch_assoc( $result ) ) {
//	var_dump($row); print "<br/>";
	$name     = $row["Field"];

	$var_name = '_' . $name;
	if ( $name == "id" ) {
		fwrite( $output, "request_url = request_url + \"&id=\" + get_value(document.getElementById(\"id\"));\n" );
	} else {
//		if (isset($insert[$name])) {
//			fwrite( $output, $var_name . '=' . $var_name . '.substr(0, ' . $var_name . '.indexOf(")"));' );
//		}
		fwrite( $output, "if (document.getElementById(\"chk_" . $name . "\").checked){\n" );
		fwrite( $output, "var " . $var_name . " = encodeURI(get_value(document . getElementById(\"" . $name . "\")))\n" );
		fwrite( $output, "request_url = request_url + \"&" . $name . "=\" + _" . $name . ";\n" );
		fwrite( $output, "}\n;" );
	}
}

fwrite( $output, "request = new XMLHttpRequest();\n" );
fwrite( $output, "request.onreadystatechange = function()\n" );
fwrite( $output, "{\n" );
fwrite( $output, "  if (request.readyState==4 && request.status==200) \n" );
fwrite( $output, "{\n" );
fwrite( $output, "var http_text = request.responseText.trim();
                        document.getElementById(\"logging\").innerHTML = http_text;	" );
// fwrite ($output, "window.location = window.location; \n");
fwrite( $output, "}\n" );
fwrite( $output, "}\n" );
// fwrite ($output,  \$request_calc);
fwrite( $output, "request.open(\"GET\", request_url, true);\n" );
fwrite( $output, "request.send();\n" );
fwrite( $output, "}\n" );
fwrite( $output, "</script>\n" );

if ( isset( $datalist ) ) {
	fwrite( $output, '<?php ' . $datalist . '?>' );
}

fwrite( $output, "<button id=\"btn_" . $obj_name . "_save\" onclick =\"save_new()\">שמור" . "</button><br/>" );

//$o = new $obj_name;
//var_dump($o);
//if (method_exists(new $obj_name, "createNew"))
//{
//	print "xxx";
//	die (1);
//}
fwrite( $output, "<textarea id=\"logging\" rows=\"4\" cols=\"50\"></textarea>" );
fwrite( $output, "<?php \n" );

// Print input fields
//print "מייצר קוד לטבלא " . $table_name . "<br/>";
fwrite( $output, "function print_entry(\$id)\n" );
fwrite( $output, "{\n" );
fwrite( $output, "print \"<table>\";" );
fwrite( $output, "global \$conn;\n" );
fwrite( $output, "if (\$id > 0) {\n" );
fwrite( $output, "\$sql = \"select * from " . $table_name . " where id = \" . \$id;\n" );
fwrite( $output, "\$result = \$conn->query(\$sql);\n" );
fwrite( $output, "\$values=mysqli_fetch_assoc(\$result);\n" );
fwrite( $output, "}
 else {
    if (function_exists('" . $obj_name . "_set_defaults')) " . $obj_name . "_set_defaults(\$values);
   else {
    \$values = array();  
  }
  }
 " );

$sql = "describe " . $table_name;
$conn->query( $sql );
$result  = mysqli_query( $conn, $sql );
$line_id = 1;
while ( $row = mysqli_fetch_assoc( $result ) ) {
	$name = $row["Field"];
	$type = $row["Type"];

	$value = "\$values[\"" . $name . "\"]";

	print "value = " . $value . "<br/>";

	// fwrite($output, "if (! isset(" . $value . ")) $value = null;");

	print_row_start();

	print $name;
	$star = "";
	if ( ( $row["Null"] != "YES" ) ) {
		$star = "(*)";
	}
	print " " . $type . " " . $star . "<br/>";

	if ( $name == "id" ) {
		print_cell_start();
		print_cell_end();
		print_cell_start();
		fwrite( $output, "print \"מזהה\";\n" );
		print_cell_end();
		print_cell_start();
		print_label( $name, $value );
		print_cell_end();
		print_row_end();
		continue;
	}
	// fwrite($output, "print \"<td><input class=\\\"chk\\\" id=\\\"chk_" . $line_id ."\\\" type=\\\"checkbox\\\"></td>\";<br/>";
	fwrite( $output, 'if (get_param("' . $name . '")){' .
	                 $value . '= get_param("' . $name . '");' .
	                 '$values[\'chk_' . $name . '\'] = true;' .
	                 "}" );
	print_cell_start();
	print_checkbox( "chk_" . $name );
	print_cell_end();
	$line_id += 1;
	print_cell_start();
	print_header( translate2heb( $name ) . $star );
	print_cell_end();
	print_cell_start();
	$transformer = null;

	if ( isset( $input_table[ $name ] ) ) {
		print_input_select( $name, $input_table[ $name ] );
		continue;
	}
	if ( isset( $insert[ $name ] ) ) {
		$gui = $insert[ $name ] . "(\"" . $name . "\", $value, \"onchange=\\\"changed(this)\\\"\"";
		if ( isset( $insert_id[ $name ] ) ) {
			$gui .= ", \$id";
		}
		$gui .= ")";
		print "insert $name: " . $gui . "<br/>";

		// fwrite($output, "print \"<?php print \" . $gui . \"; \";");
		fwrite( $output, "if (! isset($value)) $value = null;" );
		fwrite( $output, "print $gui;" );

		//		print $gui;
//		die(1);
//		print "trans-" . $transformer . "<br/>";
		continue;
	}

	switch ( substr( $type, 0, 3 ) ) {
		case "var":
		case "big":
		case "int":
			print_input( $name, $value /*, "checked(this)" */ );
			break;
		case "dat":
			print_date( $name, $value );
			break;
		case "tin":
			if ( $type == "tinyint(1)" ) {
				print_checkbox( $name );
			}
			break;
		case "bit":
			print_checkbox( $name );
			break;
		case "flo":
		case "dou":
			print_input( $name, $value );
			break;
		default:
			print $type;
	}
	print_cell_end();
	print_row_end();
	// print_br();
}
fwrite( $output, "print \"</table>\";\n" );;
fwrite( $output, "}\n" );

fwrite( $output, "?>" );

// print_button("activity", "save_new");

fclose( $output );

$output = fopen( $post_filename, "w" );

fwrite( $output, "<?php
require_once ('$root_file');
// require_once('../header.php');
require_once(ROOT_DIR . '/niver/data/translate.php');
require_once(ROOT_DIR . '/niver/gui/inputs.php');\n" );

// fwrite($output, "<br/>------------------------\n");

$result = mysqli_query( $conn, $sql );
$row    = mysqli_fetch_assoc( $result ); // Skip id.

//$row=mysqli_fetch_assoc($result); // Get first
//$name = $row["Field"];
// fwrite($output, "if (isset(\$_GET[\"" . $name . "\"]) $" . $name . " = \$_GET[\"" . $name . "\"];\n");
// $args = "\$id, $" . $name;
// $func_code .= "if (strlen(\$" . $name . ") > 0) \$sql .= \"" . $name . "\" . \" = '\" . $" . $name . ". \"', \";\n";


fwrite( $output, "
if (! isset(\$_GET[\"operation\"])) { 
	print \"No operation sent<br/>\";
	die(1);
}

\$operation = \$_GET[\"operation\"];
switch (\$operation) {
case \"update\":
	update_" . $table_name . "();
	break;
	
case \"insert\":
	insert_" . $table_name . "();
	break;
}
" );


$update_func = "function update_" . $table_name . "()";
$insert_func = "function insert_" . $table_name . "()";

append_to_function( "
{
" );

$update_func .= "\$id = \$_GET[\"id\"];\n";

$update_func .= "\t\$sql = \"update " . $table_name . " set \";\n";
$insert_func .= "\t\$sql = \"insert into " . $table_name . " (\";\n";
$insert_func .= "\t\$values = \" VALUES (\";
";

while ( $row = mysqli_fetch_assoc( $result ) ) {
	$name = $row["Field"];
	// $args .= ", " . "$" . $name;
	// fwrite($output, "$" . $name . " = \$_GET[\"" . $name . "\"];\n");
	// $set_fields .= ". \", " . $name . " = '\" . $" . $name . ".\"'\"\n");
	print $name . " " . $row["Type"] . "<br/>";
	$insert_func .= "\tif (isset(\$_GET[\"" . $name . "\"])) { 
		\$sql .= \"" . $name . ", \";
	";

	switch ( substr( $row["Type"], 0, 3 ) ) {
		case "int":
		case "tin":
		case "bit":
			$update_func .= "if (isset(\$_GET[\"" . $name . "\"])) \$sql .= \"" . $name . "\" . \" = \" . \$_GET[\"" . $name . "\"]. \", \";\n";
			$insert_func .= "\t\$values .= \$_GET[\"" . $name . "\"]. \", \";\n";
//			$func_code .= " \$sql .= \"" . $name . "\" . \" = \" . $" . $name . ". \", \";\n";
			break;
		default:
//			$func_code .= "if (strlen(\$" . $name .") > 0) \$sql .= \"" . $name . "\" . \" = '\" . $" . $name . ". \"', \";\n";
//			$func_code .= "\$sql .= \"" . $name . "\" . \" = '\" . $" . $name . ". \"', \";\n";
			$insert_func .= "\t\$values .= \"'\" . \$_GET[\"" . $name . "\"]. \"', \";\n";
			$update_func .= "if (isset(\$_GET[\"" . $name . "\"])) \$sql .= \"" . $name . "\" . \" = '\" . \$_GET[\"" . $name . "\"]. \"', \";\n";

	}
	$insert_func .= "}
	";
	// $func_code .= "if (strlen(\$" . $name . ") > 0) \$sql .= \"" . $name . "\" . \" = '\" . $" . $name . ". \"', \";\n");

	// $type = $row["Type"];
}
$update_func .= "\$sql = rtrim(\$sql, \", \");\n";
$insert_func .= "\$values = rtrim(\$values, \", \") . \")\";
\$sql = rtrim(\$sql, \", \") . \")\" . \$values;";

$update_func .= "\$sql .= \" where id = \$id;\";\n";
append_to_function( "sql_query(\$sql);
print \"done\";
}
" );

fwrite( $output, $update_func );
fwrite( $output, $insert_func );


print "done";

