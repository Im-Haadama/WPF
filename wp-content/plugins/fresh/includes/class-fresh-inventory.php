<?php


class Fresh_Inventory
{
	private $version = null;
	private $plugin_name = null;

	static function handle()
	{
		$operation = get_param("operation", false, "show_status");

		print self::handle_operation($operation);
	}

	public function __construct($name, $version)
	{
		$this->version = $version;
		$this->plugin_name = $name;
//		add_action( 'wp_enqueue_scripts', array('Fresh_Inventory', 'enqueue_styles' ));
	}

	static function handle_operation($operation)
	{
		switch ($operation){
			case "show_status":
				$year = (date('m') < 12 ? date('Y') - 1 : date('Y'));
				return self::show_status($year);
				break;
			case "show":
				$year = get_param("year");
				$supplier_id = get_param("supplier_id");
				return self::show_supplier($year, $supplier_id);
				break;
			case "save_inv":
				$data = get_param_array("data", true);
				return self::save_inv($data);
				break;
			case "inv_save_count":
				$supplier_id = get_param("supplier_id", true);
				return self::save_count($supplier_id);
				break;
		}
	}

	static function save_inv( $data ) {
		for ( $i = 0; $i < count( $data ); $i += 2 ) {
			$id = $data[ $i ];
			$q  = $data[ $i + 1 ];

			my_log( "set inv " . $data[ $i ] . " " . $data [ $i + 1 ] );
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
		sql_query($delete_sql);

		$sql = 'SELECT pl.id ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $supplier_id
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

		$pricelist_ids = sql_query_array_scalar( $sql );

		foreach ($pricelist_ids as $pl_id){
			$link_data = $catalog->GetProdID( $pl_id );
			if ( $link_data ) {
				$product_id = $link_data[0];
				$p = new Fresh_Product($product_id);
				$count = $p->getStock();
				if ($count) {
					$sql = "insert into im_inventory_count (count_date, supplier_id, product_id, product_name, quantity) values  
				          (" . quote_text(date('Y-m-d')) .", " . $supplier_id . ", " .$product_id . ", '" . $p->getName() . "'," . $count . ")";
					if (! sql_query($sql)) return false;
				}
			}
		}
		return true;
	}

	static function show_status($year)
	{
		$result = Core_Html::gui_header(1, "Inventory status for 31 Dec $year");

		$suppliers = sql_query_array_scalar("select id from im_suppliers where active = 1");
		$status_table = array(array("supplier id", "status"));

		foreach ($suppliers as $supplier_id) {
			if (! $supplier_id > 0) {
				print "skipping imvalied supplier. $supplier_id<br/>";
				continue;
			}
			$Supplier = new Fresh_Supplier($supplier_id);
			array_push($status_table, array("id" => $supplier_id, "supplier_name" => $Supplier->getSupplierName(), "status" => $Supplier->getCountStatus($year)));
		}

		$args = array("links" => array("id" => "?operation=show&supplier_id=%d&year=$year"));

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
		$count = sql_query_single_scalar($sql);
//		print $count;
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
		$result = sql_query( $sql );
		while ( $row = sql_fetch_row( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			if ( $link_data ) {
				$prod_id = $link_data[0];
//				$p = new Fresh_Product($prod_id);
//				print $p->getName() . "<br/>";
				$line    = self::product_line( $prod_id, false, false, null, true, $supplier_id );
				$table[$prod_id] = $line;
//				array_push( $table, $line );
			}
		}

		if (count($table) == 1) { // Just the header
			return null;
		}
//		var_dump($table);

		$display .= Core_Html::gui_table_args( $table, "table_" . $supplier_id );

		$display .= Core_Html::GuiButton( "btn_save_inv" . $supplier_id, "Save inventory", array("action" => "save_inv('term_" . $supplier_id . "')" ));

		return $display;
	}

	public function enqueue_scripts() {
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