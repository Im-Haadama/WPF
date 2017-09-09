<?php
require_once( '../im_tools.php' );
require_once( '../pricing.php' );
?>
<html dir="rtl">
<header>
</header>
<?php

$basket_id = $_GET["basket_id"];

if ( $basket_id > 0 ) {
	print_basket( $basket_id );
} else {
	print header_text( true );
}

function print_basket( $basket_id ) {
	$sql = 'SELECT DISTINCT product_id, quantity FROM im_baskets WHERE basket_id = ' . $basket_id;

	$result = sql_query( $sql );

	$basket_content = "";

	print "פרטי סל " . get_product_name( $basket_id );

	$data            = "<table><tr><td><h3>שם הפריט</h3></td><td><h3>כמות</h3></td><td><h3>עלות קניה</h3></td><td><h3>מכירה</h3></td><td><h3>ספק</h3></td></tr>";
	$total_pricelist = 0;
	$total_price     = 0;
	$line_idx        = 1;
	while ( $row = mysqli_fetch_row( $result ) ) {
		$prod_id  = $row[0];
		$quantity = $row[1];

		$line = "<tr>";
		$line .= "<td>" . $line_idx . ") " . get_product_name( $prod_id ) . "</td>";
		$line_idx ++;
		$line            .= "<td>" . $quantity . "</td>";
		$line            .= "<td>" . get_buy_price( $prod_id ) . "</td>";
		$total_pricelist += $quantity * get_buy_price( $prod_id );
		$line            .= "<td>" . get_price( $prod_id ) . "</td>";
		$total_price     += $quantity * get_price( $prod_id );
		$line            .= "<td>" . "</td>";
		$line            .= "</tr>";
		$basket_content  .= get_product_name( $prod_id ) . ", ";

		$data .= $line;
	}

	$line = "<tr>";
	$line .= "<td></td>";
	$line .= "<td></td>";
	$line .= "<td>" . $total_pricelist . "</td>";
	$line .= "<td>" . $total_price . "</td>";
	$line .= "<td>" . "</td>";
	$line .= "</tr>";

	$data .= $line;

	$data .= "</table>";

	print $data;

	print $basket_content;
}

?>
</html>