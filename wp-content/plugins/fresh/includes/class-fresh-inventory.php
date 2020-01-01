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
		}
	}

	static function save_inv( $data ) {
		for ( $i = 0; $i < count( $data ); $i += 2 ) {
			$id = $data[ $i ];
			$q  = $data[ $i + 1 ];

			my_log( "set inv " . $data[ $i ] . " " . $data [ $i + 1 ] );
			$p = new Fresh_Product( $id );
			if (! $p->setStock( $q ))
				return false;
		}
		return true;
	}


	static function show_status($year)
	{
		$result = gui_header(1, "Inventory for 31 Dec $year");

		$suppliers = sql_query_array_scalar("select id from im_suppliers where active = 1");
		$status_table = array(array("supplier id", "status"));

		foreach ($suppliers as $supplier_id) {
			$status = "not entered";
			if (sql_query_single_scalar("select count(*) from im_inventory_count where supplier_id = $supplier_id and year(count_date) = $year")) $status = "entered";
			array_push($status_table, array("id" => $supplier_id, "supplier_name" => get_supplier_name($supplier_id), "status" => $status));
		}

		$args = array("links" => array("id" => "?operation=show&supplier_id=%d&year=$year"));

		$result .= gui_table_args($status_table, "inventory_status", $args);
		return $result;
	}

	static function show_supplier($year, $supplier_id)
	{
		$result = gui_header(1, __("Inventory of supplier")); // . " " . get_supplier_name($supplier_id) ." " . __("for year ending in") . " " . $year);

		$sql = "select count(*) from im_inventory_count where year(count_date) = " . $year . " and supplier_id = " . $supplier_id;
//		print $sql;
		$count = sql_query_single_scalar($sql);
//		print $count;
		if ($count) {
			$args = [];
			$result .= GemTable("im_inventory_count", $args);
		} else {
			$result .= self::show_supplier_inventory($supplier_id);
		}

		return $result;
	}

	static function show_supplier_inventory( $supplier_id ) {
		$table = array( array( "", "מוצר", "מחיר עלות", "כמות במלאי" ) );

		$display = gui_header( 1, "מלאי לספק " . get_supplier_name( $supplier_id ) );
		$catalog = new Fresh_Catalog();

		$sql = 'SELECT product_name, price, date, pl.id, supplier_product_code, s.factor ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $supplier_id
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

		$result = sql_query( $sql );
		while ( $row = sql_fetch_row( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			if ( $link_data ) {
				$prod_id = $link_data[0];
				$line    = self::product_line( $prod_id, false, false, null, true, $supplier_id );
				array_push( $table, $line );
			}
		}

		if (count($table) == 1) { // Just the header
			return null;
		}

		$display .= gui_table_args( $table, "table_" . $supplier_id );

		$display .= gui_button( "btn_save_inv" . $supplier_id, "save_inv(" . $supplier_id . ")", "שמור מלאי" );

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

//	print "ct=" . $customer_type . "<br/>";
		$p = new Fresh_Product( $prod_id );
		if ( $text ) {
			$line = get_product_name( $prod_id ) . " - " . get_price_by_type( $prod_id, $customer_type ) . "<br/>";
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
		array_push( $line, get_product_name( $prod_id ) );

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
			array_push( $line, gui_label( "prc_" . $prod_id, $p->getSalePrice() ) );
			array_push( $line, gui_label( "vpr_" . $prod_id, $p->getRegularPrice() ) );
		} else {
			if ( $inv ) {
				array_push( $line, gui_label( "buy_" . $prod_id, $p->getBuyPrice() ) );

			} else {
				if ( ! $month ) {
					array_push( $line, gui_label( "prc_" . $prod_id, $p->getPrice($customer_type) ) );
					$q_price = get_price_by_type( $prod_id, null, 8 );
					//			if ( is_numeric( get_buy_price( $prod_id ) ) ) {
					//				$q_price = min( round( get_buy_price( $prod_id ) * 1.25 ), $product->get_price() );
					//			}
					array_push( $line, gui_label( "vpr_" . $prod_id, $q_price ) );
				}
			}
		}
		if ( ! $inv and ! $month) {
			array_push( $line, gui_input( "qua_" . $prod_id, "0", array( 'onchange="calc_line(this)"' ) ) );
			array_push( $line, gui_label( "tot_" . $prod_id, '' ) );
		}
		if ( $inv ) {
			array_push( $line, gui_input( "term_" . $term_id, $p->getStock(), "", "inv_" . $prod_id ) );
			array_push( $line, gui_label( "term_" . $term_id, $p->getStockDate() ) );
			array_push( $line, gui_hyperlink( "דוח", "../delivery/report.php?prod_id=" . $prod_id ) );
//		array_push( $line, gui_label( "ord_" . $term_id, $p->getOrderedDetails() ) );
		}

//	if (get_user_id() == 1){
//	var_dump($line);
//	die (1);
//	}
		return $line;
	}

}