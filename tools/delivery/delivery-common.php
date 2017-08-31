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
		price = 6,
		order_line = 7,
		// Delivery info
		delivery_q = 8,
		has_vat = 9,
		line_vat = 10,
		delivery_line = 11,
		// Refund info
		refund_q = 12,
		refund_line = 13,
		max_fields = 14;
}

$delivery_fields_names = array(
	"chk",
	"nam",
	"pid",
	"ter",
	"orq",
	"oru",
	"prc",
	"orl",
	"deq",
	"hvt",
	"lvt",
	"del",
	"req",
	"ret"
);

$header_fields = array(
	"בחר",
	"פריט",
	"ID",
	"קטגוריה",
	"כמות הוזמן",
	"יחידות הוזמנו",
	"מחיר",
	"סה\"כ להזמנה",
	"כמות סופק",
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
	const create = 1,
		show = 2,
		edit = 3;
}

function print_fresh_category() {
	$list = "";
	if ( MultiSite::LocalSiteID() == 1 ) {
		foreach ( get_term_children( 15, "product_cat" ) as $child_term_id ) {
			$list .= $child_term_id . ", ";
		}
	}
	print rtrim( $list, ", " );
}
