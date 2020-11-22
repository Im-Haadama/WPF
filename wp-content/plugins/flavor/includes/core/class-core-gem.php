<?php

class Core_Gem {
	private $object_types;
	protected static $_instance = null;

	/**
	 * Core_Gem constructor.
	 *
	 * @param $object_types
	 */
	private function __construct( ) {
		$this->object_types = array();
		self::$_instance = $this;
		AddAction("gem_v_show", array(__CLASS__, "v_show_wrap"));
		AddAction("gem_v_csv", array(__CLASS__, "gem_v_csv"));

		// Import
		// prepare
//		AddAction("gem_show_import", array(__CLASS__, "show_import_wrap"));
//		AddAction("gem_v_show_import", array(__CLASS__, "show_v_import_wrap"));

		// Do
	}

	function init_hooks(Core_Loader $loader)
	{
		$loader->AddAction("gem_do_import", $this, "do_import_wrap");
		$loader->AddAction("gem_v_do_import", $this, "do_v_import_wrap");
		$loader->AddAction("gem_show", $this, "show_wrap", 10, 3);

	}

	/**
	 * @return Core_Gem|null
	 */
	public static function getInstance(): ? Core_Gem {
		if (! self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	public function AddVirtualTable($table, $args, $class = __CLASS__)
	{
		$this->object_types[$table] = $args;

		$db_table = null;
		if (isset($args["database_table"]))	$db_table = $args["database_table"];

		AddAction("gem_v_add_" . $table, array($class, "v_add_wrapper"), 10, 3);
		AddAction("gem_add_" . $db_table, array($class, "v_add_wrapper"), 10, 3);
//		AddAction("gem_v_edit_" . $table, array($class, "v_edit_wrapper"), 10, 3);
		AddAction("gem_v_show_" . $table, array($class, "v_show_wrapper"), 10, 3);
	}

	static function AddTable($table, $class = 'Core_Gem')
	{
		$debug = 0; //  (get_user_id() == 1);
		// if (get_user_id() == 1) print __CLASS__ . ":" . $table;
		// New Row
		AddAction("gem_add_" . $table, array($class, 'add_wrapper'), 10, 3);

		// Edit
//		AddAction("gem_edit_" . $table, array($class, 'edit_wrapper'), 10, 3);

		// Show row
//		AddAction("gem_show_" . $table, array($class, 'show_wrapper'), 10, 3, $debug);

		// Import
//		AddAction("gem_import_$table", array($class, "import_wrapper"), 10, 3);

		// Page
//		AddAction("gem_page_$table", array($class, "page_wrapper"), 10, 3);
	}


	static function page_wrapper($result)
	{
		$operation = GetParam("operation", true);
		$result .= $operation;
		$table = substr($operation, 9);
		$args = [];
		$args["page"] = GetParam("param", false, 1);
		if (isset($_GET["operation"])) unset ($_GET["operation"]);
		$result .= self::GemTable($table, $args);
		print $result;
		return true;
	}

	static function edit_wrapper($result, $id, $args)
	{
		$id = GetParam("id", true);
		if (! ($id > 0)) return __FUNCTION__ . ":bad id";

		$operation = GetArg($args, "operation", null);
		if (! $operation)  return __FUNCTION__ . ":no operation";

		$table_name = substr($operation, 9);
		return $result . self::GemElement( $table_name, $id, $args);
	}

	static function add_wrapper($result, $id = null, $args = null)
	{
		$operation = GetArg($args, "operation", null);
		if (! $operation)  return __FUNCTION__ . ": no operation. Add it to \$args<br/>";

		$table_name = substr($operation, 8);
		if (! $table_name or ! strlen($table_name)){
			print __FUNCTION__ . ": coding error. invalid table in '$operation'.<br/>";
			return false;
		}
		Core_Data::set_args_value($args);

		// 10-11-2020 not sure if needed: unset($args["add_checkbox"]);
		return $result . self::GemAddRow($table_name, null, $args);
	}

	static function show_import_wrap()
	{
		$table = GetParam("table", true);
		$args = self::getInstance()->object_types[$table];
		print self::ShowImport($table, $args);
		die();
	}

	static function v_import_wrap()
	{
		$table = GetParam("table", true);
		$args = self::getInstance()->object_types[$table];
		print self::ShowVImport($table, $args);
		die();
	}

	function do_import_wrap()
	{
		$fields = GetParams(array("page", "table"));
		$table = GetParam("table", true);
		$db_prefix = GetTablePrefix($table);
//		var_dump(self::getInstance()->object_types[$v_table]);
//		$table = self::getInstance()->object_types[$v_table]['database_table'];
//		print "$v_table $table<br/>";
		if (! isset($_FILES["fileToUpload"]["tmp_name"]))
			return "No file selected";

		$file_name = $_FILES["fileToUpload"]["tmp_name"];

		$result = "";
		$unmapped = [];
		$rc = Core_Importer::Import($file_name, $table, $fields,  $unmapped);
		if (count($unmapped)) {
			$result .= self::MapFields($unmapped, $db_prefix, $table, Flavor::getPost()) .
			           Core_Html::load_scripts(array('/wp-content/plugins/flavor/includes/core/gui/client_tools.js',
				           '/wp-content/plugins/flavor/includes/core/data/data.js'));
			print $result;
//			die(1);
			return false;
		}

		$result .= $rc[0] . " rows imported<br/>";
		$result .= $rc[1] . " duplicate rows <br/>";
		$result .= $rc[2] . " failed rows <br/>";

		return $result;
	}

	static function do_v_import_wrap()
	{
		$result = "Importing";
		$v_table = GetParam("table", true);
//		var_dump(self::getInstance()->object_types[$v_table]);
		$table_args = self::getInstance()->object_types[$v_table];
		$import_fix_fields = GetArg($table_args, "import_fix_fields", null);

//		var_dump($table_args);
		$table = $table_args['database_table'];
//		print "$v_table $table<br/>";
		if (isset($_FILES["fileToUpload"]["tmp_name"]))
			$file_name = $_FILES["fileToUpload"]["tmp_name"];
		else {
			print "No file selected<br/>";
			print "Try again<br>";
			return false;
		}
		$db_prefix = GetTablePrefix();

		$unmapped = [];

		// Clear data before import and set default values.
		$fields = array();
		if ($f = GetArg($table_args, 'action_before_import', null)) $f($fields);
		do_action($v_table . "_before_import", $fields);

		$rc = Core_Importer::Import($file_name, $table, $fields, $unmapped);
		// Unmapped is seq array with the unknown headder.
		if (count($unmapped)) {
			$result .= self::MapFields($unmapped, $db_prefix, $table, $table_args['post_file']);
			print $result;
//			die(1);
			return false;
		}
		if ($f = GetArg($table_args, 'action_after_import', null)) $f($fields);

		if (is_array($rc)){
			$result .= $rc[0] . " new rows<br/>" .
			           $rc[1]. " not valid rows (duplicate or not enough data<br/>" .
			           $rc[2] . " failed rows<br/>";
		} else
			$result .= $rc;
		print $result;
	}

	static function import_wrapper($result, $id, $args)
	{
		$table = GetArg($args, "table_name", null);
		if (! $table) return "no table selected";
		self::ShowImport($table);
	}

	static function MapFields($unmapped, $db_prefix, $table, $post_file)
	{
		$result = "Those fields not mapped: " . CommaImplode($unmapped);
		// Let's map them.
		foreach ($unmapped as $u)
		{
			$u = EscapeString($u);
			// Prepare the table for the mapping.
			if (! SqlQuerySingleScalar("select count(*) from im_conversion where table_name = '$table' and header ='$u'")) {
				$sql = "insert into im_conversion (table_name, col, header) values ('$table', '', '$u')";
				SqlQuery( $sql );
			}
		}
		$instance = self::getInstance();
		$v_args = array("database_table" => "conversion", "query_part" => "from ${db_prefix}conversion where table_name = '$table'", "page_number"=>-1);

		$instance->AddVirtualTable("conversion", $v_args);
		$args = [];
		$args["id"] = "id";
		$args["post_file"] = $post_file;
		$args["selectors"] = array("col" => "gui_select_field");
		$args["import_table"] = $table;
		// $args["fields"]
		$result .= $instance->GemVirtualTable("conversion", $args);
		return $result;
	}

	static function gem_v_csv()
	{
		$v_table = GetParam("table", true);

		$args = self::getInstance()->object_types[$v_table];
		$v_key= GetArg($args, "v_key", "id");
		$args["id"] = GetParam($v_key, true);
		// Next line doesn't make sense. If returns check https://fruity.co.il/wp-admin/admin.php?page=suppliers&operation=gem_v_show&table=pricelist&id=100007
//		if (isset(self::getInstance()->object_types[$v_table]["post_file"]))
//			$args["post_file"] = self::getInstance()->object_types[$v_table]["import_page"] . "?id=" . $args['id'] . "&table=" . $v_table;

		$instance = self::getInstance();

		print $instance->GemVirtualTable($v_table, $args, true);
		die (0);
	}

	static function v_show_wrap($result = null)
	{
		if (! $result)
			$result = "";
		$v_table = GetParam("table", true);

		$args = self::getInstance()->object_types[$v_table];
		$v_key= GetArg($args, "v_key", "id");
		$args["id"] = GetParam($v_key, true);
		// Next line doesn't make sense. If returns check https://fruity.co.il/wp-admin/admin.php?page=suppliers&operation=gem_v_show&table=pricelist&id=100007
//		if (isset(self::getInstance()->object_types[$v_table]["post_file"]))
//			$args["post_file"] = self::getInstance()->object_types[$v_table]["import_page"] . "?id=" . $args['id'] . "&table=" . $v_table;

		$instance = self::getInstance();

		$result .= $instance->GemVirtualTable($v_table, $args);

		if (isset(self::getInstance()->object_types[$v_table]["import"])) {
			$show_import = GetArg($args, "show_import", null);
			if ($show_import)
				$result .= '<div><iframe src="' . $show_import . '"></iframe></div>';
			$result .= self::ShowVImport( "$v_table", $args );
		}

		$result .= Core_Html::GuiHyperlink("download as CSV", Flavor::getPost() . "?operation=gem_v_csv&table=" . $v_table . '&'. $v_key . '=' . $args["id"]) ;

		return $result;
	}

	static function v_show_wrapper($operation, $id, $args)
	{
		$table_name = substr($operation, 11);
		if (! $id) return "id is missing";
		$instance = self::getInstance();
		if (! $instance) return __CLASS__ . ":" . __FUNCTION__ . " no instance. Call constructor first";
		return $instance->GemVirtualTable($table_name, $args);
	}

	static function v_add_wrapper($operation, $id, $args)
	{
		$table_name = GetParam("table", true);
		$instance = self::getInstance();
		if (! $instance) return __CLASS__ . ":" . __FUNCTION__ . " no instance. Call constructor first";
		$args['values'] = GetParams();
		return self::GemAddRow($table_name, 'Add', $args);
	}

	static function show_wrap($result, $id = 0, $args = null)
	{
		$table_name = GetParam("table", true);
		return $result . self::GemElement($table_name, $id, $args);
	}

	static function GemAddRow($table_name, $text = null, $args = null){
		$result = "";
		if (! $table_name or ! strlen($table_name)) return __FUNCTION__ . ": No table selected";

		if (! $text) $text = __("Add"). " " . $table_name;
		$result .= Core_Html::GuiHeader(1, $text);
		$result .= Core_Html::NewRow($table_name, $args);
		$post = GetArg($args, "post_file", null);
		// $next_page = GetArg($args, "next_page", null);
		if (! $post) die(__FUNCTION__ . " :" . $text . "must send post_file " . $table_name);

		$next_page = @apply_filters("gem_next_page_" . $table_name, '');

//		print "np=$next_page<br/>";
		if ($next_page){
			$result .= '<script>
//		function next_page(xmlhttp) { 
//		    if (xmlhttp.response.indexOf("failed") === -1 ) { 
//		        let new_id = xmlhttp.response;
//		      window.location = "' . $next_page . '&new=" + new_id;  
//		    }  else alert(xmlhttp.response);
//		}
		</script>';
			$result .= "\n" . Core_Html::GuiButton("add_row", "add", array("action" => "data_save_new('" . $post . "', '$table_name', '$next_page')\n"));
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
		$db_prefix = GetTablePrefix();
		$result = "";
		$title = GetArg($args, "title", null);
		$post_file = GetArg($args, "post_file", null);
//		print "pf=$post_file<br/>";
		// Later, add permissions checks in custom post_file.

		$operation = GetParam("operation", false, null);
		$duplicate = ($operation=="gem_duplicate");

		// Set defaults
		if (!isset($args["transpose"])) $args["transpose"] = true;
		if (!isset($args["edit"])) $args["edit"] = true;
		if (!isset($args["hide_cols"])) $args["hide_cols"] = array();
		$args["v_checkbox"] = true;
		$args["checkbox_class"] = $table_name;

		// Todo: hiding default values
//		$args["hide_cols"]["is_active"] = 1;
//		$args["values"]["is_active"] = 1;

		if ($duplicate and $row_id) {
			$args["duplicate_of"] = $row_id;
			return self::GemElement( $table_name, 0, $args );
		}

		if ($title)
			$result .= Core_Html::GuiHeader(1, $title, true, true). " " . ($row_id ? Core_Html::gui_label("id", $row_id) : __("New"));

		$check_active = GetArg($args, "check_active", false);
		if ($check_active) {
			$sql = "select is_active from ${db_prefix}$table_name where id = $row_id";
			$active = SqlQuerySingleScalar($sql);
			if (! $active) $result .= " not active ";
		}

		$row_to_get = $row_id;
		$copy_of = GetArg($args, "duplicate_of", 0);
		if (! $row_to_get and $copy_of) {
			$row_to_get = $copy_of;
			$row_to_save = 0;
		}
		if (! ($row = Core_Html::GuiRowContent($table_name, $row_to_get, $args))) return null;
		$result .= $row;

		if (GetArg($args, "edit", false) and $post_file) {
			if (! $copy_of)
				$result .= Core_Html::GuiButton( "btn_save", "save", array("action" => "data_save_entity('" . $post_file . "', '$table_name', " . $row_id . ')'));
			else
				$result .= Core_Html::GuiButton("add_row", "add", array("action" => "data_save_new('" . $post_file . "', '$table_name')", "add"));
			if ($row_id) $result .= " " . Core_Html::GuiHyperlink('[' . __("Duplicate") . ']', AddToUrl("operation", "gem_duplicate"));


			if ($check_active) $result .= Core_Html::GuiButton( "btn_active", $active ? "inactive" : "activate", array("action" => "active_entity(" . (1 - $active) .", '" . $post_file . "', '$table_name', " . $row_id . ')') );
			if (GetArg($args, "allow_delete",  null))
				$result .= Core_Html::GuiButton( "btn_delete", "Delete", array("action" => "data_delete_entity('$post_file', '$table_name', $row_id)"));

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

		$only_active = GetArg($args, "only_active", 2);
		$title = GetArg($args, "title", null) . " ";
		if ( $only_active != 2) $title .= ($only_active ? __("Active") : __("All"));
		$edit = GetArg($args, "edit", false);
		$enable_import = GetArg($args, "enable_import", false);

		$post_action = null;
		$post_file = null;
		do {
			if ($post_action = GetArg($args, "import_page", null)) break;
			if ($post_action = GetArg($args, "post_action", null)) break;
			if ($post_file = GetArg($args, "post_file", null)) // For regular next_page and all post_file.
				$post_action = $post_file."?operation=gem_page_".$table_id;
		} while (0);

		$no_data_message = GetArg($args, "no_data_message", "No data for now");
		if ($title) $result .= Core_Html::GuiHeader(2, $title);

		$page = GetArg($args, "page_number", 1);

		if ($rows_data){
			if (! isset($args["checkbox_class"])) $args["checkbox_class"] = "checkbox_" . $table_id; // delete_items depend on that

			$args["count"] = count($rows_data);

			$div_content = "";

			// if ($args["count"] > 10) $div_content .= Core_Html::GuiHyperlink("search", AddToUrl("search", "1"));

			$div_content .= Core_Html::gui_table_args( $rows_data, $table_id, $args );

			$result .= Core_Html::gui_div("gem_div_" . $table_id, $div_content);

			$result .= Core_Html::PageLinks($args);

		} else {
			$result .=  $no_data_message . Core_Html::Br();
		}

		if (($page == 1 or $page == -1) and GetArg($args, "add_button", true))
			$result .= Core_Html::GuiHyperlink("[" . __("Add") . "]", AddToUrl("operation" , "gem_add_" . $table_id)) . " ";
//			$result .= self::Entry($table_id);

		$checkbox_class = GetArg($args, "checkbox_class", "class");

		if ($post_action and $enable_import) {
//			print $post_file;
			$result .= "<br/>" . Core_Html::GuiButton("btn_show_import", "Import", "gem_show_import('$post_action', '$table_id', import_div)") .
			           "<div id='import_div'></div>";
				// Core_Gem::ShowImport( $table_id, $args );
		}
		if ($post_file and $edit) {
			$delete_action = GetArg($args, "delete_action", null);
			$result .= Core_Html::GuiButton( "btn_delete_$table_id", "Delete",
				array( "action" => "delete_items(" . QuoteText( $checkbox_class ) . "," . QuoteText( $post_file ) .
				                   ($delete_action ? ", '$delete_action'" : '') .")" ) );
		}

		return $result;
	}

	static function Entry($table_id)
	{
		$entry = "entry_$table_id";
		$div_args = array("class"=>"gem_modal");
		$args = array("post_file" => Flavor::getPost());
		$html = Core_Html::GuiDiv($entry, self::GemAddRow($table_id, 'Add', $args), $div_args);
		$html .= Core_Html::GuiButton("btn_add_$table_id", "Add", "show_modal($entry);");

		return $html;
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
//		if (! TableExists($table_name)) return "Table $table_name not exists";

		// Try... Handle operation here. Works for clients types. May require to remove apply_filter from others.
		$operation = GetParam("operation", false, null);
		if ($operation) {
			$args['operation']= $operation;
			print apply_filters( $operation, '', null, $args );
		}

		if (! $table_name) die("Error #N2 no table given");
		if (! isset($args["title"])) $title = "content of table " . $table_name;
		$post_file = GetArg($args, "post_file", null);
		$table_prefix = GetTablePrefix($table_name);
		$only_active = GetArg($args, "only_active", 2);

		if (! isset($args["events"])) $args["events"] = 'onchange="update_table_field(\'' . $post_file . '\', \'' . $table_name . '\', \'%d\', \'%s\', check_result)"';
		$sql = GetArg($args, "sql", null);

		if (! $sql){
			$fields = GetArg($args, "fields", null);
			if ($fields) {
				$sql = "select " . CommaImplode( $fields );
				$sql = trim($sql, ", ") . " from ${table_prefix}$table_name ";
			}
			else $sql = "select * from ${table_prefix}$table_name";

			$query = GetArg($args, "query", " 1 ");
			if ($only_active == 1) $query .= " and is_active = 1";
			if ($query) $sql .= " where $query";

			$order = GetArg($args, "order", null);
			if ($order) $sql .= " order by $order";
		}

		$rows_data = Core_Data::TableData( $sql, $args);

		$new_row = GetArg($args, "new_row", null);
		if ($new_row) $rows_data["new_row"] = $new_row;

		if (isset($args["csv"])) {
			$result = "";
			foreach ($rows_data as $row) {
				foreach ( $row as $cell ) {
					$result .= "$cell, ";
				}
				$result .= PHP_EOL;
			}
			return $result;
		}

		$result = Core_Gem::GemArray($rows_data, $args, $table_name);

		if ($result != 2) $result .=
		       ($only_active ? Core_Html::GuiHyperlink("[" . __("All") ."]", AddToUrl("only_active",'0' ) ):
			       Core_Html::GuiHyperlink("[" . __("Active") ."]", AddToUrl("only_active",'1' )));

		       return $result;

	}

	function GemVirtualTable($table_name, $args, $csv = false)
	{
		if (! $table_name) die("no table given:" . __FUNCTION__);

		if (! isset($this->object_types[$table_name])) return __FUNCTION__ . ": coding error $table_name wasn't added";
		$database_table = $this->object_types[$table_name]['database_table'];

		// $args["table_prefix"] = (isset($this->object_types[$table_name]['prefix']) ? $this->object_types[$table_name]['prefix'] : get_table_prefix());
		$query_part = GetArg( $this->object_types["$table_name"], 'query_part', null);
		if (! $query_part) return "Query part for $table_name is missing";

		$query_id = GetArg($args, "id", null);
		if (! $query_id) return __FUNCTION__ . ": id is missing";
		$query = sprintf($query_part, $query_id);
		$order = GetArg($args, "order", "");
		$fields = GetArg($this->object_types["$table_name"], "fields", '*');

		$args["sql"] = "select " . CommaImplode($fields) . " $query $order";
		$args["add_operation"] = "gem_v_add_$table_name";
		$args = array_merge($this->object_types["$table_name"], $args);

		if ($csv) {
			$args["csv"] = true;
			unset ($_GET["operation"]);
			$file = self::GemTable($database_table, $args);
			$date = date('Y-m-d');
			$file_name = "list_${date}.csv";

			header("Content-Disposition: attachment; filename=\"" . $file_name . "\"");
			header("Content-Type: application/octet-stream");
			header("Content-Length: " . strlen($file));
			header("Connection: close");

			print $file;
			die (1);
		}
		return self::GemTable($database_table, $args);
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
			$search_fields = SqlQueryArrayScalar("describe  $table_name");
		}
		if (!$args) $args = array();
		$args["transpose"] = true;
		$args["fields"] = $search_fields; /// Not tested...
//	$events = GetArg($args, "events", "onchange=changed_field(%s)");

		print self::NewRow($table_name, $args);
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
//	print Core_Html::gui_table_args($search_table, $table_name, $args);

		$script_function = GetArg($args, "search", "search_table('".  $table_name . "')");

		print Core_Html::GuiButton("btn_search", $script_function, "Search");
	}

	static function ShowVImport($table_name, $args)
	{
		$import_page = GetArg($args, "import_page", null);
		$table_name = self::getInstance()->object_types[$table_name]['database_table'];
		$args["import_page"] = AddParamToUrl($import_page, array("operation" => "gem_v_do_import"));

		return self::ShowImport($table_name, $args);
	}

	/**
	 * @param $table_name
	 * @param null $args
	 *
	 * @return string
	 * @throws Exception
	 */
	static function ShowImport($table_name, $args = null)
	{
		$html_file = true;
		$result = "";
		$header = GetArg($args, "header", "Import to $table_name");
		$post_file = GetArg($args, "post_file", null);
		do {
			$action_file = GetArg($args, "import_page", null);
			if ($action_file) break;
			if ($post_file) {
				$action_file = AddParamToUrl($post_file , array("operation" => "gem_do_import", "table" => $table_name));
				break;
			}
			throw new Exception("must supply import action or post_file");
		} while (0);

//		$action_file = AddParamToUrl($action_file, "operation", "gem_do_import");
//		print "action file: $action_file<br/>";
		if ($html_file)
			$result .= "<html><header>" . Core_Html::load_scripts(array('/wp-content/plugins/flavor/includes/core/gui/client_tools.js')) .
			           "</header>";

		$result .= Core_Html::GuiHeader(1, $header);

		$selector = GetArg($args, "selector", null);

		$form_id = 'gem_import_' . $table_name;
		$args["events"] = "onchange='change_import(\"" . $action_file . "\", \"$form_id\")'";

		if ($selector) $result .= $selector("import_select", null, $args);
//		print $action_file . "<br/>";
		// Selecting gui
//		print "done.af=$action_file<br/>";
//		print "af=$action_file<br/>";
		$result .= '<form action="' . $action_file . '" name="upload_csv" method="post" enctype="multipart/form-data">' .
		           ETranslate('Load from csv file') .
		           '<input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="טען" name="submit">
    	</form>';
//		if ($selector) $result .= '	wait_for_selection();';
//		else $result .= 'let forms = document.getElementsByName("submit");
//        forms.forEach(element => element.disabled = false);
//        let upcsv = document.getElementById("' .$form_id . '");
//        upcsv.action = "' . $action_file . '"';

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

function gui_select_field($id, $selected, $args) {
	$table = $args["import_table"];
	$i     = 0;
	foreach ( SqlTableFields( $table ) as $field ) {
		$args["values"][ $i ]['id']   = $field;
		$args["values"][ $i ]['name'] = $field;
		$i ++;
	}
	$args["values"][$i]['id'] = "Don't import";
	$args["values"][$i]['name'] = $args["values"][$i]['id'];

	return Core_Html::GuiSelect( "table_field", $selected, $args );
}