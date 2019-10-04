<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 30/10/17
 * Time: 07:01
 */
require_once( '../r-shop_manager.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );

print header_text( false );
print gui_header( 1, 'טעינת רשימת השף' );

print gui_list( "יש להכנס לניהול השף" );
print gui_list( "בתפריט מוצרים/כל המוצרים" );
print gui_list( "ייצוא לאקסל. פעולה זאת תשמור בתיקיית הורדות קובץ Xls עם כל הפריטים." );
print gui_list( "ללחוץ על בחר קובץ. לבחור אותו מתיקיית ההורדות" );
print gui_list( "לסיום ללחוץ החלף" );


?>
<br/>
<br/>
<style>
    .btn {
        font: bold 11px Arial;
        text-decoration: none;
        background-color: #EEEEEE;
        color: #333333;
        padding: 2px 6px 2px 6px;
        border-top: 1px solid #CCCCCC;
        border-right: 1px solid #333333;
        border-bottom: 1px solid #333333;
        border-left: 1px solid #CCCCCC;
    }
</style>
<form action="pricelist-upload-supplier-prices.php?supplier_id=200053" name="upload_csv" id="upcsv" method="post"
      enctype="multipart/form-data">
    החלף רשימה של הספק:
    <label for="fileToUpload" class="btn">בחר קובץ XLS</label>
    <input style="visibility:hidden; width: 40px" type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="החלף" name="submit">

    <input type="hidden" name="post_type" value="product"/>
</form>
