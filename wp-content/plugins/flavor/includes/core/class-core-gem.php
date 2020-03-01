<?php


class Core_Gem {
	private $object_types;
	protected static $_instance = null;

	/**
	 * Core_Gem constructor.
	 *
	 * @param $object_types
	 */
	public function __construct( ) {
		$this->object_types = array();
		self::$_instance = $this;
	}

	/**
	 * @return Core_Gem|null
	 */
	public static function getInstance(): ?Core_Gem {
		return self::$_instance;
	}

	public function AddVirtualTable($table, $args, $class = __CLASS__)
	{
		$this->object_types[$table] = $args;

		AddAction("gem_v_add_" . $table, array($class, "v_add_wrapper"), 10, 3);
		AddAction("gem_v_edit_" . $table, array($class, "v_edit_wrapper"), 10, 3);
		AddAction("gem_v_show_" . $table, array($class, "v_show_wrapper"), 10, 3);
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

		// Import
//		AddAction("gem_import_$table", array($class, "import_wrapper"), 10, 3);
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

	static function add_wrapper($result, $id, $args)
	{
		$operation = GetArg($args, "operation", null);
		if (! $operation)  return __FUNCTION__ . ":no operation";

		$table_name = substr($operation, 8);
		if (! $table_name or ! strlen($table_name)){
			print __FUNCTION__ . ": coding error. invalid table in '$operation'.<br/>";
			return false;
		}
		return $result . self::GemAddRow(get_table_prefix() . $table_name, null, $args);
	}

	static function import_wrapper($result, $id, $args)
	{
		$table = GetArg($args, "table_name", null);
		if (! $table) return "no table selected";
		self::GemImport($table);
	}

	static function v_show_wrapper($operation, $id, $args)
	{
		$table_name = substr($operation, 11);
		if (! $id) return "id is missing";
		$instance = self::getInstance();
		if (! $instance) return __CLASS__ . ":" . __FUNCTION__ . " no instance. Call constructor first";
		return $instance->GemVirtualTable($table_name, $args);
	}

	static function show_wrapper($operation, $id, $args)
	{
		$table_name = substr($operation, 10);
		if (! $id) return "id is missing";
		return self::GemElement($table_name, $id, $args);
	}

	static function GemAddRow($table_name, $text = null, $args = null){
		$result = "";
		if (! $table_name or ! strlen($table_name)) return __FUNCTION__ . ": No table selected";

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
		$enable_import = GetArg($args, "enable_import", false);

		$post_action = null;
		$post_file = null;
		do {
			if ($post_action = GetArg($args, "post_action", null)) break;
			if ($post_file = GetArg($args, "post_file", null)) // For regular next_page and all post.
				$post_action = $post_file."?operation=gem_show&table=".$table_id;
		} while (0);

		if (! $post_action) {
			return debug_trace(1) . "<br/>".
			       "post_file is missing";
		}

		$no_data_message = GetArg($args, "no_data_message", "No data for now");
		if ($title) $result .= Core_Html::gui_header(2, $title);

		$page = GetArg($args, "page_number", 1);
		$rows_per_page = GetArg($args, "rows_per_page", 10);

		if ($rows_data){
			if (! isset($args["checkbox_class"])) $args["checkbox_class"] = "checkbox_" . $table_id; // delete_items depend on that

			$args["count"] = count($rows_data);

			$div_content = "";

			if (count($rows_data) == $rows_per_page + 1) { // 1 for the header
				$div_content .= Core_Html::gui_header(1, "page_number", true, true) . " " . Core_Html::gui_label("gem_page_" . $table_id, $page) . "<br/>";
				// $result .= Core_Html::GuiHyperlink("Next page", AddToUrl("page_number", $page + 1)) . " ";
				$div_content .= Core_Html::GuiButton("btn_gem_next_" . $table_id, "Next", array("action" => "gem_next_page(" . QuoteText($post_action)  . "," . QuoteText($table_id) . ")"));
				$div_content .= Core_Html::GuiButton("btn_gem_all__" . $table_id, "All", array("action" => "gem_all_page(" . QuoteText($post_action)  . "," . QuoteText($table_id) . ")"));
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
			$result .= Core_Html::GuiHyperlink("Add", AddToUrl("operation" , "gem_add_" . $table_id)) . " ";

		if ($post_file and $edit or $enable_import) {
			$checkbox_class = GetArg($args, "checkbox_class", "class");
			$result .= Core_Html::GuiButton( "btn_delete_$table_id", "delete",
				array( "action" => "delete_items(" . QuoteText( $checkbox_class ) . "," . QuoteText( $post_file ) . ")" ) );
			$result .= Core_Gem::GemImport( $table_id, $args );
		}

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
		$table_prefix = get_table_prefix();

		$args["events"] = 'onchange="update_table_field(\'' . $post_file . '\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';
		$sql = GetArg($args, "sql", null);

		if (! $sql){
			$fields = GetArg($args, "fields", null);
			if ($fields) $sql = "select " . CommaImplode($fields) . " from ${table_prefix}$table_name ";
			else $sql = "select * from ${table_prefix}$table_name";

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

	function GemVirtualTable($table_name, $args)
	{
		if (! $table_name) die("no table given:" . __FUNCTION__);

		if (! isset($this->object_types[$table_name])) return __FUNCTION__ . ": coding error $table_name wasn't added";

		// $args["table_prefix"] = (isset($this->object_types[$table_name]['prefix']) ? $this->object_types[$table_name]['prefix'] : get_table_prefix());
		$query_part = GetArg( $this->object_types["$table_name"], 'query_part', null);
		if (! $query_part) return "Query part for $table_name is missing";

		$query_id = GetArg($args, "id", null);
		if (! $query_id) return "id is missing";
		$query = sprintf($query_part, $query_id);
		$fields = GetArg($args, "fields", '*');

		$args["sql"] = "select $fields $query";

		return self::GemTable($table_name, $args);
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
		$post_file = GetArg($args, "post_file", null);
		do {
			$action_file = GetArg($args, "import_action", null);
			if ($action_file) break;
			if ($post_file) {
				$action_file = $post_file . "?operation=import_$table_name";
				break;
			}
			throw new Exception("must supply import action or post_file");
		} while (0);

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
		<script> ';
		if ($selector) $result .= '	wait_for_selection();';
		else $result .= 'let forms = document.getElementsByName("submit");
        forms.forEach(element => element.disabled = false);
        let upcsv = document.getElementById("' .$form_id . '");
        upcsv.action = "' . $action_file . '"';

		$result .= '</script>';

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
			case "page_number":
				return self::GemTable($table, $args);
		}
	}

// Header that attach to upper screen.
// Gets array of elements to display next to logo
}