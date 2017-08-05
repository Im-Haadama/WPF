<?php
include_once( "../tools_wp_login.php" );
include_once( "../orders/orders-common.php" );
include_once( "delivery.php" );
include_once( "../multi-site/multi-site.php" );
?>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">

	<?php

	$script_file = MultiSite::LocalSiteTools() . "/delivery/create-delivery-script.php?";
	$first       = true;
	if ( isset( $_GET["id"] ) ) {
		$script_file .= "id=" . $_GET["id"];
		$first       = false;
	}
	if ( isset( $_GET["order_id"] ) ) {
		if ( ! $first ) {
			$script_file .= '&';
		}
		$script_file .= 'order_id=' . $_GET["order_id"];
		$first       = false;
	}
	$contents = file_get_contents( $script_file );
	// $contents = fread($handle, filesize($filename));
	print $contents;
	?>
</head>
<body>
<center><img src="<?php print $logo_url; ?>"></center>
<?php


// display form for creating invoice. If id already exist, open for edit
$order_id = $_GET["order_id"];
$id       = $_GET["id"];
if ( isset( $_GET["refund"] ) ) {
	$refund = true;
}

$edit = false;
if ( $id > 0 ) {
	$edit     = true;
	$order_id = get_order_id( $id );
}

my_log( __FILE__, "order=" . $order_id . " id = " . $id );

if ( $id > 0 ) {
	print "<form name=\"delivery\" action= \"\">";
	print "<center>עריכת תעודת משלוח מספר  " . $id;
	print  " </center>";

	$d = new Delivery( $id );
	$d->print_delivery( false, false, $refund );

	$d = new delivery( $id );
	print "</form>";

} else {
	$client_id = get_customer_id_by_order_id( $order_id );
	print "<form name=\"delivery\" action= \"\">";
	print "<center>הפקת תעודת משלוח להזמנה מספר  ";
	print $order_id;
	print  " </center>";

	print_order_info( $order_id );
	$d = delivery::CreateFromOrder( $order_id );
	$d->print_delivery( true );
	print "</form>";
}

?>

<button id="btn_calc" onclick="calcDelivery()">חשב תעודה</button>
<button id="btn_add" onclick="addDelivery()">אשר ושמור תעודה</button>
<button id="btn_addline" onclick="addLine()">הוסף שורה</button>
<textarea id="logging" rows="2" cols="50"></textarea>


</body>
</html>
