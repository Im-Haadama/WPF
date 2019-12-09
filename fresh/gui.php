<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/08/17
 * Time: 05:08
 */

require_once(ROOT_DIR . '/fresh/delivery/delivery.php' );


// $key, $data, $args
function gui_select_document_type( $id = null, $selected = null, $args = null ) {
	global $DocumentTypeNames;

	$events = GetArg($args, "events", null);
	$types = array();
	for ( $i = 1; $i < ImDocumentType::count; $i ++ ) {
		$value["id"]   = $i;
		$value["name"] = $DocumentTypeNames[ $i ];
		array_push( $types, $value );
	}

	return gui_select( $id, "name", $types, $events, $selected, "id" );
}
