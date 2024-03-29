<?php

class Finance_Suppliers
{
	private static $_instance;

	function init_hooks(Core_Hook_Handler $loader) {
		$loader->AddAction( "gem_v_show", $this, "pricelist_header" , 9 );
//		AddAction( "gem_v_show", $this, "draftable_products" ), 12 );
		$loader->AddAction( "gem_v_show", $this, "pricelist_functions" , 11 );
		$loader->AddAction( "suppliers_map_products", $this, "suppliers_map_products" );
		$loader->AddAction( "suppliers_map_remove", $this, "suppliers_map_remove" );
		$loader->AddAction( "add_pricelist_item", $this, 'add_pricelist_item' ) ;
		$loader->AddAction('pricelist_delete', $this, 'delete');
		$loader->AddAction("pricelist_before_import", $this, 'supplier_pricelist_before_import');
		$loader->AddAction("pricelist_update_price" , $this, 'pricelist_update_price');
		$loader->AddAction("plan_supply", $this);

		add_action('init', array($this, 'init'));

		// Init GEM
		Core_Gem::getInstance()->AddTable( "suppliers" );

		add_filter("supplier_price_list_check_valid", array($this, 'supplier_price_list_check_valid'), 10, 2);
	}

	function init()
	{
		if (! TableExists("suppliers")) return;

		$gem =Core_Gem::getInstance();

		// Products
		$args = array("query_part" => "from wp_posts p,
                     wp_postmeta m
                where post_type = 'product'
                and post_status = 'publish'
                and p.id = m.post_id
                and m.meta_key = 'supplier_id'
                and m.meta_value = %d");
		$gem->AddVirtualTable( "products", $args );
		$supplier_id = GetParam("supplier_id", false);

		// Pricelist
		$args = array("database_table" => "supplier_price_list",
		              "query_part" => "from im_supplier_price_list 
		              where 
		              supplier_id = %d
		              and ((select machine_update from im_suppliers where id = supplier_id) = 0 or  
		              date >= supplier_last_pricelist_date(supplier_id))",
			"fields" => apply_filters("finance_supplier_fields", array("id", "product_name", "price", "date")),
			"extra_header" => array("Delete", "linked product", "tag", "published", "calculated price", "price", "sale price", "open orders", "unlink","auto update"),
			"order"=>"order by id",
			"prepare" => "Fresh_Pricelist_Item::add_prod_info",
//			"header_fields" => array("supplier_product_code" => "code", "product_name" => "name"),
			"import_page"=> GetUrl(),
			"post_file" => Finance::getPost(),
			"import" => true,
			"prepare_plug" => "Fresh_Pricelist_Item::add_prod_info",
			"action_before_import" => array(__CLASS__, "action_before_import"),
	        "action_after_import" => array(__CLASS__, "action_after_import"),
			"page_number"=>0,
			"edit"=>true,
			"edit_cols"=> array("product_name"=>1,"price"=>1),
			"col_width"=> array("price"=>3),
			"v_key" => "supplier_id",
			"show_draft" => true,
			"add_checkbox" => true);

		if ($supplier_id)
			$args["new_row"] = array ("id" => Core_Html::GuiButton("btn_add_pricelist_item", "Add",
				"supplier_add_pricelist_item('" . WPF_Flavor::getPost() . "', $supplier_id)"),
				"product_name" => "", "price"=>Core_Html::GuiInput("price_new_row", "", array("size"=>3)));

		$gem->AddVirtualTable( "pricelist", $args );

		$args["prepare_plug"] = "Fresh_Suppliers::supply_plan_line";
		$args["extra_header"] = array("מלאי", "הזמנות חודש", "מכירות חודש", "הזמנות 6 חודשים", "מכירות 6 חודשים");
		$args["class"] = "sortable";

		$gem->AddVirtualTable( "supplier_products", $args );

		// load classes
//		new Fresh_supply(1);
//		Fresh_Catalog::instance();
	}

	function supplier_pricelist_before_import($fields)
	{
		$sql = "delete from im_supplier_price_list where supplier_id = " . $fields['supplier_id'] . ' and date = \'' . $fields['date'] . "'";

		return SqlQuery($sql);
	}

	function supplier_price_list_check_valid($fields, $values)
	{
		$product_name_idx = array_search( "product_name", $fields );
		$price_idx = array_search("price", $fields);

		if (false === $price_idx)
		{
			FinanceLog("import: no price");
			return false;
		}
		if (false === $product_name_idx) {
			FinanceLog("import no product name");
			return false;
		}

		$price     = $values[ $price_idx ];
		$product_name = $values[$product_name_idx];

		// Check we've got info to add.
//		if (! $account or ! $date or ! $balance and ! ($in_amount or $out_amount)) return false;
		if (! ($price > 0) or (! strlen($product_name)))
		{
//			MyLog(__FUNCTION__ . " false");
			return false;
		}

//		MyLog(__FUNCTION__ . " true $price $product_name");
		return true;

		// Check duplicate (from previous import).
//		$sql = "SELECT count(*) FROM ${table_prefix}supplier_pri WHERE account_id = " . $account .
//		       " AND date = " . QuoteText( $date );
//		if ($in_amount) $sql .= " and in_amount = " . $in_amount;
//		$sql .= " AND round(balance, 2) = " . round( $balance, 2 );
//
//		$dup = SqlQuerySingleScalar( $sql );
//
//		return (! $dup);

	}

	static function suppliers()
	{
		$operation = GetParam("operation", false, null, true);

//		print "op=$operation";
		Core_Gem::getInstance(); // Make sure that initiated.

		$result = "";
		if ($operation){
			$args = self::Args("suppliers");
			$args["operation"] = $operation;
			$id = GetParam("id", false);
			Core_Hook_Handler::instance()->DoAction($operation, $args);
			return;
//			$result = apply_filters( $operation, $result, $id, $args );
//			MyLog($result);
		}

		if ( !$result )
			$result = self::SuppliersTable();

		print $result;
	}

	function delete()
	{
		$item = new Fresh_Pricelist_Item(GetParam("id", true));
		return $item->delete();
	}

	static function suppliers_map_remove()
	{
		$ids = GetParamArray("ids");
		for($i=0; $i < count($ids); $i += 2 ){
			$product_id   = $ids[ $i ];
			$pricelist_id = $ids[ $i + 1 ];
			Fresh_Catalog::RemoveMapping( $product_id, $pricelist_id );
		}

		return true;
	}

	static function suppliers_map_products()
	{
		$ids = GetParamArray("ids");
		for($i=0; $i < count($ids); $i += 2 ){
			$product_id   = $ids[ $i ];
			$pricelist_id = $ids[ $i + 1 ];
			Fresh_Catalog::AddMapping( $product_id, $pricelist_id );
		}

		return true;
	}

	static function SuppliersTable()
	{
		$result = ""; //Core_Html::GuiHeader(2, "Suppliers", array("class"=>"wc-shipping-zones-heading", "close"=>false)) . "<br/>"; // . Core_Html::GuiHyperlink("Add supplier", "link", array("class"=> "page-title-action")) .'</h2>';

		$args = self::Args("suppliers");
		$args["prepare_plug"] = __CLASS__ . "::prepare_supplier_list_row";

		$args["fields"] = array("id", "supplier_name", "supplier_description");
		// $args["links"] = array("id"=> AddToUrl(array( "operation" => "show_supplier", "id" => "%s")));
		$args["header_fields"] = array("supplier_name" => "Name", "supplier_description" => "Description");
		$args["actions"] = array(array("Show products", AddToUrl(array("operation" => "gem_v_show", "table"=>"pricelist", "supplier_id" => "%s"))),
			array("Plan supply", AddToUrl(array("operation" => "plan_supply", "supplier_id"=>"%s"))));
		$args["order"] = "supplier_last_pricelist_date(id) desc";
		$args["title"] = "Suppliers";
//		$result .= "page number: " . $args["page_number"] . "<br/>";
//		$result .= "page: " . $args["page"] . "<br/>";
		$args["page_number"] = -1;
		$args["add_button"] = true;
		$args["only_active"] = GetParam("only_active", false, 1);
		$result .= Core_Gem::GemTable("suppliers", $args);

		// $result .= GuiTableContent("im_suppliers",null, $args);

		return $result;
	}

	static function handle()
	{
		 return __FUNCTION__ . "is used"; // ; called eg from needed.
		$operation = GetParam("operation", false, null);

		if (! $operation) {
			// print self::SuppliersTable();
			return;
		}
		$args = array("post_file" => plugin_dir_url(dirname(__FILE__)) . "post.php");

		handle_supplier_operation($operation, $args);
	}

	public function enqueue_scripts() {

	}

	static function Args( $table_name = null, $action = null ) {
		$ignore_list = [];
		$args        = array(
//			"page_number"      => GetParam( "page_number", false, -1 ),
			"post_file" => WPF_Flavor::getPost()
		);
		if ( GetParam( "non_active", false, false ) ) {
			$args["non_active"] = 1;
		}
		foreach ( $_GET as $param => $value ) {
			if ( ! in_array( $param, $ignore_list ) ) {
				$args[ $param ] = $value;
			}
		}

		if ( $table_name )
			switch ( $table_name ) {
				case "suppliers":
					break;
				case "pricelist":
					$args["edit"] = true;
					$args["hide_cols"] = array("id"=>1);
					break;
			}

		return $args;
	}

	static public function prepare_supplier_list_row($row)
	{
		// id, supplier_name, supplier_description
//		$args["links"] = array("id" => AddToUrl(array("operation" => "gem_show_suppliers" , "id" => "%d")));
		$event = "on";

		$row_id = isset($row['id']) ? $row['id'] : 0;
		$edit_target = AddToUrl(array("operation" => "gem_show", "table" => "suppliers" , "id" => $row_id));
		return array(
			"name" => Core_Html::GuiHyperlink($row["supplier_name"],  $edit_target) . "<br/>" .
			          Core_Html::GuiHyperlink("delete", AddToUrl(array("operation" => "data_set_active",
			                                                           "value"=>0,
			                                                           "id"=>$row_id,
				                                                        "table"=>"suppliers"
			          ))),
			"description" => $row['supplier_description']
		);

//		return $new_row;
	}

	function getShortcodes() {
		return null;
		//           code                   function                              capablity (not checked, for now).
//		return array( 'suppliers' => array( __CLASS__ .'::page', 'edit_suppliers' ));          // Suppliers.
	}

	static function TodaySupply($supplier_id)
	{
		$sql = "select id 
			from im_supplies 
			where supplier = $supplier_id 
			  and date = curdate()
				and status = 1 " .
		       " order by id desc limit 1";

		return SqlQuerySingleScalar($sql);
	}

	static function action_after_import(&$fields)
	{
		if ($fields['new_rows']) { // If no new rows, keep the old values.
			$supplier_id = GetParam( "supplier_id", true );
			InfoUpdate( "import_supplier_$supplier_id", $fields['date'] );
		}
	}

	// Supplier pricelist import
	static function action_before_import(&$fields)
	{
		$supplier_id = GetParam("supplier_id", true);
		// sql_query("update line_status = 2 where supplier_id =$supplier_id")
		// No delete anymore. We'll read the last date fields.
//		SqlQuery("delete from im_supplier_price_list where supplier_id = $supplier_id");
		$fields["date"] = date('Y-m-d');
		$fields["supplier_id"] = $supplier_id;
	}

	static function draftable_products()
	{
		$supplier_id = GetParam("supplier_id");

		$machine_query = (SqlQuerySingleScalar("select machine_update from im_suppliers where id = $supplier_id") ?
			(" date = (select max(date) from im_supplier_price_list where supplier_id = " . $supplier_id .")") : '1');

		$result = "";
		$sql = "select distinct sp.id as map_id, product_id, post_title
		from im_supplier_mapping sp
		     join wp_posts p
		where
		      supplier_id = $supplier_id and
		      product_id > 0 AND
		      product_id not in (select product_id
		                           from im_supplier_price_list
		                           where supplier_id = $supplier_id
		                                 and product_id is not null
		                                                       and $machine_query) and
		      p.id = product_id and
		      p.post_status = 'publish'";

//		print $sql;
		$draftable_products = SqlQuery($sql);

		if ($draftable_products) {
			$result .= Core_Html::GuiHeader(2, "מוצרים להורדה");
			$rows = array("header" => array("product_id", "product_title", "product name", "Draft", "Remove link"));
			while($product_info = SqlFetchAssoc($draftable_products))
			{
//				$prod_id = $product_info['product_id'];
				$map_id = $product_info["map_id"];
//				$product_name = $product_info['post_title'];
				$rows[$map_id] = $product_info;
//				array_push($rows, $product_info);
			}
			$args = [];
			$args["links"] = array("product_id" => Fresh_Product::get_edit_link('%d'));
			$args["id_field"] = "map_id";
			$args["actions"] = array(
				array("draft", Finance::getPost() . '?operation=draft_by_map_id&map_id=%d;action_hide_row'),
				array("remove_map", Finance::getPost() . '?operation=remove_map&id=%d;action_hide_row'));
			$args["class"] = "sortable";
			$result .= Core_Html::gui_table_args($rows, "draftable_products", $args);
		}
		return $result;
	}

	static function pricelist_header($args)
	{
		$result = "";
		$supplier_id = GetParam("supplier_id");
		$s = new Finance_Supplier($supplier_id);
		$result .= Core_Html::GuiHeader(1, __("Supplier pricelist") . " " . $s->getSupplierName());
		$result .= Core_Html::GuiButton("btn_filter", __("Filter"), "pricelist_filter()");
		if ($s->getMachineUpdate()) $result .= "This supplier list defined as been imported<br/>";

		print $result;
	}

	static function pricelist_functions($args)
	{
		$result = "";
		$table_name = GetParam("table", false, null);
//		$id = GetParam("id", false, 0);
		if ("pricelist" == $table_name){
			$result .= Core_Html::GuiHyperlink("Create products", AddToUrl( "create_products", 1));
			$result .= Core_html::GuiButton("btn_map", "Map Products", array("action" => "pricelist_map_products('". Fresh::getPost() ."')"));
//			$result .= Core_html::GuiButton("btn_remove_map", "Remove Map", array("action" => "pricelist_remove_map('". Fresh::getPost() ."')"));
			$result .= self::draftable_products();
		}
		print $result;
	}

	function add_pricelist_item()
	{
		$supplier_id = GetParam("supplier_id", true);
		$price = GetParam("price");
		$product_name = GetParam("product_name");

		$pricelist = new Fresh_Pricelist($supplier_id);
		return $pricelist->AddOrUpdate($price, 0, $product_name);
	}

	static function get_link($supplier_id)
	{
		return "/wp-admin/admin.php?page=suppliers&operation=gem_v_show&table=pricelist&supplier_id=" . $supplier_id;
	}

	static function pricelist_update_price()
	{
		$pricelist_id = GetParam("pricelist_id", true);
		$new_price = GetParam("price", true);

		$item = new Fresh_Pricelist_Item($pricelist_id);
		$rc = $item->setPrice($new_price);
		if ($item->message) print $item->message;
		return $rc;
	}

	function plan_supply($args)
	{
		$result = Core_Html::GuiHeader(1, "Plan a supply");

//		array("operation" => "gem_v_show", "table"=>"pricelist", "supplier_id" => "%s"))
		$instance = Core_Gem::getInstance();
		$args["id"] = GetParam("supplier_id");

		$result .= $instance->GemVirtualTable("supplier_products", $args);

		print $result;
	}

	static public function supply_plan_line($row, $edit = true) {
		$create_info = GetParam( "create_products", false, false );
		$supplier_id = GetParam( "supplier_id", true );

		$pl_id = $row["id"];
		if (! ($pl_id > 0)) return $row; // New row

		$item = new Fresh_Pricelist_Item( $pl_id );
		$catalog = new Fresh_Catalog();
		$linked_prods = $catalog->GetProdID( $item->getId());

		if (! $linked_prods) {
			$row["inventory"] = '';
			$row["buy1"] = '';
			$row["sell1"] = '';
			$row["buy6"] = '';
			$row["sell6"] = '';
			return $row;
		}

		$P = new Fresh_Product($linked_prods[0]);
		$row["inventory"] = $P->getStock();
		$data = $P->SellingInfo("1 month");
		foreach ($data as $key => $value)
			$row[$key. "1"] = $value;

		$data = $P->SellingInfo("6 month");
		foreach ($data as $key => $value)
			$row[$key. "6"] = $value;

//		$row = $item->AddProdInfo( $row, $edit, $create_info, $supplier_id );
		return $row;
	}

	function admin_menu() {
		$menu = Core_Admin_Menu::instance();

		// Suppliers
		$menu->AddSubMenu( "finance", "edit_shop_orders",
			array( 'page_title' => 'Suppliers', 'function' => array( "Finance_Suppliers", 'suppliers' ) ) );

		$menu->AddTop("suppliers", __("Suppliers", "e-fresh"), "/wp-admin/admin.php?page=suppliers");

	}

	public static function instance() : Finance_Suppliers
	{
		if (self::$_instance == null)
		{
			self::$_instance = new Finance_Suppliers();
		}

		return self::$_instance;
	}
}
