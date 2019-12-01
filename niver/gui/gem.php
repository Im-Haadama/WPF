<?php
require_once(ROOT_DIR . '/niver/gui/input_data.php' );

// Id, class, $args
/**
 * @param $table_name
 * @param $text
 * @param $args
 *
 * @return string
 */
function GemAddRow($table_name, $text, $args){
	$result = "";

	$result .= gui_header(1, $text);
	$result .= NewRow($table_name, $args);
	$post = GetArg($args, "post_file", get_url(1));
	$next_page = GetArg($args, "next_page", null);
	if (! $post) die(__FUNCTION__ . ":" . $text . "must send post file");
	if ($next_page){
		$result .= '<script>
		function next_page(xmlhttp) {
		    if (xmlhttp.response.substr(0, 4) === "done") {
		        let new_id = xmlhttp.response.substr(5);
		      window.location = "' . $next_page . '&new=" + new_id;  
		    }  else alert(xmlhttp.response);
		}
		</script>';
		$result .= "\n" . gui_button("add_row", "data_save_new('" . $post . "', '$table_name', next_page)\n", "add");
	} else {
		$result .= gui_button("add_row", "data_save_new('" . $post . "', '$table_name')", "add");
		$result .= gui_button("add_row", "data_save_new('" . $post . "', '$table_name', success_message)", "add and continue");
	}

	return $result;
}

/**
 * @param $table_name
 * @param $row_id
 * @param $args
 *
 * @return string
 * @throws Exception
 */
function GemElement($table_name, $row_id, $args)
{
	$result = "";
	$title = GetArg($args, "title", null);
	$post = GetArg($args, "post_file", '/niver/data/data-post.php'); // Default should be used in first stages.
	// Later, add permissions checks in custom post.

	// Set defaults
	if (!isset($args["transpose"])) $args["transpose"] = true;
	if (!isset($args["edit"])) $args["edit"] = true;

	if ($title)
		$result .= gui_header(1, $title, true, true) . " " . gui_label("id", $row_id);

	$result .= GuiRowContent($table_name, $row_id, $args);

	if (GetArg($args, "edit", false)) {
		$result .= gui_button( "btn_save", "data_save_entity('" . $post . "', '$table_name', " . $row_id . ')', "save" );
		$result .= gui_button( "btn_cancel", "cancel_entity('" . $post . "', '$table_name', " . $row_id . ')', "delete" );
	}
	return $result;
}


/**
 * @param $rows_data
 * @param $args
 * page - GemArray gets the data. The page param used only for the next/previous. TODO: Check if next is needed (last page).
 * @param $table_id
 *
 * @return string|null
 * @throws Exception
 */
function GemArray($rows_data, &$args, $table_id)
{
	$result = "";

	$title = GetArg($args, "title", null);
	$no_data_message = GetArg($args, "no_data_message", "No data for now");
	if ($title) $result .= gui_header(2, $title);

	$page = GetArg($args, "page", 1);
	$rows_per_page = GetArg($args, "rows_per_page", 10);

	if ($rows_data){
		if (! isset($args["checkbox_class"])) $args["checkbox_class"] = "checkbox_" . $table_id; // delete_items depend on that

		$args["count"] = count($rows_data);

		$result .= gui_table_args( $rows_data, $table_id, $args );

		if (count($rows_data) == $rows_per_page + 1) { // 1 for the header
			$result .= gui_hyperlink("Next page", add_to_url("page", $page + 1)) . " ";
			$result .= gui_hyperlink("All", add_to_url("page", -1)) . " ";
		}
		if ($page > 1)
			$result .= gui_hyperlink("Previous page", add_to_url("page", $page - 1));

		$result .= gui_hyperlink("search", add_to_url("search", "1"));

	} else {
		$result .=  $no_data_message . gui_br();
	}

//	if ($button_text = GetArg($args, "button_text", "add")){
//		$button_function = GetArg($args, "button_function", "data_save_new('" . get_url() . ", '" . $table_id . "', location_reload)"); // "function () { window.location = $button_target; }
//
//		$result .= gui_button("btn_" . $button_text, $button_function, $button_text);
//	}
	$result .= gui_hyperlink("Add", add_to_url(array("operation" => "show_add_" . $table_id)));

	$post_file = GetArg($args, "post_file", null);
	if ($post_file)
		$result .= gui_button("btn_delete_$table_id", "delete_items(" . quote_text($args["checkbox_class"]) . "," .
			quote_text($post_file) . ")", "delete");

//	$args = array();
//	$search_url = "search_table('im_bank', '" . add_param_to_url($url, "search", "1") . "')";
//	$args["search"] = $search_url; //'/fresh/bank/bank-page.php?operation=do_search')";
//	GemSearch("im_bank", $args);

	 // print "XXXX" . $result . "YYYY";
	return $result;
}

// Data is updated upon change by the client;
/**
 * @param $table_name
 * @param $args
 *
 * @return string|null
 * @throws Exception
 */
function GemTable($table_name, &$args)
{
	if (! $table_name) die("Error #N2 no table given");
	if (! isset($args["title"])) $title = "content of table " . $table_name;

	$args["events"] = 'onchange="update_table_field(\'/niver/data/data-post.php\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';
	$sql = GetArg($args, "sql", null);

	if (! $sql){
		$fields = GetArg($args, "fields", null);
		if ($fields) $sql = "select " . comma_implode($fields) . " from $table_name ";
		else $sql = "select * from $table_name";

		$query = GetArg($args, "query", null);
		if ($query) $sql .= " where $query";

		$order = GetArg($args, "order", null);
		if ($order) $sql .= " order by $order";
	}

	// $args["count"] = 0;
	// $table = GuiTableContent($table_name, $sql, $args);

	// print "c=" . $args["count"] . "<br/>";
	$rows_data = TableData( $sql, $args);

	return GemArray($rows_data, $args, $table_name);
}

/**
 * @param $table_name
 * @param null $args
 *
 * @throws Exception
 */
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

/**
 * @param $table_name
 * @param null $args
 *
 * @return string
 * @throws Exception
 */
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

// Header that attach to upper screen.
// Gets array of elements to display next to logo
function GemHeader($id, $class, $args)
{
	$args["print_logo"] = false;

	$header_elements = $args["header_elements"];

	$result = HeaderText($args); /// Displays <html><header><scripts><styles>

	$div_text = "";
	if (! $header_elements) $header_elements = [];
	$i = 0;

	foreach ($header_elements as $element)
		$div_text .= '<div class="' . $class . '_child" id="element_' . $i++ . '">' . $element . "</div>";

	$args["class"] = $class;

	$result .= GuiDiv($id, $div_text, $args );

	return $result;
}