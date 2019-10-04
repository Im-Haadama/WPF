<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/03/17
 * Time: 22:48
 * purpose: export all published products as pricelist for external store
 */

require_once( '../r-multisite.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( '../pricing.php' );
require_once( "../wp/terms.php" );

if ( isset( $_GET["incremental"] ) ) {
	if ( ! isset( $_GET["site_id"] ) ) {
		print "must send site_id";
		die( 1 );
	}
	$site_id     = $_GET["site_id"];
	$incremental = true;
} else {
	$incremental = false;
}

$sql = "SELECT post_title, id, post_modified FROM im_products";
if ( $incremental ) {
	$max      = sql_query_single_scalar( "SELECT max(post_modified) FROM wp_posts" );
	$date_q   = "SELECT last_inc_update FROM im_multisite WHERE id = " . $site_id;
	$prev_max = sql_query_single_scalar( $date_q );
	print "last date: " . $prev_max . "<br/>";

	if ( $prev_max ) {
		$sql .= " where post_modified > '" . $prev_max . "'";
	}
}

print header_text();

$result = sql_query( $sql );

if ( ! $result ) {
	sql_error( $sql );
	die ( 1 );
}
$data = "";

print $sql;
$line_count = 0;
print "<table>";
while ( $row = mysqli_fetch_row( $result ) ) {
	$line_count ++;
	$product_name  = $row[0];
	$prod_id       = $row[1];
	$date          = $row[2];
	$attachment_id = get_post_thumbnail_id( $prod_id );
	$picture_path  = wp_get_attachment_url( $attachment_id );

	$regular_price = get_regular_price( $prod_id );
	$sale_price    = get_sale_price( $prod_id );

	$vars = get_product_variations( $prod_id );

	$terms = terms_get_as_string( $prod_id );

	$line = "<tr>";
	$line .= "<td>prod</td>";
	$line .= "<td>" . $prod_id . "</td>";
	$line .= "<td>" . $product_name . "</td>";
	$line .= "<td>" . $date . "</td>";
	$line .= "<td>" . $regular_price . "</td>";
	$line .= "<td>" . $sale_price . "</td>";
	$line .= gui_cell( count( $vars ) );
	$line .= "<td>" . $picture_path . "</td>";
	$line .= "<td>" . $terms . "</td>";
	$line .= "</tr>";
	$data .= $line;

	if ( $vars ) {
		foreach ( $vars as $var ) {
			$line = "<tr>";
			$line .= "<td>var</td>";
			$line .= "<td>" . $var . "</td>";
			$line .= "<td>" . get_product_name( $var ) . "</td>";
			$line .= "<td>" . $date . "</td>";
			$line .= "<td>" . get_regular_price( $var ) . "</td>";
			$line .= "<td>" . get_sale_price( $var ) . "</td>";
			$line .= "<td>" . $prod_id . "</td>";
			$line .= "</tr>";
			$data .= $line;
		}
	}
}

if ( $incremental ) {
	sql_query( "UPDATE im_multisite SET last_inc_update = NOW() WHERE id = " . $site_id );
	if ( $line_count == 0 ) {
		print "no new lines since $prev_max<br/>";
	}
}

print $data;
print "</table>";
print "</table>";

// $line .= '<td><input type="text" value="' . $price . '"</td>';
// $line .= '<td>' . $calc_price . '</td>';
//    if ($prod_id > 0) {
//        $line .= '<td>' . get_product_name($prod_id) .'</td>';
//        $line .= '<td>' . get_price($prod_id) . '</td>';
//        $line .= '<td>' . get_sale_price($prod_id) . '</td>';
//    } else {
//        if ($prod_id == -1)
//            $line .= "<td>לא למכירה</td><td></td><td></td>";
//        else
//            $line .= "<td></td><td></td><td></td>";
//    }
//    $line .= gui_cell($prod_id);

// http://super-organi.co.il/fresh/catalog/get-as-pricelist.php?incremental&site_id=1&api_key=89e12f8f-682c-4280-8657-5dae8ba46ecb