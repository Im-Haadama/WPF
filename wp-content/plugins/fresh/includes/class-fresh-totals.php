<?php
/**
 * Class Fresh_Totals
 * wrapped 28 Jan 2020
 */

class Fresh_Totals {
	private $post_file;
	private $version;
	protected static $_instance = null;

	public function __construct( $post_file ) {

		$this->post_file     = $post_file;
		$this->version       = "1.0";
		$this->nav_menu_name = null;
	}

	public static function instance( $post = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $post );
		}

		return self::$_instance;
	}

	public function enqueue_scripts() {
//		print "<script>let focus_post_url = \"" . self::getPost() . "\"; </script>";

//		$file = FLAVOR_INCLUDES_URL . 'core/data/data.js';
//		wp_enqueue_script( 'data', $file, null, $this->version, false );
//
//		$file = FLAVOR_INCLUDES_URL . 'core/gui/client_tools.js';
//		wp_enqueue_script( 'client_tools', $file, null, $this->version, false );
//
//		$file = FOCUS_INCLUDES_URL . 'focus.js';
//		wp_enqueue_script( 'focus', $file, null, $this->version, false );
//
//		$file = FLAVOR_INCLUDES_URL . 'core/gem.js';
//		wp_enqueue_script( 'gem', $file, null, $this->version, false );
	}

	function getShortcodes() {
		//             code                           function                  capablity (not checked, for now).
		return ( array(
			'fresh_totals'         => array( 'Fresh_Totals::totals', 'show_orders' )
		) );

	}

	function totals( $filter_zero, $history = false, $filter_stock, $supplier_id = null )
	{
		$result = "";
		$needed_products = array();

		Fresh_Order::CalculateNeeded( $needed_products );

		if (! count ($needed_products))
		{
			$result .= __("No needed products. Any orders in processing status?");
			return $result;
		}

		$suppliers       = array();
		$supplier_needed = array();

		// Find out which suppliers are relevant
		foreach ( $needed_products as $prod_id => $product_info ) {
			$found_supplier = false;
			foreach ( alternatives( $prod_id ) as $alter ) {
				$supplier = $alter->getSupplierId();
				if ( ! in_array( $supplier, $suppliers ) ) {
					array_push( $suppliers, $supplier );
					$supplier_needed[ $supplier ] = array();
				}
				$supplier_needed[ $supplier ][ $prod_id ] = $product_info;
				$found_supplier = true;
			}
			if (! $found_supplier){
				if (! isset($supplier_needed["missing"]))
					$supplier_needed["missing"] = array();

				$supplier_needed["missing"][$prod_id] = $product_info;
			}
		}

		if ($supplier_id) {
			if (! isset($supplier_needed[ $supplier_id ]))
			{
				print "אין מוצרים רלוונטים לספק " . get_supplier_name($supplier_id);
				return;
			}
			print get_total_orders_supplier( $supplier_id, $supplier_needed[ $supplier_id ], $filter_zero, $filter_stock, $history );

			print Core_Html::GuiButton( "btn_supplier_" . $supplier_id, "createSupply(" . $supplier_id . ")", "צור אספקה" );

			return;
		}

		if ($supplier_needed["missing"]) {
//		var_dump($supplier_needed["missing"]);
			print get_total_orders_supplier( $supplier_id, $supplier_needed["missing" ], $filter_zero, $filter_stock, $history );

		}
		$sql = "SELECT id, supplier_priority FROM im_suppliers WHERE id IN (" . CommaImplode( $suppliers ) . ")" .
		       " AND active " .
		       " ORDER BY 2";

		$result = sql_query( $sql );

		while ( $row = sql_fetch_row( $result ) ) {
			$supplier_id = $row[0];
			print get_total_orders_supplier( $supplier_id, $supplier_needed[ $supplier_id ], $filter_zero, $filter_stock, $history );

			print Core_Html::GuiButton( "btn_supplier_" . $supplier_id, "createSupply(" . $supplier_id . ")", "צור אספקה" );
		}
	}
}

//
//		print "<style>
//table {
//    font-family: arial, sans-serif;
//    border-collapse: collapse;
//}
//
//td, th {
//    border: 1px solid #dddddd;
//    text-align: right;
//    padding: 8px;
//}
//
//tr:nth-child(even) {
//    background-color: #dddddd;
//}
//</style>";
//
