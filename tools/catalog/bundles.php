<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( __FILE__ ) ) );
}

require_once( ROOT_DIR . "/tools/im_tools.php" );

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/01/16
 * Time: 16:31
 */
// require_once( '../r-shop_manager.php' );
require_once( "catalog.php" );
require_once( "gui.php" );

class Bundles {

	private $class_name = "bundle";

	function PrintHTML( $only_active = true ) {
		$data = "";

		$sql = 'SELECT prod_id, bundle_prod_id, quantity, margin, id, get_product_name(prod_id) FROM im_bundles';
		if ( $only_active ) {
			$sql .= " where is_active = 1 ";
		}
		$sql .= ' ORDER BY 6';

		// print $sql;

		$result = sql_query( $sql );

		$data .= "<tr>";
		$data .= "<td>בחר</td>";
		$data .= "<td>שם מוצר</td>";
		$data .= "<td>כמות במארז</td>";
		$data .= "<td>מזהה מארז</td>";
		$data .= "<td>רווח</td>";
		$data .= "<td>מחיר מכירה</td>";
		$data .= "</tr>";

		while ( $row = mysqli_fetch_row( $result ) ) {
			$product_name = $row[5]; // get_product_name( $row[0] );
			$bundle_id    = $row[1];
			$quantity     = $row[2];
			$margin       = $row[3];
			$id           = $row[4];
			$b            = Bundle::CreateFromDb( $id );

			$line = "<tr>";
			$line .= "<td><input id=\"" . $id . "\" class=\"" . $this->get_class_name() . "_checkbox\" type=\"checkbox\"></td>";
			$line .= "<td>" . $product_name . "</td>";
			$line .= "<td>" . gui_input( "qty", $quantity ) . "</td>";
			$line .= "<td>" . $bundle_id . "</td>";
			$line .= '<td><input type="text" value="' . $margin . '"</td>';
			$line .= gui_cell( $b->CalculatePrice() );
			$line .= "</tr>";
			$data .= $line;
		}
		print $data;
	}

	function get_class_name() {
		return $this->class_name;
	}
}

class NotFoundException extends Exception {
}

class Bundle {
	private $id;
	private $bundle_prod_id;
	private $prod_id;
	private $quantity;
	private $margin;

	static function CreateNew( $product_id, $q, $margin ) {
		$b           = new Bundle();
		$b->prod_id  = $product_id;
		$b->quantity = $q;
		$b->margin   = $margin;

		// Check if exists in system
		$sql = "SELECT id, bundle_prod_id FROM im_bundles WHERE prod_id = " . $b->prod_id .
		       " AND quantity = " . $b->quantity;

		$row = sql_query_single( $sql );

		if ( $row ) {
			$b->id             = $row[0];
			$b->bundle_prod_id = $row[1];
		}

		return $b;
	}

	static function CreateFromProd( $prod_id ) {
		$sql = "select id from im_bundles where prod_id = $prod_id";
		$_id = sql_query_single_scalar( $sql );
		// print $_id . " ";
		if ( $_id ) {
			return new self( $_id );
		}

		return null;
	}

	static function CreateFromBundleProd( $prod_id ) {
		$sql = "select id from im_bundles where bundle_prod_id = $prod_id";

		$_id = sql_query_single_scalar( $sql );
		if ( $_id ) {
			return Bundle::CreateFromDb( $_id );
		}

		return null;
	}

	static function CreateFromDb( $_id ) {
		$b     = new Bundle();
		$b->id = $_id;
		$sql   = "select prod_id, quantity, margin, bundle_prod_id from im_bundles where id = $_id";
		$row   = sql_query_single_assoc( $sql );
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

	function UpdateMargin( $margin ) {
		$this->margin = $margin;
		$this->Update();

		$sql = "update im_bundles set margin = '" . $margin . "'" .
		       " where id = " . $this->id;

		sql_query( $sql );
	}

	function Update() {
		$product_id    = $this->bundle_prod_id;
		$regular_price = $this->quantity * get_price( $this->prod_id );
		$sale_price    = $this->CalculatePrice();
		print "price: $sale_price <br/>";

		my_log( "Bundle::Update $product_id $regular_price $sale_price " . $this->quantity );

		update_post_meta( $product_id, "_regular_price", $regular_price );
		update_post_meta( $product_id, "_sale_price", $sale_price );
		update_post_meta( $product_id, "_price", $sale_price );
		update_post_meta( $product_id, "buy_price", get_buy_price( $this->prod_id ) * $this->quantity );
	}

	function CalculatePrice() {
		if ( strstr( $this->margin, "%" ) ) {
			$percent = substr( $this->margin, 0, strlen( $this->margin ) - 1 );

			return round( get_buy_price( $this->prod_id ) * $this->quantity * ( 100 + $percent ) / 100, 0 );
		}

		if ( $this->margin == "" ) {
			$m = 0;
		} else {
			$m = $this->margin;
		}

		return round( ( get_buy_price( $this->prod_id ) ) * $this->quantity + $m, 0 );
	}

	function Delete() {
		my_log( "delete bundle", __CLASS__ );
		$sql = "SELECT bundle_prod_id FROM im_bundles WHERE id = " . $this->id;
		my_log( $sql, __CLASS__ );
		$bundle_prod_id = sql_query_single_scalar( $sql );
		my_log( $bundle_prod_id, __CLASS__ );

		Catalog::DraftItems( array( $bundle_prod_id ) );

		$sql = "DELETE FROM im_bundles WHERE id = " . $this->id;

		my_log( "sql = " . $sql );

		sql_query( $sql );
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
		if ( $this->id ) {
			// Update
		} else {
			// Create
			$this->Add();
		}
	}

	function Add() {
		//$b = Bundle::CreateNew( $prod_id, $quantity, $margin );
		// print $prod_name . "<br/>";
		// $prod_id        = get_product_id_by_name( $prod_name );

		// print $prod_id. "<br/>";
		// ($product_name, $sell_price, $supplier_name, $categ = null, $image_path)
		$p              = new Product( $this->prod_id );
		$regular_price  = $this->quantity * $p->getPrice();
		$bundle_prod_id = Catalog::DoCreateProduct( "מארז " . $this->quantity . " ק\"ג " . get_product_name( $this->prod_id ),
			$regular_price, $this->GetSupplier(), "מארזי כמות", Catalog::GetProdImage( $this->prod_id ), $this->CalculatePrice() );
		// $bundle_prod_id = get_product_id_by_name( $bundle_prod_name );

		$sql = "INSERT INTO im_bundles (prod_id, quantity, margin, bundle_prod_id, is_active) VALUES (" . $this->prod_id . ", " .
		       $this->quantity . ", '" . $this->margin . "', " . $bundle_prod_id . ", 1)";

		sql_query( $sql );
	}

	function GetSupplier() {
		return get_supplier( $this->prod_id );
	}

	function Disable() {
		// Draft the bundle.
		$p = new Product( $this->bundle_prod_id );
		$p->draft();

		$sql = "UPDATE im_bundles SET is_active = FALSE WHERE id = " . $this->id;
		sql_query_single_scalar( $sql );
	}
}
