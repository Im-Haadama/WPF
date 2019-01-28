<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 08/01/19
 * Time: 09:26
 */

function append_to_function( $s ) {
	global $update_func;
	global $insert_func;

	$update_func .= $s;
	$insert_func .= $s;
}

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
	fwrite( $output, "if (\$id > 0 and isset(\$values[\"" . $field_name . "\"])) print \" value=\\\"\" .  $value . \"\\\"\";" );
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
	// Label
	fwrite( $output, "print \"<label id=\\\"" . $field_name . "\\\">\";" );
	// Value if set
	fwrite( $output, 'if (isset (' . $value . ')) print ' . $value . '; ' );
	// Close
	// fwrite ($output, "print ';'" );
//	fwrite( $output, "print \"<label id=\\\"" . $field_name . "\\\">\" . " . $value . ";" );

//    fwrite($output, "if (\$id > 0) print \" value=\\\"\" .  $value . \"\\\"\";";
	fwrite( $output, "print \"</label>\";" );
}

function print_checkbox( $field_name, $init = true ) {
	global $output;
	$value = "\$values[\"" . $field_name . "\"]";
	fwrite( $output, "print \"<input id=\\\"" . $field_name . "\\\" type = \\\"checkbox\\\" \";" );
	if ( $init ) {
		fwrite( $output, "if (isset($value) and $value ) print \" checked \";" );
		fwrite( $output, "print \" onchange=\\\"changed(this)\\\";\";" );
	}
//	fwrite( $output, "print \" onchange=\\\"changed(this)\\\">\";" );

	fwrite( $output, "print \">\";" );
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
