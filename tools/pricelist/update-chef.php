<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/10/17
 * Time: 07:01
 */
require_once( '../r-shop_manager.php' );
require_once( '../gui/inputs.php' );

print header_text( true );
print gui_header( 1, 'טעינת רשימת השף' );
?>

<form action="pricelist-upload-supplier-prices.php?supplier_id=200053" name="upload_csv" id="upcsv" method="post"
      enctype="multipart/form-data">
    החלף רשימה של הספק:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="החלף" name="submit">

    <input type="hidden" name="post_type" value="product"/>
</form>
