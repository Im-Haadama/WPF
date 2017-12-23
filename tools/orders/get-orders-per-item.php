<?php
require '../r-shop_manager.php';
require_once( 'orders-common.php' );
require_once( '../gui/inputs.php' );
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

print "<center>פירוט הזמנות לפריט " . get_product_name( $prod_id ) . "</center>";

$data = "";

$data .= "<br>ישירות";

$data .= "<table>";

$data .= "<tr> " . orders_per_item( $prod_id, 1 ) . "</tr>";

// Second display all basket orders
$data .= "</table>";

$data .= "<br>בסלים";

$data .= "<table>";

$sql    = 'SELECT  basket_id, quantity FROM `im_baskets` WHERE `product_id` = ' . $prod_id;
$result = mysqli_query( $conn, $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$data .= "<tr> " . trim( orders_per_item( $row[0], $row[1] ) ) . "</tr>";

	// $data .= "<tr> ". trim( $line ) . "</tr>";
}

$data .= "</table>";

$data .= "<br>במארזים";

$data .= "<table>";

$sql    = 'SELECT  bundle_prod_id, quantity, id FROM im_bundles WHERE prod_id = ' . $prod_id;
$result = mysqli_query( $conn, $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$b    = Bundle::createFromDb( $row[2] );
	$data .= "<tr> " . trim( orders_per_item( $b->GetBundleProdId(), $b->GetQuantity() ) ) . "</tr>";

	// $data .= "<tr> ". trim( $line ) . "</tr>";
}

$data .= "</table>";

$data .= "<br />באספקות";

$data .= "<table>";
$data .= "<tr><td>הספקה</td><td>כמות</td></tr>";

$sql = ' SELECT d.id, quantity, d.supplier FROM im_supplies_lines dl JOIN im_supplies d'
       . ' WHERE dl.supply_id = d.id AND (d.status = 1 OR d.status = 3) AND dl.status = 1 AND dl.product_id = ' . $prod_id;

$result = mysqli_query( $conn, $sql );

while ( $row = mysqli_fetch_row( $result ) ) {
	$supply_id = $row[0];

	$data .= "<tr><td><a href='../supplies/supply-get.php?id=" . $supply_id . "'>" . $supply_id . "</a></td><td>" . $row[1]
	         . "</td>";
	$data .= gui_cell( get_supplier_name( $row[2] ) );
	$data .= "</tr>";

	// $data .= "<tr> ". trim( $line ) . "</tr>";
}

$data .= "</table>";

$data = str_replace( "\r", "", $data );

print "$data";

?>
</html>
