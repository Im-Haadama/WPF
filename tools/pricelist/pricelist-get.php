<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 15:25
 */
require_once( '../r-shop_manager.php' );
require_once( '../gui/inputs.php' );
require_once( "../suppliers/gui.php" );

?>
<html dir="rtl" lang="he">
<header>
    <meta charset="UTF-8">
    <script>
		<?php
		$filename = __DIR__ . "/../client_tools.js";
		$handle = fopen( $filename, "r" );
		$contents = fread( $handle, filesize( $filename ) );
		print $contents;
		?>

        var supplier_id;

        function delList() {
            var sel = document.getElementById("supplier_id");
            supplier_id = sel.options[sel.selectedIndex].value;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=delete_list&supplier_id=" + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }
        function changed(field) {
            var subject = field.name;
            document.getElementById("chk" + subject).checked = true;
        }

        function savePrices() {
            var table = document.getElementById('price_list');
            var sel = document.getElementById("supplier_id");
            supplier_id = sel.options[sel.selectedIndex].value;

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var line_id = collection[i].id.substr(3);
                    // var code = get_value(table.rows[i+1].cells[1].firstChild);
                    // var name_code = get_value(table.rows[i+1].cells[2].firstChild);
                    var new_price = get_value(table.rows[i + 1].cells[4].firstChild);
                    // var sel = document.getElementById("supplier_id");
                    // var supplier_id = sel.options[sel.selectedIndex].value;

                    // if (code > 0 && code != 10) name_code = code;

                    params.push(line_id, new_price);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=update_price&supplier_id=" + supplier_id + "&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        //    function loadPrices()
        //    {
        //        xmlhttp = new XMLHttpRequest();
        //        xmlhttp.onreadystatechange = function()
        //        {
        //            // Wait to get query result
        //            if (xmlhttp.readyState==4 && xmlhttp.status==200)  // Request finished
        //            {
        //                change_supplier();
        //            }
        //        }
        //        var request = "pricelist-post.php?operation=update_price&supplier_id=" + supplier_id + "&params=" + params;
        //        xmlhttp.open("GET", request, true);
        //        xmlhttp.send();
        //    }

        function delPrices() {
            // var table = document.getElementById('price_list');
            // var sel = document.getElementById("supplier_id");
            // var id = sel.options[sel.selectedIndex].value;

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);
//                var name = get_value(table.rows[i+1].cells[1].firstChild);
//                var sel = document.getElementById("supplier_id");
//                var supplier_id = sel.options[sel.selectedIndex].value;

                    params.push(id);
                    //        alert(id);
                }
            }
            execute_url("pricelist-post.php?operation=delete_price&params=" + params, change_supplier);
        }

        function delMap() {
            var table = document.getElementById('price_list');

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {

                    var pricelist_id = table.rows[i + 1].cells[0].firstChild.id.substr(3);

                    params.push(pricelist_id);
                    //        alert(map_id);
                }
            }
            var URL = "pricelist-post.php?operation=delete_map&params=" + params;
            execute_url(URL, change_supplier);
        }

        function donPrices() {
            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);

                    params.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var sel = document.getElementById("supplier_id");
            supplier_id = sel.options[sel.selectedIndex].value;
            var request = "pricelist-post.php?operation=dont_price&params=" + params;
            // alert(request);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function change_supplier() {
            var sel = document.getElementById("supplier_id");
            var selected = sel.options[sel.selectedIndex];
            supplier_id = selected.value;
            var site_id = selected.getAttribute("data-site-id");
            var tools = selected.getAttribute("data-tools-url-id");
            var upcsv = document.getElementById("upcsv");

            if (site_id > 0) {
                document.getElementById("btn_save").style.visibility = "hidden";
                document.getElementById("btn_delete").style.visibility = "hidden";
                upcsv.style.visibility = "hidden";
                document.getElementById("addcsv").style.visibility = "hidden";
            } else {
                document.getElementById("btn_save").style.visibility = "visible";
                document.getElementById("btn_delete").style.visibility = "visible";
                upcsv.style.visibility = "visible";
                upcsv.action = "pricelist-upload-supplier-prices.php?supplier_id=" + supplier_id;
                document.getElementById("addcsv").style.visibility = "visible";
            }

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById("price_list");
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = "pricelist-post.php?operation=get_priceslist&supplier_id=" + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            // Get last update date
            xmlhttp_date = new XMLHttpRequest();
            xmlhttp_date.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp_date.readyState == 4 && xmlhttp_date.status == 200)  // Request finished
                {
                    lable = document.getElementById("last_update");
                    lable.innerHTML = xmlhttp_date.response;
                }
            }
            request = "pricelist-post.php?operation=header&supplier_id=" + supplier_id;
            xmlhttp_date.open("GET", request, true);
            xmlhttp_date.send();

            // Change the action of upload button according to selected supplier

            var fa = document.getElementById("addcsv");
            fa.action = "pricelist-upload-supplier-prices.php?add&supplier_id=" + supplier_id;

            var fd = document.getElementById("downcsv");
            fd.href = "pricelist-post.php?operation=get_csv&supplier_id=" + supplier_id;
            // alert(fd.action);

            // Disable buttons if pricelist is slave of other site
//        xmlhttp_slave = new XMLHttpRequest();
//        xmlhttp_slave.onreadystatechange = function()
//        {
//            // Wait to get query result
//            if (xmlhttp_slave.readyState==4 && xmlhttp_slave.status==200)  // Request finished
//            {
//                if (xmlhttp_slave.response.substr(0, 5) == "slave") {
////                    document.getElementById("div_add").style.visibility = 'hidden';
//                    document.getElementById("div_change").style.visibility = 'hidden';
//                    document.getElementById("is_slave").innerHTML = '<b>' + 'שים לב! מנוהל מרחוק' + '</b>';
//                    document.getElementById("upcsv").style.visibility = 'hidden';
//
//                } else {
////                    document.getElementById("div_add").style.visibility = 'visible';
//                    document.getElementById("div_change").style.visibility = 'visible';
//                    document.getElementById("is_slave").innerHTML = '';
//                    document.getElementById("upcsv").style.visibility = 'visible';
//                }
//            }
//        }
//        request = "pricelist-post.php?operation=is_slave&supplier_id=" + supplier_id;
//        xmlhttp_slave.open("GET", request, true);
//        xmlhttp_slave.send();
        }

        function add_item() {
            var sel = document.getElementById("supplier_id");
            var id = sel.options[sel.selectedIndex].value;
            var name = get_value(document.getElementById("product_name"));
            var price = get_value(document.getElementById("price"));
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=add_price&product_name=" + name + '&price=' + price +
                '&supplier_id=' + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
</header>

<style>
    h1 {
        text-align: center;
    }
</style>

<body onload="change_supplier()">
<h1>
    מחירון ספק

	<?
	print_select_supplier( "supplier_id", true );
	?>
</h1>
<lable id="last_update"></lable>

<div id="div_change">
    <button id="btn_save" onclick="savePrices()">שמור עדכונים</button>
    <button id="btn_delete" onclick="delPrices()">מחק פריטים</button>
    <button id="btn_delete_map" onclick="delMap()">מחק מיפוי</button>
    <button id="btn_dontsell" onclick="donPrices()">לא למכירה</button>
	<?php
	$user = wp_get_current_user();
	if ( $user->ID == "1" ) {
		print '<button id="btn_delete_list" onclick="delList()">מחק רשימה</button>';
	}

	?>
</div>
<lable id="is_slave"></lable>
<br/>
</div>

<form name="upload_csv" id="upcsv" method="post" enctype="multipart/form-data">
    החלף רשימה של הספק:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="החלף" name="submit">

    <input type="hidden" name="post_type" value="product"/>
</form>

<form name="add_csv" id="addcsv" method="post" enctype="multipart/form-data">
    הוסף לרשימה של הספק:
    <input type="file" name="fileToUpload" id="fileToUpload1">
    <input type="submit" value="הוסף" name="submit">

    <input type="hidden" name="post_type" value="product"/>
</form>

<?php //print gui_button("download", "download_csv()","הורד"); ?>

<!--<form id="downcsv" method="get" action="download_csv.php">-->
<!--    <button type="submit">הורד</button>-->
<!--    <input type='hidden' name='supplier_id'/>-->
<!--</form>-->
<a id="downcsv" href="path_to_file" download="pricelist.csv">הורד CSV</a>
<table id="price_list"></table>
<!--            <button id="btn_load_prices" onclick="load_file()">טען רשימה</button>-->

</div>

</body>
</html>