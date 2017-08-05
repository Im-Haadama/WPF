<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/06/17
 * Time: 18:29
 */
require_once( "pricelist-process.php" );

print header_text();

$source = "https://docs.google.com/spreadsheets/d/1G__NAu2aEOHhGix-9dFxPTE7eWI9AbmltuqkbDD9rbI/pub?gid=0&single=true&output=csv";
$sql    = "SELECT id FROM im_suppliers WHERE supplier_name = 'יוסף'";
$sid    = sql_query_single_scalar( $sql );

pricelist_process( $source, $sid, false );