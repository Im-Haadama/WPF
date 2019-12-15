<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/03/17
 * Time: 22:48
 * purpose: export all published products as pricelist for external store
 */
require_once( '../r-shop_manager.php' );
require_once( FRESH_INCLUDES . '/niver/gui/inputs.php' );

$sql = "SELECT post_title, id, post_modified FROM im_products";

$result = sql_query( $sql );

$data .= "<tr>";
$data .= "<td>בחר</td>";
$data .= "<td>קוד מוצר</td>";
$data .= "<td>שם מוצר</td>";
$data .= "<td>תאריך שינוי</td>";
$data .= "<td>מחיר קנייה</td>";
$data .= "<td>מחיר מחושב</td>";
$data .= "<td>פריט מקושר</td>";
$data .= "<td>מחיר מכירה</td>";
$data .= "<td>מחיר מבצע</td>";
$data .= "<td>מזהה</td>";
$data .= "</tr>";

// Add new item fields
$data .= "<tr>";
$data .= "<td>" . gui_button( "add", "add_item()", "הוסף" ) . "</td>";
$data .= gui_cell( gui_input( "product_name", "", "" ) );
$data .= gui_cell( gui_input( "price", "", "" ) );
$data .= "</tr>";

while ( $row = mysqli_fetch_row( $result ) ) {
	$product_name = $row[0];
	$prod_id      = $row[1];
	$date         = $row[2];

	$price = get_price( $prod_id );

	$line = "<tr>";
	$line .= "<td><input id=\"chk" . $pl_id . "\" class=\"product_checkbox\" type=\"checkbox\"></td>";
	$line .= "<td>" . $prod_id . "</td>";
	$line .= "<td>" . $product_name . "</td>";
	$line .= "<td>" . $date . "</td>";
	$line .= "<td>" . $price . "</td>";
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
	$line .= "</tr>";
	$data .= $line;
}
print $data;
