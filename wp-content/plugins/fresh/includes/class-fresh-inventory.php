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
		$catalog = new Catalog();

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
				$line    = product_line( $prod_id, false, false, null, true, $supplier_id );
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
}