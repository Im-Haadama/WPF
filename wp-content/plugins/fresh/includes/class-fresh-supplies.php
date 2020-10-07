<?php


class Fresh_Supplies {

	/**
	 * Fresh_Supplies constructor.
	 */
	public function __construct() {
//		self::init_hooks();
	}

	function init()
	{

	}

	public function init_hooks()
	{
		AddAction('admin_menu', array($this, 'admin_menu'));
		AddAction('create_supply_from_file', array($this, 'create_supply_from_file'));
		AddAction('create_supply', array($this, 'create_supply'));
		AddAction('delete_supplies', array($this, 'delete_supplies'));
		AddAction('show_supply', array($this, 'show_supply'));
		AddAction('set_mission', array($this, 'set_mission'));
		AddAction('supply_add_item', array($this, 'supply_add_item'));
		AddAction('supply_delete_items', array($this, 'supply_delete_item'));
		AddAction('supply_update_items', array($this, 'supply_update_items'));
		AddAction('supply_save_comment', array($this, 'supply_save_comment'));
		AddAction('supply_upload', array($this, 'supply_upload'));
		AddAction('got_supply', array($this, 'got_supply_wrap'));
		AddAction('supplies_merge', array($this, 'supplies_merge'));
		AddAction('supplies_send', array($this, 'supplies_send'));

		Core_Gem::AddTable("supplies");
	}

//	public function NewSupply()
//	{
//		$post_file = Fresh::getPost();
//		$data = Core_Html::HeaderText();
//		$data .= self::SupplyHeader();
//		$data .= Core_Html::gui_table_args(array("header"=>array("פריט", "כמות")), "supply_items");
//
////		$data .= '<iframe name="load-result""></iframe>';
////
////		$data .='<form name="upload_csv" id="upcsv" method="post" enctype="multipart/form-data">
////				טען אספקה מקובץ CSV
////				<input type="file" name="fileToUpload" id="fileToUpload">
////				<input type="submit" value="החלף" name="submit">
////				<input type="hidden" name="post_type" value="product"/>
////			</form>';
//
//		// add the first line to the new supply
//		$data .= "<script> supply_new_add_line('" . $post_file . "')";
//		$data .= "</script>";
//
//		return $data;
//	}

	public function show_supply()
	{
		$id = GetParam("id", true);
		$internal    = GetParam("internal", false, true);
//			$categ_group = GetParam( "categ_group" );
		$Supply      = new Fresh_Supply( $id );
			// print header_text(true);
		return $Supply->Html( $internal, true );
	}

	public function admin_menu()
	{
		$menu = new Core_Admin_Menu();
		$menu->AddSubMenu("woocommerce", "edit_shop_orders",
			array('page_title' => 'Supplies', 'function' => array("Fresh_Supplies" , 'main' )));
	}

	//create_supplies:
	//$prod_id  = $params[ $i + 0 ];
	//$supplier = $params[ $i + 1 ];
	//$quantity = $params[ $i + 2 ];
	//$units    = $params[ $i + 3 ];
	//$price    = get_buy_price( $prod_id, $supplier );

	static function main()
	{
		$result = Core_Html::GuiHeader(1, "Supplies management");

		$operation = GetParam("operation", false, null);
		if ($operation) {
			print apply_filters($operation, "");
			return;
		}
		$tabs = [];

//		print Core_Html::GuiHyperlink("Create supply", GetUrl(1) . "?operation=new_supply");
//		$args["title"] = "Supplies to get";       print SuppliesTable( SupplyStatus::Sent, $args );
//		$args["title"] = "Supplies to collect";   print SuppliesTable( SupplyStatus::OnTheGo, $args );
//		$args["title"] = "Supplies done";         print SuppliesTable( SupplyStatus::Supplied, $args );

//		array_push( $tabs, array( "supplies_upload", "Upload", self::NewSupply() ) );
		array_push( $tabs, array( "supplies_new", "Supplies to send", self::SuppliesTable(eSupplyStatus::NewSupply) ) );
		array_push( $tabs, array( "supplies_create", "New", self::NewSupply() ) );
		array_push( $tabs, array( "supplies_import", "Import", self::ImportSupply() ) );
		array_push( $tabs, array( "supplies_sent", "Supplies to come", self::SuppliesTable(eSupplyStatus::Sent)));
		array_push( $tabs, array( "supplies_archive", "Archive", self::SuppliesTable(eSupplyStatus::Supplied)));

		$args = [];

		$args["tabs_load_all"] = true;
		$result .= Core_Html::GuiTabs($tabs, $args);

		$selected_tab = GetParam("selected_tab", false, null);

		$result .= Core_Html::gui_div("logging");

		print  $result;
	}

	static function print_driver_supplies( $mission_id = 0 ) {
		// Self collect supplies
		$data = "";

		$supplies = self::mission_supplies($mission_id);

		if ( count( $supplies ) ) {
			foreach ( $supplies as $supply ) {
//			   print "id: " . $supply . "<br/>";
				$s = new Fresh_Supply($supply);
				$data .= $s->SupplyText();
					// print_supply( $supply );
			}
		}

		return $data;
	}

	static function mission_supplies($mission_id)
	{
		$sql  = "SELECT s.id FROM im_supplies s
          JOIN im_suppliers r
          WHERE r.self_collect = 1
          AND s.supplier = r.id
          AND s.status IN (1, 3)" .
		        " AND (s.picked = 0 or isnull(s.picked))";

		if ( $mission_id ) {
			$sql .= " AND s.mission_id = " . $mission_id;
		}
		// DEBUG $data .= $sql;

		return SqlQueryArrayScalar( $sql );

	}


	static function SuppliesTable( $status, $args = null )
	{
		switch ($status)
		{
			case eSupplyStatus::NewSupply:
			case eSupplyStatus::Sent:
				$sql = "SELECT id, supplier, date(date) FROM im_supplies WHERE status = $status " .
				       " ORDER BY 3 desc";
				break;

			case eSupplyStatus::OnTheGo:
				$sql = "SELECT id, supplier, date(date), mission_id FROM im_supplies WHERE status = $status " .
				       " ORDER BY 3 desc";
				break;

			default:
				$sql = "SELECT id, supplier, date(date) FROM im_supplies WHERE status = $status " .
				       " ORDER BY 3 desc";
				$args["drill"] = true;
		}

		$args["sql"] = $sql;
		$args["header"] = array("Id", "Supplier", "Date", "Mission");
		$args["add_checkbox"] = true;
		$args["selectors"] = array("supplier" => 'Fresh_Supplier::gui_select_supplier', "mission_id" => 'Flavor_Mission::gui_select_mission');
		$args["links"] = array("id" => Fresh_Supply::getLink("%s"));
		$args["checkbox_class"] = self::gui_select_supply_status(null, $status);
		$args["edit"] = false;
		$args["post_file"] = Fresh::getPost();

		$result = Core_Gem::GemTable("supplies", $args);
		if ($result == "No data for now<br/>") return null;

		$post_file = Fresh::getPost();
		// $result .= Core_Html::GuiButton("btn_delete", "close_supplies('" . $status_name . "')", "close");
		if ($status == eSupplyStatus::NewSupply){
			$result .= Core_Html::GuiButton("btn_send", "send", "supplies_send('$post_file')");
			$result .= Core_Html::GuiButton("btn_merge", "merge", "supplies_merge('$post_file')");
			$result .= Core_Html::GuiButton("btn_delete", "delete", "supplies_delete('" . Fresh::getPost() . "', 'new')");
		}
		return $result;

		// return DoSuppliesTable( $sql );
	}

	static function gui_select_supply_status($id, $value, $args = null)
	{
		$args["values"] = array(
			eSupplyStatus::NewSupply => "new",
			eSupplyStatus::Sent => "sent",
			eSupplyStatus::OnTheGo => "on the go",
			eSupplyStatus::Supplied => "supplied",
			eSupplyStatus::Merged => "merged",
			eSupplyStatus::Deleted => "delete");

		if (is_null($id)) return $args["values"][$value];

		return Core_Html::GuiSimpleSelect($id, $value, $args);
	}

	static function SupplyHeader($header, $event = '')
	{
		$data = Core_Html::GuiHeader( 1, $header );
//		$event = ''; // 'onchange="new_supply_change(\'' . Fresh::getPost() . '\')"'
		$args = array("edit" => 1, "prepare"=>false);
		$data .= Core_Html::gui_table_args(array(
			array(
				Core_Html::GuiHeader( 2, "בחר ספק" ),
				Core_Html::GuiHeader( 2, "בחר מועד" ),
				Core_Html::GuiHeader( 2, "בחר משימה" )
			),
			array(
				Fresh_Supplier::gui_select_supplier( "supplier_select", null, array("events" => $event)),
				Core_Html::gui_input_date( "date", "", date('y-m-d'),  'onchange="change_supplier()"'),
				Flavor_Mission::gui_select_mission( "new_mission", "", array("events"=>"gui_select_mission") )
			)
		),
			"supply_info", $args);

		return $data;
	}

	static function ImportSupply()
	{
		$action = Fresh::getPost() . "?operation=supply_upload";

		$result = self::SupplyHeader("יבוא הזמנה", 'onchange="supply_new_supplier_selected(this, \'' . $action . '\')"');

		$result .= '<form name="upload_csv" method="post" enctype="multipart/form-data">' .
		           ImTranslate('Load from csv file') .
		           '<input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="טען" name="submit" disabled>
    	</form>
		</script>';

		return $result;
	}

	static function NewSupply()
	{
		$post_file = Fresh::getPost();
//		$data = Core_Html::HeaderText();

		$data = self::SupplyHeader("הזמנה חדשה");

		$data .=Core_Html::GuiHeader( 2, "בחר מוצרים" );

		$data .= Core_Html::gui_table_args( array( array( "פריט", "כמות", "קג או יח" ) ),
			"supply_items" );

		$data .= Core_Html::gui_div("div_log",Core_Html::gui_label("log", ""));

		$data .= Core_Html::GuiButton( "btn_add_line", "הוסף שורה", "supply_new_add_line('". $post_file . "')" );
		$data .= Core_Html::GuiButton( "btn_add_item", "הוסף אספקה", "supply_add('$post_file')");

		// add the first line to the new supply
		$data .= "<script> supply_new_add_line('" . $post_file . "')";
		$data .= "</script>";

		return  $data;
	}

	function create_supply_from_file() {
		$supplier_id = GetParam( "supplier_id" );
		$S = new Fresh_Supplier($supplier_id);
		print ImTranslate( "Creating supply for" ) . " " . $S->getSupplierName() . " <br/>";

		if (! isset($_FILES['fileToUpload']))
			return "No file selected";

		$tmp_file = $_FILES["fileToUpload"]["tmp_name"];
		$date     = GetParam( "date", true );
		$args     = array( "needed_fields" => array( "name" => 1, "quantity" => 1 ) );
		$s        = Fresh_Supply::CreateFromFile( $tmp_file, $supplier_id, $date, $args );
		if ( $s ) {
			print $s->EditSupply( true );
		}
		return __FUNCTION__;
	}

	/////////////
	// Actions //
	/////////////

	function supply_add_item()
	{
		$supply_id = GetParam("supply_id", true);
		$prod_id = GetParam("prod_id", true);
		$q = GetParam("quantity", true);

		$S = new Fresh_Supply($supply_id);
		return $S->AddLine($prod_id, $q);
	}

	function supply_save_comment()
	{
		$supply_id = GetParam("id", true);
		$text = GetParam("text", true);
		$s = new Fresh_Supply($supply_id);
		return $s->setText($text);
	}

	function supply_delete_item()
	{
		$supply_id = GetParam("supply_id", true);
		$params = GetParamArray("params", true);

		$S = new Fresh_Supply($supply_id);
		foreach ($params as $param) {
			if (!$S->DeleteLine( $param )) return false;
		}
		return true;
	}

	function supply_update_items()
	{
		$result = "";
		$supply_id = GetParam("supply_id", true);
		$params = GetParamArray("params", true);

		$Supply = new Fresh_Supply($supply_id);
		$supplier_id = $Supply->getSupplierID();
		$pricelist = new Fresh_PriceList($supplier_id);
		// Double update - the supply and also the pricelist
		for ( $pos = 0; $pos < count( $params ); $pos += 3 ) {
			$line_id = $params[ $pos ];
			$q       = $params[ $pos + 1 ];
			$price = $params[$pos + 2];
			MyLog( "update supply line" . $line_id . " q= " . $q );
			$Supply->UpdateLine($line_id, $q);

			// Update the pricelist.
			$prod_id = $Supply->GetProduct($line_id);
			$pricelist_id = Fresh_Catalog::PricelistFromProduct($prod_id, $supplier_id);
			// print "pl=" . $pricelist_id . "<br/>";
			if ($pricelist_id)
				$pricelist->Update($pricelist_id, $price);
			else
				$result .= "can't find pricelist for " . get_product_name($prod_id) . " " . $prod_id;
		}
		if (! strlen($result)) return true;
		return $result;
	}

	function set_mission()
	{
		$mission_id = GetParam("mission_id", true);
		$supply_id = GetParam("supply_id", true);
		$m = new Fresh_Supply($supply_id);
		return $m->setMissionID($mission_id);
	}

	public function create_supply()
	{
		$params = GetParamArray("params", true);
		$supplier_id = GetParam("supplier_id", true);
		MyLog( __METHOD__);

		$supply = Fresh_Supply::CreateSupply($supplier_id);
		for ( $i = 0; $i < count( $params ); $i += 2 ) {
			$prod_id = $params[$i];
			$quantity = $params[$i+1];
			// units - $params[$i+2];
			$supply->addLine($prod_id, $quantity);
			// print $prod_id . " " . $supplier . " " . $quantity . " " . $units . "<br/>";
		}
		return true;
	}

	public function supplies_merge()
	{
		$params = GetParamArray("params");
		for ( $i = 0; $i < count( $params ); $i ++ ) {
			$supply_id = $params[$i];
			$s = new Fresh_Supply($supply_id);
			if ( $s->getStatus() != eSupplyStatus::NewSupply ) {
				print "ניתן לאחד רק הספקות במצב חדש ";

				return false;
			}
		}
		$supply_id = $params[0];
		unset( $params[0] );
		return self::do_merge_supplies( $params, $supply_id );
	}

	public function supplies_send()
	{
		$params = GetParamArray("id");
		for ( $i = 0; $i < count( $params ); $i ++ ) {
			$supply_id = $params[$i];
			$s = new Fresh_Supply($supply_id);
			if ( $s->getStatus() != eSupplyStatus::NewSupply ) {
				print "ניתן לשלוח רק הספקות במצב חדש ";

				return false;
			}
		}
		return self::do_send_supplies( $params);
	}


	public function delete_supplies()
	{
		$supplies = GetParamArray("params", true);
		foreach ($supplies as $supply_id) {
			$supply = new Fresh_Supply($supply_id);
			if (! $supply->delete()) return false;
		}
		return true;
	}

	static function got_supply_wrap()
	{
		$id = GetParam("supply_id", true);
		if (! ($id > 0)) {
			print "Must supply supply id. Got $id";
			return false;
		}
		// https://fruity.co.il//wp-content/plugins/fresh/post.php?operation=got_supply&supply_id=1249&supply_total=2&supply_number=1&net_amount=3&is_invoice=0

		$supply_total  =GetParam("supply_total", true);

		$supply_number = GetParam("supply_number", true);

		$net_amount= GetParam("net_amount", true);

		$is_invoice = GetParam("is_invoice", true);

		$document_date = GetParam("document_date", false, null);

		$S = new Fresh_Supply($id);

		return $S->got_supply($supply_total, $supply_number, $net_amount, $is_invoice ? FreshDocumentType::invoice : FreshDocumentType::supply, $document_date );
	}

	function do_merge_supplies( $params, $supply_id ) {
		// Read sum of lines.
		$sql     = "SELECT sum(quantity), product_id, 1 FROM im_supplies_lines
WHERE status = 1 AND supply_id IN (" . $supply_id . ", " . rtrim( implode( ",", $params ) ) . ")" .
		           " GROUP BY product_id, status ";
		$result  = SqlQuery($sql );
		$results = array();

		while ( $row = mysqli_fetch_row( $result ) ) {
			array_push( $results, $row );
		}

		// Move all lines to be in merged status
		$sql = "UPDATE im_supplies_lines SET status = " . eSupplyStatus::Merged . " WHERE supply_id IN ("
		       . $supply_id . ", " . rtrim( implode( ",", $params ) ) . ")";

		SqlQuery( $sql );

		// Insert new lines
		$sql = "INSERT INTO im_supplies_lines (status, supply_id, product_id, quantity) VALUES ";
		foreach ( $results as $row ) {
			$sql .= "( " . eSupplyStatus::NewSupply . ", " . $supply_id . ", " . $row[1] . ", " . $row[0] . "),";
		}
		$sql = rtrim( $sql, "," );
		SqlQuery( $sql );

		$sql = "UPDATE im_supplies SET status = " . eSupplyStatus::Merged .
		       " WHERE id IN (" . rtrim( implode( $params, "," ) ) . ")";

		return SqlQuery( $sql );
	}

	function do_send_supplies( $params ) {
		// Read sum of lines.
		foreach ($params as $supply_id)
		{
			$s = new Fresh_Supply($supply_id);
			if (!$s->Send()) return false;
		}
		return true;
	}

}
