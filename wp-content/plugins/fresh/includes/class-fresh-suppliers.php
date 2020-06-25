<?php

//require_once dirname( FRESH_PLUGIN_FILE ) . '/includes/core/gui/inputs.php';
//require_once dirname(FRESH_PLUGIN_FILE) . '/includes/suppliers/suppliers.php';

//add_shortcode('bla', 'Fresh_Suppliers::status');

class Fresh_Suppliers {
	private $gem;

	function init_hooks()
	{
		AddAction("gem_v_show", array($this, "pricelist_header"), 9);
		AddAction("gem_v_show", array($this, "pricelist_functions"), 11);
		AddAction("suppliers_map_products", __CLASS__ . "::suppliers_map_products");
		AddAction("add_pricelist_item", array($this, 'add_pricelist_item'));
	}

	function init()
	{
		Core_Gem::AddTable( "suppliers" );

		$this->gem =Core_Gem::getInstance();

		// Products
		$args = array("query_part" => "from wp_posts p,
                     wp_postmeta m
                where post_type = 'product'
                and post_status = 'publish'
                and p.id = m.post_id
                and m.meta_key = 'supplier_id'
                and m.meta_value = %d");
		$this->gem->AddVirtualTable( "products", $args );
		$supplier_id = GetParam("supplier_id", false);

		// Pricelist
		$args = array("database_table" => "supplier_price_list",
		              "query_part" => "from im_supplier_price_list 
		              where 
		              supplier_id = %d
		              and ((select machine_update from im_suppliers where id = supplier_id) = 0 or  
		              date = supplier_last_pricelist_date(supplier_id))",
			"fields" => array("id", "product_name", "price", "date"),
			"extra_header" => array("linked product", "published", "calculated price", "price", "sale price", "open orders"),
			"order"=>"order by id",
			"prepare" => "Fresh_Pricelist_Item::add_prod_info",
//			"header_fields" => array("supplier_product_code" => "code", "product_name" => "name"),
			"import_page"=> GetUrl(),
			"post_file" => Fresh::getPost(),
			"import" => true,
			"prepare_plug" => "Fresh_Pricelist_Item::add_prod_info", 
			"action_before_import" => array(__CLASS__, "action_before_import"),
			"page_number"=>0,
			"edit"=>true,
			"edit_cols"=> array("product_name"=>1,"price"=>1),
			"v_key" => "supplier_id",
			"add_checkbox" => true);

		if ($supplier_id)
			$args["new_row"] = array ("id" => Core_Html::GuiButton("btn_add_pricelist_item", "Add",
				"supplier_add_pricelist_item('" . Fresh::getPost() . "', $supplier_id)"),
				"product_name" => "", "price"=>"");


		$this->gem->AddVirtualTable( "pricelist", $args );

		// load classes
		new Fresh_supply(1);
		new Fresh_Catalog();
	}

	static function admin_page()
	{
		$operation = GetParam("operation", false, null, true);

		Core_Gem::getInstance(); // Make sure that initiated.

		MyLog("op=$operation");
		$result = "";
		if ($operation){
			$args = self::Args("suppliers");
			$args["operation"] = $operation;
			$id = GetParam("id", false);
			$result = apply_filters( $operation, $result, $id, $args );
//			MyLog($result);
		}

		if ( !$result )
			$result = self::SuppliersTable();

		print $result;
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
		$result = Core_Html::GuiHeader(2, "Active suppliers", array("class"=>"wc-shipping-zones-heading", "close"=>false)) . "<br/>"; // . Core_Html::GuiHyperlink("Add supplier", "link", array("class"=> "page-title-action")) .'</h2>';

		$args = self::Args("suppliers");
		$args["prepare_plug"] = __CLASS__ . "::prepare_supplier_list_row";

		$args["fields"] = array("id", "supplier_name", "supplier_description");
		// $args["links"] = array("id"=> AddToUrl(array( "operation" => "show_supplier", "id" => "%s")));
		$args["query"] = "is_active = 1";
		$args["header_fields"] = array("supplier_name" => "Name", "supplier_description" => "Description");
		$args["actions"] = array(array("Show products", AddToUrl(array("operation" => "gem_v_show", "table"=>"pricelist", "supplier_id" => "%s"))));
		$args["order"] = "supplier_last_pricelist_date(id) desc";
//		$result .= "page number: " . $args["page_number"] . "<br/>";
//		$result .= "page: " . $args["page"] . "<br/>";
		$args["page_number"] = -1;
		$result .= Core_Gem::GemTable("suppliers", $args);

		// $result .= GuiTableContent("im_suppliers",null, $args);

		return $result;
	}

	static function handle()
	{
		return "lalal";
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
			"post_file" => self::getPost()
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
			          Core_Html::GuiHyperlink("delete", "remove"),
			"description" => $row['supplier_description']
		);

//		return $new_row;
	}
	static private function getPost()
	{
		return get_site_url() . "/wp-content/plugins/fresh/post.php";
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
				and status = " . eSupplyStatus::NewSupply .
		       " order by id desc limit 1";

		return SqlQuerySingleScalar($sql);
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

	static function pricelist_header($result)
	{
		$supplier = GetParam("supplier_id");
		$s = new Fresh_Supplier($supplier);
		$result .= Core_Html::GuiHeader(1, __("Supplier pricelist") . " " . $s->getSupplierName());

		return $result;
	}
	static function pricelist_functions($result)
	{
		$table_name = GetParam("table", false, null);
//		$id = GetParam("id", false, 0);
		if ("pricelist" == $table_name){
			$result .= Core_Html::GuiHyperlink("Create products", AddToUrl( "create_products", 1));
			$result .= Core_html::GuiButton("btn_map", "Map Products", array("action" => "pricelist_map_products('". Fresh::getPost() ."')"));
		}
		return $result;
	}

	function add_pricelist_item()
	{
		$supplier_id = GetParam("supplier_id", true);
		$price = GetParam("price");
		$product_name = GetParam("product_name");

		$pricelist = new Fresh_Pricelist($supplier_id);
		return $pricelist->AddOrUpdate($price, 0, $product_name);
	}
}
