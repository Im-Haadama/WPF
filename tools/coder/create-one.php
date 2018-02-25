<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/11/16
 * Time: 10:40
 */

if ( ! defined( STORE_DIR ) ) {
	define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}
require_once( STORE_DIR . "/wp-config.php" );
require_once( STORE_DIR . "/im-config.php" );
require_once( "../sql.php" );
if ( ! isset( $_GET["obj_name"] ) ) {
	print "Must sent obj name";
	die( 1 );
}
$obj_name = $_GET["obj_name"];
print "obj: " . $obj_name . "<br/>";

require_once( "coder-config-" . $obj_name . ".php" );
require_once( "../translate.php" );

$filename      = $target_folder . "/c-get-$obj_name.php";
$post_filename = $target_folder . "/c-" . $obj_name . ".php";

print "creating " . $filename . "<br/>";

$output = fopen( $filename, "w" );

fwrite( $output, "<?php
require_once ('../$root_file');
// require_once('../header.php');
require_once(\"../translate.php\");
require_once('../gui/inputs.php');
require_once('" . $obj_name . ".php');
print header_text(false, false);
if (isset(\$_GET[\"debug\"])) \$debug = true;
if (isset(\$_GET[\"id\"])) {
	\$edit = true;
	\$id = \$_GET[\"id\"];
	\$operation = 'update';
} else {
	\$operation = 'insert';
}
print_entry(\$id);

?>
<script>
" );

$client_tools = __DIR__ . "/../client_tools.js";
$handle       = fopen( $client_tools, "r" );
$contents     = fread( $handle, filesize( $client_tools ) );
fwrite( $output, $contents );

fwrite( $output, "function changed(field){
        var subject = field.id;
        document.getElementById(\"chk_\" + subject).checked = true;
    }
    " );
fwrite( $output, "function save_new() {\n" );
$sql = "describe " . $table_name;
$conn->query( $sql );
$result = mysqli_query( $conn, $sql );

fwrite( $output, "var request_url = \"c-" . $obj_name . ".php?operation=<?php print \$operation;?>\";\n" );

while ( $row = mysqli_fetch_assoc( $result ) ) {
	$name = $row["Field"];
	if ( $name == "id" ) {
		fwrite( $output, "request_url = request_url + \"&id=\" + get_value(document.getElementById(\"id\"));\n" );
	} else {
		fwrite( $output, "var _" . $name . " = encodeURI(get_value(document . getElementById(\"" . $name . "\")))\n" );
		fwrite( $output, "if (document.getElementById(\"chk_" . $name . "\").checked)\n" );
		fwrite( $output, "request_url = request_url + \"&" . $name . "=\" + _" . $name . ";\n" );
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
fwrite( $output, "<button id=\"btn_" . $obj_name . "_save\" onclick =\"save_new()\">שמור" . "</button><br/>" );

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

	print_row_start();

	print "// " . $name . "<br/>";

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
	print_cell_start();
	print_checkbox( "chk_" . $name );
	print_cell_end();
	$line_id += 1;
	print_cell_start();
	print_header( translate2heb( $name ) );
	print_cell_end();
	print_cell_start();
	switch ( substr( $type, 0, 3 ) ) {
		case "var":
		case "big":
		case "int":
			print_input( $name, $value, "checked(this)" );
			break;
		case "dat":
			print_date( $name, $value );
			break;
		case "tin":
			if ( $type == "tinyint(1)" ) {
				print_checkbox( $name );
			}
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
require_once ('../$root_file');
// require_once('../header.php');
require_once(\"../translate.php\");
require_once('../gui/inputs.php');\n" );

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

function a( $s ) {
	global $update_func;
	global $insert_func;

	$update_func .= $s;
	$insert_func .= $s;
}

$update_func = "function update_" . $table_name . "()";
$insert_func = "function insert_" . $table_name . "()";

a( "
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
a( "print \$sql;
sql_query(\$sql);
}
" );

fwrite( $output, $update_func );
fwrite( $output, $insert_func );


function print_br() {
	global $output;

	fwrite( $output, "print \"<br/>\";" . "\n" );
}

function print_header( $name ) {
	global $output;
	fwrite( $output, "print \"" . $name . "\";" . "\n" );
}

function print_date( $field_name, $value ) {
	global $output;
	fwrite( $output, "print \"<input type=\\\"date\\\" id=\\\"" . $field_name . "\\\"\"; " );
	fwrite( $output, "if (\$id > 0 or isset(\$values[\"" . $field_name . "\"])) print \" value=\\\"\" .  $value . \"\\\"\";" );
	fwrite( $output, "print \" onchange=\\\"changed(this)\\\">\";" );
}

function print_input( $field_name, $value ) {
	global $output;
	fwrite( $output, "print \"<input id=\\\"" . $field_name . "\\\"\"; " );
	fwrite( $output, "if (\$id > 0 or isset(\$value[\"" . $field_name . "\"])) print \" value=\\\"\" .  $value . \"\\\"\";" );
	fwrite( $output, "print \" onchange=\\\"changed(this)\\\">\";" );
}

function print_row_start() {
	global $output;
	fwrite( $output, "print \"<tr>\";\n" );
}

function print_row_end() {
	global $output;
	fwrite( $output, "print \"</tr>\";\n" );
}

function print_cell_start() {
	global $output;
	fwrite( $output, "print \"<td>\";\n" );
}

function print_cell_end() {
	global $output;
	fwrite( $output, "print \"</td>\";\n" );
}

function print_label( $field_name, $value ) {
	global $output;
	fwrite( $output, "print \"<label id=\\\"" . $field_name . "\\\">\" . " . $value . ";" );
//    fwrite($output, "if (\$id > 0) print \" value=\\\"\" .  $value . \"\\\"\";";
	fwrite( $output, "print \"</label>\";" );
}

function print_checkbox( $field_name ) {
	global $output;
	fwrite( $output, "print \"<input id=\\\"" . $field_name . "\\\" type = \\\"checkbox\\\">\";" );
}

// <button id="save" onclick="save_changes()">שמור</button>
function print_button( $field_name, $action ) {
	global $output;
	fwrite( $output, "print \"<button id=\\\"btn_" . $field_name . "_save\\\" onclick =\\\"" . $action . "()\\\">" );
	fwrite( $output, "שמור" . "</button>\";" );
}

function print_input_options( $field_name, $table_name ) {
	global $output;
	fwrite( $output, "<br/>?>\n" );
//    fwrite($output, ">\n");
	fwrite( $output, "<datalist id=\"" . $field_name . "_options" . "\">;" );

	fwrite( $output, "<br/><?php<br/>" );

	fwrite( $output, "\$sql = \"select name from " . $table_name . "\";\n" );
	fwrite( $output, "\$results = \$mysqli->query(\$sql);\n" );

	fwrite( $output, "while( \$row = \$results->fetch_assoc() )\n" );
	fwrite( $output, "{\n" );
	// <option data-value="42" value="The answer">
	// fwrite($output, "<option value=\"" .         $row["institute"]      . "\">";

	// TODO: fwrite($output, "print \"<option value=\\\"\" . "\$row[\"name\"].\"\\\">\";\n");
	fwrite( $output, "}\n" );
	fwrite( $output, "?>" );
	fwrite( $output, "</datalist>" );
	fwrite( $output, "<input id=\"" . $field_name . "\" list=\"" . $field_name . "_options\">\n" );

	fwrite( $output, "<?php\n" );
}

function print_input_select( $field_name, $table_name ) {
	global $output;
	fwrite( $output, "<br/>?>\n" );
//    fwrite($output, ">\n");
	fwrite( $output, "<select id=\"" . $field_name . "\">;" );

	fwrite( $output, "<br/><?php\n" );

	fwrite( $output, "\$sql = \"select id, name from " . $table_name . "\";\n" );
	fwrite( $output, "\$results = \$mysqli->query(\$sql);\n" );

	fwrite( $output, "while( \$row = \$results->fetch_assoc() )\n" );
	fwrite( $output, "{\n" );
	// <option data-value="42" value="The answer">
	// fwrite($output, "<option value=\"" .         $row["institute"]      . "\">";
	fwrite( $output, "print \"<option value=\\\"\" . \$row[\"id\"].\"\\\">\";\n" );
	fwrite( $output, "print \$row[\"name\"] . \"</option>\";" );
	fwrite( $output, "}\n" );
	fwrite( $output, "?>" );
	fwrite( $output, "</select>" );
//    fwrite($output, "<input id=\"" . $field_name . "\" list=\"". $field_name . "_options\">\n");

	fwrite( $output, "<?php\n" );
}
