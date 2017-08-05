<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
</head>
<?php
require_once( '../tools.php' );
require_once( '../../wp-content/plugins/woocommerce-delivery-notes/woocommerce-delivery-notes.php' );
?>
<style type="text/css" media="print">
    .page {
        -webkit-transform: rotate(-90deg);
        -moz-transform: rotate(-90deg);
        filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
    }
</style>
<?php
$print = 1; //$_GET["print"];

function get_field( $order_id, $field_name ) {
	$sql = 'SELECT meta_value FROM `wp_postmeta` pm'
	       . ' WHERE pm.post_id = ' . $order_id
	       . " AND meta_key = '" . $field_name . "'";
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

//	print $row[0] + "<br>";
	return $row[0];
}

function get_delivery_driver( $order_id ) {
	$city = get_field( $order_id, '_shipping_city' );

	$sql = 'SELECT path  FROM im_paths WHERE city = "' . $city . '"';
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

	$sql = 'SELECT driver FROM im_path_info WHERE id = "' . $row[0] . '"';
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );

	return $row[0];
}

function get_order_order( $order_id ) {
	$city = get_field( $order_id, '_shipping_city' );

	$sql = 'SELECT city_order, path FROM im_paths WHERE city = "' . $city . '"';
	// print $sql . "<br>";
	$export = mysql_query( $sql ) or die ( "Sql error: " . mysql_error() );
	$row = mysql_fetch_row( $export );
//	print $row[0] + "<br>";
	$city_order = $row[0];
	$path       = $row[1];

	return 100 * $path + $city_order;
}

print "<header><center><h1>מסלולים ליום " . date( 'd/m/Y' );
print "</h1></center></header>";

$sql = 'SELECT id'
       . ' FROM `wp_posts` posts'
       . ' WHERE `post_status` LIKE \'%wc-processing%\''
       . " union select order_id as id from  im_delivery\n"
       . "where date = curdate()  "
       . ' order by 1';

$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

for ( $i = 0; $i < $fields; $i ++ ) {
	$header .= mysql_field_name( $export, $i ) . "\t";
}

$data = "<table>";
$data .= "<tr><td><h3>מספר </br> הזמנה</h3></td>";
$data .= "<td><h3>שם המזמין</h3></td>";
$data .= "<td><h3>שם המקבל</h3></td>";
$data .= "<td><h3>טלפון</h3></td>";
$data .= "<td><h3>כתובת אספקה</h3></td>";
$data .= "<td><h3></h3></td>";
$data .= "<td><h3>עיר</h3></td>";
$data .= "<td><h3></h3></td>";
$data .= "<td><h3>נהג</h3></td>";
$data .= "<td><h3>ארוז</h3></td>";
$data .= "<td><h3>בדוק</h3></td>";
$data .= "<td><h3>דיווח<br/>חוסר</h3></td>";
$data .= "<td><h3>סופק</h3></td>";
$data .= "</tr>";

$data_lines = array();

while ( $row = mysql_fetch_row( $export ) ) {
	foreach ( $row as $value ) {
		// display order_id with link to display it.
		$order_id = $value;
		$row_text = "<td><a href=\"get-order.php?order_id=" . $order_id . "\">" . $order_id . "</a></td>";

		// display customer name
		$fname    = get_field( $order_id, '_billing_first_name' );
		$lname    = get_field( $order_id, '_billing_last_name' );
		$row_text .= "<td>" . $fname . " " . $lname . "</td>";

		// display receiver name
		$fname    = get_field( $order_id, '_shipping_first_name' );
		$lname    = get_field( $order_id, '_shipping_last_name' );
		$row_text .= "<td>" . $fname . " " . $lname . "</td>";

		// display other fields
		$fields = array(
			'_billing_phone',
			'_shipping_address_1',
			'_shipping_address_2',
			'_shipping_city',
			'method_id'
		);
		foreach ( $fields as $field ) {
			$field_value = get_field( $order_id, $field );
			$row_text    .= "<td>" . $field_value . '</td>';
		}
		$row_text .= "<td>" . get_delivery_driver( $order_id ) . '</td>';

		$row_text .= "<td><input type='checkbox'></td>";
		$row_text .= "<td><input type='checkbox'></td>";
		$row_text .= "<td><input type='checkbox'></td>";
		$row_text .= "<td><input type='checkbox'></td>";

		print "order id= " . $order_id . "<br/>";
		$sort_index = get_order_order( $order_id );

		$row_text .= "<td>" . $sort_index . '</td>';

		$line = "<tr> " . trim( $row_text ) . "</tr>";

		// get_field($order_id, '_shipping_city');
		array_push( $data_lines, array( $sort_index, $line, $order_id ) );
	}
}

sort( $data_lines );

$plugin       = new WooCommerce_Delivery_Notes_Print();
$print_url    = 'http://store.im-haadama.co.il/wp-admin/admin-ajax.php?print-order=';
$print_url_id = "";

for ( $i = 0; $i < count( $data_lines ); $i ++ ) {
	$line = $data_lines[ $i ][1];
	$data .= trim( $line );

	$print_url_id = $data_lines[ $i ][2] . "-" . $print_url_id;
}

$print_url .= $print_url_id . "&print-order-type=invoice&action=print_order";
print "<a href=\"" . $print_url . "\"" . ">" . "הדפסה" . "</a>";

if ( $data == "" ) {
	$data = "\n(0) Records Found!\n";
}

$data .= "</table>";

$data = str_replace( "\r", "", $data );

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

?>
</html>
