<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 22/07/17
 * Time: 12:08
 */

require_once( "../r-shop_manager.php" );
require_once( "../pricelist/pricelist.php" );
require_once( "../pricelist/pricelist-process.php" );

$supplier_id = $_GET["supplier_id"];
//print "supplier_id = " . $supplier_id . "<br/>";

$remote_supplier = $_GET["remote_supplier"];
//print "remote_supplier = " . $remote_supplier . "<br/>";

$remote_category_name = $_GET["remote_category_name"];
$local_category_name  = $_GET["local_category_name"];
$params               = $_GET["Params"]; // name and price repeating

print header_text( false );
// print "remote_category_id: " . $remote_category_id . "<br/>";
// print "local_category_id: " . $local_category_id . "<br/>";
// print "params: " . $params . "<br/>";

$PL      = new PriceList( $supplier_id );
$site_id = $PL->SiteId();

$in_create_params     = explode( ",", $params );
$remote_pricelist_ids = array();
$pricelist_ids        = array();

$update_list_results = array();

if ( is_numeric( $site_id ) and ( $site_id != MultiSite::LocalSiteID() ) ) {
	print gui_header( 1, "מוסיף פריטים לרשימת ספק " . $remote_supplier ) . "<br/>";
	$request = "pricelist/pricelist-post.php?operation=add_prices&supplier_id=" . $remote_supplier . "&Params=" . $params;
	// print '<p dir="ltr">' . $request . "</p>";
	// Add to pricelist
	$result = str_replace( "<br/>", "\n", MultiSite::Execute( $request, $site_id ) );
	$lines  = explode( PHP_EOL, $subject );

	$fp = fopen( "php://memory", 'r+' );
	fputs( $fp, $result );
	rewind( $fp );
	$send_create_params = array();
	for ( $i = 0; $i < count( $in_create_params ); $i += 2 ) {
//		$pricelist_id = intval($lines[$i]);
		$line                       = fgets( $fp );
		$remote_pricelist_ids[ $i ] = intval( $line );
//		print "XX". $pricelist_id . "XX<br/>";
		$name = $in_create_params[ $i ];
		if ( strlen( $name ) < 3 ) {
			print "short name<br/>";
			die( 1 );
		}

		//print "name: " . $name . "<br/>";
		array_push( $send_create_params, $name );  // Name

//		print "supplier_id: " . $supplier_id . "<br/>";
		array_push( $send_create_params, $remote_supplier );          // Supplier ID

		// print "pricelist_id: " . $remote_pricelist_ids[$i] . "<br/>";
		array_push( $send_create_params, $remote_pricelist_ids[ $i ] );         // Pricelist ID

		array_push( $send_create_params, 0 );                 // Supplier product code
	}
	fclose( $fp );

	// var_dump($send_create_params);
	$p = implode( ",", $send_create_params );
//	print $p;

	// Create products in remote site
	$request = "catalog/catalog-map-post.php?operation=create_products&category_name=" . urlencode( $remote_category_name ) .
	           "&create_info=" . $p;
//	print '<p dir="ltr">' . $request . "</p>";

	print gui_header( 2, "יוצר פריטים באתר מרוחק" ) . "<br/>";
	// Create remote products.
	print MultiSite::Execute( $request, $site_id );

	// Sync remote products
	print gui_header( 2, "מסנרכן פריטים בין האתרים" ) . "<br/>";
	pricelist_remote_site_process( $supplier_id, $update_list_results, true );
	print "הסתיים" . "<br/>";

} else {
	// Insert to local pricelist
	$PL    = new PriceList( $supplier_id );
	$count = 0;
	for ( $i = 0; $i < count( $in_create_params ); $i += 2 ) {
		$product_name = $in_create_params[ $i ];
		$price        = $in_create_params[ $i + 1 ];

		$PL->AddOrUpdate( $price, '', $product_name, $code = 10, "", $pricelist_ids[ $count ], 0 );
		$count ++;
	}
}

// Process local
$create_params = array();

$count = 0;

print gui_header( 2, "יוצר פריטים באתר מקומי" );
// Create local products + link to remote
$send_create_params = array();
for ( $i = 0; $i < count( $in_create_params ); $i += 2 ) {
	$name = $in_create_params[ $i ];
	array_push( $send_create_params, $name );                 // Name

	array_push( $send_create_params, $supplier_id );          // Supplier ID

	if ( isset( $remote_supplier ) ) {
//		print "pricelist_id: " . $pricelist_ids[$i] . "<br/>";
		array_push( $send_create_params, find_pricelist_id( $update_list_results, $name ) ); // Pricelist ID

		array_push( $send_create_params, $remote_pricelist_ids[ $count ] ); // Supplier product code
	} else {
		array_push( $send_create_params, $pricelist_ids[ $count ] );

		array_push( $send_create_params, 0 ); // Supplier product code
	}
	$count ++;
}

Catalog::CreateProducts( $local_category_name, $send_create_params );

function find_pricelist_id( $results, $name ) {
	// Most of the time it would be in new list.
	for ( $i = 0; $i < count( $results[ UpdateResult::NewPrice ] ); $i ++ ) {
//		print $results[ UpdateResult::NewPrice ][ $i ][0] . "<br/>";
		if ( $results[ UpdateResult::NewPrice ][ $i ][0] == $name ) {
			return $results[ UpdateResult::NewPrice ][ $i ][2];
		}
	}
	for ( $i = 0; $i < count( $results[ UpdateResult::NoChangPrice ] ); $i ++ ) {
		if ( $results[ UpdateResult::NoChangPrice ][ $i ][0] == $name ) {
			return $results[ UpdateResult::NoChangPrice ][ $i ][2];
		}
	}

	return - 1;
}
