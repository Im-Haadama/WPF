<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 23/10/15
 * Time: 17:36
 */

require_once( '../r-shop_manager.php' );
require_once( "../catalog/catalog.php" );

$operation = get_param("operation", false);
if ($operation) {
	if (handle_basket_operation($operation)) print "done";
	return;
}

	print header_text( true );
	print load_scripts(array("/core/data/data.js", "/core/gui/client_tools.js"));

?>
<script>
    function add_to_basket(basket_id)
    {
        let prod_id = get_value_by_name("new_product");
        execute_url('show_baskets.php?operation=add_to_basket&basket_id=' + basket_id + '&new_product=' + prod_id, location_reload);
    }

    function remove_from_basket(basket_id)
    {
        let param = get_selected("product_checkbox");
        execute_url('show_baskets.php?operation=remove_from_basket&basket_id=' + basket_id + '&products=' + param, location_reload);
    }
</script> <?

$basket_id = get_param("basket_id", false, 0);


function handle_basket_operation($operation)
{
    switch ($operation)
    {
        case "add_to_basket":
            $basket_id = get_param("basket_id", true);
            $new_product = get_param("new_product", true);
            $sql = 'INSERT INTO im_baskets (basket_id, date, product_id, quantity) VALUES (' . $basket_id . ", '" . date( 'Y/m/d' ) . "', " .
                       $new_product . ", " . 1 . ')';
            if (sql_query($sql)) return true;
            break;

        case "remove_from_basket":
            $basket_id = get_param("basket_id", true);
            $products = get_param("products", true);
            $sql = "delete from im_baskets where basket_id = " . $basket_id . " and product_id in ( $products ) ";
            if (sql_query($sql)) print "done";
            break;
    }
}
if ( $basket_id > 0 ) {
	print_basket( $basket_id );
	return;
}
$sql = 'SELECT DISTINCT basket_id FROM im_baskets';

$result = sql_query( $sql );

$data = "<table><tr><td><h3>שם הסל</h3></td><td><h3>עלות קניה</h3></td><td><h3>מכירה</h3></td><td><h3>מחיר בנפרד</h3></td><td><h3>אחוזי הנחה</h3></td></tr>";

while ( $row = mysqli_fetch_row( $result ) ) {
	$basket_id = $row[0];

	$line            = "<tr>";
	$line            .= "<td><a href=\"show_baskets.php?basket_id=" . $basket_id . "\">" . get_product_name( $basket_id ) . "</a></td>";
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
    print gui_header(1, im_translate("basket") . " " . get_product_name($basket_id));
	$sql = 'SELECT DISTINCT product_id, quantity, product_price(product_id) as price, quantity * product_price(product_id) as line_price FROM im_baskets WHERE basket_id = ' . $basket_id .
           " and post_status(product_id) like '%pub%'";

	$args["id_field"] = "product_id";
	$args["selectors"] = array("product_id" => "gui_select_product");
	$args["header_fields"] = array("Product", "Quantity", "Price", "Line total");
	$args["add_checkbox"] = true;
	// $args["sum_fields"] = array("quantity" => array(0, "sum_numbers"));

    $total = 0;
    $basket_content = TableData($sql, $args);
    foreach($basket_content as &$row) {
        if (is_numeric($row["line_price"])) $total += $row["line_price"];
    }

    array_push($basket_content, array("product_id" => im_translate("Total"), "price" => "", "quantity" => "", "line_price" => $total));
    $args["checkbox_class"] = "product_checkbox";

    print gui_table_args($basket_content, "basket_contents", $args);


	print gui_button("remove_product", "remove_from_basket(" . $basket_id . ")", "remove");

	print "<br/>";
	print gui_select_product("new_product");
	print gui_button("add_product", "add_to_basket(" . $basket_id . ")", "add");

	$sql = 'SELECT DISTINCT product_id FROM im_baskets WHERE basket_id = ' . $basket_id .
	       " and post_status(product_id) like '%draft%'";

	// print $sql;
    $result = sql_query_array_scalar($sql);
    if ($result){
	    print gui_header(1, "Not available, and removed:");
        foreach ($result as $prod_id){
            print get_product_name($prod_id) . "<br/>";
            sql_query("delete from im_baskets where product_id = " . $prod_id);
        }
    }
	return;
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

		$p = new Product($prod_id);

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