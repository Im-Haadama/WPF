<?php


class Core_Gem {
	static function GemAddRow($table_name, $text, $args){
		$result = "";

		$result .= Core_Html::gui_header(1, $text);
		$result .= Core_Html::NewRow($table_name, $args);
		$post = GetArg($args, "post_file", null);
		$next_page = GetArg($args, "next_page", null);
		if (! $post) die(__FUNCTION__ . ":" . $text . "must send post_file");
		if ($next_page){
			$result .= '<script>
		function next_page(xmlhttp) {
		    if (xmlhttp.response.substr(0, 4) === "done") {
		        let new_id = xmlhttp.response.substr(5);
		      window.location = "' . $next_page . '&new=" + new_id;  
		    }  else alert(xmlhttp.response);
		}
		</script>';
			$result .= "\n" . Core_Html::GuiButton("add_row", "add", array("action" => "data_save_new('" . $post . "', '$table_name', next_page)\n"));
		} else {
			$result .= Core_Html::GuiButton("add_row", "add", array("action" => "data_save_new('" . $post . "', '$table_name')", "add"));
			$result .= Core_Html::GuiButton("add_row", "add and continue", array("action" => "data_save_new('" . $post . "', '$table_name', success_message)", "add and continue"));
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
	static function GemElement($table_name, $row_id, $args)
	{
		$result = "";
		$title = GetArg($args, "title", null);
		$post = GetArg($args, "post_file", null);
//	var_dump($post);
		// Later, add permissions checks in custom post.

		// Set defaults
		if (!isset($args["transpose"])) $args["transpose"] = true;
		if (!isset($args["edit"])) $args["edit"] = true;

		if ($title)
			$result .= Core_Html::gui_header(1, $title, true, true) . " " .Core_Html::gui_label("id", $row_id);

		$sql = "select is_active from $table_name where id = $row_id";
		$active = sql_query_single_scalar($sql);
		if (! $active) $result .= " not active ";

		if (! ($row = Core_Html::GuiRowContent($table_name, $row_id, $args))) return null;
		$result .= $row;

		if (GetArg($args, "edit", false) and $post) {
//			if (get_user_id() == 1) var_dump($post);
			$result .= Core_Html::GuiButton( "btn_save", "save", array("action" => "data_save_entity('" . $post . "', '$table_name', " . $row_id . ')'));
			$result .= Core_Html::GuiButton( "btn_active", $active ? "inactive" : "activate", array("action" => "active_entity(" . (1 - $active) .", '" . $post . "', '$table_name', " . $row_id . ')') );
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
	static function GemArray($rows_data, &$args, $table_id)
	{
		$result = "";

		$title = GetArg($args, "title", null);
		$edit = GetArg($args, "edit", false);

		$no_data_message = GetArg($args, "no_data_message", "No data for now");
		if ($title) $result .= Core_Html::gui_header(2, $title);

		$page = GetArg($args, "_page", 1);
		$rows_per_page = GetArg($args, "rows_per_page", 10);

		if ($rows_data){
			if (! isset($args["checkbox_class"])) $args["checkbox_class"] = "checkbox_" . $table_id; // delete_items depend on that

			$args["count"] = count($rows_data);

			$result .= Core_Html::gui_table_args( $rows_data, $table_id, $args );

			if (count($rows_data) == $rows_per_page + 1) { // 1 for the header
				$result .= Core_Html::GuiHyperlink("Next page", add_to_url("page", $page + 1)) . " ";
				$result .= Core_Html::GuiHyperlink("All", add_to_url("_page", -1)) . " ";
			}
			if ($page > 1)
				$result .= Core_Html::GuiHyperlink("Previous page", add_to_url("_page", $page - 1));

			if ($args["count"] > 10) $result .= Core_Html::GuiHyperlink("search", add_to_url("search", "1"));

		} else {
			$result .=  $no_data_message . Core_Html::Br();
		}

//	if ($button_text = GetArg($args, "button_text", "add")){
//		$button_function = GetArg($args, "button_function", "data_save_new('" . get_url() . ", '" . $table_id . "', location_reload)"); // "function () { window.location = $button_target; }
//
//		$result .= Core_Html::GuiButton("btn_" . $button_text, $button_function, $button_text);
//	}
		if (GetArg($args, "add_button", true))
			$result .= Core_Html::GuiHyperlink("Add", add_to_url(array("operation" => "show_add_" . $table_id))) . " ";

		$post_file = GetArg($args, "post_file", null);
//		var_dump($post_file);
		if ($post_file and $edit)
			$result .= Core_Html::GuiButton("btn_delete_$table_id", "delete",
				array("action" => "delete_items(" . quote_text($args["checkbox_class"]) . "," . quote_text($post_file) . ")"));

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
	static function GemTable($table_name, &$args)
	{
		if (! $table_name) die("Error #N2 no table given");
		if (! isset($args["title"])) $title = "content of table " . $table_name;
		$post_file = GetArg($args, "post_file", null);
		if (! $post_file) {
			print sql_trace();
			print "must send post_file";
		}

		$args["events"] = 'onchange="update_table_field(\'' . $post_file . '\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';
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
		$rows_data = Core_Data::TableData( $sql, $args);

		return Core_Gem::GemArray($rows_data, $args, $table_name);
	}

	/**
	 * @param $table_name
	 * @param null $args
	 *
	 * @throws Exception
	 */
	static function GemSearch($table_name, $args = null)
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

		print Core_Html::GuiButton("btn_search", $script_function, "Search");
	}

	/**
	 * @param $table_name
	 * @param null $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function GemImport($table_name, $args = null)
	{
		$result = "";
		$header = GetArg($args, "header", "Import to $table_name");
		$action_file = GetArg($args, "import_action", null);
		if (! $action_file) throw new Exception("must supply import action");

		$result .= Core_Html::gui_header(1, $header);

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
}