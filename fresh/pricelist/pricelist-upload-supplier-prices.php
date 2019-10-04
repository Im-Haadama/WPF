<?php
require_once( '../r-shop_manager.php' );
require_once( 'pricelist.php' );
require_once( 'pricelist-process.php' );

print header_text( false, true, true );
$supplier_id = $_GET["supplier_id"];
$add         = false;
if ( isset( $_GET["add"] ) ) {
	$add = true;
}

$tmp_file  = $_FILES["fileToUpload"]["tmp_name"];
$file_name = $_FILES["fileToUpload"]["name"];
$ext       = pathinfo( $file_name, PATHINFO_EXTENSION );

$target_dir    = dirname( __FILE__ ) . "/uploads/";
$target_file   = $target_dir . "prices" . $supplier_id . "." . $ext;
$imageFileType = pathinfo( $target_file, PATHINFO_EXTENSION );
if ( ! file_exists( $tmp_file ) ) {
	print "file " . $tmp_file . " not found ";
	die ( 1 );
}
if ( ! file_exists( $target_dir ) ) {
	print "directory " . $target_dir . " not found ";
	die ( 1 );
}


if ( move_uploaded_file( $tmp_file, $target_file ) ) {
	echo "The file " . basename( $file_name ) . " has been uploaded.<br/>";
	print $target_file . "<br/>";
}

switch ( $ext ) {
	case "csv":
		pricelist_process( $target_file, $supplier_id, $add );
		break;
	case "xls":
		read_chef_xls( $target_file, $supplier_id );
		break;
	default:
		print "File type " . $ext . "not handled.";
		die( 1 );
}

function read_chef_xls( $input_file, $supplier_id ) {
	print $input_file . "<br/>";
	$file = "http://super-organi.co.il/" . substr( $input_file, strpos( $input_file, "tools" ) );
	$url  = "http://tabula.aglamaz.com/imap/xls2csv.php?input=" . $file;
	print "reading " . $url . "<br/>";
	$txt = file_get_contents( $url );

	print strlen( $txt ) . " bytes reader <br/>";

	$temp_file = tmpfile();

	fwrite( $temp_file, $txt );
	fseek( $temp_file, 0 );
	pricelist_process( $temp_file, $supplier_id, false, 'http://www.cheforgani.co.il/img/uploads/products' );
//	print realpath($target_file);

	// print "coming soon..";
}

?>

