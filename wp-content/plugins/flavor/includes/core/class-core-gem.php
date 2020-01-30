<?php


class Core_Gem {
	private $object_types;

	/**
	 * Core_Gem constructor.
	 *
	 * @param $object_types
	 */
	public function __construct( ) {
		$this->object_types = null;
	}

	static function AddTable($table, $class = 'Core_Gem')
	{
		$debug = 0; //  (get_user_id() == 1);
		// if (get_user_id() == 1) print __CLASS__ . ":" . $table;
		// New Row
		AddAction("gem_add_" . $table, array($class, 'add_wrapper'), 10, 3);

		// Edit
		AddAction("gem_edit_" . $table, array($class, 'edit_wrapper'), 10, 3);

		// Show
		AddAction("gem_show_" . $table, array($class, 'show_wrapper'), 10, 3, $debug);
	}

	static function edit_wrapper($result, $id, $args)
	{
//		var_dump($prev); print "<br/>";
//		var_dump($table_name); print "<br/>";
//		var_dump($args); print "<br/>";
//		return;
//		$id = GetArg($args, "id", null);
		if (! ($id > 0)) return __FUNCTION__ . ":bad id";

		$operation = GetArg($args, "operation", null);
		if (! $operation)  return __FUNCTION__ . ":no operation";

		$table_name = substr($operation, 9);
		return $result . self::GemElement($table_name, $id, $args);
	}

	static function add_wrapper($operation, $text, $args)
	{
		$table_name = substr($operation, 8);
		return self::GemAddRow($table_name, $text, $args);
	}

	static function show_wrapper($operation, $id, $args)
	{
		$table_name = substr($operation, 9);
		if (! $id) return "id is missing";
		return self::GemElement("im_" . $table_name, $id, $args);
	}

	static function GemAddRow($table_name, $text = null, $args = null){
		$result = "";

		if (! $text) $text = __("Add"). " " . $table_name;
		$result .= Core_Html::gui_header(1, $text);
		$result .= Core_Html::NewRow($table_name, $args);
		$post = GetArg($args, "post_file", null);
		$next_page = GetArg($args, "next_page", null);
		if (! $post) die(__FUNCTION__ . ":" . $text . "must send post_file " . $table_name);
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

	function NewElement($table_name, $args)
	{
		return Core_Html::NewRow($table_name, $args);
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
		// Later, add permissions checks in custom post.

		// Set defaults
		if (!isset($args["transpose"])) $args["transpose"] = true;
		if (!isset($args["edit"])) $args["edit"] = true;
		if (!isset($args["hide_cols"])) $args["hide_cols"] = array();
		$args["hide_cols"]["is_active"] = 1;

		if ($title)
			$result .= Core_Html::gui_header(1, $title, true, true) . " " .Core_Html::gui_label("id", $row_id);

		$check_active = GetArg($args, "check_active", false);
		if ($check_active) {
			$sql = "select is_active from $table_name where id = $row_id";
			$active = sql_query_single_scalar($sql);
			if (! $active) $result .= " not active ";
		}

		if (! ($row = Core_Html::GuiRowContent($table_name, $row_id, $args))) return null;
		$result .= $row;

		if (GetArg($args, "edit", false) and $post) {
			$result .= Core_Html::GuiButton( "btn_save", "save", array("action" => "data_save_entity('" . $post . "', '$table_name', " . $row_id . ')'));
			if ($check_active) $result .= Core_Html::GuiButton( "btn_active", $active ? "inactive" : "activate", array("action" => "active_entity(" . (1 - $active) .", '" . $post . "', '$table_name', " . $row_id . ')') );
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
		$post_file = GetArg($args, "post_file", null);
		if (! $post_file) {
			return debug_trace(1) . "<br/>".
			       "post_file is missing";
		}

		$no_data_message = GetArg($args, "no_data_message", "No data for now");
		if ($title) $result .= Core_Html::gui_header(2, $title);

		$page = GetArg($args, "page", 1);
		$rows_per_page = GetArg($args, "rows_per_page", 10);

		if ($rows_data){
			if (! isset($args["checkbox_class"])) $args["checkbox_class"] = "checkbox_" . $table_id; // delete_items depend on that

			$args["count"] = count($rows_data);

			$div_content = "";

			if (count($rows_data) == $rows_per_page + 1) { // 1 for the header
				$div_content .= Core_Html::gui_header(1, "page", true, true) . " " . Core_Html::gui_label("gem_page_" . $table_id, $page) . "<br/>";
				// $result .= Core_Html::GuiHyperlink("Next page", AddToUrl("page", $page + 1)) . " ";
				$div_content .= Core_Html::GuiButton("btn_gem_next_" . $table_id, "Next", array("action" => "gem_next_page(" . QuoteText($post_file."?operation=gem_show&table=".$table_id)  . "," . QuoteText($table_id) . ")"));
				$div_content .= Core_Html::GuiButton("btn_gem_all__" . $table_id, "All", array("action" => "gem_all_page(" . QuoteText($post_file."?operation=gem_show&table=".$table_id)  . "," . QuoteText($table_id) . ")"));
//				$div_content .= Core_Html::GuiHyperlink("All", AddToUrl("_page", -1)) . " ";
			}
			if ($page > 1)
				$div_content .= Core_Html::GuiButton("btn_gem_prev_" . $table_id, "Previous", array("action" => "gem_previous_page(" . QuoteText($post_file."?operation=gem_show&table=".$table_id)  . "," . QuoteText($table_id) . ")"));
				// $div_content .= Core_Html::GuiButton("Previous, AddToUrl("page", $page - 1));

			// if ($args["count"] > 10) $div_content .= Core_Html::GuiHyperlink("search", AddToUrl("search", "1"));

			$div_content .= Core_Html::gui_table_args( $rows_data, $table_id, $args );

			$result .= Core_Html::gui_div("gem_div_" . $table_id, $div_content);

		} else {
			$result .=  $no_data_message . Core_Html::Br();
		}

		if (GetArg($args, "add_button", true))
			// $result .= Core_Html::GuiHyperlink("Add", $post_file . "&operation=gem_add_" . $table_id) . " ";
			$result .= Core_Html::GuiHyperlink("Add", GetUrl(1) . "?operation=gem_add_" . $table_id) . " ";

		if ($post_file and $edit)
			$result .= Core_Html::GuiButton("btn_delete_$table_id", "delete",
				array("action" => "delete_items(" . QuoteText($args["checkbox_class"]) . "," . QuoteText($post_file) . ")"));

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
			print "must send post_file " . $table_name . "<br/>";
			print sql_trace();
			die(1);
		}

		$args["events"] = 'onchange="update_table_field(\'' . $post_file . '\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';
		$sql = GetArg($args, "sql", null);

		if (! $sql){
			$fields = GetArg($args, "fields", null);
			if ($fields) $sql = "select " . CommaImplode($fields) . " from $table_name ";
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

		$form_id = 'gem_import_' . $table_name;
		$args["events"] = "onchange='change_import(\"" . $action_file . "\", \"$form_id\")'";

		if ($selector) $result .= $selector("import_select", null, $args);

		// Selecting gui
		$result .= '<form name="gem_import" id="' . $form_id . '" method="post" enctype="multipart/form-data">' .
		           ImTranslate('Load from csv file') .
		           '<input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="טען" name="submit">

        <input type="hidden" name="post_type" value="product"/>
    </form>
		<script>
		wait_for_selection();
       </script>';

		// Setting the action upon selection
		return $result;
	}

	static function handle_operation($operation, $args)
	{
		if (substr($operation, 0, 3) == "gem") {
			strtok($operation, "_");  // remove gem_ if exist
			$operation = strtok("_");
		}
		$table = GetParam("table");
		switch ($operation){
			case "add":
				return self::GemAddRow($table);
			case "show":
			case "page":
				return self::GemTable($table, $args);
		}
	}

// Header that attach to upper screen.
// Gets array of elements to display next to logo
}