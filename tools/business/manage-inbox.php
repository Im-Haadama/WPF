<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/01/19
 * Time: 07:46
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( "ROOT_DIR" ) ) {
	define( 'ROOT_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( ROOT_DIR . '/niver/data/attachment_folder.php' );
require_once( ROOT_DIR . '/tools/im_tools.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( ROOT_DIR . '/tools/suppliers/Supplier.php' );

$filename = ROOT_DIR . '/tools/mail-config.php';

if ( ! file_exists( $filename ) ) {
	print "config file " . $filename . " not found<br/>";
	die ( 1 );
}
require_once( $filename );

$table = inbox_files( $hostname, $mail_user, $password, $attach_folder, $folder_url );

$by_supplier_table = array();

$header = array( "נושא", "מסמך" );

// -1 - not related to supplier

// print gui_table($table);
foreach ( $table as $row ) {
	// group by supplier
	$sender   = $row[1];
	$supplier = Supplier::getByInvoiceSender( $sender );

	if ( $supplier ) {
		$supplier_id = $supplier->getId();
	} else {
		$supplier_id = - 1;
	} // Not found

	if ( ! isset( $by_supplier_table[ $supplier_id ] ) ) {
		$by_supplier_table[ $supplier_id ] = array();
		array_push( $by_supplier_table[ $supplier_id ], $header );
	}
	$line = array( $row[0], $row[2] );
	if ( $supplier_id == - 1 ) {
		array_push( $line, $row[1] );
	}
	array_push( $by_supplier_table[ $supplier_id ], $line );
}

foreach ( $by_supplier_table as $supplier_id => $table ) {
	// print "sid= " . $supplier_id . "<br/>";
	if ( $supplier_id > - 1 ) {
		print gui_header( 1, get_supplier_name( $supplier_id ) );
	} else {
		print gui_header( 1, "לא משויכים" );
	}

	print gui_table( $by_supplier_table[ $supplier_id ] );
}

