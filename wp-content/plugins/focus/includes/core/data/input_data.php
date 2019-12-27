<?php
function NewRow($table_name, $args)
{
	$args["edit"] = true;
	$args["table_name"] = $table_name;
	$args['events'] = 'onchange="changed_field(\'%s\')"';
	$args["add_field_suffix"] = false;
	$args["new_row"] = true; // Selectors can use that to offer creating of new row. E.g, new project.
	              $args["table_id"] = $table_name . "_new";
	if (! isset($args["hide_cols"])) $args["hide_cols"] = [];
	$row = GuiRowContent($table_name, null, $args);
	return $row;
}
