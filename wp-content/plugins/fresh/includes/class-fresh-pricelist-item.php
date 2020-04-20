<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/15
 * Time: 10:16
 */
// require_once( '../im-tools.php' );
//
//

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}

if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ));
}

class Fresh_Pricelist_Item {
	private $id;
	private $product_name;
	private $supplier_id;
	private $date;
	private $price;
	private $supplier_product_code;
	private $sale_price;
	private $category;
	private $picture_path;

	function __construct( $pricelist_id ) {
		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price, category, picture_path FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = sql_query_single_assoc( $sql );
		if ( $result == null ) {
			print "pricelist item $pricelist_id not found<br/>";
			die( 1 );
		}
		$this->id                    = $pricelist_id;
		$this->product_name          = $result["product_name"];
		$this->supplier_id           = $result["supplier_id"];
		$this->date                  = $result["date"];
		$this->price                 = $result["price"];
		$this->supplier_product_code = $result["supplier_product_code"];
		$this->sale_price            = $result["sale_price"];
		$this->category              = $result["category"];
		$this->picture_path          = $result["picture_path"];
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @return mixed
	 */
	public function getProductName() {
		return $this->product_name;
	}

	/**
	 * @return mixed
	 */
	public function getSupplierId() {
		return $this->supplier_id;
	}

	/**
	 * @return mixed
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @return mixed
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return mixed
	 */
	public function getSupplierProductCode() {
		return $this->supplier_product_code;
	}

	/**
	 * @return mixed
	 */
	public function getSalePrice() {
		return $this->sale_price;
	}

	/**
	 * @return mixed
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @return mixed
	 */
	public function getPicturePath() {
		return $this->picture_path;
	}

	public function getSellPrice() {
		return Fresh_Pricing::calculate_price( $this->price, $this->supplier_id, $this->sale_price );
	}

	public function getSupplierName() {
		$s = new Fresh_Supplier( $this->supplier_id );

		return $s->getSupplierName();
	}
	
	static public function add_prod_info($row, $edit = true)
	{
		$create_info = GetParam("create_products", false, false);
		$supplier_id = GetParam("id", true);

		$catalog = new Fresh_Catalog();
		$pl_id   = $row["id"];

		$link_data = $catalog->GetProdID( $pl_id );
		$linked_prod_id   = "";
		$map_id    = "";
		if ( $link_data ) {
			if ($create_info) return null; // Show just new products (not mapped).
			$linked_prod_id = $link_data[0];
			$p       = new Fresh_Product( $linked_prod_id );
			$map_id  = null;

			if ( isset( $link_data[1] ) ) {
				$map_id = $link_data[1];
			}
			// print $prod_id . " " . $map_id . "<br/>";
//			if ($create_option)
//				continue; // Show only non linked products.

//			if ( $ordered_only and ! isset( $needed_products[ $prod_id ][0] ) and ! isset( $needed_products[ $prod_id ][1] ) )
//				continue;

//			if ( $need_supply_only and ( $needed_products[ $prod_id ][0] <= $p->getStock() ) ) {
//				continue;
//			}

			array_push( $row, $p->getName() );
//			array_push( $row, ($edit ? Core_Html::GuiInput("prc_$pl_id", $p->getPrice()) : $p->getPrice()));
			array_push( $row, $p->getPrice());
			array_push( $row, $p->getSalePrice() );
			$stockManaged = $p->getStockManaged();
//			array_unshift( $row, gui_checkbox( "chm_" . $linked_prod_id, "stock", $stockManaged, "onchange=\"change_managed(this)\")" ) );
			array_push( $row, Core_Html::GuiLabel( "stk_" . $linked_prod_id, Core_Html::GuiHyperlink( $p->getStock(), "../orders/get-orders-per-item.php?prod_id=" . $linked_prod_id ) ) );
			$n = Fresh_Packing::orders_per_item( $linked_prod_id, 1, true, true, true );
			array_push( $row, $n );
		} else {
			if ($create_info) {
				array_push($row, Fresh_Category::Select("categ_" . $row['id'], "categories", null));
				array_push($row, Core_Html::GuiButton("btn_" . $row['id'], "Add Product", array("action" => "create_product('". Fresh::getPost() ."', $supplier_id, " . $row['id'] .")")));
			} else {
				var_dump($row);
//				array_push($row, self::prod_options())
			}
		}
		return $row;
	}

	static function prod_options($product_name, $pl_id)
	{
		$striped_prod = $product_name;
		foreach ( array( "אורגני", "יחידה", "טרי" ) as $word_to_remove ) $striped_prod = str_replace( $word_to_remove, "", $striped_prod );

		$striped_prod = trim( $striped_prod );

		$prod_options = Fresh_Catalog::GetProdOptions( $product_name );

		$options = [];
		$selected  = null;
		foreach ( $prod_options as $row1 )
		{
			$striped_option = $row1["post_title"];
			$striped_option = str_replace( "-", " ", $striped_option );
			$striped_option = trim( $striped_option, " " );
			array_push($options, array("id" => $row1["id"], "name"=> $row1["post_title"]));
			$options .= '<option value="' . "XX"  . '" ';
			if ( ! strcmp( $striped_option, $striped_prod ) ) $selected = $striped_option;
		}
		$args = array("values"=>$options, "events"=>'onchange="selected(this)"');

		return Core_Html::GuiSimpleSelect("prd" .  $pl_id, $selected, $args);

//		$line = "<tr>";
//		$line .= "<td>" . gui_checkbox( "chk" . $pricelist_id, "product_checkbox", $match ) . "</td>";
//		$line .= "<td>" . $supplier_product_code . "</td>";
//		$line .= "<td>" . $product_name . "</td>";
//		$line .= "<td>" . $supplier_id . "</td>";
//		$line .= "<td><select onchange='selected(this)' id='$pricelist_id'>";
//
//		$line .= $options;
//
//		$line .= '</select></td>';

	}
}


class UpdateResult {
	const UsageError = 0;
	const UpPrice = 1;
	const NoChangPrice = 2;
	const DownPrice = 3;
	const ExitPrice = 4;
	const NewPrice = 5;
	const SQLError = 6;
	const DeletePrice = 7;
	const NotUsed = 8;
}


function pricelist_get_price( $prod_id ) {
	// my_log("prod_id = " . $prod_id);
	if ( ! ( $prod_id > 0 ) ) {
		print "missing prod_id " . $prod_id . "<br/>";
		die ( 1 );
	}
	$supplier_id = get_supplier_id( get_postmeta_field( $prod_id, "supplier_name" ) );

	$sql = 'SELECT price FROM im_supplier_price_list WHERE supplier_id = \'' . $supplier_id . '\'' .
	       ' AND product_name IN (SELECT supplier_product_name FROM im_supplier_mapping WHERE product_id = ' . $prod_id . ')';

	return sql_query_single_scalar( $sql );
}



function pricelist_strip_product_name( $name ) {
	// trim sadot product name starting with * or **
	$name = str_replace( array( '.', ',', '*', '\'' ), '', $name );
	$name = str_replace( array( ')', '(', '-' ), ' ', $name );

	return $name;
}

function product_other_suppliers( $prod_id, $supplier_id ) {
	$result       = "";
	$alternatives = alternatives( $prod_id );
	foreach ( $alternatives as $alter ) {
		$a_supplier_id = $alter->getSupplierId();
		if ( $a_supplier_id != $supplier_id ) {
			$result .= get_supplier_name( $a_supplier_id ) . " " . $alter->getPrice() . ", ";
		}
	}

	return rtrim( $result, ", " );
}


function get_order_itemmeta( $order_item_id, $meta_key ) {
	if ( is_array( $order_item_id ) ) {
		$sql = "SELECT sum(meta_value) FROM wp_woocommerce_order_itemmeta "
		       . ' WHERE order_item_id IN ( ' . CommaImplode( $order_item_id ) . ") "
		       . ' AND meta_key = \'' . escape_string( $meta_key ) . '\'';

		return sql_query_single_scalar( $sql );
	}
	if ( is_numeric( $order_item_id ) ) {
		$sql2 = 'SELECT meta_value FROM wp_woocommerce_order_itemmeta'
		        . ' WHERE order_item_id = ' . $order_item_id
		        . ' AND meta_key = \'' . escape_string( $meta_key ) . '\''
		        . ' ';

		return sql_query_single_scalar( $sql2 );
	}

	return - 1;
}

?>