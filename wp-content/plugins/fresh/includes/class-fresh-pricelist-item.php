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
		if ($pricelist_id == 0) return;

		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price, category, picture_path FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = SqlQuerySingleAssoc( $sql );
		if ( $result == null ) {
			print "pricelist item $pricelist_id not found<br/>";
			$this->price = 0;
			return 0;
			// die( 1 );
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

	public function getPreviousPrice()
	{
		$previous_date = SqlQuerySingleScalar("select date from im_supplier_price_list 
			where supplier_id = $this->supplier_id and date < '" . $this->date . "' order by date desc limit 1");

		return SqlQuerySingleScalar("select price from im_supplier_price_list
			where supplier_id = $this->supplier_id 
			  and date = '$previous_date' 
			  and product_name = '" . EscapeString($this->product_name) ."'");
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
		$supplier_id = GetParam( "supplier_id", true );

		$pl_id = $row["id"];
		if (! ($pl_id > 0)) return $row; // New row

		$item = new Fresh_Pricelist_Item( $pl_id );

		$row = $item->AddProdInfo( $row, $edit, $create_info, $supplier_id );
		return $row;
	}

	function delete()
	{
		$sql = "delete from im_supplier_price_list where id = $this->id";
		MyLog($sql);
		return SqlQuery($sql);
	}

	public function AddProdInfo($row, $edit, $create_info, $supplier_id)
	{
		$catalog = new Fresh_Catalog();

		$post_file = Fresh::getPost();
		$link_data = $catalog->GetProdID( $this->id);
		array_push($row, Core_Html::GuiButton("del_" . $this->id, "X", "pricelist_delete('$post_file', $this->id)"));
		$price = $row['price'];
		$args['style'] = 'background-color: ' . self::get_prod_color();
		$row['price'] = Core_Html::GuiInput("price_" . $row['id'], $price, $args);

		if ( $link_data ) {
			if ($create_info) return null; // Show just new products (not mapped).

			$linked_prod_id = $link_data[0];
			$p       = new Fresh_Product( $linked_prod_id );
			$calculated_price = Fresh_Pricing::calculate_price($this->getPrice(), $supplier_id);
//			if (! $p->isPublished()) $p->PublishItem($calculated_price);
			SqlQuery("update im_supplier_price_list set product_id = $linked_prod_id where id = " .$this->id);

			$map_id  = null;

			array_push( $row, $p->getName() );
			array_push($row, Core_Html::GuiCheckbox("pub_" . $linked_prod_id, $p->isPublished(), array("events" => "onchange=\"product_publish('" . Fresh::getPost() . "', $linked_prod_id)\"")));
			array_push( $row, $calculated_price);
			$style = null;
			if ($p->getPrice() and ($p->getPrice() < $price)) $style = "background-color: #EC7063";
			array_push( $row, Core_Html::GuiInput("prc_$linked_prod_id", $p->getPrice(),
				array("events"=>array("onchange=\"product_change_regularprice('" . Fresh::getPost() . "', " . $this->id . ", $linked_prod_id)\"", 'onkeypress="moveNext(this)"'),
				      "size"=>5,
					"style" => $style)));
			array_push( $row, Core_Html::GuiInput("sal_$linked_prod_id", $p->getSalePrice(),
				array("events"=>array("onchange=\"product_change_saleprice('" . Fresh::getPost() . "', $linked_prod_id)\"", 'onkeypress="moveNext(this)"'), "size"=>5)));
			$n = Fresh_Packing::orders_per_item( $linked_prod_id, 1, true, true, true );
			array_push( $row, $n );
		} else {
			if ($create_info) {
				array_push($row, Fresh_Category::Select("categ_" . $row['id'], "categories", null));
				array_push($row, Core_Html::GuiButton("btn_" . $row['id'], "Add Product", array("action" => "create_product('". Fresh::getPost() ."', $supplier_id, " . $row['id'] .")")));
			} else {
				array_push($row, Fresh_Product::gui_select_product("prd" . $this->id, "", array("events" => 'onchange=pricelist_option_selected(this)')),
				"",
				"",
				"",
				"");
			}
		}
		return $row;
	}

	function get_prod_color()
	{
		$current_price = self::getPrice();
		$prev_price = self::getPreviousPrice();
		$color = "white";
		if (! $prev_price) $color = 'lightgreen';
		else
		if ($current_price > $prev_price) $color = 'salmon';
		if ($current_price < $prev_price) $color = 'lightblue';
		return $color;
	}
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
