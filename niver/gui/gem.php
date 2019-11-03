<?php

function GemAddRow($table_name, $text, $args){
	$result = "";

	$result .= gui_header(1, $text);
	$result .= NewRow($table_name, $args);
	$post = GetArg($args, "post_file", null);
	$next_page = GetArg($args, "next_page", null);
	if (! $post) die(__FUNCTION__ . ":" . $text . "must send post file");
	if ($next_page){
		$result .= '<script>
		function next_page(xmlhttp) {
		    if (xmlhttp.response === "done") window.location = "' . $next_page .
		           '"; else alert(xmlhttp.response);
		}
		</script>';
		$result .= "\n" . gui_button("add_row", "data_save_new('" . $post . "', '$table_name', next_page)\n", "add");
	} else {
		$result .= gui_button("add_row", "data_save_new('" . $post . "', '$table_name')", "add");
		$result .= gui_button("add_row", "data_save_new('" . $post . "', '$table_name', success_message)", "add and continue");
	}

	return $result;
}

function GemElement($table_name, $row_id, $args)
{
	$result = "";
	$title = GetArg($args, "title", null);

	// Set defaults
	if (!isset($args["transpose"])) $args["transpose"] = true;
	if (!isset($args["edit"])) $args["edit"] = true;

	if ($title)
		$result .= gui_header(1, $title, true, true) . " " . gui_label("id", $row_id);

	$result .= GuiRowContent($table_name, $row_id, $args);

	if (GetArg($args, "edit", false)) {
		$result .= gui_button( "btn_save", "data_save_entity('/niver/data/data-post.php', '$table_name', " . $row_id . ')', "שמור" );
		$result .= gui_button( "btn_cancel", "cancel_entity('/focus/focus-post.php', 'im_task_templates', " . $row_id . ')', "cancel" );
	}
	return $result;
}

// Data is updated upon change by the client;
function GemTable($table_name, $args){
	$result = "";

	$title = GetArg($args, "title", "content of table " . $table_name);
	$result .= gui_header(2, $title);
	$args["events"] = 'onchange="update_table_field(\'/niver/data/data-post.php\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';
	$sql = GetArg($args, "sql", null);

	if (! $sql){
		$fields = GetArg($args, "fields", null);
		if ($fields) $sql = "select " . comma_implode($fields) . " from $table_name ";
		else $sql = "select * from $table_name";
	}
	$query = GetArg($args, "query", null);
	if ($query) $sql .= " where $query";

	$order = GetArg($args, "order", null);
	if ($order) $sql .= " order by $order";

	$args["count"] = 0;
	$table = GuiTableContent($table_name, $sql, $args);

	$page = GetArg($args, "page", 1);
	$rows_per_page = GetArg($args, "rows_per_page", 10);

	if (! $table) return null;

	$result .= $table;

	if ($args["count"] == $rows_per_page + 1) { // 1 for the header
		$result .= gui_hyperlink("Next page", add_to_url("page", $page + 1)) . " ";
	}
	if ($page > 1)
		$result .= gui_hyperlink("Previous page", add_to_url("page", $page - 1));


	if ($button_text = GetArg($args, "button_text", null)){
		$button_function = GetArg($args, "button_function", null);

		$result .= gui_button("btn_gem", $button_function, $button_text);
	}

	return $result;
}

function GemSearch($table_name, $args = null)
{
	$search_fields = GetArg($args, "search_fields", null);
	if (! $search_fields) {
		$search_fields = sql_query_array_scalar("describe  $table_name");
	}
	if (!$args) $args = array();
	$args["transpose"] = true;
	$args["fields"] = $search_fields; /// Not tested...
//	$events = GetArg($args, "events", "onchange=changed_field(%s)");

	print NewRow($table_name, $args);
//	foreach ($search_fields as $field)
//	{
//		if ($events) {
//			$field_events = sprintf($events, quote_text($field));
//			$args["events"] = $field_events;
//		}
//		$type = sql_type($table_name, $field);
//		$input = gui_input_by_type($field, $type, $args);
//		array_push($search_table, array($field, $input));
//	}
//	print gui_table_args($search_table, $table_name, $args);

	$script_function = GetArg($args, "search", "search_table('".  $table_name . "')");

	print gui_button("btn_search", $script_function, "Search");
}

function GemImport($table_name, $args = null)
{
	$result = "";
	$header = GetArg($args, "header", "Import to $table_name");
	$action_file = GetArg($args, "import_action", null);
	if (! $action_file) throw new Exception("must supply import action");

	$result .= gui_header(1, $header);

	$selector = GetArg($args, "selector", null);

	$args["events"] = "onchange='change_import'";
	if ($selector) $result .= $selector("import_select", null, $args);

	// Selecting gui
	$result .= '<form name="upload_csv" id="upcsv" method="post" enctype="multipart/form-data">'.
	           im_translate('Load from csv file') .
	           '<input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="טען" name="submit">

        <input type="hidden" name="post_type" value="product"/>
    </form>';

	// Setting the action upon selection
	$result .= '
<script>
function change_import() {
		let selected = get_value_by_name("import_select");
		let upcsv = document.getElementById("upcsv");
		upcsv.action = \'' . $action_file . '&selection=\'+ selected;
		}
	</script>';

	return $result;
}