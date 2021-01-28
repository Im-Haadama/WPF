<?php
if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( __FILE__ ) ) );
}

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/01/16
 * Time: 16:31
 */
// require_once( '../r-shop_manager.php' );
//require_once( "catalog.php" );

class Fresh_Bundles {
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			return new self();
		}
		return self::$_instance;
	}

	private $class_name = "bundle";

	function init_hooks($loader)
	{
		Core_Gem::getInstance()->AddTable("bundles", $loader);
		self::AddHook('bundle_change_quantity');
		self::AddHook('bundle_add_item');
	}

	function AddHook($hook)
	{
		AddAction($hook, array($this, $hook));
	}

	function bundle_add_item()
	{
		$product_id = GetParam("product_id", true);
		$quantity= GetParam("quantity", true);
		$margin = GetParam("margin", true);

		$b = Fresh_Bundle::CreateNew($product_id, $quantity, $margin);
		if (! $b) return false;
		return $b->CreateOrUpdate();
	}
	function bundle_change_quantity()
	{
		$id = GetParam("id", true);
		$q = GetParam("q", true);

		$B = Fresh_Bundle::CreateFromDb($id);
		return $B->Update($q);
	}

	static function add_wrapper()
	{
		$post_file = Fresh::getPost();
		$result = "";
		$result .=  Core_Html::GuiHeader( 1, "יצירת מארז" );
		$result .=  Core_Html::gui_table_args( array(
			array(
				Core_Html::GuiHeader( 2, "בחר מוצר" ),
				Core_Html::GuiHeader( 2, "מחיר עלות" ),
				Core_Html::GuiHeader( 2, "מחיר יחידה" ),
				Core_Html::GuiHeader( 2, "כמות במארז" ),
				Core_Html::GuiHeader( 2, "רוווח %/שח" ),
				Core_Html::GuiHeader( 2, "מחיר מארז" ),
				Core_Html::GuiHeader( 2, "מחיר רגיל" )
			),
			array(
//				Core_Html::GuiInputDatalist( "product_name", "products", "onchange=\"calcBundle()\"" ),
			Fresh_Product::gui_select_product("product_name", null),
				// '<input id="item_name" list="items" onchange="getPrice()">',
				'<div id="buy_price">',
				'<div id="price">',
				'<input id="quantity" onchange="bundle_calc(\'' . $post_file . '\')">',
				'<input id="margin" onchange="bundle_calc(\'' . $post_file . '\')">',
				'<div id="bundle_price">',
				'<div id="regular_price">'
			)
		) );
		$result .=  Core_Html::GuiButton( "btn_create_bundle", "צור", "bundle_create('$post_file')");
		$result .=  Core_Html::gui_datalist( "products", "im_products", "ID"); // "post_title", true );

		return $result;
	}

	function PrintHTML( $only_active = true ) {
		$args = [];
		$args["headers"] = array("prod_id" => "Product name", "quantity" => "Quantity", "margin"=>"Profit(%/$)", "bundle_prod_id"=>"Bundle name","bundle_price" => "Bundle price");
		$args["selectors"] = array("prod_id" => 'Fresh_Product::gui_select_product',
			"bundle_prod_id" => 'Fresh_Product::gui_select_product',
		"quantity"=>'Fresh_Bundle::gui_select_quantity',
		"margin"=>'Fresh_Bundle::gui_select_margin');
		$args["edit"] = false;
		$args["hide_cols"] = array("id"=>true);
		$args["add_checkbox"] = true;
		$args["prepare_plug"] = __CLASS__ . '::add_bundle_info';
//		$args["actions"] = array("edit" => Core_Html::GuiHyperlink("Edit", AddToUrl(array("operation"=>"bundle_edit", "id"=>'%d'))));
//		,	                         "Not active"=> Core_Html::GuiButton("btn_disable", "Inactive", AddToUrl(array("operation"=>"bundle_inactive", "id"=>'%d')) . ";action_hide_row"));
		if ($only_active) $args["query"] = "is_active = 1";
//		$args["operation"] = GetParam("operation", false, null, true);
		return Core_Gem::GemTable("bundles", $args);
	}

	function get_class_name() {
		return $this->class_name;
	}

	static function add_bundle_info($row)
	{
		$bundle_prod_id = $row['bundle_prod_id'];
		$b = new Fresh_Product($bundle_prod_id);

		$row['bundle_price'] = $b->getPrice();
		return $row;
	}
}

class NotFoundException extends Exception {
}

class Fresh_Bundle {
	private $id;
	private $bundle_prod_id;
	private $prod_id;
	private $quantity;
	private $margin;

	static function CreateNew( $product_id, $q, $margin ) {
		$b           = new Fresh_Bundle();
		$b->prod_id  = $product_id;
		$b->quantity = $q;
		$b->margin   = $margin;

		// Check if exists in system
		$sql = "SELECT id, bundle_prod_id FROM im_bundles WHERE prod_id = " . $b->prod_id .
		       " AND quantity = " . $b->quantity;

		$row = SqlQuerySingle( $sql );

		if ( $row ) {
			$b->id             = $row[0];
			$b->bundle_prod_id = $row[1];
		}

		return $b;
	}

	static function CreateFromProd( $prod_id ) {
		$sql = "select id from im_bundles where prod_id = $prod_id";
		$_id = SqlQuerySingleScalar( $sql );
		// print $_id . " ";
		if ( $_id ) {
			return new self( $_id );
		}

		return null;
	}

	static function gui_select_quantity($id, $value, $args)
	{
		$post_file = Fresh::getPost();
		$row_id = substr($id, strpos($id, '_') + 1);
		$args["events"] = "onchange=\"bundle_update_quantity('$post_file', $row_id)\"";
		$args["size"] = 4;
		return Core_Html::GuiInput($id, $value, $args);
	}

	static function gui_select_margin($id, $value, $args)
	{
		$post_file = Fresh::getPost();
		$row_id = substr($id, strpos($id, '_') + 1);
		$args["events"] = "onchange=\"bundle_update_margin('$post_file', $row_id)\"";
		$args["size"] = 4;
		return Core_Html::GuiInput($id, $value, $args);
	}

	static function CreateFromBundleProd( $prod_id ) {
		$sql = "select id from im_bundles where bundle_prod_id = $prod_id";

		$_id = SqlQuerySingleScalar( $sql );
		if ( $_id ) {
			return Fresh_Bundle::CreateFromDb( $_id );
		}

		return null;
	}

	static function CreateFromDb( $_id ) {
		$b     = new Fresh_Bundle();
		$b->id = $_id;
		$sql   = "select prod_id, quantity, margin, bundle_prod_id from im_bundles where id = $_id";
		$row   = SqlQuerySingleAssoc( $sql );
		if ( $row ) {
			$b->bundle_prod_id = $row["bundle_prod_id"];
			$b->prod_id        = $row["prod_id"];
			$b->quantity       = $row["quantity"];
			$b->margin         = $row["margin"];
		} else {
			print "cant find bundle " . $_id . "<br/>";
			throw new NotFoundException();
		}

		return $b;
	}

	static function GetBundles( $prod_id ) {
		return SqlQueryArrayScalar( "SELECT bundle_prod_id FROM im_bundles WHERE prod_id = " . $prod_id );
	}

	function Delete() {
		MyLog( "delete bundle", __CLASS__ );
		$sql = "SELECT bundle_prod_id FROM im_bundles WHERE id = " . $this->id;
		MyLog( $sql, __CLASS__ );
		$bundle_prod_id = SqlQuerySingleScalar( $sql );
		MyLog( $bundle_prod_id, __CLASS__ );

		Catalog::DraftItems( array( $bundle_prod_id ) );

		$sql = "DELETE FROM im_bundles WHERE id = " . $this->id;

		MyLog( "sql = " . $sql );

		SqlQuery( $sql );
	}

	function GetBuyPrice() {
		return $this->quantity * get_buy_price( $this->prod_id );
	}

	function GetProdId() {
		return $this->prod_id;
	}

	function GetBundleProdId() {
		return $this->bundle_prod_id;
	}

	function GetQuantity() {
		return $this->quantity;
	}

	function CreateOrUpdate() {
		if ( $this->id )
			return $this->Update();
		return $this->Add();
	}

	function Update( $quantity = null, $margin = null ) {
		if ( $quantity ) {
			$this->quantity = $quantity;
		}
		if ( $margin ) {
			$this->margin = $margin;
		}
		return $this->DoUpdate() and $this->Save();
	}

	function DoUpdate() {
		$p = new Fresh_Product($this->prod_id);
		$single_price = $p->getPrice();
		$product_id    = $this->bundle_prod_id;
		$regular_price = $this->quantity * $single_price;
		$sale_price    = $this->CalculatePrice($p->getPrice());
//		print "price: $sale_price <br/>";

		MyLog( "Bundle::Update $product_id $regular_price $sale_price " . $this->quantity );

		update_post_meta( $product_id, "_regular_price", $regular_price );
		update_post_meta( $product_id, "_sale_price", $sale_price );
		update_post_meta( $product_id, "_price", $sale_price );
//		update_post_meta( $product_id, "buy_price", get_buy_price( $this->prod_id ) * $this->quantity );
		$my_post = array(
			'ID'         => $product_id,
			'post_title' => "מארז " . $this->quantity . ' ק"ג ' . $p->getName()
		);

		// Update the post into the database
		return wp_update_post( $my_post );
	}

	function CalculatePrice($single_price) {
		if ( strstr( $this->margin, "%" ) ) {
			$percent = substr( $this->margin, 0, strlen( $this->margin ) - 1 );

			return round( $single_price * $this->quantity * ( 100 + $percent ) / 100, 0 );
		}

		if ( $this->margin == "" ) {
			$m = 0;
		} else {
			$m = $this->margin;
		}

		return round( $single_price * $this->quantity + $m, 0 );
	}

	function Save() {
		$sql = "update im_bundles " .
		       " set margin = " . $this->margin . ", " .
		       " quantity = " . $this->quantity .
		       " where id = " . $this->id;

//		print $sql;

		return SqlQuery( $sql );
	}

	function Add() {
		$p              = new Fresh_Product( $this->prod_id );
		$regular_price  = $this->quantity * $p->getPrice();

//		print "reg=$regular_price sal= " . $this->CalculatePrice() . "<br/>";
		$bundle_prod_id = Fresh_Catalog::DoCreateProduct( "מארז " . $this->quantity . " ק\"ג " . $p->getName(),
			$regular_price, $p->getSupplierId(), "מארזי כמות", Fresh_Catalog::GetProdImage( $this->prod_id ), $this->CalculatePrice($p->getPrice()) );
		// $bundle_prod_id = get_product_id_by_name( $bundle_prod_name );

		$sql = "INSERT INTO im_bundles (prod_id, quantity, margin, bundle_prod_id, is_active) VALUES (" . $this->prod_id . ", " .
		       $this->quantity . ", '" . $this->margin . "', " . $bundle_prod_id . ", 1)";

		return SqlQuery( $sql );
	}

	function Disable() {
		// Draft the bundle.
		$p = new Fresh_Product( $this->bundle_prod_id );
		$p->draft();

		$sql = "UPDATE im_bundles SET is_active = FALSE WHERE id = " . $this->id;
		SqlQuerySingleScalar( $sql );
	}

	function getName()
	{
		return get_product_name($this->prod_id);
	}

	function edit()
	{

	}
}
