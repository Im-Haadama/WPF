<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/10/15
 * Time: 17:36
 */

require_once( '../im_tools.php' );
require_once( 'show_basket.php' );
require_once( "../catalog/catalog.php" );
?>
<html dir="rtl">
<header>
</header>
<?php

$sql = 'SELECT DISTINCT basket_id FROM im_baskets';

$result = sql_query( $sql );

$data .= "<table><tr><td><h3>שם הסל</h3></td><td><h3>עלות קניה</h3></td><td><h3>מכירה</h3></td><td><h3>מחיר בנפרד</h3></td><td><h3>אחוזי הנחה</h3></td></tr>";

while ( $row = mysqli_fetch_row( $result ) ) {
	$basket_id = $row[0];

	$line            = "<tr>";
	$line            .= "<td><a href=\"show_basket.php?basket_id=" . $basket_id . "\">" . get_product_name( $basket_id ) . "</a></td>";
	$total_listprice = get_total_listprice( $basket_id );
	$line            .= "<td>" . $total_listprice . "</td>";
	$basket_price    = get_price( $basket_id );
	if ( $basket_price > 0 ) {
		$line .= "<td>" . $basket_price . '(' . round( 100 * $basket_price / $total_listprice, 1 ) . "%)</td>";
	} else {
		$line .= "<td></td>";
	}
	$total_sellprice = get_total_sellprice( $basket_id );
	$line            .= "<td>" . $total_sellprice . '(' . round( 100 * $total_sellprice / $total_listprice, 1 ) . "%)</td>";
	if ( $basket_price > 0 ) {
		$line .= "<td>" . round( 100 * ( $total_sellprice - $basket_price ) / $basket_price, 1 ) . "%</td>";
	}
	$line .= "</tr>";

	$data .= $line;

}

function get_total_sellprice( $basket_id ) {
	$total_price = 0;
	$sql         = 'SELECT product_id FROM im_baskets WHERE basket_id = ' . $basket_id;

	$result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		$total_price += get_price( $row[0] );
	}

	return $total_price;
}

function get_total_listprice( $basket_id ) {
	$total_price = 0;
	$sql         = 'SELECT product_id FROM im_baskets WHERE basket_id = ' . $basket_id;

	$result = sql_query( $sql );

	while ( $row = mysqli_fetch_row( $result ) ) {
		// Catalog::GetBuyPrice($row[0]);
		$total_price += pricelist_get_price( $row[0] );
	}

	return $total_price;
}

$data .= "</table>";

print $data;

print "<br>";
print "שבוע טוב! " . "<br/>";
print "החלוקה, כרגיל, ביום שלישי." . "<br/>";
print "סלים שפע אורגני לבחירתכם השבוע:" . "<br/>";

print get_product_name( 36 ) . ": " . get_basket_content( 36 );
print "<br/>";
//print get_product_name( 2201 ) . ": " . get_basket_content( 2201 );
//print "<br/>";
//print get_product_name( 711 ) . ": " . get_basket_content( 711 );

print "<br/>";
print "ניתן לבצע עד שתי החלפות בסל או להרכיב סל משלכם מהשפע שקיים באתר!" . "<br/>";
print "איזור המשלוחים - מישור החוף מראשון לציון ועד חיפה.";
print "<br/>";
print "השתדללו להקדים הזמנותיכם, ולא יאוחר מיום שני בשעה 18 ";
print " באתר - http://store.im-haadama.co.il או בהודעה חוזרת.";

?>
</html>