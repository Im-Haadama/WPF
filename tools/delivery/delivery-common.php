<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/08/17
 * Time: 12:38
 */

require_once( "../multi-site/multi-site.php" );

class DeliveryFields {
	const
		/// User interface
		line_select = 0,
		/// Product info
		product_name = 1,
		product_id = 2,
		term = 3,
		// Order info
		order_q = 4, // Only display
		order_q_units = 5,
		delivery_q = 6,
		price = 7,
		order_line = 8,
		// Delivery info
		has_vat = 9,
		line_vat = 10,
		delivery_line = 11,
		// Refund info
		refund_q = 12,
		refund_line = 13,
		max_fields = 14;
}

$delivery_fields_names = array(
	"chk", // 0
	"nam", // 1
	"pid", // 2
	"ter", // 3
	"orq", // 4
	"oru", // 5
	"deq", // 6
	"prc", // 7
	"orl", // 8
	"hvt", // 9
	"lvt", // 10
	"del", // 11
	"req", // 12
	"ret"  // 13
);

$header_fields = array(
	"בחר",
	"פריט",
	"ID",
	"קטגוריה",
	"כמות הוזמן",
	"יחידות הוזמנו",
	"כמות סופק",
	"מחיר",
	"סה\"כ להזמנה",
	"חייב מע\"מ",
	"מע\"מ",
	"סה\"כ",
	"כמות לזיכוי",
	"סה\"כ זיכוי"
);

class ImDocumentType {
	const order = 1,
		delivery = 2,
		refund = 3;
}

class ImDocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3;     // Load and edit

}

function print_fresh_category() {
	$list = "";

	$option = sql_query_single_scalar( "SELECT option_value FROM wp_options WHERE option_name = 'im_discount_categories'" );
	if ( ! $option ) {
		return;
	}

	$fresh_categ = explode( ",", $option );
	foreach ( $fresh_categ as $categ ) {
		$list .= $categ . ",";
		foreach ( get_term_children( $categ, "product_cat" ) as $child_term_id ) {
			$list .= $child_term_id . ", ";
		}
	}
	print rtrim( $list, ", " );
}
