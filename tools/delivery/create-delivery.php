<?php
include_once( "../tools_wp_login.php" );
include_once( "../orders/orders-common.php" );
include_once( "delivery.php" );
include_once( "../multi-site/multi-site.php" );

print header_text( true );

/// If id is set -> edit. get order_id from id.
/// Otherwise order_id should be set.
?>
<script type="text/javascript" src="../client_tools.js"></script>
<?php

$script_file = MultiSite::LocalSiteTools() . "/delivery/create-delivery-script.php?i=1";

if ( isset( $_GET["id"] ) ) {
	$id          = $_GET["id"];
	$script_file .= "&id=" . $id;
	$edit        = false;
	if ( $id > 0 ) {
		$edit     = true;
		$order_id = get_order_id( $id );
	}
} else {
	if ( isset( $_GET["order_id"] ) ) {
		$order_id = $_GET["order_id"];
	} else {
		print "nothing to work with<br/>";
		die ( 1 );
	}
}

$script_file .= "&order_id=" . $order_id;

$contents = file_get_contents( $script_file );
	print $contents;
	?>
</head>
<body>
<?php

// display form for creating invoice. If id already exist, open for edit
$id       = $_GET["id"];
if ( isset( $_GET["refund"] ) ) {
	$refund = true;
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
