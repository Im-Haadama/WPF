<?php


class Fresh_Inventory
{
	private $version = null;
	private $plugin_name = null;
	private $post_file = null;
	static private $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			die ("Create instance with parameters");
		}
		return self::$_instance;
	}

	static function handle()
	{
		$operation = GetParam("operation", false, "show_status");

		print self::handle_operation($operation);
	}

	public function __construct($name, $version, $post_file)
	{
		$this->version = $version;
		$this->plugin_name = $name;
		$this->post_file = $post_file;
		self::$_instance = $this;
//		add_action( 'wp_enqueue_scripts', array('Fresh_Inventory', 'enqueue_styles' ));
	}

	static function getPost()
	{
//		print "post_file= " . self::instance()->post_file;;
		return self::instance()->post_file;
	}

	static function handle_operation($operation)
	{
		switch ($operation){
			case "download_inventory_count":
				$year = (date('m') < 12 ? date('Y') - 1 : date('Y'));
				return self::download_inventory($year);
				break;
			case "show_status":
				$year = (date('m') < 12 ? date('Y') - 1 : date('Y'));
				$include_counted = GetParam("include_counted", false, false);
				return self::show_status($year, $include_counted);
				break;
			case "show":
				$year = GetParam("year");
				$supplier_id = GetParam("supplier_id");
				return self::show_supplier($year, $supplier_id);
				break;
			case "save_inv":
				$data = GetParamArray("data", true);
				return self::save_inv($data);
				break;
			case "inv_save_count":
				$supplier_id = GetParam("supplier_id", true);
				return self::save_count($supplier_id);
				break;

			case "inventory_zero":
				$supplier_id = GetParam("supplier_id", true);
				return self::zero_count($supplier_id);
				break;
		}
	}

	static function download_inventory($year)
	{
		$File = "inventory-$year.csv";
		$buffer = "";

		$sql = "select product_id, product_name, quantity " .
		       " from im_inventory_count " .
		       " where count_date > " . QuoteText( $year . '-12-1') . ' and count_date < ' . QuoteText(( $year + 1 . '-2-1')) . ' and quantity > 0';

//		print $sql;

		$rows = SqlQuery($sql);
		if (! $rows) return;
		$buffer = "Product id, Product name, quantity, price, total\n";
		while ($row = SqlFetchAssoc($rows))
		{
			$prod_id = $row["product_id"];
			$quantity = $row["quantity"];
			$buy_price = Fresh_Pricing::get_buy_price($prod_id);
			$total = ((is_numeric($buy_price) and is_numeric($quantity)) ? ($quantity * $buy_price) : 'error');

			$buffer .=  $prod_id . ", " . $row["product_name"] . ", " . $quantity . ", ". $buy_price . "," . $total.
			            "\n";
		}
		$size = strlen($buffer);

		header("Content-Disposition: attachment; filename=\"" . basename($File) . "\"");
		header("Content-Type: application/octet-stream");
		header("Content-Length: " . $size);
		header("Connection: close");

		print $buffer;
		return true;
	}
	static function zero_count($supplier_id)
	{
		$sql = sprintf( "insert into im_inventory_count (count_date, supplier_id, product_id, product_name, quantity) values  
				          (%s, %s, 0 , 'zero count', 0)", QuoteText( date( 'Y-m-d' ) ), $supplier_id );

		return SqlQuery($sql);
	}
	static function save_inv( $data ) {
		for ( $i = 0; $i < count( $data ); $i += 2 ) {
			$id = $data[ $i ];
			$q  = $data[ $i + 1 ];

			MyLog( "set inv " . $data[ $i ] . " " . $data [ $i + 1 ] );
			$p = new Fresh_Product( $id );
			if (! $p) continue; // Product not found;
			if (! $p->setStock( $q ))
				return false;
		}
		return true;
	}

	static function save_count($supplier_id)
	{
		$catalog = new Fresh_Catalog();

		// Delete last lately count from this supplier
		$delete_sql = "delete from im_inventory_count where supplier_id = " . $supplier_id .
		              " and count_date > '" . date('Y-m-d', strtotime('-20 days')) . "'";
//		print $delete_sql;
		SqlQuery($delete_sql);

		$sql = 'SELECT pl.id ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $supplier_id
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

		$pricelist_ids = SqlQueryArrayScalar( $sql );

		foreach ($pricelist_ids as $pl_id){
			$link_data = $catalog->GetProdID( $pl_id );
			if ( $link_data ) {
				$product_id = $link_data[0];
				$p = new Fresh_Product($product_id);
				$count = $p->getStock();
				if ($count) {
					$sql = "insert into im_inventory_count (count_date, supplier_id, product_id, product_name, quantity) values  
				          (" . QuoteText(date('Y-m-d')) . ", " . $supplier_id . ", " . $product_id . ", '" . EscapeString($p->getName()) . "'," . $count . ")";
					if (! SqlQuery($sql)) return false;
				}
			}
		}
		return true;
	}

	static function show_status($year, $include_counted = false)
	{
		$result = Core_Html::gui_header(1, "Inventory status for 31 Dec $year");
		$result .= Core_Html::GuiHyperlink("include counted", AddToUrl("include_counted", 1)) . " ";
		$result .= Core_Html::GuiHyperlink("download count", self::getPost() . "?operation=download_inventory_count");


		$suppliers = SqlQueryArrayScalar("select id from im_suppliers where active = 1");
		$status_table = array(array("supplier id", "Supplier name", "Count Date", "Zero"));

		foreach ($suppliers as $supplier_id) {
			if (! $supplier_id > 0) {
				print "skipping invalid supplier. $supplier_id<br/>";
				continue;
			}
			$Supplier = new Fresh_Supplier($supplier_id);

			if ($include_counted or !$Supplier->getLastCount() or ((strtotime('now') - strtotime($Supplier->getLastCount())) > 2592000)) // One month
				$status_table[$supplier_id] = array("id" => $supplier_id, "supplier_name" => $Supplier->getSupplierName(), "count_date" => $Supplier->getLastCount());
		}

		$args = array("links" => array("id" => "?operation=show&supplier_id=%d&year=$year"));
		$args["actions"] = array(array("Zero", self::getPost() . "?operation=inventory_zero&supplier_id=%d;action_hide_row"));

		$result .= Core_Html::gui_table_args($status_table, "inventory_status", $args);
		return $result;
	}

	static function show_supplier($year, $supplier_id)
	{
		$supplier = new Fresh_Supplier($supplier_id);

		$result = Core_Html::gui_header(1, __("Inventory of supplier")). " " . $supplier->getSupplierName() ." " . __("for year ending in") . " " . $year;
		$result .= Core_Html::GuiLabel("supplier_id", $supplier_id, array("hidden" => true));

		$sql = "select count(*) from im_inventory_count where year(count_date) = " . $year . " and supplier_id = " . $supplier_id;
//		print $sql;
		$count = SqlQuerySingleScalar($sql);
//		print "count = $count<br/>";
		if ($count) {
			$args = [];
			$result .= Core_Gem::GemTable("im_inventory_count", $args);
		} else {
			$result .= self::show_supplier_inventory($supplier_id);
		}
		$result .= Core_Html::GuiButton("btn_save_count", "Save count", array("action" => "inventory_save_count(" . $supplier_id . ")"));

		return $result;
	}

	static function show_supplier_inventory( $supplier_id ) {
		$img_size = 40;
		$display = "";
		$table = array( array( "", "מוצר", "מחיר עלות", "כמות במלאי" ) );

//		$display = Core_Html::gui_header( 1, "מלאי לספק " . get_supplier_name( $supplier_id ) );
		$catalog = new Fresh_Catalog();

		$sql = 'SELECT product_name, price, date, pl.id, supplier_product_code, s.factor ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $supplier_id
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

//		print $sql;
		$result = SqlQuery( $sql );
		while ( $row = SqlFetchRow( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			if ( $link_data ) {
				$prod_id = $link_data[0];
				$p = new Fresh_Product($prod_id);
//				print $p->getName() . "<br/>";
//				 $line    = self::product_line( $prod_id, false, false, null, true, $supplier_id );
//				array_push( $table, $line );
				$table[$prod_id] = array(
					(has_post_thumbnail( $prod_id ) ? get_the_post_thumbnail( $prod_id, array( $img_size, $img_size ) ) : ""),
					$p->getName(),
					$p->getBuyPrice($supplier_id),
					Core_Html::GuiInput( "inv_" . $prod_id, $p->getStock(), array("name" =>"term_" . $supplier_id )),
					$p->getStockDate());
			}
		}

		if (count($table) == 1) { // Just the header
			return null;
		}

		$display .= Core_Html::gui_table_args( $table, "table_" . $supplier_id );

		$display .= Core_Html::GuiButton( "btn_save_inv" . $supplier_id, "Save inventory", array("action" => "save_inv('term_" . $supplier_id . "')" ));

		return $display;
	}

	public function admin_scripts() {
		$file = plugin_dir_url( __FILE__ ) . 'js/inventory.js';
//		print "loading $file,....";
		wp_enqueue_script( $this->plugin_name, $file, array( 'jquery' ), $this->version, false );
	}

	private static function product_line( $prod_id, $text, $sale, $customer_type, $inv, $term_id, $month = null )
	{
		$line     = array();
		$img_size = 40;

		$p = new Fresh_Product( $prod_id );
		if (! $p) return "";
		if ( $text ) {
			$line = $p->getName() . " - " . get_price_by_type( $prod_id, $customer_type ) . "<br/>";
			// print "line = " . $line . "<br/>";
			// $result .= $line;
			return $line;
		}
		if ( has_post_thumbnail( $prod_id ) ) {
			array_push( $line, get_the_post_thumbnail( $prod_id, array( $img_size, $img_size ) ) );
		} else {
			array_push( $line, '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="' . $img_size . 'px" height="'
			                   . $img_size . 'px" />' );
		}
		array_push( $line, $p->getName());

		if ( $month ) {
			if ( $month == "all" )
				for ( $i = 1; $i <= 12; $i ++ ) {
					array_push( $line, month_availability( $prod_id, $i ) );
				}
			else {
				$a = month_availability( $prod_id, $month );
				if ( $a == "N/A" ) {
					return "";
				}
				array_push( $line, $a );

				return $line;
			}
		}
		if ( $sale ) {
			array_push( $line, Core_Html::GuiLabel( "prc_" . $prod_id, $p->getSalePrice() ) );
			array_push( $line, Core_Html::GuiLabel( "vpr_" . $prod_id, $p->getRegularPrice() ) );
		} else {
			if ( $inv ) {
				array_push( $line, Core_Html::GuiLabel( "buy_" . $prod_id, $p->getBuyPrice() ) );

			} else {
				if ( ! $month ) {
					array_push( $line, Core_Html::GuiLabel( "prc_" . $prod_id, $p->getPrice($customer_type) ) );
					$q_price = get_price_by_type( $prod_id, null, 8 );
					//			if ( is_numeric( get_buy_price( $prod_id ) ) ) {
					//				$q_price = min( round( get_buy_price( $prod_id ) * 1.25 ), $product->get_price() );
					//			}
					array_push( $line, Core_Html::GuiLabel( "vpr_" . $prod_id, $q_price ) );
				}
			}
		}
		if ( ! $inv and ! $month) {
			array_push( $line, Core_Html::GuiInput( "qua_" . $prod_id, "0", array( 'onchange="calc_line(this)"' ) ) );
			array_push( $line, Core_Html::GuiLabel( "tot_" . $prod_id, '' ) );
		}
		if ( $inv ) {
			array_push( $line, Core_Html::GuiInput( "inv_" . $prod_id, $p->getStock(), array("name" =>"term_" . $term_id )));
			array_push( $line, Core_Html::GuiLabel( "term_" . $term_id, $p->getStockDate() ) );
			array_push( $line, Core_Html::GuiHyperlink( "דוח", "../delivery/report.php?prod_id=" . $prod_id ) );
//		array_push( $line, Core_Html::GuiLabel( "ord_" . $term_id, $p->getOrderedDetails() ) );
		}

//	if (get_user_id() == 1){
//	var_dump($line);
//	die (1);
//	}
		return $line;
	}
}