<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/04/17
 * Time: 10:31
 */
require_once( "pricelist-process.php" );
// print header_text();

$supplier_name = $_GET["supplier_name"];

if ( isset( $_GET["file"] ) ) {
	// inbox manager activation
	$file = "http://tabula.aglamaz.com/attachments/" . $_GET["file"];
	print "processing " . $supplier_name . "<br/>";
	pricelist_process_name( $file, $supplier_name, false );
	exit( 0 );
}

$sql    = "SELECT source_path FROM im_suppliers WHERE eng_name = '" . $supplier_name . "'";
$source = sql_query_single_scalar( $sql );
if ( strlen( $source ) < 5 ) {
	print "no source or filename";
	die( 1 );
}
// $source = "https://docs.google.com/spreadsheets/d/1G__NAu2aEOHhGix-9dFxPTE7eWI9AbmltuqkbDD9rbI/pub?gid=0&single=true&output=csv";

$sql = "SELECT id FROM im_suppliers WHERE eng_name = '$supplier_name'";
$sid = sql_query_single_scalar( $sql );

pricelist_process( $source, $sid, false );