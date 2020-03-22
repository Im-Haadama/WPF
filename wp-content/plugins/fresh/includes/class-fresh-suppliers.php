<?php

//require_once dirname( FRESH_PLUGIN_FILE ) . '/includes/core/gui/inputs.php';
//require_once dirname(FRESH_PLUGIN_FILE) . '/includes/suppliers/suppliers.php';

//add_shortcode('bla', 'Fresh_Suppliers::status');

class Fresh_Suppliers {
	private $gem;

	static function init_hooks()
	{
		AddAction("create_supplies",  "Fresh_Supply::create_supplies");
	}

	function init()
	{
		Core_Gem::AddTable( "suppliers" );

		$this->gem = new Core_Gem();

		// Products
		$args = array("query_part" => "from wp_posts p,
                     wp_postmeta m
                where post_type = 'product'
                and post_status = 'publish'
                and p.id = m.post_id
                and m.meta_key = 'supplier_id'
                and m.meta_value = %d");
		$this->gem->AddVirtualTable( "products", $args );

		// Pricelist
		$args = array("database_table" => "supplier_price_list",
		              "query_part" => "from im_supplier_price_list
                where supplier_id = %d",
			"fields" => array("id", "product_name", "price", "date"),
			"order"=>"product_name",
			"prepare" => "Fresh_Pricelist_Item::add_prod_info",
//			"header_fields" => array("supplier_product_code" => "code", "product_name" => "name"),
			"prepare_plug" => "Fresh_Pricelist_Item::add_prod_info");
		$this->gem->AddVirtualTable( "pricelist", $args );

		// load classes
		new Fresh_supply(0);
		new Fresh_Catalog();
	}

	static function admin_page()
	{
		$operation = GetParam("operation");

		$result = null;
		if ($operation){
			$id = GetParam("id", false, 0);
			$args = self::Args("pricelist");

			$result = apply_filters( $operation, $operation, $id, $args );
		}

		if ( !$result )
			$result = self::SuppliersTable();
		return $result;
	}

	static function SuppliersTable()
	{
		$result = Core_Html::GuiHeader(2, "Active suppliers", array("class"=>"wc-shipping-zones-heading", "close"=>false)) . Core_Html::GuiHyperlink("Add supplier", "link", array("class"=> "page-title-action")) .'</h2>';

		$args = self::Args("suppliers");
		$args["fields"] = array("id", "supplier_name", "supplier_description");
		// $args["links"] = array("id"=> AddToUrl(array( "operation" => "show_supplier", "id" => "%s")));
		$args["query"] = "is_active = 1";
		$args["header_fields"] = array("supplier_name" => "Name", "supplier_description" => "Description");
		$args["actions"] = array(array("Show products", AddToUrl(array("operation" => "gem_v_show_pricelist", "id" => "%s"))));
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
			"page"      => GetParam( "page_number", false, - 1 ),
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

		// $args["links"] = array("id" => AddToUrl("operation=supplier_pricelist&id", "%d"));

		if ( $table_name )
			switch ( $table_name ) {
				case "suppliers":
					$args["prepare_plug"] = __CLASS__ . "::prepare_row";
					break;
				case "pricelist":
					$args["edit"] = true;
					$args["hide_cols"] = array("id"=>1);
					break;
			}

		return $args;
	}

	static public function prepare_row($row)
	{
		// id, supplier_name, supplier_description
//		$args["links"] = array("id" => AddToUrl(array("operation" => "gem_show_suppliers" , "id" => "%d")));
		$event = "on";


		$edit_target = AddToUrl(array("operation" => "gem_show_suppliers" , "id" => $row['id']));
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

		return sql_query_single_scalar($sql);
	}
}


//	function OrdersStatus()
//	{
//		$result = "";
//
//		$order_status = sql_query_array("select count(*), post_status
//from wp_posts
//where post_status like 'wc%' and post_status not in ('wc-cancelled', 'wc-completed')
//group by post_status");
//
//		//in ('	wc-awaiting-shipment', 'wc-pending', 'wc-processing')
//		$args = [];
//		foreach ($order_status as $status => $info) {
//			$status_parts = explode("-", $order_status[$status][1]);
//			unset($status_parts[0]); // remove wc;
//			$text_status = ucfirst(implode(" ", $status_parts));
//			$order_status[$status] [0] = GuiHyperlink($order_status[$status][0], add_to_url(array("operation" => "show_orders", "status" => $info[1])));
//			$order_status[ $status ][1] = $text_status;
//		}
//		$result .= Core_Html::gui_header(2, "Orders");
//
//		array_unshift($order_status, array(im_translate("Count"), im_translate("Status")));
//
//		$result .= gui_table_args($order_status, "orders_status", $args);
//
//		return $result;
//	}

//	function Status()
//	{
//		$result = "";
//
//		$supply_status = sql_query_array("select count(*), status
//from im_supplies
//where status > 0 and status < 5
//group by status");
//
////		var_dump($supply_status);
//		//in ('	wc-awaiting-shipment', 'wc-pending', 'wc-processing')
//		$args = [];
//		foreach ($supply_status as $row_number => $info) {
//			$status = $supply_status[$row_number][1];
//			$supply_status[$row_number] [0] = GuiHyperlink($supply_status[$row_number][0], add_to_url(array("operation" => "show_supplies", "status" => $status)));
//			$supply_status[ $row_number ][1] = im_translate(get_supply_status($status));
//		}
//		$result .= Core_Html::gui_header(2, "Supply");
//
//		array_unshift($supply_status, array(im_translate("Count"), im_translate("Status")));
//
//		$result .= gui_table_args($supply_status, "orders_status", $args);
//
//		return $result;
//	}
