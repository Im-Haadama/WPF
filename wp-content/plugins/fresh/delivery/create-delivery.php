<?php
require_once ('../../flavor/includes/core/class-core-fund.php');

require_once ("../../../../wp-config.php");

new Fresh_Delivery(0); // load delivery classes

$edit     = false;
$order_id = -1;
$id = null; // delivery id

/// If id is set -> edit. get order_id from id.
/// Otherwise order_id should be set.
if ( isset( $_GET["id"] ) ) {
	$id          = $_GET["id"];
	$d = new Fresh_Delivery($id);
	if ( $id > 0 ) {
		$edit     = true;
		$order_id = $d->OrderId();
		$O = $d->getOrder();
	}
} else {
	if ( isset( $_GET["order_id"] ) ) {
		$order_id = $_GET["order_id"];
		$O = new Fresh_Order($order_id);
		if ($O->getDeliveryId()){
			print "<meta http-equiv=\"Refresh\" content=\"0; url=get-delivery.php?order_id=$order_id\" />";
			return;
		}
	} else {
		print "nothing to work with<br/>";
		die ( 1 );
	}
}
$user_id = $O->getCustomerId();
?>
<html dir="rtl">
<?php
print Core_Html::load_scripts(array('/wp-content/plugins/flavor/includes/core/gui/client_tools.js',
	'/wp-content/plugins/fresh/includes/js/delivery.js',
	'/wp-content/plugins/flavor/includes/core/data/data.js'));

require ('create-delivery-script.php');

?>
</head>
<body>
<?php

// display form for creating invoice. If id already exist, open for edit
$id = null;

</body>
</html>
