<?php

//require_once dirname( FRESH_PLUGIN_FILE ) . '/includes/core/gui/inputs.php';
//require_once dirname(FRESH_PLUGIN_FILE) . '/includes/suppliers/suppliers.php';

//add_shortcode('bla', 'Fresh_Suppliers::status');

class Fresh_Suppliers {
	private $gem;

	static function init_hooks()
	{
		AddAction("create_supplies", __CLASS__ . "::create_supplies");
	}

	function init()
	{
		Core_Gem::AddTable( "suppliers" );
		$args = array("query_part" => "from wp_posts p,
                     wp_postmeta m
                where post_type = 'product'
                and post_status = 'publish'
                and p.id = m.post_id
                and m.meta_key = 'supplier_id'
                and m.meta_value = %d");

		$this->gem = new Core_Gem();

		$this->gem->AddVirtualTable( "products", $args );

		// load classes
		new Fresh_supply(0);
		new Fresh_Catalog();
	}

	static function page()
	{
		$operation = GetParam("operation");

		if ($operation){
			$id = GetParam("id", false, 0);
			$args = self::Args();

			$result = apply_filters( $operation, $operation, $id, $args );
			if ( $result ) 	return $result;
		}

		$result = self::SuppliersTable();
		print $result;
	}

	static function SuppliersTable()
	{
		$result = Core_Html::gui_header(1, "Active suppliers");

		$args = self::Args();
		$args["fields"] = array("id", "supplier_name", "supplier_description");
		// $args["links"] = array("id"=> AddToUrl(array( "operation" => "show_supplier", "id" => "%s")));
		$args["query"] = "is_active = 1";
		$args["header_fields"] = array("supplier_name" => "Name", "supplier_description" => "Description");
		$args["actions"] = array(array("Show products", AddToUrl(array("operation" => "gem_v_show_products", "id" => "%s"))));
		$result .= Core_Gem::GemTable("suppliers", $args);

		// $result .= GuiTableContent("im_suppliers",null, $args);

		return $result;
	}

	static function handle()
	{
		$operation = GetParam("operation", false, null);

		if (! $operation) {
			print self::SuppliersTable();
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
			"page"      => GetParam( "page", false, - 1 ),
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

		$args["links"] = array("id"=>"?operation=gem_show_suppliers&id=%s");

		if ( $table_name )
			switch ( $table_name ) {
			}

		return $args;
	}

	static private function getPost()
	{
		return "/wp-content/plugins/fresh/post.php";
	}


	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'suppliers' => array( __CLASS__ .'::page', 'edit_suppliers' ));          // Suppliers.
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

	static function create_supplies()
	{
		$date        = GetParam( "date", false, date('Y-m-d'));
		$supplier_id = GetParam("supplier_id", true);

		$ids = GetParamArray("params");
		$supply      = Fresh_Supply::CreateSupply( $supplier_id, $date );
		if ( ! $supply->getID() ) {
			return false;
		}
		for ( $pos = 0; $pos < count( $ids ); $pos += 2 ) {
			$prod_id  = $ids[ $pos ];
			$quantity = $ids[ $pos + 1 ];
			$price = Fresh_Pricing::get_buy_price( $prod_id, $supplier_id );
			if ( ! $supply->AddLine( $prod_id, $quantity, $price) ) {
				return false;
			}
		}
//		$mission_id = GetParam( "mission_id" );
//		if ( $mission_id ) {
//			$s->setMissionID( $mission_id );
//		}
		return $supply->getID();
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
