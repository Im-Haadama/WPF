<?php
require '../r-shop_manager.php';
require_once( 'orders-common.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
?>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
</head>

<?php
$history = false;

$prod_id = $_GET["prod_id"];
if ( isset( $_GET["history"] ) ) {
	$history = true;
}

print "<center>פירוט הזמנות ומלאי לפריט " . get_product_name( $prod_id ) . "</center>";

$data = "";

$data .= gui_header( 1, "הזמנות פתוחות" );

$data .= "<table>";

$data .= "<tr> " . orders_per_item( $prod_id, 1 ) . "</tr>";

// Second display all basket orders
$data .= "</table>";

$basket = "";

$sql    = 'SELECT  basket_id, quantity FROM `im_baskets` WHERE `product_id` = ' . $prod_id;
$result = mysqli_query( $conn, $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$o = orders_per_item( $row[0], $row[1] );
	if ( strlen( $o ) ) {
		$basket .= "<tr> " . trim( $o ) . "</tr>";
	}
	// $data .= "<tr> ". trim( $line ) . "</tr>";
}

if ( strlen( $basket ) ) {
	$data .= "<br>בסלים";
	$data .= "<table>";
	$data .= $basket;
	$data .= "</table>";
}

$bundle = "";

$sql    = 'SELECT  bundle_prod_id, quantity, id FROM im_bundles WHERE prod_id = ' . $prod_id;
$result = mysqli_query( $conn, $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$b    = Bundle::CreateFromDb( $row[2] );
	$o    = orders_per_item( $b->GetBundleProdId(), $b->GetQuantity() );
	if ( strlen( $o ) )
		$data .= "<tr> " . trim( $o ) . "</tr>";

	// $data .= "<tr> ". trim( $line ) . "</tr>";
}


if ( strlen( $bundle ) ) {
	$data .= "<br>במארזים";
	$data .= "<table>";
	$data .= $bundle;
	$data .= "</table>";
}
$data .= "<br />" . gui_header( 1, "אספקות אחרונות");

$data .= "<table>";
$data .= "<tr><td>הספקה</td><td>כמות</td></tr>";

$sql = ' SELECT d.id, quantity, d.supplier, d.date FROM im_supplies_lines dl JOIN im_supplies d'
       . ' WHERE dl.supply_id = d.id AND (d.status = 1 OR d.status = 3) AND dl.status = 1 AND dl.product_id = ' . $prod_id .
       " order by 1 desc ";

$result = mysqli_query( $conn, $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$supply_id = $row[0];

	$data .= "<tr><td><a href='../supplies/supply-get.php?id=" . $supply_id . "'>" . $supply_id . "</a></td><td>" . $row[1]
	         . "</td>";
	$data .= gui_cell( get_supplier_name( $row[2] ) );
	$data .= gui_cell( $row[3] );
	$data .= "</tr>";

	// $data .= "<tr> ". trim( $line ) . "</tr>";
}

$data .= "</table>";

$data = str_replace( "\r", "", $data );

print "$data";

?>
</html>
