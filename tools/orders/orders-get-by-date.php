<?php
require_once( '../tools.php' );

?>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
</head>
<?php


$last_days = $_GET["last_days"];

$link = mysql_connect( $servername, $username, $password );
mysql_set_charset( 'utf8', $link );

// Check connection
if ( $link->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}

mysql_select_db( $dbname );

print "<center>הזמנות מה-" . $last_days . " ימים אחרונים " . "</center>";

$sql = 'SELECT posts.id, posts.post_date'
       . ' FROM `wp_posts` posts'
       . ' WHERE `post_date` >= date_sub(now(), interval ' . $last_days . ' day)'
       . ' and post_status like \'wc%\'';


$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

for ( $i = 0; $i < $fields; $i ++ ) {
	$header .= mysql_field_name( $export, $i ) . "\t";
}

$data = "<table>";

while ( $row = mysql_fetch_row( $export ) ) {
	$order_id  = $row[0];
	$post_date = $row[1];
	$row_text  = "<td><a href=\"http://store.im-haadama.co.il/tools/orders/get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a></td><td>" . $post_date . "</td>";

	$sql_i = 'SELECT meta_value FROM `wp_postmeta` pm'
	         . ' WHERE pm.post_id = ' . $order_id
	         . ' AND `meta_key` IN ( \'_shipping_first_name\', \'_shipping_last_name\', \'_shipping_city\', \'_billing_phone\', \'method_id\')';
	$export_i = mysql_query( $sql_i ) or die ( "Sql error: " . mysql_error() );
	while ( $row_i = mysql_fetch_row( $export_i ) ) {
		$row_text .= "<td>" . $row_i[0] . '</td>';
	}
	$user_id   = get_postmeta_field( $order_id, '_customer_user' );
	$user_info = get_userdata( $user_id );
	$row_text  .= "<td>" . $user_info->user_email . "</td>";

	$line = $row_text;

	$sql_o = 'SELECT order_item_name FROM `wp_woocommerce_order_items` WHERE order_id= \'' . $order_id . '\' AND order_item_type = \'shipping\' LIMIT 0, 30 ';

	$export_o = mysql_query( $sql_o ) or die ( "Sql error: " . mysql_error() );
	while ( $row_o = mysql_fetch_row( $export_o ) ) {
		$row_text .= "<td>" . $row_o[0] . '</td>';
	}
	$line = $row_text;
	$data .= "<tr> " . trim( $line ) . "</tr>";
}
$data = str_replace( "\r", "", $data );

if ( $data == "" ) {
	$data = "\n(0) Records Found!\n";
}

$data .= "</table>";

print "$data";

$sql = 'select '
       . ' woi.order_item_name, sum(woim.meta_value), woi.order_item_id'
       . ' from wp_woocommerce_order_items woi join wp_woocommerce_order_itemmeta woim'
       . ' where order_id in'
       . ' (SELECT id'
       . ' FROM `wp_posts` '
       . ' WHERE `post_status` LIKE \'%wc-processing%\')'
       . ' and woi.order_item_id = woim.order_item_id and woim.`meta_key` = \'_qty\''
       . ' group by woi.order_item_name order by 1'
       . ' ';

$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

for ( $i = 0; $i < $fields; $i ++ ) {
	$header .= mysql_field_name( $export, $i ) . "\t";
}

$data = "<table>";

while ( $row = mysql_fetch_row( $export ) ) {
	$line          = '';
	$prod_name     = $row[0];
	$prod_quantity = $row[1];
	$order_item_id = $row[2];

	$sql2 = 'select woim.meta_value'
	        . ' from wp_woocommerce_order_itemmeta woim'
	        . ' where woim.order_item_id = ' . $order_item_id . ' and woim.`meta_key` = \'_product_id\''
	        . ' ';

	$export2 = mysql_query( $sql2 ) or die ( "Sql error : " . mysql_error() );

	$fields = mysql_num_fields( $export2 );

	$row2    = mysql_fetch_row( $export2 );
	$prod_id = $row2[0];

	$line = "<td> " . $prod_name .
	        "</td><td><a href = \"http://store.im-haadama.co.il/tools/get-orders-per-item.php?prod_id=" . $prod_id . "\">" . $prod_quantity . "</a></td>";

	$data .= "<tr> " . trim( $line ) . "</tr>";

	$data = str_replace( "\r", "", $data );
}

if ( $data == "" ) {
	$data = "\n(0) Records Found!\n";
}

print "<center>סך הפריטים שהוזמנו</center>";
$data .= "</table>";

print "$data";


?>
</html>
