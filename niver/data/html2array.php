<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/02/19
 * Time: 17:58
 */

function html2array( $text ) {
	$dom   = im_str_get_html( $text );
	$array = array();

	foreach ( $dom->find( 'tr' ) as $row ) {
		$new_row = array();
		foreach ( $row->find( 'td' ) as $cell ) {
			array_push( $new_row, $cell->plaintext );
		}
		array_push( $array, $new_row );
	}

	return $array;
}
