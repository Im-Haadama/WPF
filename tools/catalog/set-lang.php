<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 27/04/18
 * Time: 08:40
 */

$wpml_element_type = apply_filters( 'wpml_element_type', 'post' );

// get the language info of the original post
// https://wpml.org/wpml-hook/wpml_element_language_details/
$get_language_args           = array( 'element_id' => $inserted_post_ids['original'], 'element_type' => 'post' );
$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );

$set_language_args = array(
	'element_id'           => $inserted_post_ids['translation'],
	'element_type'         => $wpml_element_type,
	'trid'                 => $original_post_language_info->trid,
	'language_code'        => 'de',
	'source_language_code' => $original_post_language_info->language_code
);

do_action( 'wpml_set_element_language_details', $set_language_args );
