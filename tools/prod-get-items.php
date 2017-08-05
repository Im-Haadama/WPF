<?php
require_once( 'tools.php' );
?>


<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
</head>
<body>

<center><h1>פריטים פעילים בחנות</h1></center>
<?php

$link = mysql_connect( $servername, $username, $password );
mysql_set_charset( 'utf8', $link );

// Check connection
if ( $link->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}

mysql_select_db( $dbname );

$sql = 'select '
       . ' id,post_title '
       . ' from wp_posts '
       . ' where post_status = \'publish\' and post_type = \'product\''
       . ' ';

$export = mysql_query( $sql ) or die ( "Sql error : " . mysql_error() );

$fields = mysql_num_fields( $export );

$data      .= "<table><tr><td><h3>מזהה</h3></td><td><h3>שם פריט</h3></td><td><h3>מחיר מכירה</h3></td><td><h3>מחיר</h3></td><td><h3>סהכ</h3></td></tr>";
$total     = 0;
$vat_total = 0;

while ( $row = mysql_fetch_row( $export ) ) {
	$line      = "";
	$prod_id   = $row[0];
	$prod_name = $row[1];

	// Display product line
	$line = "<tr>";
	$line .= "<td>" . $prod_id . '</td>';
	$line .= '<td>' . $prod_name . '</td>';

	// price
	$price = get_postmeta_field( $prod_id, '_price' );
	$line  .= '<td>' . $price . '</td>';

	// vat percent
	$vat_percent = get_postmeta_field( $prod_id, 'vat_percent' );
	if ( $vat_percent > 0 ) {
		$line .= '<td>' . $vat_percent . '%</td>';
	}

	$line .= "</tr>";
	$data .= "<tr> " . trim( $line ) . "</tr>";
}
$data .= "</table>";

print $data;
?>

</body>
</html>
