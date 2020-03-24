<html dir="rtl">
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ('../../flavor/includes/core/class-core-fund.php');
print Core_Fund::load_scripts(array('/wp-content/plugins/flavor/includes/core/gui/client_tools.js',
	'/wp-content/plugins/fresh/includes/js/delivery.js',
	'/wp-content/plugins/flavor/includes/core/data/data.js'));

//print gui_datalist( "items", "im_products", "post_title", true );
//print gui_datalist( "draft_items", "im_products_draft", "post_title", true );

require_once ("../../../../wp-config.php");
require_once ("../../../../im-config.php");

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
	} else {
		print "nothing to work with<br/>";
		die ( 1 );
	}
}


require ('create-delivery-script.php');

?>
</head>
<body>
<?php

// display form for creating invoice. If id already exist, open for edit
$id = null;

if ( isset( $_GET["id"] ) ) {
	$id = $_GET["id"];
}
if ( isset( $_GET["refund"] ) ) {
	$refund = true;
}

$show_inventory = false;
if (InfoGet("manage_inventory") and GetParam("show_inv", false, 1))
      $show_inventory = true;

if ( $id > 0 ) {
	print "<form name=\"delivery\" action= \"\">";
	$O->infoBox();

	$d = new Fresh_Delivery( $id );
	$d->PrintDeliveries( FreshDocumentType::delivery, Fresh_DocumentOperation::edit, false, $show_inventory );

	//$d = new delivery( $id );
	print "</form>";
} else {
	$client_id = $O->getCustomerId();
	print "<form name=\"delivery\" action= \"\">";
	// print gui_header( 2, "יצירת תעודת משלוח להזמנה מספר " . $order_id, true );

	if ( 0 and  sql_query_single_scalar( "select order_is_group(" . $order_id . ")" ) == 1 ) {
//		 print "הזמנה קבוצתית";
		$sql       = 'SELECT posts.id as id '
		             . ' FROM `wp_posts` posts'
		             . " WHERE post_status LIKE '%wc-processing%'  "
		             . " and order_user(id) = " . $client_id;
		$order_ids = sql_query_array_scalar( $sql );
		if ( count( $order_ids ) == 0 ) {
			print "אין הזמנות ללקוח הזמנות במצב טיפול<br/>";
			die ( 1 );
		}
		print " הזמנות " . CommaImplode( $order_ids );
		$d = delivery::CreateFromOrders( $order_ids );
		print $d->OrderInfoBox( $order_ids, false, "יצירת תעודת משלוח ל" );
	} else {
		print $O->infoBox( false, "יצירת תעודת משלוח ל" );

		$d = Fresh_Delivery::CreateFromOrder( $order_id );
	}
	$d->PrintDeliveries( FreshDocumentType::delivery, Fresh_DocumentOperation::create, false, $show_inventory );
	print "</form>";
}

?>

<button id="btn_calc" onclick="calcDelivery()">חשב תעודה</button>
<?php
$show_save_draft = false;
if ( ! $edit ) { // New
	$show_save_draft = true;
} else {
	// Still draft
//	$d               = delivery::CreateFromOrder( $order_id );
	$show_save_draft = $d->isDraft();
}

if ( $show_save_draft ) {
	print Core_Html::GuiButton( "btn_save_draft", "שמור טיוטא" , array("action" => "addDelivery(1)"));
}

?>
<button id="btn_add" onclick="addDelivery(0)">אשר תעודה</button>
<button id="btn_addline" onclick="addLine(0)">הוסף שורה</button>
<button id="btn_addline" onclick="addLine(1)">מוצר לא באתר</button>
<textarea id="logging" rows="2" cols="50"></textarea>

</body>
</html>