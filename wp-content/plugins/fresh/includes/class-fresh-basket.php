<?php

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 07/11/18
 * Time: 07:22
 */
class Fresh_Basket {
	private $id;

	/**
	 * Basket constructor.
	 *
	 * @param $id
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	static public function init()
	{
		AddAction( "add_to_basket", __CLASS__ . "::add_to_basket" );
		AddAction( "remove_from_basket", __CLASS__ . "::remove_from_basket" );
	}
	public function GetQuantity( $prod_id ) {
		$sql = "SELECT quantity FROM im_baskets WHERE basket_id = " . $this->id .
		       " AND product_id = " . $prod_id;

		// print $sql;
		return sql_query_single_scalar( $sql );
	}
	function get_basket_date( $basket_id ) {
		$sql = 'SELECT max(date) FROM im_baskets WHERE basket_id = ' . $basket_id;

		$row = sql_query_single_scalar( $sql );

		return substr( $row, 0, 10 );
	}

	function get_basket_content( $basket_id ) {
		// t ;

		$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
		       ' ORDER BY 3';

		$result = sql_query( $sql );

		$basket_content = "";

		while ( $row = mysqli_fetch_row( $result ) ) {
			$prod_id  = $row[0];
			$quantity = $row[1];

			if ( $quantity <> 1 ) {
				$basket_content .= $quantity . " ";
			}
			if ($prod_id > 0){
				$b = new Fresh_Product($prod_id);
				if ($b) $basket_content .= $b->getName(true) . ", ";
			}
		}

		return chop( $basket_content, ", " ) . ".";
	}

	function get_basket_content_array( $basket_id ) {
		$result = array();

		$sql = 'SELECT DISTINCT product_id, quantity, id FROM im_baskets WHERE basket_id = ' . $basket_id .
		       ' ORDER BY 3';

		$sql_result = sql_query( $sql );

		while ( $row = mysqli_fetch_row( $sql_result ) ) {
			$prod_id            = $row[0];
			$quantity           = $row[1];
			$result[ $prod_id ] = $quantity;
		}

		return $result;
	}

	function is_basket( ) {
		return sql_query_single_scalar('SELECT count(product_id) FROM im_baskets WHERE basket_id = ' . $this->id);
	}

	static function Settings($url)
	{
		$basket_id = GetParam("basket_id", false, null);
		if ($basket_id)
		{
			return self::show_basket($basket_id);
		}
		$result = Core_Html::gui_header(1, "This week's baskets");

		$result .= self::current_baskets($url);

		return $result;
	}

	static function current_baskets($url)
	{
		$sql = 'SELECT DISTINCT basket_id FROM im_baskets';

		$result = sql_query( $sql );

		$data = "<table><tr><td><h3>שם הסל</h3></td><td><h3>עלות קניה</h3></td><td><h3>מכירה</h3></td><td><h3>מחיר בנפרד</h3></td><td><h3>אחוזי הנחה</h3></td></tr>";

		while ( $row = mysqli_fetch_row( $result ) ) {
			$basket_id = $row[0];
			$p = new Fresh_Product($basket_id);

			$line            = "<tr>";
			$line            .= "<td>" . Core_Html::GuiHyperlink($p->getName(), AddParamToUrl($url, "basket_id", $basket_id)) . "</td>";
			// <a href=\"show_baskets.php?basket_id=" . $basket_id . "\">" . $p->getName() . "</a></td>";
			$total_listprice = self::get_total_listprice( $basket_id );
			$line            .= "<td>" . $total_listprice . "</td>";
			$basket_price    = $p->getPrice();
			if ( $basket_price > 0 ) {
				$line .= "<td>" . $basket_price . '(' . round( 100 * $basket_price / $total_listprice, 1 ) . "%)</td>";
			} else {
				$line .= "<td></td>";
			}
			$total_sellprice = self::get_total_sellprice( $basket_id );
			$line            .= "<td>" . $total_sellprice . '(' . round( 100 * $total_sellprice / $total_listprice, 1 ) . "%)</td>";
			if ( $basket_price > 0 ) {
				$line .= "<td>" . round( 100 * ( $total_sellprice - $basket_price ) / $basket_price, 1 ) . "%</td>";
			}
			$line .= "</tr>";

			$data .= $line;
		}
		return $data;
	}

	static function get_total_sellprice( $basket_id ) {
		$total_price = 0;
		$sql         = 'SELECT product_id FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result = sql_query( $sql );

		while ( $row = mysqli_fetch_row( $result ) ) {
			$total_price += Fresh_Pricing::get_price( $row[0] );
		}

		return $total_price;
	}

	static function get_total_listprice( $basket_id ) {
		$total_price = 0;
		$sql         = 'SELECT product_id FROM im_baskets WHERE basket_id = ' . $basket_id;

		$result = sql_query( $sql );

		while ( $row = mysqli_fetch_row( $result ) ) {
			// Catalog::GetBuyPrice($row[0]);
			$total_price += Fresh_Catalog::GetBuyPrice( $row[0], -1 );
		}

		return $total_price;
	}

	static function show_basket($basket_id)
	{
		$data = "";
		$basket = new Fresh_Product($basket_id);
		$data .= Core_Html::gui_header(1, ImTranslate("basket") . " " . $basket->getName());
		$sql = 'SELECT DISTINCT product_id, quantity, product_price(product_id) as price, quantity * product_price(product_id) as line_price FROM im_baskets WHERE basket_id = ' . $basket_id .
		       " and post_status(product_id) like '%pub%'";

		$args["post_file"] = "/wp-content/plugins/fresh/post.php";
		$args["id_field"] = "product_id";
		$args["selectors"] = array("product_id" => "Fresh_Product::gui_select_product");
		$args["header_fields"] = array("Product", "Quantity", "Price", "Line total");
		$args["add_checkbox"] = true;
		$args["edit"] = false;
		// $args["sum_fields"] = array("quantity" => array(0, "sum_numbers"));

		$total = 0;
		$basket_content = Core_Data::TableData($sql, $args);
		foreach($basket_content as &$row) {
			if (is_numeric($row["line_price"])) $total += $row["line_price"];
		}

		array_push($basket_content, array( "product_id" => ImTranslate("Total"), "price" => "", "quantity" => "", "line_price" => $total));
		$args["checkbox_class"] = "product_checkbox";

		$data .= Core_Html::gui_table_args($basket_content, "basket_contents", $args);


		$data .= Core_Html::GuiButton("remove_product", "remove", array("action" => "remove_from_basket(" . $basket_id . ")", "remove"));

		$data .= "<br/>";
		$data .= Fresh_Product::gui_select_product("new_product");
		$data .= Core_Html::GuiButton("add_product", "add", array("action" => "add_to_basket(" . $basket_id . ")"));

		$sql = 'SELECT DISTINCT product_id FROM im_baskets WHERE basket_id = ' . $basket_id .
		       " and post_status(product_id) like '%draft%'";

		// $data .= $sql;
		$result = sql_query_array_scalar($sql);
		if ($result){
			$data .= Core_Html::gui_header(1, "Not available, and removed:");
			foreach ($result as $prod_id){
				$p = new Fresh_Product($prod_id);
				$data .= $p->getName() . "<br/>";
				sql_query("delete from im_baskets where product_id = " . $prod_id);
			}
		}
		return $data;
	}

	static function add_to_basket() {
		$basket_id   = GetParam( "basket_id", true );
		$new_product = GetParam( "new_product", true );
		$sql         = 'INSERT INTO im_baskets (basket_id, date, product_id, quantity) VALUES (' . $basket_id . ", '" . date( 'Y/m/d' ) . "', " .
		               $new_product . ", " . 1 . ')';

		return sql_query( $sql );
	}

	static function remove_from_basket() {
		$basket_id = GetParam( "basket_id", true );
		$products  = GetParam( "products", true );
		$sql       = "delete from im_baskets where basket_id = " . $basket_id . " and product_id in ( $products ) ";

		return sql_query( $sql );
	}
}
