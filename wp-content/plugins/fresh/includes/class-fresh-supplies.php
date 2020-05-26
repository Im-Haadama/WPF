<?php


class Fresh_Supplies {


	/**
	 * Fresh_Supplies constructor.
	 */
	public function __construct() {
		self::init_hooks();
	}

	public function init_hooks()
	{
		AddAction('admin_menu', array($this, 'admin_menu'));
		AddAction('create_supply_from_file', array($this, 'create_supply_from_file'));
	}
	public function admin_menu()
	{
		$menu = new Core_Admin_Menu();
		$menu->AddSubMenu("woocommerce", "edit_shop_orders",
			array('page_title' => 'Supplies', 'function' => array("Fresh_Supplies" , 'main' )));

	}

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

		array_push( $tabs, array( "supplies_create", "New", self::NewSupply() ) );
		array_push( $tabs, array( "supplies_new", "Supplies to send", self::SuppliesTable(eSupplyStatus::NewSupply) ) );
		array_push( $tabs, array( "supplies_sent", "Supplies to come", self::SuppliesTable(eSupplyStatus::OnTheGo)));
		array_push( $tabs, array( "supplies_archive", "Archive", self::SuppliesTable(eSupplyStatus::Supplied)));

		$args = [];

		$result .= Core_Html::GuiTabs($tabs, $args);

		$selected_tab = GetParam("selected_tab", false, null);

		if ($selected_tab) {
			$args["tabs_load_all"] = false;
			$args["selected_tab"] = $selected_tab;
		} else {
			$args["tabs_load_all"] = true;
		}

		$result .= Core_Html::gui_div("logging");

		print  $result;

	}

	static function print_driver_supplies( $mission_id = 0 ) {
		// Self collect supplies
		$data = "";
		$sql  = "SELECT s.id FROM im_supplies s
          JOIN im_suppliers r
          WHERE r.self_collect = 1
          AND s.supplier = r.id
          AND s.status IN (1, 3)" .
		        " AND (s.picked = 0 or isnull(s.picked))";

		// print $sql;

		if ( $mission_id ) {
			$sql .= " AND s.mission_id = " . $mission_id;
		}
		// DEBUG $data .= $sql;

		$supplies = SqlQueryArrayScalar( $sql );

		if ( count( $supplies ) ) {
			foreach ( $supplies as $supply ) {
//			   print "id: " . $supply . "<br/>";
				$data .= print_supply( $supply );
			}
		}

		return $data;
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
		$args["selectors"] = array("supplier" => 'gui_select_supplier', "mission_id" => 'gui_select_mission');
		$args["links"] = array("id" => AddToUrl(array( "operation" =>"show", "id" => "%s")));
		$args["checkbox_class"] = self::gui_select_supply_status(null, $status);
		$args["edit"] = false;
		$args["post_file"] = Fresh::getPost();

		$result = Core_Gem::GemTable("im_supplies", $args);
		if ($result == "No data for now<br/>") return null;

		// $result .= Core_Html::GuiButton("btn_delete", "close_supplies('" . $status_name . "')", "close");
		if ($status == eSupplyStatus::NewSupply){
			$result .= Core_Html::GuiButton("btn_send", "send", "send_supplies()");
			$result .= Core_Html::GuiButton("btn_merge", "merge", "merge_supplies()");
			$result .= Core_Html::GuiButton("btn_delete", "delete", "supply_delete('new')");
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

	static function NewSupply()
	{
		$post_file = Fresh::getPost();

		$data = "";
		$data .= Core_Html::gui_header( 1, "יצירת אספקה" );
		$data .= Core_Html::gui_table_args(array(
			array(
				Core_Html::gui_header( 2, "בחר ספק" ),
				Core_Html::gui_header( 2, "בחר מועד" ),
				Core_Html::gui_header( 2, "בחר משימה" )
			),
			array(
				Fresh_Supplier::gui_select_supplier( "supplier_select", null, array("events" => 'onchange="new_supply_change(\'' . GetUrl() . '\')"')),
				Core_Html::gui_input_date( "date", "", date('y-m-d'),  'onchange="change_supplier()"'),
				Fresh_Packing::gui_select_mission( "new_mission", "", array("events"=>"gui_select_mission") )
				// gui_select_mission( "mis_new")
			)
		),
			"supply_info",
			array("edit" => 1, "prepare"=>false));

		$data .=Core_Html::gui_header( 2, "בחר מוצרים" );

		$data .= Core_Html::gui_table_args( array( array( "פריט", "כמות", "קג או יח" ) ),
				"supply_items" );

		$data .= Core_Html::GuiButton( "btn_add_line", "הוסף שורה", "supply_new_add_line('". $post_file . "')" );
		$data .= Core_Html::GuiButton( "btn_add_item", "הוסף אספקה", "supply_add()");

		$data .= '<iframe name="load-result""></iframe>';

		                                                      $data .='<form name="upload_csv" id="upcsv" method="post" enctype="multipart/form-data">
				טען אספקה מקובץ CSV
				<input type="file" name="fileToUpload" id="fileToUpload">
				<input type="submit" value="החלף" name="submit">
				<input type="hidden" name="post_type" value="product"/>
			</form>';

		$data .= "<script> supply_new_add_line('" . $post_file . "'); </script>";
		return $data;
	}

	function create_supply_from_file() {
		return "lalal";
		$supplier_id = GetParam( "supplier_id" );
		$S = new Fresh_Supplier($supplier_id);
		print ImTranslate( "Creating supply for" ) . " " . $S->getSupplierName() . " <br/>";

		if (! isset($_FILES['fileToUpload']))
			return "No file selected";

//		$tmp_file = $_FILES["fileToUpload"]["tmp_name"];
//		$date     = GetParam( "date", true );
//		$args     = array( "needed_fields" => array( "name" => 1, "quantity" => 1 ) );
//		$s        = Fresh_Supply::CreateFromFile( $tmp_file, $supplier_id, $date, $args );
//		if ( $s ) {
//			$s->EditSupply( true );
//		}
		return __FUNCTION__;
	}

}

//function handle_supplies_operation($operation)
//{
//	switch ( $operation )
//	{
//		case "show":
//		case "get":
//			$id = GetParam("id", true);
//			 get_supply($id);
//			 break;
//
//		case "supply_pay":
//			print "supply pay<br/>";
//			$id   = $_GET["id"];
//			$date = $_GET["date"];
//			supply_set_pay_date( $id, $date );
//			break;
//
//		case "get_business":
//			// print header_text(false); DONT!!!
//			$supply_id = $_GET["supply_id"]; // מספר אספקה שלנו
//			supply_business_info( $supply_id );
//			break;
//
//		case "supplied":
//			$supply_ids = GetParamArray("ids");
//			foreach ($supply_ids as $supply_id)
//				supply_supplied($supply_id);
//			print "done";
//			break;
//
//		case "got_supply":
//			$supply_id     = GetParam("supply_id", true); // מספר אספקה שלנו
//			$supply_total  = $_GET["supply_total"]; // סכום
//			$supply_number = $_GET["supply_number"]; // מספר תעודת משלוח
//			$net_amount    = GetParam( "net_amount" );
//			$is_invoice    = GetParam( "is_invoice" );
////			print "ii=" . $is_invoice . "<br/>";
//			$doc_type      = $is_invoice ? FreshDocumentType::invoice : FreshDocumentType::supply;
////			print "dt=" . $doc_type;
////			die(1);
//			$document_date = GetParam( "document_date" );
//			$bid           = got_supply( $supply_id, $supply_total, $supply_number, $net_amount, $doc_type, $document_date );
//			if ( $bid > 0) print "done";
//			break;
//
//		case "send":
//			$params = $_GET["id"];
//			$ids    = explode( ',', $params );
//			if (send_supplies( $ids ))
//				sent_supplies( $ids );
//			break;
//
//		case "print":
//			$params = GetParam("id", true);
//			$ids    = explode( ',', $params );
//			print_supplies_table( $ids, true );
//			break;
//
//		case "create_delta":
//			create_delta();
//			break;
//
//		case "create_supplies":
//			$params = $_GET["params"];
//			create_supplies( explode( ',', $params ) );
//			break;
//
//		case "get_supply":
//			$supply_id   = $_GET["id"];
//			$internal    = isset( $_GET["internal"] );
//			$categ_group = GetParam( "categ_group" );
//			$Supply      = new Fresh_Supply( $supply_id );
//			// print header_text(true);
//			print $Supply->Html( $internal, true, $categ_group );
//			break;
//
//		case "get_supply_lines":
//			$supply_id = $_GET["id"];
//			$internal  = isset( $_GET["internal"] );
//			HtmlLines( $supply_id, $internal );
//			break;
//
//		case "get_comment":
//			$supply_id = $_GET["id"];
//			$s         = new Fresh_Supply( $supply_id );
//			print $s->getText();
//			break;
//
//		case "show_all":
//			$args = array();
//			print load_scripts(true);
//			print Core_Html::gui_header(1, "Supply management");
//			print gui_div("results");
//			$args["title"] = "Supplies to send";      print SuppliesTable( SupplyStatus::NewSupply, $args );
//			print Core_Html::GuiHyperlink("Create supply", GetUrl(1) . "?operation=new_supply");
//			$args["title"] = "Supplies to get";       print SuppliesTable( SupplyStatus::Sent, $args );
//			$args["title"] = "Supplies to collect";   print SuppliesTable( SupplyStatus::OnTheGo, $args );
//			$args["title"] = "Supplies done";         print SuppliesTable( SupplyStatus::Supplied, $args );
//			break;
//
//		case "delete_supplies":
//			MyLog( "delete supplies" );
//			$params = explode( ',', $_GET["params"] );
//			if (delete_supplies( $params ))
//				print "done";
//			break;
//
//		case "sent_supplies":
//			MyLog( "sent supplies" );
//			$params = explode( ',', $_GET["params"] );
//			sent_supplies( $params );
//			break;
//
//		case "delete_lines":
//			MyLog( "delete lines" );
//			$params = GetParamArray( "params" );
//			delete_supply_lines( $params );
//			break;
//
//		case "merge_supplies":
//			MyLog( "merge supplies" );
//			$params = explode( ',', $_GET["params"] );
//			merge_supplies( $params );
//			break;
//
//		case 'update_lines':
//			MyLog( "update lines" );
//			$params = explode( ',', $_GET["params"] );
//			$supply_id = GetParam("supply_id", true);
//			print update_supply_lines( $supply_id, $params );
//			break;
//
////			var request = post_file + "?operation=update_field" +
////			              "&field_name=" + field_name +
////			              "&value=" + encodeURI(value) +
////			              "&id=" + id;
//
//		case 'update_field':
//			$field_name = GetParam( "field_name" );
//			$value      = GetParam( "value" );
//			$id         = GetParam( "id" );
//			$s          = new Fresh_Supply( $id );
//			$s->UpdateField( $field_name, $value );
//
//			break;
//
//		case 'save_comment':
//			$comment = $_GET["text"];
////			print $comment . "<br/>";
//			$supply_id = $_GET["id"];
////			print $supply_id . "<br/>";
//			$sql = "UPDATE im_supplies SET text = '" . $comment .
//			       "' WHERE id = " . $supply_id;
//
//			if (sql_query($sql)) print "done";
//
//			break;
//
//		case "add_item":
//			$prod_id = GetParam("prod_id", true);
//			$q = GetParam("quantity", true);
//			$supply_id = GetParam("supply_id", true);
//			$supply = new Fresh_Supply( $supply_id );
//			$price = get_buy_price( $prod_id, $supply->getSupplier() );
//			if (supply_add_line( $supply_id, $prod_id, $q, $price ))
//				print "done";
//			break;
//
//		case "set_mission":
//			$supply_id  = $_GET["supply_id"];
//			$mission_id = $_GET["mission_id"];
//			$s          = new Fresh_Supply( $supply_id );
//			$s->setMissionID( $mission_id);
//			break;
//
//		case "delivered":
//			$ids = explode( ",", $_GET["ids"] );
//			foreach ( $ids as $supply_id ) {
//				got_supply( $supply_id, 0, 0 );
//			}
//			print "delivered";
//
//			break;
//
//		case "check_open":
//			$count =  sql_query_single_scalar("select count(*) from im_supplies where status = " . SupplyStatus::NewSupply  );
//			print $count;
//			break;
//
//		case "new_supply":
//			print new_supply();
//			break;
//
//		case "show_archive":
//			$args = [];
//			$args["drill"] = true;
//			print SuppliesTable( SupplyStatus::Supplied, $args );
//			break;
//
//		default:
//			print $operation . " not handled <br/>";
//
//	}


