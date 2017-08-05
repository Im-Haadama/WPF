<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 10:31
 */
require_once( "pricelist-process.php" );
print header_text();

$supplier_name = $_GET["supplier_name"];

if ( isset( $_GET["file"] ) ) {
	// inbox manager activation
	$file = "http://tabula.aglamaz.com/imap/attachment-manager/" . $_GET["file"];
	print "processing " . $supplier_name . "<br/>";
}

pricelist_process_name( $file, $supplier_name, false );
