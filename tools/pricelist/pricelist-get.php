<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 15:25
 */
//error_reporting( E_ALL );
//ini_set( 'display_errors', 'on' );

require_once( '../r-shop_manager.php' );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( "../suppliers/gui.php" );
require_once( "../multi-site/imMulti-site.php" );
require_once( "../suppliers/Supplier.php" );

$option = get_param("option");

function set_supplier_id() {
	if ( ! isset( $_GET["supplier_id"] ) ) {
		print 'var sel = document.getElementById("supplier_id");
                var selected = sel.options[sel.selectedIndex];
                supplier_id = selected.value;
                var site_id = selected.getAttribute("data-site-id");
                var tools = selected.getAttribute("data-tools-url-id");
                ';
	} else {
		$id      = $_GET["supplier_id"];
		$supp    = new Supplier( $id );
		$site_id = $supp->getSiteId();
		print 'var supplier_id = ' . $id . ';';
		if ( $site_id > 0 ) {
			print 'var site_id = ' . $site_id . ';';
			print 'var tools = \'' . ImMultiSite::SiteTools( $site_id ) . "';";
		} else {
			print 'var site_id = "";';
			print 'var tools = "";';
		}
	}
}
?>
<html dir="rtl" lang="he">
<header>
    <meta charset="UTF-8">
    <script type="text/javascript" src="/niver/gui/client_tools.js"></script>
	<?php
	$map_table = "price_list";
	require_once( "../catalog/mapping.php" );
	?>
    <script>
        var supplier_id;

        function selected(sel) {
            var pricelist_id = sel.id.substr(3);
            document.getElementById("chk" + pricelist_id).checked = true;
        }

        function createProducts()
        {
	        <?php
	        set_supplier_id();
	        ?>
            window.location = "pricelist-get.php?supplier_id=" + supplier_id + "&option=create_products";
        }
        function create_supply() {
	        <?php
	        set_supplier_id();
	        ?>

            var table = document.getElementById('pricelist');

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();

            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var pl_id = collection[i].id.substr(3);
                    var prod_id = get_value_by_name("pid_" + pl_id);
                    var stock = parseFloat(get_value_by_name("stk_" + prod_id));
                    if (isNaN(stock)) stock = 0;
                    var ordered = 1;
                    var ordered_text = get_value_by_name("ord_" + prod_id);
                    if (ordered_text.length > 2) {
                        ordered = parseFloat(ordered_text.substr(0, ordered_text.indexOf(":")));
                        if (isNaN(ordered)) ordered = 1;
                    }

                    // if (stock > ordered) continue;
                    // var code = get_value(table.rows[i+1].cells[1].firstChild);
                    // var name_code = get_value(table.rows[i+1].cells[2].firstChild);
                    // var new_price = get_value_by_name("prc_" + line_id);
                    // var sel = document.getElementById("supplier_id");
                    // var supplier_id = sel.options[sel.selectedIndex].value;

                    // if (code > 0 && code != 10) name_code = code;

                    var to_order = ordered - stock;
                    if (to_order < 3) to_order = 3;
                    params.push(prod_id);
                    params.push(to_order);
                    params.push(0); // units
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    add_message(xmlhttp.response);
                    // change_supplier();
                }
            }
            if (!params.length) {
                alert("יש לבחור חסרים במלאי כדי ליצור הספקה");
                return;
            }
            var request = "../supplies/supplies-post.php?operation=create_supply&supplier_id=" + supplier_id + "&create_info=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }

        function updatePrices() {
            <?php set_supplier_id(); ?>
            // var sel = document.getElementById("supplier_id");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=refresh_prices&supplier_id=" + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function change_managed(field) {
            var subject = field.id.substr(4);
            var is_managed = get_value_by_name("chm_" + subject);

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {

                }
            }
            var request = "pricelist-post.php?operation=managed&is_managed=" + is_managed + "&prod_id=" + subject;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function inActiveList() {
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
            var request = "pricelist-post.php?operation=inactive&supplier_id=" + supplier_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function changed(field) {
            var subject = field.name.substr(4);
            document.getElementById("chk" + subject).checked = true;
        }

        function savePrices() {
	        <?php
	        set_supplier_id();
	        ?>

            var table = document.getElementById('price_list');
//            var sel = document.getElementById("supplier_id");
//            supplier_id = sel.options[sel.selectedIndex].value;

            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var line_id = collection[i].id.substr(3);
                    // var code = get_value(table.rows[i+1].cells[1].firstChild);
                    // var name_code = get_value(table.rows[i+1].cells[2].firstChild);
                    var new_price = get_value_by_name("prc_" + line_id);
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
                    // Test - don't reload page on each change.
                    alert("שינויים נשמרו. אפשר להמשיך לעדכן");
                    // change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=update_price&supplier_id=" + supplier_id + "&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function del_line(pricelist_id) {
            var btn = document.getElementById("del_" + pricelist_id);
            btn.parentElement.parentElement.style.display = 'none';
            execute_url("pricelist-post.php?operation=delete_price&params=" + pricelist_id);
        }
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
            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {

                    var pricelist_id = collection[i].id.substr(3);

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

        function select_category(pl_id)
        {
            enable_btn("cre_" + pl_id);
        }

        function create_product(pl_id)
        {
            <?php set_supplier_id(); ?>

            var categ = get_value_by_name("cat_" + pl_id);

            var request = "../catalog/catalog-map-post.php?operation=create_products&category_name=" + encodeURI(categ) +
                "&create_info=" + supplier_id + "," + pl_id;

            execute_url(request);

            // alert (categ);
        }

        function change_supplier() {
	        <?php
	        set_supplier_id();
	        ?>
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
            xmlhttp.onloadend = function () {
                if (xmlhttp.status == 404 || xmlhttp.status == 500)
                    change_supplier();
            }
            var request = "pricelist-post.php?operation=get_priceslist&supplier_id=" + supplier_id;
//            var o = get_value_by_name("chk_ordered");
//            alert (o);

            <?php
                if ($option) print 'request = request + "&option=' . $option . '";';
            ?>

            if (get_value_by_name("chk_ordered")) request += "&ordered";
            if (get_value_by_name("chk_need_supply")) request += "&need_supply";

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            // Get last update date
            xmlhttp_date = new XMLHttpRequest();
            xmlhttp_date.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp_date.readyState == 4 && xmlhttp_date.status == 200)  // Request finished
                {
                    label = document.getElementById("last_update");
                    label.innerHTML = xmlhttp_date.response;
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
	        <?php
	        set_supplier_id();
	        ?>

            savePrices();
            var code = get_value(document.getElementById("product_code"));
            var name = get_value(document.getElementById("product_name"));
            var price = get_value(document.getElementById("price"));
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    // For now after add, reload.
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=add_price&product_name=" + name + '&price=' + price +
                '&supplier_id=' + supplier_id;
            if (code.length > 0) request += "&code=" + code;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function refresh() {
            change_supplier();
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

	<?php
	if ( ! isset ( $_GET["supplier_id"] ) ) {
		print_select_supplier( "supplier_id", true );
	} else {
		print get_supplier_name( $_GET["supplier_id"] );
	}
	?>


</h1>
<label id="last_update"></label>

<div id="div_change">
    <button id="btn_save" onclick="savePrices()">שמור עדכונים</button>
    <button id="btn_delete" onclick="delPrices()">מחק פריטים</button>
    <button id="btn_delete_map" onclick="delMap()">מחק מיפוי</button>
    <button id="btn_dontsell" onclick="donPrices()">לא למכירה</button>
    <button id="btn_create_products" onclick="createProducts()">יצירת מוצרים</button>

	<?php
	$user = wp_get_current_user();
	if ( $user->ID == "1" ) {
		print '<button id="btn_delete_list" onclick="inActiveList()">הקפא ספק</button>';
	}
	print '<button id="btn_update_list" onclick="updatePrices()">עדכן מחירים</button>';

	?>
    <button id="btn_map" onclick="map_products()">שמור מיפוי</button>
    <button id="btn_create_supply" onclick="create_supply()">צור הספקה</button>

    <label id="log"></label>
</div>
<label id="is_slave"></label>
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

<?php

print gui_checkbox( "chk_ordered", "", "", "onchange=change_supplier()" );
print "הצג רק מוזמנים<br/>";

print gui_checkbox( "chk_need_supply", "", "", "onchange=change_supplier()" );
print "הצג רק פריטים להזמין<br/>";

//print gui_button("download", "download_csv()","הורד"); ?>

<!--<form id="downcsv" method="get" action="download_csv.php">-->
<!--    <button type="submit">הורד</button>-->
<!--    <input type='hidden' name='supplier_id'/>-->
<!--</form>-->
<a id="downcsv" href="path_to_file" download="pricelist.csv">הורד CSV</a>
<div id="price_list"></div>
<!--            <button id="btn_load_prices" onclick="load_file()">טען רשימה</button>-->

</div>

</body>
</html>