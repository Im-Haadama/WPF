<?php
require_once("sql_table.php");

/// This file contains functions the combines functions that takes data from database and draw html.
///

function NewRow($table_name, $args)
{
	$args["edit"] = true;
	$args["table_name"] = $table_name;
	$args['events'] = 'onchange="changed_field(\'%s\')"';
	$args["add_field_suffix"] = false;
	$args["new_row"] = true; // Selectors can use that to offer creating of new row. E.g, new project.
	$args["table_id"] = $table_name . "_new";
//	$args["skip_id"] =  true;
//	$args["id_field"] = "ID";
	if (! isset($args["hide_cols"])) $args["hide_cols"] = [];
	$row = GuiRowContent($table_name, null, $args);
//	debug_var($row);
	return $row;
}

