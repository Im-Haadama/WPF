<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/10/15
 * Time: 17:36
 */

require_once( '../r-shop_manager.php' );
require_once( "../catalog/catalog.php" );

$operation = GetParam("operation", false);
if ($operation) {
	if (handle_basket_operation($operation)) print "done";
	return;
}

	print header_text( true );
	print load_scripts(array("/core/data/data.js", "/core/gui/client_tools.js"));

?>
<script>
</script> <?

$basket_id = GetParam("basket_id", false, 0);

if ( $basket_id > 0 ) {
	print_basket( $basket_id );
	return;
}

$data .= "</table>";

print $data;

print "<br>";
print "שבוע טוב! " . "<br/>";
print "החלוקה, כרגיל, ביום שלישי." . "<br/>";
print "סלים שפע אורגני לבחירתכם השבוע:" . "<br/>";

print get_product_name( 1118 ) . ": " . get_basket_content( 1118 );
print "<br/>";
print get_product_name( 1085 ) . ": " . get_basket_content( 1085 );
print "<br/>";
print get_product_name( 1121 ) . ": " . get_basket_content( 1121 );

print "<br/>";
print "ניתן לבצע עד שתי החלפות בסל או להרכיב סל משלכם מהשפע שקיים באתר!" . "<br/>";
print "איזור המשלוחים - מישור החוף מראשון לציון ועד חיפה.";
print "<br/>";
print "השתדללו להקדים הזמנותיכם, ולא יאוחר מיום שני בשעה 18 ";
print " באתר - http://store.im-haadama.co.il או בהודעה חוזרת.";

function print_basket( $basket_id )
{
    // old:
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

		$p = new Fresh_Product($prod_id);

		if (! $p->isPublished()) {
		    $line = gui_row(array($line_idx . ")" . $p->getName(), "not available"));
        } else {
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
		}
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