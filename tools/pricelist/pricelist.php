<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/12/15
 * Time: 10:16
 */
require_once( '../im_tools.php' );
require_once( '../catalog/catalog.php' );
require_once( '../gui/inputs.php' );
require_once( '../multi-site/multi-site.php' );

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

class PriceList {
	private $SupplierID = 0;

	function PriceList( $id ) {
		$this->SupplierID = $id;
	}

	function SiteId() {
		return sql_query_single_scalar( "SELECT site_id FROM im_suppliers WHERE id =" . $this->SupplierID );
	}

	function PrintCSV() {
		global $conn;

		$sql = 'SELECT product_name, price, supplier_product_code' .
		       ' FROM im_supplier_price_list pl '
		       . ' where supplier_id = ' . $this->SupplierID;

		$result = mysqli_query( $conn, $sql );

		print "שם, מחיר, קוד\n";

		while ( $row = mysqli_fetch_row( $result ) ) {
			print $row[0] . ", " . $row[1] . ", " . $row[2] . "\n";
		}

	}

	function PrintHTML() {
		$catalog = new Catalog();

		$data = "";

		$sql = 'SELECT product_name, price, date, pl.id, supplier_product_code, s.factor ' .
		       ' FROM im_supplier_price_list pl ' .
		       ' Join im_suppliers s '
		       . ' where supplier_id = ' . $this->SupplierID
		       . ' and s.id = pl.supplier_id '
		       . ' order by 1';

		$result = sql_query( $sql );

		$data .= "<tr>";
		$data .= "<td>בחר</td>";
		$data .= "<td>קוד מוצר</td>";
		$data .= "<td>שם מוצר</td>";
		$data .= "<td>תאריך שינוי</td>";
		$data .= "<td>מחיר קנייה</td>";
		$data .= "<td>מחיר מחושב</td>";
		$data .= "<td>פריט מקושר</td>";
		$data .= "<td>מחיר מכירה</td>";
		$data .= "<td>מחיר מבצע</td>";
		$data .= "<td>מזהה</td>";
		$data .= "</tr>";

		// Add new item fields

		while ( $row = mysqli_fetch_row( $result ) ) {
			$pl_id     = $row[3];
			$link_data = $catalog->GetProdID( $pl_id );
			$prod_id   = $link_data[0];

			$map_id = $link_data[1];
			// print $prod_id . " " . $map_id . "<br/>";
			$data .= print_html_line( $row[0], $row[1], $row[2], $pl_id, $row[4], $row[5], $prod_id, true, $map_id );
			// $data .= $line;
		}

		$data .= "<tr>";
		$data .= "<td>" . gui_button( "add", "add_item()", "הוסף" ) . "</td>";
		$data .= gui_cell( gui_input( "product_name", "" ) );
		$data .= gui_cell( gui_input( "price", "" ) );
		$data .= "</tr>";
		print $data;
	}


	function GetUpdateDate() {
		$sql = 'SELECT max(date) FROM im_supplier_price_list'
		       . ' WHERE supplier_id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		$row = mysqli_fetch_row( $result );

		return $row[0];
	}

	function GetFactor() {
		$sql = 'SELECT factor FROM im_suppliers'
		       . ' WHERE id = ' . $this->SupplierID;

		$result = sql_query( $sql );

		$row = mysqli_fetch_row( $result );

		return $row[0];
	}

	function AddOrUpdate( $regular_price, $sale_price, $product_name, $code = 10, &$id, $parent_id = null ) {
		global $debug;
		my_log( __METHOD__, __FILE__ );
		if ( mb_strlen( $product_name ) > 40 ) {
			$product_name = mb_substr( $product_name, 0, 40 );
		}
		if ( ! is_numeric( $sale_price ) ) {
			$sale_price = 0;
		}
		if ( ! is_numeric( $regular_price ) ) {
			print "Bad price: " . $regular_price;

			return UpdateResult::UsageError;
		}
//        print "Add: " . $product_name . ", " . $price . "<br/>";
		global $conn;

		// Change if line exits.
		$sql = "select id, price " .
		       " from im_supplier_price_list " .
		       " where supplier_id = " . $this->SupplierID .
		       " and product_name = '" . $product_name . "'";

		$date = date( 'y/m/d' );

		$id     = 0;
		$result = mysqli_query( $conn, $sql );

		if ( $result ) {
			$row = mysqli_fetch_row( $result );
			$id  = $row[0];
		}
		if ( $id == - 1 ) { // Hide
			return UpdateResult::NotUsed;
		}
		if ( $id > 0 ) {
			if ( $debug ) {
				print "Exists, update.. ";
			}
			$old_price = $this->Get( $id )["price"];
			$sql       = "update im_supplier_price_list " .
			             " set line_status = 1, price = " . $regular_price . ", sale_price = " . $sale_price .
			             ", date = '" . $date . "' " .
			             " where product_name = '" . $product_name . "' and supplier_id = " . $this->SupplierID;

			// print "<br/>"  . $product_name . "<br/>";
			// print "<p dir='ltr'>"  . $sql . "</p>";

			$result = mysqli_query( $conn, $sql );
			if ( ! $result ) {
				sql_error( $sql );

				return UpdateResult::SQLError;
			}

			if ( $regular_price > $old_price ) {
				$rc = UpdateResult::UpPrice;
			} else if ( $regular_price < $old_price ) {
				$rc = UpdateResult::DownPrice;
			} else {
				$rc = UpdateResult::NoChangPrice;
			}

			// Update linked products
			$prod_ids = Catalog::GetProdID( $id );
			$line     = "";
			if ( $prod_ids ) {
				foreach ( $prod_ids as $prod_id ) {
					Catalog::UpdateProduct( $prod_id, $line );
					my_log( $line );
				}
			}

		} else {
			if ( $code == "" ) {
				$code = "10";
			}

			// New in price list
			if ( $parent_id > 0 ) {
				// Variation
				$sql = "INSERT INTO im_supplier_price_list (product_name, supplier_id, "
				       . "date, price, sale_price, supplier_product_code, line_status, variation, parent_id ) " .
				       "VALUES ('" . addslashes( $product_name ) . "', " . $this->SupplierID .
				       ", " . "'" . $date . "', " . $regular_price . ", " . $sale_price . ", " . $code . ", 1, TRUE, " . $parent_id . ")";
			} else {
				// Product
				$sql = "INSERT INTO im_supplier_price_list (product_name, supplier_id, "
				       . "date, price, sale_price, supplier_product_code, line_status, variation ) " .
				       "VALUES ('" . addslashes( $product_name ) . "', " . $this->SupplierID .
				       ", " . "'" . $date . "', " . $regular_price . ", " . $sale_price . ", " . $code . ", 1, FALSE )";
			}

//	        print "<p dir=ltr>" . $sql . "</p>";
			$result = mysqli_query( $conn, $sql );
			if ( ! $result ) {
				sql_error( $sql );

				return UpdateResult::SQLError;
			}
			// Output
			$id = mysqli_insert_id( $conn );
			$rc = UpdateResult::NewPrice;
		}

		return $rc;
	}

//	function Delete($pricelist_id)
//	{
//		global $conn;
//		my_log(__METHOD__ . $pricelist_id);
//
//		// Get product id for drafting
//		$sql = "select product_id from im_supplier_mapping " .
//		       " where pricelist_id = " . $pricelist_id;
//
//		$result = mysqli_query($conn, $sql);
//		$row = mysqli_fetch_assoc($result);
//
//		$prod_id = $row["product_id"];
//		my_log("prod_id = " . $prod_id);
//
//		// If not found, try with name
//		if (! ($prod_id > 0)) {
//			$sql = "select supplier_id, product_name from im_supplier_price_list " .
//			       " where id = " . $pricelist_id;
//			my_log($sql);
//			$result = mysqli_query($conn, $sql);
//			$row = mysqli_fetch_assoc($result);
//
//			$supplier_id = $row["supplier_id"];
//			$supplier_product_name = $row["product_name"];
//			my_log("supplier_id = " . $supplier_id);
//			my_log("supplier_product_name = " . $supplier_product_name);
//
//			$sql = "select product_id from im_supplier_mapping " .
//			       " where supplier_id = " . $supplier_id .
//			       " and supplier_product_name = '" . $supplier_product_name . "'";
//
//			my_log($sql);
//			$result = mysqli_query($conn, $sql);
//			if ($result) {
//				$row = mysqli_fetch_assoc($result);
//				$prod_id = $row["product_id"];
//				my_log("prod_id = " . $prod_id);
//			}
//		}
//
//		my_log("Delete. id = " . $pricelist_id);
//		my_log("catalog_delete_price", "pricelist-post.php");
//		$sql = "delete from im_supplier_price_list  "
//		       . " where id = " . $pricelist_id;
//
//		mysqli_query($conn, $sql);
//
//		// The mapping stays - in case supplier gets it back.
//
//		// If no other option for this product - make it draft
//		if ($prod_id > 0) {
//			$sql = "select count(*) as c " .
//			       " from im_supplier_price_list " .
//			       " where id in (select pricelist_id " .
//			       " from im_supplier_mapping " .
//			       " where product_id = " . $prod_id . ")";
//
//			my_log($sql);
//			$result = mysqli_query($conn, $sql);
//
//			$count = 0;
//			if ($result) {
//				$row = mysqli_fetch_assoc($result);
//
//				$count = $row["c"];
//				//            my_log(__METHOD__ . " count = " . $count);
//			}
//			if ($count == 0)
//			{
//				my_log("No more source for " . $prod_id);
//				$prods = array($prod_id);
//				//            my_log("count = " . count($prods));
//
//				catalog::DraftItems($prods);
//			}
//		}
//		// $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=delete&params=" . $pricelist_id);
//
//		return;
//	}

	static function Get( $pricelist_id ) {
		// my_log("Pricelist::Get" . $pricelist_id);
		$sql = " SELECT product_name, supplier_id, date, price, supplier_product_code, sale_price FROM im_supplier_price_list " .
		       " WHERE id = " . $pricelist_id;

		$result = sql_query_single_assoc( $sql );

		// my_log("result");
		return $result;
	}

//    function Clean()
//    {
//        my_log(__CLASS__, "pricelist-post.php");
//        global $conn;
//
//        $table_name = "temp_supplier_" . $this->SupplierID;
//
//        $sql = "drop table " . $table_name . "; create table " . $table_name .
//            " as select * from im_supplier_price_list  "
//            . " where supplier_id = " . $this->SupplierID;
//
//        mysqli_query($conn, $sql);
//
//        $sql = "delete from im_supplier_price_list  "
//            . " where supplier_id = " . $this->SupplierID;
//
//        my_log($sql, __CLASS__);
//        if (! $export)
//            die ('Invalid query: ' . mysql_error());
//
//        return;
//    }

//    function AddInSlave($price, $product_name, $line_id)
//    {
//        global $conn;
//
//        $date = date('y/m/d');
//
//        $sql = "insert into im_supplier_price_list (id, product_name, supplier_id, date, price,  line_status) " .
//            " Values(". $line_id. ", '" . $product_name . "', " . $this->SupplierID . ", " . $date . ", " . $price . ", 1)";
//
//        // print $sql . "<br/>";
//        if (! mysqli_query($conn, $sql)){
//            sql_error($sql);
//            die(1);
//        };
//    }

//    function UpdateInSlave($price, $line_id)
//    {
//        global $conn;
//        $date = date('y/m/d');
//
//        $sql = "update im_supplier_price_list set date = " . $date .
//            ", price=" . $price . " where id=" . $line_id;
//
//        mysqli_query($conn, $sql);
//
//    }

	// Return code: 0 - usage error: error. 1:
	// ID: output the pricelist id

	function Update( $line_id, $price ) {
		global $conn;
		$sql = "UPDATE im_supplier_price_list SET price = " . $price .
		       " WHERE id = " . $line_id;
		mysqli_query( $conn, $sql );

//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=update_in_slave&price=" . $price .           "&line_id=" . $line_id);

		//        $date = date('y/m/d');
//        my_log(__FILE__, __METHOD__);
//        my_log(__FILE__, "supplier: " . $this->SupplierID . ", price = " . $price . ", product name = " . $product_name_code);
//        if (! is_numeric($this->SupplierID)) {
//            die("bad supplier id: " . $this->SupplierID);
//        }
//        if (! is_numeric($price)) {
//            die ("Bad price: " . $price );
//        }
//        $link = $GLOBALS["glink"];
//        if (is_numeric($product_name_code) and $product_name_code != 10) {
//            $sql = "update im_supplier_price_list set date = '" . $date . "', price = " . $price
//                . " where supplier_product_code = '" . $product_name_code . "' and supplier_id = " . $this->SupplierID;
//        } else {
//            $sql = "update im_supplier_price_list set date = '" . $date . "', price = " . $price
//                . " where product_name = '" . addslashes($product_name_code) . "' and supplier_id = " . $this->SupplierID;
//        }
//
//        my_log($sql, "catalog_update_price");
//        if (! $export)
//            die ('Invalid query: ' . mysql_error());
		return;
	}

	function GetByName( $product_name ) {
		$product_name = pricelist_strip_product_name( $product_name );

		$sql = "SELECT price FROM im_supplier_price_list "
		       . " WHERE product_name = '" . addslashes( $product_name ) . "' AND supplier_id = " . $this->SupplierID;

		return sql_query_single_scalar( $sql );
	}

	function ChangeStatus( $status ) {
		// Act local
		global $conn;

		$sql = "UPDATE im_supplier_price_list SET line_status = " . $status . " WHERE supplier_id = " . $this->SupplierID;

//        print $sql;

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			handle_sql_error( $sql );
		}

		// Act remote
//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=change_status" . "&status=" . $status);
	}

//    function DraftRemoved()
//    {
//        global $conn;
//
//        $sql = "select id from ihstore.im_supplier_price_list where " .
//            " supplier_id = " . $this->SupplierID . " and line_status = 2";
//        $result = mysqli_result($sql);
//
//        if (!$result){
//            handle_sql_error($sql, $conn);
//        }
////        $table_name = "temp_supplier_" . $this->SupplierID;
////
////        $sql = "SELECT a.id " .
////            " FROM " . $table_name . " a " .
////            " LEFT JOIN im_supplier_price_list b " .
////            " ON a.product_name = b.product_name AND b.supplier_id = " . $this->SupplierID .
////            " WHERE b.id IS NULL ";
////
//////        print $sql . "<br/>";
////        $result = mysqli_query($conn, $sql);
////        if (! $result) die ($sql . mysqli_error($conn));
////
////        while ($row = mysqli_fetch_row($result))
////        {
////            print $row[0];
////        }
//    }

	function RemoveLines( $status ) {
		$removed = Array();

		// print "Removing previous items...<br/>";
		global $conn;
		$sql = "select id, price, product_name " .
		       " from im_supplier_price_list " .
		       " where line_status = " . $status .
		       " and supplier_id = " . $this->SupplierID;

		$result = mysqli_query( $conn, $sql );

		if ( ! $result ) {
			handle_sql_error( $sql );
		}

		while ( $row = mysqli_fetch_row( $result ) ) {
			$id = $row[0];
			// print "removing " . $id . "<br/>";
			my_log( "Remove " . $id );
			$this->Delete( $id );
			$removed[] = array( $row[2], $row[1] );
			// var_dump($ids);
		}
//        $this->ExecuteRemotes("pricelist/pricelist-post.php?operation=delete_price&params=" . implode(",", $ids));

//        print "Done<br/>";
		// var_dump($removed);
		return $removed;
	}

//    function ExecuteRemotes($url)
//    {
//        // print "ExecuteRemotes: " . $url . "<br/>";
//        // my_log("Execute " . $url);
//        global $conn;
//
//        $sql = "select remote_site_id, remote_supplier_id from im_multisite_pricelist " .
//            " where supplier_id = " . $this->SupplierID;
//
//        my_log($sql);
//
//        $result = mysqli_query($conn, $sql);
//
//        if ($result)
//        while ($row = mysqli_fetch_row($result)) {
//            $url .= "&supplier_id=" . $row[1];
//            my_log($url, $row[0]);
//            MultiSite::Execute($url, $row[0]);
//        }
//    }

	function Delete( $pricelist_id ) {
		global $conn;
		my_log( __METHOD__ . $pricelist_id );

		// Check if this product linked.
		$prod_info = catalog::GetProdID( $pricelist_id );
		if ( $prod_info ) {
			$prod_id = $prod_info[0];
		} else {
			$prod_id = 0;
		}
		my_log( "Delete $pricelist_id $prod_id" );

		my_log( "Delete. id = " . $pricelist_id );
		my_log( "catalog_delete_price", "pricelist-post.php" );
		$sql = "DELETE FROM im_supplier_price_list  "
		       . " WHERE id = " . $pricelist_id;

		mysqli_query( $conn, $sql );

		// The mapping stays - in case supplier gets it back.

		// If no other option for this product - make it draft
		if ( $prod_id > 0 ) {
			$line = "";
			Catalog::UpdateProduct( $prod_id, $line );
		}
	}
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


function print_html_line( $product_name, $price, $date, $pl_id, $supplier_product_code, $factor, $linked_prod_id, $editable = true, $map_id ) {
	$calc_price = round( $price * ( 100 + $factor ) / 100, 1 );

	$line = "<tr>";
	$line .= "<td><input id=\"chk" . $pl_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
	$line .= "<td>" . $supplier_product_code . "</td>";
	$line .= "<td>" . $product_name . "</td>";
	$line .= "<td>" . $date . "</td>";

	$line .= "<td>";
	if ( $editable ) {
		$line .= gui_input( $pl_id, $price, array( 'onchange="changed(this)"' ) );
	} else {
		$line .= $price;
	}
	$line .= "</td>";
	// $line .= '<td><input type="text" value="' . $price . '"</td>';
	$line .= '<td>' . $calc_price . '</td>';
	if ( $linked_prod_id > 0 ) {
		$line .= '<td>' . get_product_name( $linked_prod_id ) . '</td>';
		$line .= '<td>' . get_price( $linked_prod_id ) . '</td>';
		$line .= '<td>' . get_sale_price( $linked_prod_id ) . '</td>';
	} else {
		if ( $linked_prod_id == - 1 ) {
			$line .= "<td>לא למכירה</td><td></td><td></td>";
		} else {
			$line .= "<td></td><td></td><td></td>";
		}
	}
	$line .= gui_cell( $linked_prod_id );
	$line .= gui_cell( $map_id );
	$line .= "</tr>";

	return $line;
}

function pricelist_strip_product_name( $name ) {
	// trim sadot product name starting with * or **
	$name = str_replace( array( '.', ',', '*', '\'' ), '', $name );
	$name = str_replace( array( ')', '(', '-' ), ' ', $name );

	return $name;
}

?>