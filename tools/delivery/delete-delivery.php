<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">

</head>
<body>

<?php
include_once( "../r-shop_manager.php" );
include_once( "../orders/orders-common.php" );
include_once( "delivery.php" );
include_once( "../business/business-post.php" );

// display form for creating invoice
$id = $_GET["id"];
if ( ! is_numeric( $id ) ) {
	print "חסר מספר תעודה";
	die( "no id" );
}

$d = new delivery( $id );
$d->Delete();

business_delete_transaction( $id );

?>


</body>
</html>
