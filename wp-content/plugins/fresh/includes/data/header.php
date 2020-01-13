<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 29/01/19
 * Time: 08:21
 *
 * @param $header_line
 * @param $item_code_idx
 * @param $name_idx
 * @param $price_idx
 * @param $sale_idx
 * @param $inventory_idx
 * @param $detail_idx
 * @param $category_idx
 * @param $is_active_idx
 * @param $filter_idx
 * @param $picture_idx
 * @param $quantity_idx
 *
 * @return bool
 */

function parse_header(
	$header_line, &$item_code_idx, &$name_idx, &$price_idx, &$sale_idx, &$inventory_idx, &$detail_idx,
	&$category_idx, $is_active_idx, &$filter_idx, &$picture_idx, &$quantity_idx
) {
	$header = str_getcsv( $header_line );
	for ( $i = 0; $i < count( $header ); $i ++ ) {
		$key = trim( $header[ $i ] );

		switch ( $key ) {
			case 'פריט':
			case 'קוד':
			case 'קוד פריט':
			case 'מסד':
			case 'code':
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
			case 'name':
				array_push( $name_idx, $i );
				break;
			case 'פירוט המוצר':
				array_push( $detail_idx, $i );
				break;
			case 'price':
			case 'מחיר':
			case 'מחיר לאחר הנחה':
			case 'מחירון':
			case 'מחיר נטו':
			case 'מחיר לק"ג':
			case 'סיטונאות':
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
				// print im_translate("column %s ignored", $key) . "<br/>";

		}
		if ( strstr( $key, "הצגה" ) ) {
			$filter_idx = $i;
		}
		if ( strstr( $key, "מחיר" ) and ! in_array( $i, $price_idx ) ) {
			MyLog( "key: $key, price: " . $i, __FILE__ );
			array_push( $price_idx, $i );
		}

		if ( strstr( $key, "תמונה" ) ) {
			$picture_idx = $i;
//		print "picture idx $picture_idx<br/>";
		}
	}

	if ( count( $name_idx ) == 0 or count( $price_idx ) == 0 ) {
		return false;
	}
	print "<br/>";
	return true;
}
