<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 11:58
 */

abstract class eSupplyStatus {
	const NewSupply = 1;
	const Sent = 3;
	const OnTheGo = 4;
	const Supplied = 5;
	const Merged = 8;
	const Deleted = 9;
}

class Fresh_Supply {
	private $ID = 0;
	private $Status;
	private $Date;
	private $SupplierID;
	private $Text;
	private $BusinessID;
	private $MissionID;

	/**
	 * Supply constructor.
	 *
	 * @param int $ID
	 */
	public function __construct( $ID = 0) {
		$this->ID       = $ID;
		if (! ($ID > 0)) {
			if (get_user_id() == 1) {
				print debug_trace( 10 );
			    die("ID should be > 0. To create new use CreateSupply");
			}
		}
		$row            = SqlQuerySingle( "SELECT status, date(date), supplier, text, business_id, mission_id FROM im_supplies WHERE id = " . $ID );
		if (! $row) return null;
		$this->Status     = $row[0];
		$this->Date       = $row[1];
		$this->SupplierID = $row[2];
		$this->Text       = $row[3];
		$this->BusinessID = $row[4];
		$this->MissionID  = $row[5];
	}

	/**
	 * @param mixed $SupplierID
	 */
	public function setSupplierID( $SupplierID ): void {
		$this->SupplierID = $SupplierID;
	}

	public static function CreateFromFile( $file_name, $supplier_id, $date, $args = null) {
		$debug               = false;
		$item_code_idx       = Array();
		$name_idx            = Array();
		$price_idx           = Array();
		$sale_idx            = Array();
		$detail_idx          = Array();
		$category_idx        = Array();
		$quantity_idx        = Array();
		$is_active_idx       = null;
		$picture_path_idx    = null;
		$parse_header_result = false;
		$inventory_idx       = 0;
		$prev_name           = "";
		$needed_fields = GetArg($args, "needed_fields", array("price" => 1, "name" => 1));

		$file = file( $file_name );
		for ( $i = 0; ! $parse_header_result and ( $i < 4 ); $i ++ ) {
			if ( $debug ) {
				print "trying to locate headers " . $file[ $i ] . "<br/>";

			}
			$parse_header_result = self::parse_header( $file[ $i ], $item_code_idx, $name_idx, $price_idx, $sale_idx, $inventory_idx,
				$detail_idx, $category_idx, $is_active_idx, $filter_idx, $picture_idx, $quantity_idx );
		}
		// Name is mandatory.
		if ( ! count( $name_idx ) ) {
			print ImTranslate(array("Error: ", "name column is missing."));
			print "<br/>";

			return null;
		}
		// Quantity is needed in supply (and orders).
		if ( ! count( $quantity_idx ) ) {
			if ($needed_fields["quantity"]) {
				print ImTranslate( array("Error: ", "quantity column is missing." ));

				return null;
			}
			print ImTranslate(array("Info: ", "quantity column is missing."));
		}

		// Optional
		if ( ! count( $item_code_idx ) ) {
			print ImTranslate(array("Info: ", "can't find item code header")) . "<br/>";
		}

		// Price is needed in pricelist.
		if ( ! count( $price_idx ) ) {
			if (isset($needed_fields["price"])){
				print ImTranslate(array("Error: ",  "can't find price header")) . "<br/>";

				return null;
			}
			print ImTranslate(array("Info: ", "can't find price header"));
		}
		if ( $debug ) {
			print "headers: <br/>";
			print "price : " . $price_idx[0];
			print "<br/>";
			print "name: " . $name_idx[0];
			print "<br/>";
			print "quantity: " . $quantity_idx[0];
			print "<br/>";
		}

//		print "start " . count($file) . "<br/>";
		$lines = array();
		for ( ; $i < count( $file ); $i ++ ) {
			$data = str_getcsv( $file[ $i ] );
			if ( $data ) {
				if ( isset( $is_active_idx ) and ( $data[ $is_active_idx ] == 1 ) ) {
					print $data[ $name_idx[ 0 ] ] . " not active. Skipped<br/>";
					continue;
				}
				for ( $col = 0; $col < count( $name_idx ); $col ++ ) {  // The file can have more than one column
					$name = $data[ $name_idx[ $col ] ];
					if ( $name == $prev_name ) {
						continue;
					}
					$prev_name = $name;
					$quantity  = $data[ $quantity_idx[ $col ] ];
					if (isset($data[ $price_idx[ $col ]]))
						$price     = $data[ $price_idx[ $col ] ];
					else
						$price = 0;

					if (isset($data[ $price_idx[ $col ] ]))
						$item_code = $data[ $price_idx[ $col ] ];
					else
						$item_code = 10;

					if ( isset( $detail_idx[ $col ] ) ) {
						$detail = $data[ $detail_idx[ $col ] ];
						$detail = rtrim( $detail, "!" );
					}

					if ( isset( $item_code_idx[ $col ] ) ) {
						$item_code = $data[ $item_code_idx[ $col ] ];
					}

					$new = array( $item_code, $quantity, $name, $price );
					array_push( $lines, $new );
				}
			} else {
				print "no data in line $i<br/>";
			}
		}
		if ($debug)
			var_dump($lines);

		$comments = "";
		if ( count( $lines ) ) {
//			$supplier_id = get_supplier_id($supplier_name);
			$Supply = Fresh_Supply::CreateSupply( $supplier_id, $date ); // create_supply( $supplier_id );
			if ( ! ( $Supply->getID() > 0 ) ) {
				die ( "שגיאה " );
			}

			if ( $debug ) {
				print "creating supply " . $Supply->getID() . " supplier id: " . $supplier_id . "<br/>";
			}

			foreach ( $lines as $line ) {
				$supplier_product_code = $line[0];
				$quantity              = $line[1];
				$name                  = $line[2];
				$price                 = $line[3];
//				print "name: " . $name;

				// $prod_id = get_product_id_by_name( $name );
				$search_sql = "select product_id \n" .
				              " from im_supplier_mapping\n" .
				              " where supplier_product_name = " . QuoteText( Fresh_Pricelist::StripProductName( $name ) );

				if ($debug) print $search_sql . "<br/>";
				$prod_id = SqlQuerySingleScalar( $search_sql );
//				print "prod_id: " . $prod_id . "<br/>";

				if ( ! ( $prod_id > 0 ) ) {
					$comments .= " פריט בשם " . $name . " לא נמצא. כמות " . $quantity . "\n";
					continue;
				}

				if ( $debug ) {
					print "c=" . $supplier_product_code . " q=" . $quantity . " n=" . $name . " pid=" . $prod_id . " on=" . get_product_name( $prod_id ) . " " . $name . "<br/>";
				}
				// print "prod_id: " . $prod_id . " q= " . $quantity_idx . " price = " . $price . "<br/>";
				$Supply->AddLine( $prod_id, $quantity, $price );
			}

			print " Supply " . Core_Html::GuiHyperlink($Supply->getID(), self::getLink($Supply->getID()) . " created <br/>");

			if ($debug)
				print nl2br($comments);
			$Supply->setText( $comments );

			return $Supply;
		}
	}

	static function getLink($supply_id)
	{
		return "/wp-admin/admin.php?page=supplies&operation=show_supply&id=$supply_id";
		// AddToUrl(array( "operation" =>"show_supply", "id" => "%s")
	}

	static function parse_header(
		$header_line, &$item_code_idx, &$name_idx, &$price_idx, &$sale_idx, &$inventory_idx, &$detail_idx,
		&$category_idx, $is_active_idx, &$filter_idx, &$picture_idx, &$quantity_idx
	) {
		$header = str_getcsv( $header_line );
		for ( $i = 0; $i < count( $header ); $i ++ ) {
			$key = trim( $header[ $i ] );

			switch ( $key ) {
				case 'פריט':
				case 'קוד':
				case 'קוד פריט':
				case 'מסד':
				case 'code':
					array_push( $item_code_idx, $i );
					break;
				case 'קטגוריות':
					array_push( $category_idx, $i );
					break;
				case 'הירק':
				case 'סוג קמח':
				case 'שם פריט':
				case 'שם':
				case 'תאור פריט':
				case 'תיאור פריט':
				case 'תיאור הפריט':
				case 'תיאור':
				case 'תאור':
				case 'מוצר':
				case 'שם המוצר':
				case 'name':
					array_push( $name_idx, $i );
					break;
				case 'פירוט המוצר':
					array_push( $detail_idx, $i );
					break;
				case 'price':
				case 'מחיר':
				case 'מחיר לאחר הנחה':
				case 'מחירון':
				case 'מחיר נטו':
				case 'מחיר לק"ג':
				case 'סיטונאות':
					array_push( $price_idx, $i );
					break;
				case 'מלאי (ביחידות)':
					$inventory_idx = $i;
					break;
				case 'מחיר מבצע':
					array_push( $sale_idx, $i );
					break;
				case 'כמות':
				case 'כמות יחידות':
					array_push( $quantity_idx, $i );
					break;
				default:
					// print im_translate("column %s ignored", $key) . "<br/>";

			}
			if ( strstr( $key, "הצגה" ) ) {
				$filter_idx = $i;
			}
			if ( strstr( $key, "מחיר" ) and ! in_array( $i, $price_idx ) ) {
				MyLog( "key: $key, price: " . $i, __FILE__ );
				array_push( $price_idx, $i );
			}

			if ( strstr( $key, "תמונה" ) ) {
				$picture_idx = $i;
//		print "picture idx $picture_idx<br/>";
			}
		}

		if ( count( $name_idx ) == 0 or count( $price_idx ) == 0 ) {
			return false;
		}
		print "<br/>";
		return true;
	}

//	function get_supply_status( $status ) {
//		if (! $status > 0) die ("bad supply status");
//		$status_names = array( "New", "", "Sent", "", "Finished", "", "", "", "Cancelled" );
//
//		return $status_names[ $status - 1 ];
//	}


	public function GetProduct($line_id)
	{
		return SqlQuerySingleScalar( "select product_id from im_supplies_lines where id = " . $line_id);
	}

	public static function CreateSupply( $supplier_id, $date = null ) {
		if (!$date) $date = date('y-m-d');
		$sid = self::create_supply( $supplier_id, $date );

		return new Fresh_Supply( $sid );
	}

	/**
	 * @return int
	 */
	public function getID() {
		return $this->ID;
	}

	public function getAddress()
	{
		return SqlQuerySingleScalar( "select address from im_suppliers where id = " . $this->getSupplierID() );
	}

	public function AddLine( $prod_id, $quantity, $price = 0, $units = 0 ) {
		if ( is_null( $price ) ) {
			$price = 0;
		}
		if (! $price) $price = Fresh_Pricing::get_buy_price($prod_id, $this->getSupplierID());
		$sql = "INSERT INTO im_supplies_lines (supply_id, product_id, quantity, units, price) VALUES "
		       . "( " . $this->ID . ", " . $prod_id . ", " . $quantity . ", " . $units . ", " . $price . " )";

		SqlQuery( $sql );
		$product = new WC_Product( $prod_id );
		if ( $product->managing_stock() ) {
			$product->set_stock_quantity( $product->get_stock_quantity() + $quantity );
			$product->save();
		}

		return true;
	}

	public function DeleteLine($line_id)
	{
		//function supply_delete_line( $line_id ) {
		$sql = 'UPDATE im_supplies_lines SET status = 9 WHERE id = ' . $line_id;
		return SqlQuery( $sql );
	}

	public function UpdateField( $field_name, $value ) {
		SqlQuery( "update im_supplies " .
		          "set " . $field_name . '=' . QuoteText( $value ) .
		          " where id = " . $this->ID );
	}

	public function Html( $internal, $edit, $categ_group = false ) {
		$data = "";

		$data .= $this->HtmlHeader( $edit );
		$data .= "<br/>";
		$data .= $this->HtmlLines( $internal, $edit, $categ_group );
		$data .= "<br/>";
		$data .= $this->Operation();

		return $data;
	}

	public function Operation()
	{
		$supply_id = $this->getID();
		$post_file  = Fresh::getPost();
		$footer = "";
		switch ($this->getStatus())
		{
			case eSupplyStatus::NewSupply:
				$footer .= Core_Html::GuiButton( "btn_add_line", "add", "supply_add_item('$post_file'," . $this->getID() . ")" );
				$footer .= Fresh_Product::gui_select_product( "itm_", "", array("edit"=>true) );
				$footer .= Core_Html::GuiButton("btn_del", "delete lines", "supply_delete_items('$post_file', $supply_id)");
				$footer .= Core_Html::GuiButton("btn_update", "update items", "supply_update_items('$post_file', $supply_id)");

				$footer .= "<br/>" . self::supplier_doc();
				break;

			case eSupplyStatus::Sent:
				$invoice_text =  '<br/>   <div class="tooltip">' . gui_checkbox( "is_invoice", "" ) .
				                 '<span class="tooltiptext">יש לסמן עבור חשבונית ולהשאיר לא מסומן עבור תעודת משלוח</span> </div>';

				$footer .= Core_Html::GuiButton( "btn_add_line", "add", "supply_add_item()");
				$footer .= Fresh_Product::gui_select_product( "itm_" );
				$footer .= Core_Html::GuiButton("btn_update", "update", "updateItems()");

				$footer .= Core_Html::gui_table_args( array(
					array( "חשבונית", $invoice_text ),
					array( "מספר מסמך", Core_Html::GuiInput( "supply_number") ),
					array( "סכום כולל מעמ", Core_Html::GuiInput( "supply_total") ),
					array( "סכום ללא מעמ", Core_Html::GuiInput( "net_amount") ),
					array( "תאריך", Core_Html::GuiInput( "document_date", "" ) )
				) );
				$footer .= "<br/>";

				$footer .= Core_Html::GuiButton( "btn_got_supply", "סחורה התקבלה", "got_supply()" );
				break;

			case eSupplyStatus::Supplied:
				$internal = false;
				$transaction_id = $this->getBusinessID();
				assert($transaction_id);
				$row = SqlQuerySingleAssoc("select * from im_business_info where id = " . $transaction_id);
				$doc_id = $row["ref"];
				$amount = $row["amount"];
				$footer .= Core_Html::gui_table_args(array(array("Document number", $doc_id),
					array("Total to pay", $amount)));
				break;
		}

		return $footer;
	}

	protected function supplier_doc()
	{
		$post_file = Fresh::getPost();
		$data = '<div class="tooltip">' . __("invoice") . gui_checkbox( "is_invoice", "" );
		$data .= '<span class="tooltiptext">' . __("Check for invoice, and leave uncheck for delivery note") . '</span></div>'; // יש לסמן עבור חשבונית ולהשאיר לא מסומן עבור תעודת משלוח

		$data .= Core_Html::gui_table_args( array(
			// array( "חשבונית", $invoice_text ),
			array( "מספר מסמך", Core_Html::GuiInput( "supply_number", "" ) ),
			array( "סכום כולל מעמ", Core_Html::GuiInput( "supply_total", "" ) ),
			array( "סכום ללא מעמ", Core_Html::GuiInput( "net_amount", "" ) ),
			array( "תאריך", Core_Html::gui_input_date( "document_date", "" ) )
		) );

		$supply_id = $this->ID;
		$data .= Core_Html::GuiButton("btn_got", "Supply arrived", "got_supply('$post_file', $supply_id)");
		return $data;

	}

	function got_supply( $supply_total, $supply_number, $net_amount, $document_type, $document_date = null ) {
		if ( ! $document_date ) {
			$document_date = date( 'y-m-d' );
		}
		$id  = Finance::add_transaction( $this->getSupplierID(), $document_date, - $supply_total,
			0, $supply_number, 1, - $net_amount, $document_type );
		$sql = "UPDATE im_supplies SET business_id = " . $id . " WHERE id = " . $this->ID;
		if ( ! SqlQuery( $sql ) ) return false;

		if ( ! SqlQuery( "UPDATE im_supplies SET status = " . eSupplyStatus::Supplied . " WHERE id = " . $this->ID ) ) return false;

		return $id;
	}

	public function HtmlHeader( $edit ) {
		$rows = array();
		$row  = array( "הערות" );
		// Text + Button
		$supply_id = $this->getID();
		$post_file = Fresh::getPost();
		if ( $edit ) {
			array_push( $row, Core_Html::gui_textarea( "comment", $this->Text, "onchange=\"supply_save_comment('$post_file', $supply_id)\"" ) );
		} else {
			array_push( $row, $this->Text );
		}

		array_push( $rows, $row );
		// Date
		$row = array( "תאריך אספקה" );

		if ( $edit ) {
			array_push( $row, Core_Html::gui_input_date( "date", "", $this->Date,
				"onchange=\"update_field('" . Fresh::getPost() . "', " . $this->ID . ",'date', '')\"" ) );
		} else {
			array_push( $row, $this->Date );
		}

		array_push( $rows, $row );

		$row = array( "Delivery route" );
		if ( $edit ) {
			array_push( $row, Fresh_Packing::gui_select_mission( "mis_" . $this->ID, $this->MissionID,
				array("events" => "onchange=mission_changed('" . Fresh::getPost() . "'," . $this->ID . ")" )) );
		} else {
			array_push( $row, $this->getMissionName() );
		}

		array_push($rows, array(__("status"), $this->getStatus()));

		array_push( $rows, $row );

		$args = array();
		// $args["class"] = "sortable";
		$data = Core_Html::gui_table_args( $rows, "supply_table", $args);

		return $data;
	}

	/**
	 * @return mixed
	 */
	public function getMissionID() {
		return $this->MissionID;
	}

	public function getMissionName() {
		return SqlQuerySingleScalar( "select name from im_missions where id = " . $this->getMissionID());
	}


	/**
	 * @param mixed $MissionID
	 *
	 * @return string
	 */
	public function setMissionID( $MissionID ) {
		$this->MissionID = $MissionID;

		return SqlQuery( "UPDATE im_supplies SET mission_id = " . $MissionID . " WHERE id = " .
		                             $this->ID );
	}

	// $internal - true = for our usage. false = for send to supplier.
	function HtmlLines( $internal, $edit = 1, $categ_group = false ) {
		// my_log( __FILE__, "id = " . $this->ID . " internal = " . $internal );
		$sql = 'select product_id, quantity, id'
		       . ' from im_supplies_lines where status = 1 and supply_id = ' . $this->ID;

		$args = array("id_field" => "id", "add_checkbox" => true,
			"selectors" => array("product_id" => "Fresh_Product::gui_select_product"),
			          "id_key" => "ID",
			          "edit" => $edit,
			          "show_cols" => array("product_id" => true, "quantity" => true, '$buy' => true, '$total' => true),
			          "checkbox_class" => "supply_checkbox", "events"=>"onchange='changed(this)'");

		if ($internal) $args["show_cols"]['$buyers'] = true;
		if ($edit) {
			$args["edit_cols"] = array("quantity" => true, '$buy' => true);
			$args["edit"] = 1;
		}

		$args['fields'] = array("product_id" => 0, "quantity" => 1, '$buy' => 2, '$total' => 3, '$buyers' => 4);
		$args["header_fields"] = array("product_id" => "Product", "quantity" => "Quantity");
		$args["acc_fields"] = array(  "product_id" => "סה\"כ",
		                              "quantity" => array( "func" => 'sum_numbers', "val" => 0 ),
		                              '$total' => array("func" => 'sum_numbers' , "val" => 0) );

		$rows_data = Core_Data::TableData( $sql, $args);

		$rows_data['header']['$buy'] = "Buy price";
		$rows_data['header']['$total'] = 'Total';
		$rows_data['header']['$buyers'] = 'Buyers';

		foreach ($rows_data as $line_id => $row)
		{
			if (in_array($line_id, array("header"))) continue;

			$prod_id = $row['product_id'];

			if (! $prod_id) { print "bad id: $prod_id<br/>"; continue; }
			$p = new Fresh_Product($prod_id);
			$buy_price = $p->getBuyPrice($this->SupplierID);

			if (! is_numeric($buy_price)) {
				print "no buy price<br/>";
				$buy_price = 0;
			}
			if (! is_numeric($row["quantity"]))
			{
				print "bad quantity for $prod_id<br/>";
				$q = 0;
			} else
				$q = $row["quantity"];

			$rows_data[$line_id]['$buy'] = $buy_price;
			$rows_data[$line_id]['$total'] = $buy_price * $q;
			if ( $internal) $rows_data[$line_id]['$buyers'] = Fresh_Packing::orders_per_item( $prod_id, 1, true, true, true );
		}

		return Core_Html::gui_table_args( $rows_data, "supply_" . $this->getID(), $args );
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->Status;
	}

	public function EditSupply( $internal ) {
		// print "edit<br/>";
		// print nl2br( sql_query_single_scalar( "SELECT text FROM im_supplies WHERE id = " . $this->ID ) ) . "<br/>";
		return $this->HtmlLines( $this->ID, $internal, true );
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->Date;
	}

	/**
	 * @return mixed
	 */
	public function getText() {
		return $this->Text;
	}

	/**
	 * @param mixed $Text
	 */
	public function setText( $Text ) {
		$this->Text = $Text;
		$sql = "UPDATE im_supplies SET text = " . QuoteText( $Text ) .
		       " WHERE id = " . $this->ID;
		return SqlQuery( $sql );
	}

	/**
	 * @return mixed
	 */
	public function getBusinessID() {
		return $this->BusinessID;
	}

	public function Send() {
		$sql = 'UPDATE im_supplies SET status = 3 WHERE id = ' . $this->ID;

		return SqlQuery( $sql );
	}

	public function Picked() {
		SqlQuery( "update im_supplies " .
		          " set picked = 1 " .
		          " where id = " . $this->getID()
		);
	}

	public function getSupplierName() {
		return SqlQuerySingleScalar( 'SELECT supplier_name FROM im_suppliers WHERE id = ' . $this->SupplierID );
	}

	/**
	 * @return mixed
	 */
	public function getSupplierID() {
		return $this->SupplierID;
	}

	public function UpdateLine($line_id, $q)
	{
		$result  = SqlQuery( "SELECT product_id, quantity FROM im_supplies_lines WHERE id = " . $line_id );
		$row     = SqlFetchRow( $result );
		$prod_id = $row[0];
		$old_q   = $row[1];

		$sql = 'UPDATE im_supplies_lines SET quantity = ' . $q . ' WHERE id = ' . $line_id;

		SqlQuery( $sql );

		$product = new WC_Product( $prod_id );

		if ( $product->managing_stock() ) {
			$product->set_stock_quantity( $product->get_stock_quantity() + $q - $old_q );
			$product->save();
		}
	}

	static private function create_supply( $supplierID, $date = null )
	{
		if ( ! $date )
			$date = date('Y-m-d');
		MyLog( __METHOD__ . $supplierID );
		$sql = "INSERT INTO im_supplies (date, supplier, status, paid_date) VALUES " . "('" . $date . "' , " . $supplierID . ", 1, '1000-01-01')";

		SqlQuery( $sql );

		return SqlInsertId(  );
	}

	static function supply_add_line( $supply_id, $prod_id, $quantity, $price, $units = 0 ) {
		// For backward
		$s = new Fresh_Supply( $supply_id );

		return $s->AddLine( $prod_id, $quantity, $price, $units = 0 );
	}

	static function create_supplies()
	{
		die (1);
		$date        = GetParam( "date", false, date('Y-m-d'));
		$supplier_id = GetParam("supplier_id", true);

		$ids = GetParamArray("params");
		$supply      = self::CreateSupply( $supplier_id, $date );
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

	public function delete()
	{
		$sql    = "SELECT product_id, quantity FROM im_supplies_lines WHERE supply_id = " . $this->ID;
		$result = SqlQuery( $sql );

		while ( $row = SqlFetchRow( $result )) {
			$prod_id  = $row[0];
			$quantity = $row[1];

			try {
				$product = new WC_Product( $prod_id );
				if ( $product->managing_stock() ) {
					// print "managed<br/>";
					// print "stock was: " . $product->get_stock_quantity() . "<br/>";

					$product->set_stock_quantity( max( 0, $product->get_stock_quantity() - $quantity ) );
					// print "stock is: " . $product->get_stock_quantity() . "<br/>";
					$product->save();
				}
			} catch (Exception $e)
			{
				print $e->getMessage();
			}
		}
		$sql = 'UPDATE im_supplies SET status = 9 WHERE id = ' . $this->ID;

		return SqlQuery($sql);
	}

	public function SupplyText(  ) {
		$id = $this->getID();
		if ( ! ( $id > 0 ) ) {
			throw new Exception( "bad id: " . $id );
		}

		$fields = array();
		array_push( $fields, "supplies" );

		$address = "";

		$supplier_id = self::getSupplierID();
		$ref         = Core_Html::GuiHyperlink( $id, "../supplies/supply-get.php?id=" . $id );
		$address     = SqlQuerySingleScalar( "select address from im_suppliers where id = " . $supplier_id );
//	$receiver_name = get_meta_field( $order_id, '_shipping_first_name' ) . " " .
//	                 get_meta_field( $order_id, '_shipping_last_name' );
//	$shipping2     = get_meta_field( $order_id, '_shipping_address_2', true );
//	$mission_id    = order_get_mission_id( $order_id );
//	$ref           = $order_id;
//
		array_push( $fields, $ref );
//
		array_push( $fields, $supplier_id );
//
		array_push( $fields, "<b>איסוף</b> " . self::getSupplierName() );
//
		array_push( $fields, "<a href='waze://?q=$address'>$address</a>" );
//
		array_push( $fields, "" );
//
		array_push( $fields, "" );

		array_push( $fields, SqlQuerySingleScalar( "select supplier_contact_phone from im_suppliers where id = " . $supplier_id ) );

		array_push( $fields, "" );

//
		array_push( $fields, SqlQuerySingleScalar( "select mission_id from im_supplies where id = " . $id ) );
//
		array_push( $fields, Core_Db_MultiSite::getInstance()->getLocalSiteID() );
		// array_push($fields, get_delivery_id($order_id));

		return  "<tr> " . self::delivery_table_line( 1, $fields ) . "</tr>";

	}

	function delivery_table_line( $ref, $fields, $edit = false ) {
		//"onclick=\"close_orders()\""
		$row_text = "";
		if ( $edit ) {
			$row_text = Core_Html::gui_cell( Core_Html::GuiCheckbox("chk_" . $ref) );
		}

		foreach ( $fields as $field ) // display customer name
		{
			$row_text .= Core_Html::gui_cell( $field );
		}

		return $row_text;
	}

}
