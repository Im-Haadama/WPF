<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/01/16
 * Time: 16:31
 */

require_once( '../tools.php' );

class Bundles {
//    function PrintHTML()
//    {
//        $data = "";
//
//        $sql = 'SELECT prod_id, quantity, margin FROM im_bundles'
//            . ' order by 1';
//
//        $export = mysql_query($sql) or die ("Sql error : " . mysql_error());
//
//        $data .= "<tr>";
//        $data .= "<td>בחר</td>";
//        $data .= "<td>שם מוצר</td>";
//        $data .= "<td>כמות במארז</td>";
//        $data .= "<td>מרווח מארז</td>";
//        $data .= "<td>מרווח חדש</td>";
//        $data .= "</tr>";
//
//        while ($row = mysql_fetch_row($export)) {
//            $product_id = $row[0];
//            $quantity = $row[1];
//            $margin = $row[2];
//
//            $line = "<tr>";
//            $line .= "<td><input id=\"chk" . $product_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
//            $line .= "<td>" . get_product_name($product_id) . "</td>";
//            $line .= "<td>" . $quantity . "</td>";
//            $line .= "<td>" . $margin . "</td>";
//            $line .= '<td><input type="text" value="' . $margin . '"</td>';
//            $line .= "</tr>";
//            $data .= $line;
//        }
//        print $data;
//    }

	private $class_name = "bundle";

	function PrintHTML() {
		$data = "";

		$sql = 'SELECT prod_id, bundle_prod_id, quantity, margin, id FROM im_bundles'
		       . ' ORDER BY 1';

		$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

		$data .= "<tr>";
		$data .= "<td>בחר</td>";
		$data .= "<td>שם מוצר</td>";
		$data .= "<td>כמות במארז</td>";
		$data .= "<td>מזהה מארז</td>";
		$data .= "<td>רווח</td>";
		$data .= "</tr>";

		while ( $row = mysql_fetch_row( $export ) ) {
			$product_name = get_product_name( $row[0] );
			$bundle_id    = $row[1];
			$quantity     = $row[2];
			$margin       = $row[3];
			$id           = $row[4];

			$line = "<tr>";
			$line .= "<td><input id=\"" . $id . "\" class=\"" . $this->get_class_name() . "_checkbox\" type=\"checkbox\"></td>";
			$line .= "<td>" . $product_name . "</td>";
			$line .= "<td>" . $quantity . "</td>";
			$line .= "<td>" . $bundle_id . "</td>";
			$line .= '<td><input type="text" value="' . $margin . '"</td>';
			$line .= "</tr>";
			$data .= $line;
		}
		print $data;
	}

	function get_class_name() {
		return $this->class_name;
	}

	function Add( $prod_id, $quantity, $margin, $bundle_prod_id ) {
		$sql = "INSERT INTO im_bundles (prod_id, quantity, margin, bundle_prod_id) VALUES (" . $prod_id . ", " .
		       $quantity . ", " . $margin . ", " . $bundle_prod_id . ")";

		$export = mysql_query( $sql ) or die ( sql_error( $sql ) );
	}

	function Delete( $bundle_id ) {
		$sql = "DELETE FROM im_bundles WHERE id = " . $bundle_id;

		my_log( "sql = " . $sql );

		$export = mysql_query( $sql ) or die ( sql_error( $sql ) );
	}
}

class Bundle {
	private $id;

	function Bundle( $_id ) {
		$this->id = $_id;
	}

	function CalculatePrice() {
		$sql = "SELECT quantity, margin, prod_id FROM im_bundles WHERE bundle_prod_id = " . $this->id;

		$export = mysql_query( $sql ) or die ( sql_error( $sql ) );

		$row = mysql_fetch_row( $export );

		$quantity = $row[0];
		$margin   = $row[1];
		$prod_id  = $row[2];

		return get_buy_price( $prod_id ) * $quantity * ( 100 + $margin ) / 100;
	}

	function GetBuyPrice() {
		$sql = "SELECT quantity, prod_id FROM im_bundles WHERE bundle_prod_id = " . $this->id;
		// print $sql;

		$export = mysql_query( $sql ) or die ( sql_error( $sql ) );

		$row = mysql_fetch_row( $export );

		$quantity = $row[0];
		$prod_id  = $row[1];
		//     print "prod_id = " . $prod_id;

		//   print "buy: " . get_buy_price($prod_id);
		return $quantity * get_buy_price( $prod_id );
	}

	function GetSupplier() {
		$sql = "SELECT prod_id FROM im_bundles WHERE bundle_prod_id = " . $this->id;

		$export = mysql_query( $sql ) or die ( sql_error( $sql ) );

		$row = mysql_fetch_row( $export );

		return get_supplier( $row[0] );
	}
}

// $p = new Bundle(3099);
// print "<br />supp: " . $p->GetSupplier();
// print "<br />Buy price:" . $p->GetBuyPrice();
// print "<br />price: " . $p->CalculatePrice(); 