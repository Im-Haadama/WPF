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

			print " Supply " . Core_Html::GuiHyperlink($Supply->getID(), "/fresh/supplies/supplies-post.php?id=" . $Supply->getID() . " created <br/>");

			if ($debug)
				print nl2br($comments);
			$Supply->setText( $comments );

			return $Supply;
		}
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

		$data = '<div class="tooltip">' . __("invoice") . gui_checkbox( "is_invoice", "" );
		$data .= '<span class="tooltiptext">' . __("Check for invoice, and leave uncheck for delivery note") . '</span></div>'; // יש לסמן עבור חשבונית ולהשאיר לא מסומן עבור תעודת משלוח

		$data .= Core_Html::gui_table_args( array(
			// array( "חשבונית", $invoice_text ),
			array( "מספר מסמך", Core_Html::GuiInput( "supply_number", "" ) ),
			array( "סכום כולל מעמ", Core_Html::GuiInput( "supply_total", "" ) ),
			array( "סכום ללא מעמ", Core_Html::GuiInput( "net_amount", "" ) ),
			array( "תאריך", Core_Html::gui_input_date( "document_date", "" ) )
		) );

		$data .= Core_Html::GuiButton("btn_got", "Supply arrived", "got_supply()");
		return $data;

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
		send_supplies( array( $this->ID ) );
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

			$product = new WC_Product( $prod_id );
			if ( $product->managing_stock() ) {
				// print "managed<br/>";
				// print "stock was: " . $product->get_stock_quantity() . "<br/>";

				$product->set_stock_quantity( max( 0, $product->get_stock_quantity() - $quantity ) );
				// print "stock is: " . $product->get_stock_quantity() . "<br/>";
				$product->save();
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
		array_push( $fields, SqlQuerySingleScalar( "select supplier_contact_phone from im_suppliers where id = " . $supplier_id ) );

		array_push( $fields, "" );
//
		array_push( $fields, SqlQuerySingleScalar( "select mission_id from im_supplies where id = " . $id ) );
//
		array_push( $fields, Core_Db_MultiSite::getInstance()->getLocalSiteName() );
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



//function supply_get_supplier_id( $supply_id ) {
//	$sql = "SELECT supplier FROM im_supplies WHERE id = " . $supply_id;
//
//	return sql_query_single_scalar( $sql );
//}

//function supply_get_supplier( $supply_id ) {
//	return get_supplier_name( supply_get_supplier_id( $supply_id ) );
//}

//function supply_get_mission_id( $supply_id ) {
//	return sql_query_single_scalar( "SELECT mission_id FROM im_supplies WHERE id = " . $supply_id );
//}
//
//function supply_quantity_ordered( $prod_id ) {
//	$sql = 'SELECT sum(quantity) FROM im_supplies_lines WHERE product_id = ' . $prod_id
//	       . ' AND status = 1 AND supply_id IN (SELECT id FROM im_supplies WHERE status = 1 OR status = 3)';
//
//	return sql_query_single_scalar( $sql );
//}
//
//
//function supply_sent( $supply_id ) {
//	$sql = 'UPDATE im_supplies SET status = 3 WHERE id = ' . $supply_id;
//
//	$result = sql_query( $sql );
//
//	if ( ! $result ) {
//		sql_error( $sql );
//		die ( 1 );
//	}
//}
//
//function supply_delete_line( $line_id ) {
//	$sql = 'UPDATE im_supplies_lines SET status = 9 WHERE id = ' . $line_id;
//
//	sql_query( $sql );
//}
//
//
//function supply_change_status( $supply_id, $status ) {
//	$sql = 'UPDATE im_supplies SET status = ' . $status . ' WHERE id = ' . $supply_id;
//
//	sql_query( $sql );
//}
//
//function supply_supplied( $supply_id ) {
//	supply_change_status( $supply_id, SupplyStatus::Supplied );
//}
//
//function supply_status( $supply_id ) {
//	return sql_query_single_scalar( "SELECT status FROM im_supplies WHERE id = " . $supply_id );
//}
//
//function send_supplies( $ids ) {
//	print "Sending supplies\n";
//
//	foreach ( $ids as $id ) {
//		if (! ($id > 0)){
//			print "bad delivery id " . $id . "\n";
//			return false;
//		}
//		print "id=" . $id . "<br/>";
//		$supplier_id = supply_get_supplier_id( $id );
//
//		$site = sql_query_single_scalar( "select site_id from im_suppliers where id = " . $supplier_id );
//		if ( $site ) {
//			send_supply_as_order( $id );
//			continue;
//		}
////        print "supplier_id = " .$supplier_id . "</br>";
//		$email = sql_query_single_scalar( "SELECT email FROM im_suppliers WHERE id = " . $supplier_id );
////        print "email = " . $email . "<br/>";
//
//		ob_start();
//
//		print_page_header( true );
//
//		print '<body dir="rtl">';
//
//		print_supplies_table( array( $id ), false );
//
//		print '</body>';
//		print '</html>';
//
//		$message = " If you cannot read, please press " .
//		           Core_Html::GuiHyperlink( "<b>here</b>", get_site_tools_url( 1 ) . "/supplies/supply-get-open.php?id=" . $id );
//
//		$message .= ob_get_contents();
//
//		ob_end_clean();
//
//		send_mail( "הזמנה מספר " . $id, $email . ", info@im-haadama.co.il.test-google-a.com", $message );
//		print "הזמנוה מספר " . $id . " (ספק " . get_supplier_name( $supplier_id ) . ") נשלחה ל" . $email . "<br/>";
//	}
//	return true;
//}
//
//function print_supplies_table( $ids, $internal ) {
////    print "<html dir=\"rtl\">";
//	foreach ( $ids as $id ) {
//		print "<h1>";
//		print "אספקה מספר " . Core_Html::GuiHyperlink( $id, "../supplies/supply-get.php?id= " . $id ) . " " . supply_get_supplier( $id ) . " " . date( "Y-m-d" );
//		print "</h1>";
//		$s = new Fresh_Supply( $id );
//		print $s->Html( $internal, false );
//		print "<p style=\"page-break-after:always;\"></p>";
//	}
////    print "</html>";
//}
//
//function got_supply( $supply_id, $supply_total, $supply_number, $net_amount, $document_type, $document_date = null ) {
//	if ( ! $document_date ) {
//		$document_date = date( 'y-m-d' );
//	}
//	$id  = business_add_transaction( supply_get_supplier_id( $supply_id ), $document_date, - $supply_total,
//		0, $supply_number, 1, - $net_amount, $document_type );
//	$sql = "UPDATE im_supplies SET business_id = " . $id . " WHERE id = " . $supply_id;
//	if ( ! sql_query( $sql ) ) {
//		return false;
//	}
//
//	if ( ! sql_query( "UPDATE im_supplies SET status = " . SupplyStatus::Supplied . " WHERE id = " . $supply_id ) ) {
//		return false;
//	}
//
//	return $id;
//}
//
//function supply_business_info( $supply_id ) {
//	$sql = "SELECT business_id FROM im_supplies WHERE id = " . $supply_id;
//	$bid = sql_query_single_scalar( $sql );
//	if ( $bid > 0 ) {
//		print business_supply_info( $bid );
//	}
//}
//
//function delete_supplies( $params ) {
//	for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
//		$supply_id = $params[ $pos ];
//		MyLog( "delete supply " . $supply_id );
//		if (! supply_delete( $supply_id )) return false;
//	}
//	return true;
//}
//
//function sent_supplies( $params ) {
//	for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
//		$supply_id = $params[ $pos ];
//		MyLog( "sent supply " . $supply_id );
//		supply_sent( $supply_id );
//	}
//}
//
//function delete_supply_lines( $params ) {
//	for ( $pos = 0; $pos < count( $params ); $pos ++ ) {
//		$line_id = $params[ $pos ];
//		MyLog( "delete supply line" . $line_id );
//		supply_delete_line( $line_id );
//		print "deleted " . $line_id . "<br/>";
//	}
//}
//
//function update_supply_lines( $supply_id, $params ) {
//	$result = "";
//	$supplier_id = supply_get_supplier_id($supply_id);
//	$Supply = new Fresh_Supply($supply_id);
//	$pricelist = new PriceList($supplier_id);
//	// Double update - the supply and also the pricelist
//	for ( $pos = 0; $pos < count( $params ); $pos += 3 ) {
//		$line_id = $params[ $pos ];
//		$q       = $params[ $pos + 1 ];
//		$price = $params[$pos + 2];
//		MyLog( "update supply line" . $line_id . " q= " . $q );
////		print "line_id: " . $line_id . " new q: " . $q . "<br/>";
//		$Supply->UpdateLine($line_id, $q);
//
//		// Update the pricelist.
//		$prod_id = $Supply->GetProduct($line_id);
//		$pricelist_id = Catalog::PricelistFromProduct($prod_id, $supplier_id);
//		// print "pl=" . $pricelist_id . "<br/>";
//		if ($pricelist_id)
//			$pricelist->Update($pricelist_id, $price);
//		else
//			$result .= "can't find pricelist for " . get_product_name($prod_id) . " " . $prod_id;
//	}
//	if (! strlen($result)) return "done";
//	return $result;
//}

//function do_merge_supply( $merged, $other ) {
//	if ( supply_get_supplier_id( $merged ) != supply_get_supplier_id( $other ) ) {
//		print "לא ניתן למזג אספקות של ספקים שונים. אספקה $other לא תמוזג ";
//
//		return;
//	}
//	my_log( "merging " . $merged . " and " . $other );
//
//	$sql = "select sum("
//	// Moved the lines to merged supply
////	$sql = "update im_supplies_lines set supply_id = $merged where supply_id = $other ";
//////    . $merged . ", product_id, quantity from im_supplies_lines where "
//////        . " supply_id = " . $other;
////
////	my_log( $sql );
////	mysqli_query( , $sql );
//
//	// TODO: really merge. sum the quantities.
////	$sql = ""
////    $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) . "sql: " . $sql);
////
////    $sql = "update im_supplies set status = 8 " .
////        " where id = " . $other;
////
////    my_log ($sql);
////    $export = mysql_query ( $sql ) or die ( "Sql error : " . mysql_error( ) . "sql: " . $sql);
////
////    $sql = "update im_supplies_lines set status = 8 " .
////        " where supply_id = " . $other;
////
////    mysqli_query(, $sql);
//
//}

//function merge_supplies( $params ) {
//	for ( $i = 0; $i < count( $params ); $i ++ ) {
//		if ( supply_status( $params[ $i ] ) != 1 ) {
//			print "ניתן לאחד רק הספקות במצב חדש ";
//
//			return;
//		}
//	}
//	$supply_id = $params[0];
//	unset( $params[0] );
//	do_merge_supplies( $params, $supply_id );
//	supplies_change_status( $params, SupplyStatus::Merged );
////	for ( $pos = 1; $pos < count( $params ); $pos ++ ) {
////		$supply_id = $params[ $pos ];
////		my_log( "merging $supply_id into $params[0]" );
////		do_merge_supply( $params[0], $supply_id );
////		supply_change_status( $supply_id, SupplyStatus::Merged );
////	}
//}
//
//function supplies_change_status( $params, $status ) {
//	$sql = "UPDATE im_supplies SET status = " . $status .
//	       " WHERE id IN (" . rtrim( implode( $params, "," ) ) . ")";
//
//	sql_query( $sql );
//}
//
//function do_merge_supplies( $params, $supply_id ) {
//	// Read sum of lines.
//	$sql     = "SELECT sum(quantity), product_id, 1 FROM im_supplies_lines
//WHERE status = 1 AND supply_id IN (" . $supply_id . ", " . rtrim( implode( ",", $params ) ) . ")" .
//	           " GROUP BY product_id, status ";
//	$result  = sql_query( $sql );
//	$results = array();
//
//	while ( $row = mysqli_fetch_row( $result ) ) {
//		array_push( $results, $row );
//	}
//
//	// Move all lines to be in merged status
//	$sql = "UPDATE im_supplies_lines SET status = " . SupplyStatus::Merged . " WHERE supply_id IN ("
//	       . $supply_id . ", " . rtrim( implode( ",", $params ) ) . ")";
//
//	sql_query( $sql );
//
//	// Insert new lines
//	$sql = "INSERT INTO im_supplies_lines (status, supply_id, product_id, quantity) VALUES ";
//	foreach ( $results as $row ) {
//		$sql .= "( " . SupplyStatus::NewSupply . ", " . $supply_id . ", " . $row[1] . ", " . $row[0] . "),";
//	}
//	$sql = rtrim( $sql, "," );
//	sql_query( $sql );
//}
//
//
//function DoSuppliesTable( $sql )
//{
//	$result = sql_query( $sql );
//	MyLog( $sql);
//	$has_lines = false;
//
//	if ( ! $result ) {
//		sql_error( $sql );
//		die( 1 );
//	}
//	$result = sql_query( $sql );
//
//	$lines = array();
//	array_push( $lines, array( "בחר", "מספר", "תאריך", "משימה", "ספק", "סטטוס", "סכום", "תאריך תשלום" ) );
//	while ( $row = mysqli_fetch_row( $result ) ) {
//		$supply_id   = $row[0];
//		$supplier_id = $row[1];
//		$status      = $row[5];
//		$line        = array(
//			gui_checkbox( "chk" . $supply_id, "supply_checkbox", "", "" ),
//			Core_Html::GuiHyperlink( $supply_id, "supply-get.php?id=" . $supply_id ),
//			$row[3],
//			gui_select_mission( "mis_" . $supply_id, supply_get_mission_id( $supply_id ), array("events"=>"onchange=mission_changed(" . $supply_id . ")" )),
//			get_supplier_name( $supplier_id ),
//			get_supply_status_name( $supply_id )
//		);
//
//		if ( $status = 5 ) {
//			array_push( $line, $row[6] );
//			$business_id = $row[6];
////			 print "business id: " . $business_id . "<br/>";
//			if ( $business_id > 0 ) {
//				$amount = sql_query_single_scalar( "select amount from im_business_info where id = " . $business_id );
//				array_push( $line, $amount );
////				$value  .= gui_cell( $amount );
//			}
//			$date = $row[4];
//			if ( $date == "0000-00-00" ) {
//				$date = null;
//			}
//			array_push( $line, $date );
////			$value .= gui_cell( $date );
//		}
//
////		$value       .= "</tr>";
////
////		$data      .= $value;
//		array_push( $lines, $line );
//		$has_lines = true;
//	}
//	// $data .= "</table>";
//
//	$data = gui_table_args( $lines );
//	if ( $has_lines ) {
//		return $data;
//	}
//
//	return null;
//
//}
//
//function display_active_supplies( $status ) {
//	$in_key = info_get( 'inventory_in', true, 0);
//	$sql    = "SELECT id, supplier, status, date(date), paid_date, status, business_id FROM im_supplies WHERE status IN (" .
//	          implode( ",", $status ) . ") AND id > " . $in_key . "\n"
//	          // " and date > DATE_SUB(curdate(), INTERVAL 2 WEEK)"
//	          . " ORDER BY 4 desc, 3, 2 limit 20";
//
//	return DoSuppliesTable( $sql );
//}
//
//function create_delta() {
//	$sql = "select prod_id, q from i_total where q < 0";
//
//	$s = create_supply( 0 );
//	print "sid = " . $s . "<br/>";
//	$rows = sql_query_array( $sql );
//	foreach ( $rows as $row ) {
//		// var_dump($row);
//		//	print "add line " . $row[0] . " " . $row[1] . "<br/>";
//		supply_add_line( $s, $row[0], - $row[1], 0 );
//	}
//}
//
//function create_supplier_order( $supplier_id, $ids, $date = null ) {
//	$supply_id = create_supply( $supplier_id, $date );
//	print "creating supply " . Core_Html::GuiHyperlink( $supply_id, "supply-get.php?id=" . $supply_id) . "...";
//
//	for ( $pos = 0; $pos < count( $ids ); $pos += 3 ) {
//		$prod_id  = $ids[ $pos ];
//		$quantity = $ids[ $pos + 1 ];
//		$units    = $ids[ $pos + 2 ];
//		// print "adding " . $prod_id . " quantity " . $quantity . " units " . $units . "<br/>";
//		$price = get_buy_price( $prod_id, $supplier_id);
//
//		// Calculate the price
////        $pricelist = new PriceList($supplier_id);
////        $buy_price = $pricelist->Get($product_name);
////        $sell_price = calculate_price($buy_price, $supplier_id);
//
////        my_log("supplier_id = " . $supplier_id . " name = " . $product_name);
//		supply_add_line( $supply_id, $prod_id, $quantity, $price, $units );
//	}
//	print "done<br/>";
//}
//
//}
//
//
//function supply_set_pay_date( $id, $date ) {
//	$sql = "update im_supplies set paid_date = '" . $date . "' where id = " . $id;
//	print $sql;
//	sql_query( $sql);
//}
//
//function display_date( $date ) {
//	if ( $date != "0000-00-00" ) {
//		print $date;
//	}
//}
//
//function display_status( $status ) {
//	switch ( $status ) {
//		case SupplyStatus::Supplied:
//			print "סופק";
//			break;
//		case SupplyStatus::Sent:
//			print "נשלח";
//			break;
//		case SupplyStatus::NewSupply:
//			print "חדש";
//			break;
//
//	}
//}
//
//function supplier_report_data( $supplier_id, $start_date, $end_date ) {
//	$sql = "SELECT prod_id, sum(quantity), get_product_name(prod_id) FROM im_delivery_lines dl\n"
//
//	       . "JOIN im_delivery d\n"
//
//	       . "WHERE date > '" . $start_date . "'\n"
//
//	       . "AND date <= '" . $end_date . "'\n"
//
//	       . "AND dl.delivery_id = d.id\n"
//
//	       . "AND prod_id IN (SELECT product_id FROM im_supplier_mapping WHERE supplier_id = " . $supplier_id . ")\n"
//
//	       . "GROUP BY 1\n"
//
//	       . "ORDER BY 2 DESC";
//
//
//// print $sql;
//	return sql_query_array( $sql, true );
//}
//
//function send_supply_as_order( $id ) {
//	$supplier_id = supply_get_supplier_id( $id );
//	$row         = sql_query_single_assoc( "select site_id, user_id from im_suppliers where id = " . $supplier_id );
//
//	$site_id    = $row["site_id"];
//	$user_id    = $row["user_id"];
//	$mission_id = 0;
//
//	$prods      = array();
//	$quantities = array();
//	$units      = array();
//	$sql        = "select product_id, quantity, units from im_supplies_lines where supply_id = " . $id;
//
//	print $sql;
//	$result = sql_query( $sql );
//	while ( $row = sql_fetch_row( $result ) ) {
//		$prod_id = $row[0];
////		print "prod_id= " . $prod_id . "<br/>";
////		var_dump($row); print "<br/>";
//		$remote_prod_id = supplier_prod_id( $prod_id, $supplier_id );
//		if ( $remote_prod_id ) {
//			array_push( $prods, $remote_prod_id );
//			array_push( $quantities, $row[1] );
//			array_push( $units, $row[2] );
//		} else {
//			print "no remote prod" . get_product_name( $prod_id ) . "<br/>";
//		}
//	}
//	$remote = "orders/orders-post.php?operation=create_order&user_id=" . $user_id .
//	          "&prods=" . implode( ",", $prods ) .
//	          "&quantities=" . implode( ",", $quantities ) .
//	          "&comments=" . urlencode( "נשלח מאתר " . Core_Db_MultiSite::LocalSiteName() ) .
//	          "&units=" . implode( ",", $units ) . // TODO: support units.
//	          "&mission_id=" . $mission_id;
//
//	print $remote . " site: " . $site_id . "<br/>";
//	print Core_Db_MultiSite::sExecute( $remote, $site_id );
//}
//
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
//		case "create_from_file":
//			$supplier_id = GetParam( "supplier_id" );
//			print header_text(false);
//			print ImTranslate("Creating supply for") . " " . get_supplier_name($supplier_id) . " <br/>";
//
//			$tmp_file = $_FILES["fileToUpload"]["tmp_name"];
//			$date = GetParam("date", true);
//			$args = array("needed_fields" => array ("name" => 1, "quantity"=> 1));
//			$s        = Fresh_Supply::CreateFromFile( $tmp_file, $supplier_id, $date, $args );
//			if ( $s ) {
//				$s->EditSupply( true );
//			}
//			break;
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
//}
//
//function get_supply($id)
//{
//	$supply = new Fresh_Supply($id);
//	print Core_Html::gui_header(1, "Supply", true, true). " " .Core_Html::gui_label("supply_id", $id);
//	$edit =($supply->getStatus() == SupplyStatus::NewSupply || $supply->getStatus() == SupplyStatus::Sent);
//	$internal = true;
//
//	$footer = "<br/>";
//	switch ($supply->getStatus())
//	{
//		case SupplyStatus::NewSupply:
//			$footer .= Core_Html::GuiButton( "btn_add_line", "supply_add_item(" . $id . ")", "add" );
//			$footer .= gui_select_product( "itm_" );
//			$footer .= Core_Html::GuiButton("btn_del", "deleteItems()", "delete lines");
//			$footer .= Core_Html::GuiButton("btn_update", "updateItems()", "update items");
//
//			$footer .= "<br/>" . supplier_doc();
//			break;
//
//		case SupplyStatus::Sent:
//			$invoice_text =  '<br/>   <div class="tooltip">' . gui_checkbox( "is_invoice", "" ) .
//			                 '<span class="tooltiptext">יש לסמן עבור חשבונית ולהשאיר לא מסומן עבור תעודת משלוח</span> </div>';
//
//			$footer .= Core_Html::GuiButton( "btn_add_line", "supply_add_item()", "הוסף" );
//			$footer .= gui_select_product( "itm_" );
//			$footer .= Core_Html::GuiButton("btn_update", "updateItems()", "update");
//
//			$footer .= gui_table_args( array(
//				array( "חשבונית", $invoice_text ),
//				array( "מספר מסמך", GuiInput( "supply_number") ),
//				array( "סכום כולל מעמ", GuiInput( "supply_total") ),
//				array( "סכום ללא מעמ", GuiInput( "net_amount") ),
//				array( "תאריך", GuiInput( "document_date", "" ) )
//			) );
//			$footer .= "<br/>";
//
//			$footer .= Core_Html::GuiButton( "btn_got_supply", "got_supply()", "סחורה התקבלה" );
//			break;
//
//		case SupplyStatus::Supplied:
//			$internal = false;
//			$transaction_id = $supply->getBusinessID();
//			assert($transaction_id);
//			$row = sql_query_single_assoc("select * from im_business_info where id = " . $transaction_id);
//			$doc_id = $row["ref"];
//			$amount = $row["amount"];
//			$footer = gui_table_args(array(array("Document number", $doc_id),
//				array("Total to pay", $amount)));
//			break;
//	}
//
//	print $supply->Html($internal, $edit);
//	print $footer;
//
//	// print Core_Html::GuiButton("btn_delete", "delete_supply(" . $id . ")", "delete");
//
//	return;
//}
//
//function supplier_doc()
//{
//
//	$data = '<div class="tooltip">' . ImTranslate("invoice") . gui_checkbox( "is_invoice", "" );
//	$data .= '<span class="tooltiptext">' . ImTranslate("Check for invoice, and leave uncheck for delivery note") . '</span></div>'; // יש לסמן עבור חשבונית ולהשאיר לא מסומן עבור תעודת משלוח
//
//	$data .= gui_table_args( array(
//		// array( "חשבונית", $invoice_text ),
//		array( "מספר מסמך", GuiInput( "supply_number", "" ) ),
//		array( "סכום כולל מעמ", GuiInput( "supply_total", "" ) ),
//		array( "סכום ללא מעמ", GuiInput( "net_amount", "" ) ),
//		array( "תאריך", gui_input_date( "document_date", "" ) )
//	) );
//
//	$data .= Core_Html::GuiButton("btn_got", "got_supply()", "Supply arrived");
//	return $data;
//
//}
//
//
//
