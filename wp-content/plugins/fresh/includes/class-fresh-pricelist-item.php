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

		$result = SqlQuerySingleAssoc( $sql );
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

	public function getPricePerDate($date = null)
	{
		$sql = "select price from im_supplier_price_list
				where product_name like '%" . Fresh_PriceList::StripProductName($this->product_name) ."%'";
		if ($date) $sql .= "and date <= '" . $date . "'";
		$sql .= " order by date desc limit 1";
//		print $sql . "<br/>";
		$p = SqlQuerySingleScalar($sql);
//		if ($this->id == 749832)
//			print $sql . " $p<br/>";
		return $p;
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
	public function getProductName($striped = false) {
		if (!$striped) return $this->product_name;

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

	static public function add_prod_info($row, $edit = true) {
		$create_info = GetParam( "create_products", false, false );
		$supplier_id = GetParam( "id", true );

		$pl_id = $row["id"];

		$item = new Fresh_Pricelist_Item( $pl_id );

		return $item->AddProdInfo( $row, $edit, $create_info, $supplier_id );
	}

	public function AddProdInfo($row, $edit, $create_info, $supplier_id)
	{
		$catalog = new Fresh_Catalog();

		$link_data = $catalog->GetProdID( $this->id);
		$linked_prod_id   = "";
		$map_id    = "";
		$row['product_name'] = Fresh_Pricelist::StripProductName($row['product_name']);
		if ( $link_data ) {
			$price = $row['price'];
			$date = $row['date'];
			$previous_date = SqlQuerySingleScalar("select date from im_supplier_price_list where supplier_id = $supplier_id and date < '" . $date . "' order by date desc limit 1");
			if ($create_info) return null; // Show just new products (not mapped).
			$linked_prod_id = $link_data[0];
			$p       = new Fresh_Product( $linked_prod_id );
			$map_id  = null;

			if ( isset( $link_data[1] ) ) {
				$map_id = $link_data[1];
			}
			// print $prod_id . " " . $map_id . "<br/>";
//			if ($create_option) continue; // Show only non linked products.
//			if ( $ordered_only and ! isset( $needed_products[ $prod_id ][0] ) and ! isset( $needed_products[ $prod_id ][1] ) ) continue;
//			if ( $need_supply_only and ( $needed_products[ $prod_id ][0] <= $p->getStock() ) ) { continue;

			$color = $this->get_prod_color($date, $previous_date);
			if ($color)
				$row['price'] = Core_Html::GuiDiv("", $row['price'], array("style" => "background-color: $color"));
			array_push( $row, $p->getName() );
//			array_push( $row, ($edit ? Core_Html::GuiInput("prc_$pl_id", $p->getPrice()) : $p->getPrice()));
			array_push( $row, Fresh_Pricing::calculate_price($this->getPricePerDate($date), $supplier_id));
			$style = null;
			if ($p->getPrice() < $price) $style = "background-color: #EC7063";
			array_push( $row, Core_Html::GuiInput("prc_$linked_prod_id", $p->getPrice(),
				array("events"=>array("onchange=\"product_change_regularprice('" . Fresh::getPost() . "', " . $this->id . ", $linked_prod_id)\"", 'onkeypress="moveNext(this)"'),
				      "size"=>5,
					"style" => $style)));
			array_push( $row, Core_Html::GuiInput("sal_$linked_prod_id", $p->getSalePrice(),
				array("events"=>array("onchange=\"product_change_saleprice('" . Fresh::getPost() . "', $linked_prod_id)\"", 'onkeypress="moveNext(this)"'), "size"=>5)));
			$stockManaged = $p->getStockManaged();
//			array_unshift( $row, gui_checkbox( "chm_" . $linked_prod_id, "stock", $stockManaged, "onchange=\"change_managed(this)\")" ) );
//			array_push( $row, Core_Html::GuiLabel( "stk_" . $linked_prod_id, Core_Html::GuiHyperlink( $p->getStock(), "../orders/get-orders-per-item.php?prod_id=" . $linked_prod_id ) ) );
			$n = Fresh_Packing::orders_per_item( $linked_prod_id, 1, true, true, true );
			array_push( $row, $n );
		} else {
			if ($create_info) {
				array_push($row, Fresh_Category::Select("categ_" . $row['id'], "categories", null));
				array_push($row, Core_Html::GuiButton("btn_" . $row['id'], "Add Product", array("action" => "create_product('". Fresh::getPost() ."', $supplier_id, " . $row['id'] .")")));
			} else {
//				var_dump($row);
				array_push($row, Fresh_Product::gui_select_product("prd" . $this->id, "", array("events" => 'onchange=pricelist_option_selected(this)')),
				"",
				"",
				"",
				"");
//					self::prod_options($row['product_name'], $pl_id));

			}
		}
		return $row;
	}

	function get_prod_color($current, $previous)
	{
		$current_price = self::getPricePerDate($current);
		$prev_price = self::getPricePerDate($previous);
		$color = "white";
		if (! $prev_price) $color = 'lightgreen';
		else
		if ($current_price > $prev_price) $color = 'salmon';
		if ($current_price < $prev_price) $color = 'lightblue';
		return $color;
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
?>
