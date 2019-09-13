<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 06/05/17
 * Time: 09:12
 */
require_once( '../r-shop_manager.php' );
require_once( '../header.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( '../gui/sql_table.php' );
require_once( '../pricelist/pricelist.php' );
print "<br/>";

print "<h1>מצב המערכת</h1>";

print "<h2>הזמנות</h2>";

$sql = "SELECT count(*) AS count, post_status AS status
    FROM wp_posts
      WHERE post_status LIKE 'wc%'
      AND post_status NOT IN ('wc-cancelled', 'wc-completed')
    GROUP BY 2";

$links    = [];
$links[1] = "../../wp-admin/edit.php?post_status=%s&post_type=shop_order";
print table_content( "table", $sql, true, true, $links );

$count = count_unmapped();
if ( $count > 0 ) {
	print "<h2>מיפויים</h2>";
	print gui_hyperlink( $count . " פריטים לא ממופים ", "../catalog/catalog-map.php" );
}


function count_unmapped() {
	$sql    = "SELECT id FROM im_supplier_price_list";
	$result = sql_query( $sql );
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
