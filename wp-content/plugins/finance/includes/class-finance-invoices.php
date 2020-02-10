<?php
/////////////////////////
// Created 10-02-2020  //
// Agla                //
/////////////////////////

class Finance_Invoices {
	static private $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( "/wp-content/plugins/finance/post.php" );
		}

		return self::$_instance;
	}

	function getShortcodes() {
		//           code                   function                              capablity (not checked, for now).
		return array( 'finance_invoices'      => array( 'Finance_Invoices::table',    "edit_pricelist" ));          // Invoice table
	}

	static function table_wrapper($result, $b, $c)
	{
//		var_dump($a); print "<br/>";
//		var_dump($b); print "<br/>";
//		var_dump($c); print "<br/>";
		$args = [];
		$year = GetParam( "year", false, date("Y") );

		$result .= Core_Html::gui_header(1, __("Invoices for year") . " " . $year);

		$page ="EXTRACT(YEAR FROM DATE) = " . $year . " and document_type in (4, 8) and is_active=1";
		$t = new Core_PivotTable( "im_business_info", $page,
			"date_format(date, '%m')", "part_id", "net_amount" ); // month_with_index(DATE)

		$table = $t->Create(
			'/org/business/c-get-business_info.php?document_type=4&part_id=%s&date=' . $year . '-' . '%02s-28',
			'invoice_table.php?part_id=%s',
			$args );

		if (isset($table[1]))
			$result .= Core_Html::gui_table_args( $table, "invoices", $args );
		else
			$result .= "No invoices yet, for this year";

		$result .= Core_Html::GuiHyperlink( "שנה קודמת", AddToUrl("year", $year - 1 ) );

		return $result;
	}

}