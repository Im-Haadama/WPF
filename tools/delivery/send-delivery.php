<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 21/04/16
 * Time: 15:32
 */

include_once( "../r-shop_manager.php" );
include_once( "../orders/orders-common.php" );
include_once( "../account/account.php" );
include_once( "delivery.php" );

$del_id = $_GET["del_id"];
if ( ! ( $del_id > 0 ) ) {
	print "Usage: send_delivery.php&del_id=##<br/>";
	die( 1 );
}
$edit = false;
if ( isset( $_GET["edit"] ) ) {
	$edit = true;
}

$del_ids = get_param_array("del_id");
foreach ($del_ids as $del_id){
	$delivery = new delivery( $del_id );
	print "info: " . $info_email;
	print "track: " . $track_email;

	$option = $delivery->getPrintDeliveryOption();

	if ( strstr( $option, 'M' ) ) {
		$delivery->send_mail( $track_email, $edit );
		print "sent<br/>";
	} else {
		print "<br/>";
	}

}
//if (strstr($option, 'P')){
//	$delivery->PrintDeliveries(ImDocumentType::delivery, ImDocumentOperation::show);
//	print '      <script>  window.print(); </script>';
//}
