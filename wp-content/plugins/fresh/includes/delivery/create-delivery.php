<?php
require_once( "../r-shop_manager.php" );
include_once( "delivery.php" );

print header_text( false );

print load_scripts(array("/core/gui/client_tools.js", "/core/data/data.js"));

$script_file = ImMultiSite::LocalSiteTools() . "/fresh/delivery/create-delivery-script.php?i=1";

$edit     = false;
$order_id = - 1;
/// If id is set -> edit. get order_id from id.
/// Otherwise order_id should be set.
if ( isset( $_GET["id"] ) ) {
	$id          = $_GET["id"];
	$script_file .= "&id=" . $id;
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

$O = new Order( $order_id );


$script_file .= "&order_id=" . $order_id;

$contents = file_get_contents( $script_file );
	print $contents;
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

my_log( __FILE__, "order=" . $order_id . " id = " . $id );

$show_inventory = false;
if (info_get("manage_inventory") and get_param("show_inv", false, 1))
      $show_inventory = true;


if ( $id > 0 ) {
	print "<form name=\"delivery\" action= \"\">";
//	print gui_header( 2, "עריכת תעודת משלוח מספר  " . $id );
//	print gui_header( 3, "הזמנה מספר " . get_order_id( $id ) );
	print $O->infoBox();

	$d = new Delivery( $id );
	$d->PrintDeliveries( ImDocumentType::delivery, ImDocumentOperation::edit, false, $show_inventory );

	//$d = new delivery( $id );
	print "</form>";
} else {
	$client_id = $O->getCustomerId();
	print "<form name=\"delivery\" action= \"\">";
	// print gui_header( 2, "יצירת תעודת משלוח להזמנה מספר " . $order_id, true );

	if ( sql_query_single_scalar( "select order_is_group(" . $order_id . ")" ) == 1 ) {
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
		print " הזמנות " . comma_implode( $order_ids );
		$d = delivery::CreateFromOrders( $order_ids );
		print $d->OrderInfoBox( $order_ids, false, "יצירת תעודת משלוח ל" );
	} else {
		print $O->infoBox( false, "יצירת תעודת משלוח ל" );
		$d = delivery::CreateFromOrder( $order_id );

	}

	$d->PrintDeliveries( ImDocumentType::delivery, ImDocumentOperation::create, false, $show_inventory );
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
	$d               = delivery::CreateFromOrder( $order_id );
	$show_save_draft = $d->isDraft();
}

if ( $show_save_draft ) {
	print gui_button( "btn_save_draft", "addDelivery(1)", "שמור טיוטא" );
}

?>
<button id="btn_add" onclick="addDelivery(0)">אשר תעודה</button>
<button id="btn_addline" onclick="addLine(0)">הוסף שורה</button>
<button id="btn_addline" onclick="addLine(1)">מוצר לא באתר</button>
<textarea id="logging" rows="2" cols="50"></textarea>

</body>
</html>