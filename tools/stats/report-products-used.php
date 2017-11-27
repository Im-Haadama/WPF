<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/02/17
 * Time: 07:13
 */
require_once( "../gui/sql_table.php" );
require_once( "../header.php" );
print header_text();

if ( isset( $_GET["date"] ) ) {
	$date = $_GET["date"];
}
if ( isset( $_GET["prod_id"] ) ) {
	$prod_id = $_GET["prod_id"];
}

//$sql = "select product_name, round(sum(quantity),2) " .
//       " from im_delivery_lines " .
//       " where delivery_id IN " .
//       " (select transaction_ref from im_client_accounts " .
//       " where  transaction_method = \"משלוח\") " .
//       " group by 1 " .
//       " order by 2 DESC ";

$sql = "select product_name as 'מוצר', round(sum(quantity),2) as 'סהכ נמכר', prod_id" .
       " from im_delivery_lines " .
       " where prod_id > 0 ";
if ( isset( $prod_id ) ) {
	$sql .= " and prod_id = " . $prod_id;
}


$sql .= " and delivery_id IN " .
        " (select id from im_delivery ";
if ( isset( $date ) ) {
	$sql .= "where date > '" . $date . "'";
}

$sql .= " ) group by 3 " .
       " order by 2 DESC ";

print $sql;

print table_content( $sql );

//client_id = 196 and