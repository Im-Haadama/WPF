<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 18/05/17
 * Time: 20:39
 */
require_once( '../im_tools.php' );
require_once( '../header.php' );
require_once( "../gui/inputs.php" );
require_once( "../gui/sql_table.php" );
require_once( "../orders/orders-common.php" );
require_once( '../pricelist/pricelist.php' );
require_once( '../multi-site/multi-site.php' );

print header_text( true );
$table    = array();
$table[0] = array();

// Orders
$sql = "SELECT count(*) AS count, post_status AS status
    FROM wp_posts
      WHERE post_status LIKE 'wc%'
      AND post_status NOT IN ('wc-cancelled', 'wc-completed')
    GROUP BY 2";

$links    = [];
$links[1] = "../../wp-admin/edit.php?post_status=%s&post_type=shop_order";

$col = 0;

$table[0][ $col ] = gui_header( 1, "הזמנות" );
$table[1][ $col ] = table_content( $sql, true, true, $links );
$table[2][ $col ] = gui_hyperlink( "צור הזננות למנויים", "../weekly/create-subs.php" );
$table[3][ $col ] = "";
$table[4][ $col ] = "";

// Supplies
$i = 0;
$col ++;
$table[ $i ++ ][ $col ] = gui_header( 1, "אספקות" );
$table[ $i ++ ][ $col ] = gui_header( 2, "פריטים להזמין" );
$table[ $i ++ ][ $col ] = calculate_total_products();
$table[ $i ++ ][ $col ] = gui_link( "תכנון הספקה", "../orders/get-total-orders.php", "doc_frame" );
$sql                    = "SELECT count(*) AS 'כמות', 
CASE
  WHEN status = 1 THEN \"חדש\"
  WHEN status = 3 THEN \"נשלח\"
END AS 'מצב' FROM im_supplies WHERE status < 5 GROUP BY 2";
$links                  = [];
$links[0]               = "../supplies/supplies-get.php?status=%s";
$table[ $i ++ ][ $col ] = table_content( $sql, true, true, $links );

// Catalog
$i = 0;
$col ++;
$table[ $i ++ ][ $col ] = gui_header( 1, "קטלוג" );
$count                  = count_unmapped();
if ( $count > 0 ) {
	$table[ $i ++ ][ $col ] = gui_header( 2, "מיפויים" );
	$table[ $i ++ ][ $col ] = gui_hyperlink( $count . " פריטים לא ממופים ", "../catalog/catalog-map.php" );
}
$first = true;
foreach ( sql_query_single( "SELECT id FROM im_suppliers WHERE machine_update = TRUE " ) as $supplier_id ) {
	$PL       = new PriceList( $supplier_id );
	$a        = $PL->GetUpdateDate();
	$b        = date( 'Y-m-d' );
	$diff     = date_diff( date_create( $a ), date_create( $b ) );
	$day_diff = $diff->format( '%d' );

	if ( $day_diff > 3 ) {
		if ( $first ) {
			$table[ $i ++ ][ $col ] = gui_header( 2, "מחירונים לא מעודכנים" );
			$first                  = false;
		}
		// print $i . " "  . $col . "<br/>";
		$table[ $i ++ ][ $col ] = get_supplier_name( $supplier_id ) . " " . $a;
	}
}

$table[ $i ++ ][ $col ] = gui_hyperlink( "עדכן קטלוג", "../catalog/catalog-auto-update.php" );
$table[ $i ++ ][ $col ] = gui_hyperlink( "עדכן מכולת", "../pricelist/update-makolet.php" );
$table[ $i ++ ][ $col ] = gui_hyperlink( "הוספת פריטים", "../catalog/add-products.php" );

$i                         = 0;
$table[ $i ++ ][ ++ $col ] = gui_header( 1, "משלוחים" );

if ( MultiSite::LocalSiteID() == 2 ) {
	$table[ $i ++ ][ $col ] = gui_header( 2, "מכולת" );
	$table[ $i ++ ][ $col ] = gui_hyperlink( "מורשת", "../delivery/legacy.php" );
}

$i                         = 0;
$table[ $i ++ ][ ++ $col ] = gui_header( 1, "מלאי" );
$table[ $i ++ ][ $col ]    = gui_hyperlink( "איפוס", "../weekly/start.php" );

$i                         = 0;
$table[ $i ++ ][ ++ $col ] = gui_header( 1, "לקוחות" );
$table[ $i ++ ][ $col ]    = gui_hyperlink( "צור לקוח", "../account/add-account.php" );

for ( $i = 0; $i < sizeof( $table ); $i ++ ) {
	for ( $j = 0; $j < sizeof( $table[0] ); $j ++ ) {
		if ( is_null( $table[ $i ][ $j ] ) ) {
			$table[ $i ][ $j ] = " ";
		}
		// print $i . " " . $j . " " . $table[$i][$j] . "<br/>";
	}
	ksort( $table[ $i ] );
	// print htmlspecialchars(var_dump($table[$i]));
}

print gui_table( $table );


function count_unmapped() {
	global $conn;
	$sql    = "SELECT id FROM im_supplier_price_list";
	$result = mysqli_query( $conn, $sql );
	$count  = 0;

	while ( $row = mysqli_fetch_row( $result ) ) // mysql_fetch_row($export))
	{
		$pricelist_id = $row[0];

		$pricelist = PriceList::Get( $pricelist_id );

		$prod_id = Catalog::GetProdID( $pricelist_id )[0];
		if ( ( $prod_id == - 1 ) or ( $prod_id > 0 ) ) {
			continue;
		}
		$count ++;
	}

	return $count;
}


?>
