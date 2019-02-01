<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/01/19
 * Time: 22:05
 */

require_once( 'Supply.php' );
require_once( "../pricelist/pricelist-process.php" );


$debug = 0;
if ( $debug ) {
	print "Debugging mode<br/>";

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );
}

print "d= " . $debug . "<br/>";


$supplier_name = $_GET["supplier_name"];
$file          = get_param( "file" );

// inbox manager activation
$filename = "http://tabula.aglamaz.com/attachments/" . $_GET["file"];
print "processing " . $supplier_name . " file " . $filename . "<br/>";

$file = fopen( $filename, "r" );

if ( ! $file ) {
	print "Can't open file $filename<br/>";
	print "Exit";
	die ( 1 );
}

if ( $debug ) {
	var_dump( $file );
	print "<br/>";
}

$item_code_idx       = Array();
$name_idx            = Array();
$price_idx           = Array();
$sale_idx            = Array();
$detail_idx          = Array();
$category_idx        = Array();
$quantity_idx        = Array();
$is_active_idx       = null;
$picture_path_idx    = null;
$parse_header_result = false;
$inventory_idx       = 0;

for ( $i = 0; ! $parse_header_result and ( $i < 4 ); $i ++ ) {
	if ( $debug ) {
		print "trying to locate headers $i <br/>";
	}
	$parse_header_result = parse_header( $file, $item_code_idx, $name_idx, $price_idx, $sale_idx, $inventory_idx, $detail_idx,
		$category_idx, $is_active_idx, $picture_path_idx, $quantity_idx );
}

if ( $debug ) {
	print "headers: <br/>";
	print "price : " . var_dump( $price_idx );
	print "<br/>";
	print "name: " . var_dump( $name_idx );
	print "<br/>";
}

$item_count = 0;
$lines      = array();
while ( ( $data = fgetcsv( $file ) ) !== false ) {
	if ( $data ) {
		if ( isset( $is_active_idx ) and ( $data[ $is_active_idx ] == 1 ) ) {
			// print $data[ $name_idx[ 0 ] ] . " not active. Skipped<br/>";
			continue;
		}
		for ( $col = 0; $col < count( $price_idx ); $col ++ ) {
			$name      = $data[ $name_idx[ $col ] ];
			$quantity  = $data[ $quantity_idx[ $col ] ];
			$price     = $data[ $price_idx[ $col ] ];
			$item_code = $data[ $price_idx[ $col ] ];

			$detail = "";
			if ( isset( $detail_idx[ $col ] ) ) {
				$detail = $data[ $detail_idx[ $col ] ];
				$detail = rtrim( $detail, "!" );
			}

			if ( $item_code_idx[ $col ] != - 1 ) {
				$item_code = $data[ $item_code_idx[ $col ] ];
			}

			if ( $price > 0 ) {
				$new = array( $item_code, $quantity, $name, $price );
				array_push( $lines, $new );
			}
		}
	}
}

$comments = "ליקופן";
if ( count( $lines ) ) {
	$supplier_id = get_supplier_id( $supplier_name );
	$Supply      = Supply::CreateSupply( $supplier_id ); // create_supply( $supplier_id );
	if ( ! ( $Supply->getID() > 0 ) ) {
		die ( "שגיאה " );
	}

	if ( $debug ) {
		print "creating supply " . $Supply->getID() . " supplier id: " . $supplier_id . "<br/>";
	}

	foreach ( $lines as $line ) {
		$supplier_product_code = $line[0];
		$quantity              = $line[1];
		$name                  = $line[2];
		$price                 = $line[3];

		$prod_id = sql_query_single_scalar( "select product_id \n" .
		                                    " from im_supplier_mapping\n" .
		                                    " where supplier_product_code = " . $supplier_product_code );

		if ( ! ( $prod_id > 0 ) ) {
			$comments .= " פריט עם קוד " . $supplier_product_code . " לא נמצא. שם " . $name . " כמות " . $quantity . "\n";
			continue;
		}

		if ( $debug ) {
			print "c=" . $supplier_product_code . " q=" . $quantity . " n=" . $name . " pid=" . $prod_id . " on=" . get_product_name( $prod_id ) . " " . $name . "<br/>";
		}
		$Supply->AddLine( $prod_id, $quantity, $price );
		// supply_add_line($id, $prod_id, $quantity, $price);
	}

	print " הספקה " . $Supply->getID() . " נוצרה <br/>";

	$Supply->updateComments( $comments );

	return;
}

print " לא נמצאו פריטים ";