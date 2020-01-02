<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:08
 */

require_once(FRESH_INCLUDES . '/fresh/delivery/delivery.php' );

// $key, $data, $args
function gui_select_document_type( $id = null, $selected = null, $args = null ) {
$DocumentTypeNames = array(
	"",
	"הזמנה",
	"משלוח",
	"זיכוי",
	"חשבונית מס קבלה",
	"אספקה",
	"תעודת משלוח",
	"העברה",
	"חשבונית מס זיכוי"
);

	$events = GetArg($args, "events", null);
	$types = array();
	for ( $i = 1; $i < FreshDocumentType::count; $i ++ ) {
		$value["id"]   = $i;
		$value["name"] = $DocumentTypeNames[ $i ];
		array_push( $types, $value );
	}

	return gui_select( $id, "name", $types, $events, $selected, "id" );
}
