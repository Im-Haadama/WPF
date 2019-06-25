<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/01/19
 * Time: 08:21
 */

function parse_header(
	$header_line, &$item_code_idx, &$name_idx, &$price_idx, &$sale_idx, &$inventory_idx, &$detail_idx,
	&$category_idx, $is_active_idx, &$filter_idx, &$picture_idx, &$quantity_idx
) {
	$price_idx = Array();
	$name_idx  = Array();

//	 print "header size: " . count($header) ."<br/>";

	$header = str_getcsv( $header_line );
	for ( $i = 0; $i < count( $header ); $i ++ ) {
		$key = trim( $header[ $i ] );
		// print "key=" . $key . " ";

//		 print $key . " " . strlen($key) . " " . $i . "<br/>";
		switch ( $key ) {
			case 'פריט':
			case 'קוד':
			case 'קוד פריט':
			case 'מסד':
				print "code: " . $i . "<br/>";
				array_push( $item_code_idx, $i );
				break;
			case 'קטגוריות':
				array_push( $category_idx, $i );
				break;
			case 'הירק':
			case 'סוג קמח':
			case 'שם פריט':
			case 'שם':
			case 'תאור פריט':
			case 'תיאור פריט':
			case 'תיאור הפריט':
			case 'תיאור':
			case 'מוצר':
			case 'שם המוצר':
				print "name " . $i . "<br/>";
				array_push( $name_idx, $i );
				break;
			case 'פירוט המוצר':
				array_push( $detail_idx, $i );
				break;
			case 'מחיר':
			case 'מחיר לאחר הנחה':
			case 'מחירון':
			case 'מחיר נטו':
			case 'מחיר לק"ג':
			case 'סיטונאות':
				print "price " . $i . "<br/>";
				array_push( $price_idx, $i );
				break;
			case 'מלאי (ביחידות)':
				$inventory_idx = $i;
				break;
			case 'מחיר מבצע':
				array_push( $sale_idx, $i );
				break;
			case 'כמות':
			case 'כמות יחידות':
				array_push( $quantity_idx, $i );
				break;
			default:
				print $key . " ignored<br/>";

		}
		if ( strstr( $key, "הצגה" ) ) {
			$filter_idx = $i;
		}
		if ( strstr( $key, "מחיר" ) and ! in_array( $i, $price_idx ) ) {
			my_log( "key: $key, price: " . $i, __FILE__ );
			array_push( $price_idx, $i );
		}

		if ( strstr( $key, "תמונה" ) ) {
			$picture_idx = $i;
//		print "picture idx $picture_idx<br/>";
		}
	}

	//if ($name_idx == -1 or $price_idx == -1) die("item code " . $item_code_idx . "name " . $name_idx  . "price " . $price_idx );
	if ( count( $name_idx ) == 0 or count( $price_idx ) == 0 ) {
		// print "name_idx count: " . count( $name_idx );
		// print " price_idx count: " . count( $price_idx ) . " failed<br/>";

		return false;
		// print "Missing headers:<br/>";
		// die(" name " . count($name_idx)  . " price " . count($price_idx));
	}
//	var_dump( $name_idx );
//	var_dump( $price_idx );
	print "<br/>";

//	die("XXXXX");
	return true;
}
