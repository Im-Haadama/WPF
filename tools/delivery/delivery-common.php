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
		line_select = 0,
		product_name = 1,
		q_quantity_ordered = 2, // Only display
		q_units_ordered = 3,
		q_supply = 4,
		price = 5,
		has_vat = 6,
		line_vat = 7,
		line_total = 8,
		term = 9,
		q_refund = 10,
		refund_total = 11,
		max_fields = 12;
}

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
