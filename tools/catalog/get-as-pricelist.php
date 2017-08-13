<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/03/17
 * Time: 22:48
 * purpose: export all published products as pricelist for external store
 */
require_once( '../tools.php' );
require_once( '../gui/inputs.php' );

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

	$sql .= " where post_modified > (" . $date_q . ")";
}
print header_text();

$result = mysqli_query( $conn, $sql );

$data = "";

print "<table>";
while ( $row = mysqli_fetch_row( $result ) ) {
	$product_name = $row[0];
	$prod_id      = $row[1];
	$date         = $row[2];

	$regular_price = get_regular_price( $prod_id );
	$sale_price    = get_sale_price( $prod_id );

	$vars = get_product_variations( $prod_id );

	$line = "<tr>";
	$line .= "<td>prod</td>";
	$line .= "<td>" . $prod_id . "</td>";
	$line .= "<td>" . $product_name . "</td>";
	$line .= "<td>" . $date . "</td>";
	$line .= "<td>" . $regular_price . "</td>";
	$line .= "<td>" . $sale_price . "</td>";
	$line .= gui_cell( count( $vars ) );
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
	sql_query_single_scalar( "UPDATE im_multisite SET last_inc_update = NOW() WHERE id = " . $site_id );
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
