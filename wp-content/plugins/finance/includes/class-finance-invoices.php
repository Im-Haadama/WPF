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
		return array( 'finance_invoices'      => array( 'Finance_Invoices::table',    "show_invoices" ));          // Invoice table
	}

	function table_warp()
	{
		return "table";
	}

}