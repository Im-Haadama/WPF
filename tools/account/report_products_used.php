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

$sql = "select product_name, round(sum(quantity),2) " .
       " from im_delivery_lines " .
       " where delivery_id IN " .
       " (select transaction_ref from im_client_accounts " .
       " where  transaction_method = \"משלוח\") " .
       " group by 1 " .
       " order by 2 DESC ";

print table_content( $sql );

//client_id = 196 and