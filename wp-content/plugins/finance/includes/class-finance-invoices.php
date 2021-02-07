<?php
/////////////////////////
// Created 10-02-2020  //
// Agla                //
/////////////////////////

class Finance_Invoices {
	static private $_instance;
	static private $_post;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( );
		}

		return self::$_instance;
	}

	function init( Core_Hook_Handler $loader) {
		self::$_post = Flavor::getPost();
		$loader->AddAction( "invoice_add", $this, "invoice_add" );
		$loader->AddAction( "invoice_show", $this, "invoice_show" );
		$loader->AddAction( "invoice_supplier", $this, "supplier");
	}

	/**
	 * @return mixed
	 */
	public static function getPost() {
		return self::$_post;
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).

		return array(
			'finance_invoices' => array(
				'Finance_Invoices::table',
				"edit_pricelist"
			)
		);          // Invoice table
	}

	static function Args() {
		$args = [];
		Core_Data::set_args_value( $args );
		$args["post_file"] = self::getPost();

		return $args;
	}

	static function Table( $base_url ) {
		$result = "";
		if ( $operation = GetParam( "operation" ) ) {
			return apply_filters( $operation, self::Args() );
		}
		$year = GetParam( "the_year", false, date( "Y" ) );

		$result .= Core_Html::GuiHeader( 1, __( "Invoices for year" ) . " " . $year );

		$page = "EXTRACT(YEAR FROM DATE) = " . $year . " and document_type in (4, 8) and is_active=1";
		$t    = new Core_PivotTable( "im_business_info", $page,
			"date_format(date, '%m')", "part_id", "net_amount" ); // month_with_index(DATE)

		$args = array(
			"row_trans" => array( "part_id" => __CLASS__ . "::get_supplier_name" ),
			"order"     => "order by 1, 2"
		);

		$table = $t->Create(
			AddParamToUrl($base_url, array(
				"operation"     => "invoice_add",
				"document_type" => 4,
				"part_id"       => "%s",
				"date"          => $year . '-' . '%02s-28'
			) ),
			AddParamToUrl($base_url, array(
				"operation" => "invoice_supplier",
				"part_id"   => "%s"
			) ), // 'invoice_table.php?part_id=%s',
			$args );

		if ( count( $table ) > 1 ) {
			$result .= Core_Html::gui_table_args( $table, "invoices", $args );
		} else {
			$result .= "No invoices yet, for this year";
		}
		$result .= Core_Html::GuiHyperlink("Add", AddParamToUrl($base_url, "operation", "invoice_add")) . "<br/>";

		$result .= Core_Html::GuiHyperlink( "שנה קודמת", AddParamToUrl($base_url, "the_year", $year - 1 ) );

		return $result;
	}

	static function get_supplier_name( $supplier_id ) {
		$s = new Fresh_Supplier( $supplier_id );

		return $s->getSupplierName();
	}

	static function invoice_add( ) {
		$args = [];
		$args["edit"]             = true;
		$args["header_fields"]    = array(
			"part_id"       => "supplier",
			"date"          => "date",
			"reference"     => "Document number",
			"amount"        => "Amount",
			"net_amount"    => "amount without taxes",
			"document_type" => "Document type"
		);
		$args["selectors"]        = array(
			"part_id"       => "Fresh_Supplier::gui_select_supplier",
			"document_type" => __CLASS__ . "::gui_select_document_type"
		);
		$args["fields"]           = array( "part_id", "date", "ref", "amount", "net_amount", "document_type" );
		$args["mandatory_fields"] = array(
			"part_id"       => 1,
			"date"          => 1,
			"ref"           => 1,
			"amount"        => 1,
			"document_type" => 1,
			"net_amount"    => 1
		);
		$args["post_file"]        = self::getPost();

		return Core_Gem::GemAddRow( "business_info", "invoices", $args );
	}

	static function gui_select_document_type( $id = null, $selected = null, $args = null )
	{
		$DocumentTypeNames = array(
			"",
			"הזמנה",
			"משלוח",
			"זיכוי",
			"חשבונית מס",
			"אספקה",
			"תעודת משלוח",
			"העברה",
			"חשבונית מס זיכוי",
			"חשבונית מס קבלה"
		);

		$events = GetArg( $args, "events", null );
		$types  = array();
		for ( $i = 1; $i < Finance_DocumentType::count; $i ++ ) { // require_once FRESH_INCLUDES . 'fresh_delivery_enum.php';
			$value["id"]   = $i;
			$value["name"] = $DocumentTypeNames[ $i ];
			array_push( $types, $value );
		}

		return Core_Html::GuiSelect( $id, $selected, array(
			"events"   => $events,
			"selected" => $selected,
			"values"   => $types
		) );
	}

	static function supplier() {
		$result  = "";
		$part_id = GetParam( "part_id" );
		$year    = GetParam( "the_year", false, date("Y") );
		$result  .= Core_Html::GuiHeader( 2, self::get_supplier_name( $part_id ) );
		$result  .= Core_Html::GuiHeader( 3, "year " . $year );
		$page    = "EXTRACT(YEAR FROM DATE) = " . $year . " and document_type in (4, 8) and is_active=1"
		           . " and part_id = " . $part_id;
		$links   = array(
			"id"    => AddToUrl( array( "operation" => "invoice_show", "id" => "%s" ) ),
			"אספקה" => "/fresh/supplies/supply-get.php?id=%s"
		);

		$args              = [];
		$args["post_file"] = self::getPost();
		$args["links"]     = $links;

		$sql = "select id, date, amount, net_amount, ref, pay_date, supply_from_business(id)
        from im_business_info where " . $page . " and is_active = 1 order by 2";

		$args ["sql"] = $sql;
		$args["page"] = GetParam( "page" );
		$result       .= Core_Gem::GemTable( "transactions", $args );

		$date = date( 'Y-m-d', strtotime( "last day of previous month" ) );

		$result .= Core_Html::GuiHyperlink( "הוסף", "invoice_table.php?operation=add&part_id=$part_id&date=$date&document_type=4" );

		$result .= Core_Html::GuiHyperlink( "last year", AddToUrl( "year", $year - 1 ) );
		if ( $year < date( 'Y' ) ) {
			$result .= Core_Html::GuiHyperlink( "next year", AddToUrl( "year", $year + 1 ) );
		}
//
//		var_dump($result);
		return $result;

	}

	static function invoice_show()
	{
		$result = "";
		$id = GetPAram("id");
		$result .=  Core_Html::GuiHeader(1, "חשבונית מס " . $id);
		$args = self::Args();
		$args["edit"] = 1;
		$args["skip_id"] = true;
		$args["selectors"] = array("part_id" => "Fresh_Supplier::gui_select_supplier", "document_type" => __CLASS__ . "::gui_select_document_type");
		$args["transpose"] = true;
		$args["header_fields"] = array("Id", "Supplier", "Date", "Week", "Amount", "Reference", "Delivery fee", "Project", "Is active", "Pay date", "Document type", "Net amount", "Invoice file", "Invoice",
			"Occasional supplier");
		$args["check_active"] = true;
		// $result .=  GuiRowContent("im_business_info", $row_id, $args);
		// $result .=  Core_Html::GuiButton("btn_save", 'data_save_entity(\'im_business_info\', ' . $row_id .')', "שמור");
		$result .=  Core_Gem::GemElement("business_info", $id, $args);

		print $result;
	}

}