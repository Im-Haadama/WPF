<?php
require_once( '../tools.php' );
require_once( 'pricelist.php' );
require_once( 'pricelist-process.php' );

$supplier_id = $_GET["supplier_id"];
if ( isset( $_GET["add"] ) ) {
	$add = true;
}

$target_dir    = "../uploads/";
$target_file   = $target_dir . "prices" . $supplier_id . ".csv";
$uploadOk      = 1;
$imageFileType = pathinfo( $target_file, PATHINFO_EXTENSION );
if ( move_uploaded_file( $_FILES["fileToUpload"]["tmp_name"], $target_file ) ) {
	echo "The file " . basename( $_FILES["fileToUpload"]["name"] ) . " has been uploaded.<br/>";
	print $target_file . "<br/>";
}

print "reading file...<br/>";

pricelist_process( $target_file, $supplier_id, $add );


?>