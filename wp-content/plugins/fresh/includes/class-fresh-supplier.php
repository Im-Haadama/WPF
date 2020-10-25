<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 01/01/19
 * Time: 19:43
 */
class Fresh_Supplier {
	private $id;

	public function __construct( $_id ) {
		if (! $_id) die("invalid supplier id");
		$this->id = $_id;
	}

	function getSupplierName(  ) {
		// my_log("sid=" . $supplier_id);
		return get_supplier_name($this->id);
	}

	static function getSupplierId( $supplier_name ) {
		return SqlQuerySingleScalar( 'SELECT id FROM im_suppliers WHERE supplier_name = \'' . $supplier_name . '\'' );
	}

	static function getByInvoiceSender( $email ) {
		$id = SqlQuerySingleScalar( "SELECT id FROM im_suppliers WHERE " .
		                            " invoice_email = " . QuoteText( $email ) );

		if ( ! $id ) {
			return null;
		}

		return new Fresh_Supplier( $id );
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	function getSiteId() {
		return SqlQuerySingleScalar( "SELECT site_id FROM im_suppliers WHERE id = " . $this->id );
	}

	function getMachineUpdate() {
		return SqlQuerySingleScalar( "SELECT machine_update FROM im_suppliers WHERE id = " . $this->id );
	}

	function getAddress()
	{
		$sql = "select address from im_suppliers where id = " . $this->id;
		return SqlQuerySingleScalar( $sql);
	}

	function getLastCount() {
		//  year(date_sub(count_date, interval 30 day)) from im_inventory_count
		$result = SqlQuerySingleScalar( "select max(count_date) from im_inventory_count where supplier_id = " . $this->id);
		if (strlen($result) < 5) return null;
		return $result;
	}

	static function gui_select_supplier( $id = "supplier_select", $value = null, $args = null )
	{
		$events = null;
		$edit = GetArg($args, "edit", true);

		if (! $edit){
			if ($value) return get_supplier_name($value);
			return "supplier not selected";
		}
		$args["name"] = "supplier_name";

		return Core_Html::GuiSelectTable($id, "suppliers", $args);
//		$sql_where );
	}
}

function get_supplier_name($id)
{
	return SqlQuerySingleScalar( 'SELECT supplier_name FROM im_suppliers WHERE id = ' . $id );
}