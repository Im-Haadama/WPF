<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../tools_wp_login.php' );
require_once( 'catalog.php' );
require_once( '../gui.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

?>
<html dir="rtl" lang="he">
<header>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">

    <script>
        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }

        function create_products() {
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_name = encodeURIComponent(get_value(table.rows[i + 1].cells[2].firstChild));
                    var supplier_code = get_value(table.rows[i + 1].cells[3].firstChild);
                    var pricelist_id = collection[i].id.substr(3);
                    var supplier_product_code = get_value(table.rows[i + 1].cells[1].firstChild);
                    map_ids.push(product_name);
                    map_ids.push(supplier_code);
                    map_ids.push(pricelist_id);
                    map_ids.push(supplier_product_code);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    update_tables();
                }
            }
            var sel = document.getElementById("product_cat").selectedIndex;
            var category_name = document.getElementById("product_cat").options[sel].innerHTML;
            var request = "catalog-map-post.php?operation=create_products&category_name=" + encodeURI(category_name) +
                "&create_info=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function map_products() {
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("map_table");
            var map_ids = new Array();
//        var map_ids_remote = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
//                var product_name = get_value(table.rows[i+1].cells[2].firstChild);
//                var supplier_product_code = get_value(table.rows[i+1].cells[1].firstChild);
//                var supplier_code = get_value(table.rows[i+1].cells[3].firstChild);
                    var sel = table.rows[i + 1].cells[4].firstChild;
                    if (sel.selectedIndex == -1) {
                        alert("לא נבחר מוצר עבור " + table.rows[i + 1].cells[2].innerHTML);
                        continue;
                    }
                    var product_id = sel.options[sel.selectedIndex].value;
                    var pricelist_id = collection[i].id.substr(3);
//                map_ids.push(product_name);
//                map_ids.push(supplier_code);
                    var site = <?php print MultiSite::LocalSiteID(); ?>; // local
                    if (table.rows[i + 1].cells.length > 6) {
                        // Handle remote
                        site = table.rows[i + 1].cells[6].innerHTML;

//                    if (map_ids_remote[remote_site] == null)
//                        map_ids_remote[remote_site] = new Array();
//                    map_ids_remote[remote_site].push(product_id);
//                    map_ids_remote[remote_site].push(pricelist_id);
                    }
                    // Handle local
                    map_ids.push(site);
                    map_ids.push(product_id);
                    map_ids.push(pricelist_id);
//                map_ids.push(supplier_product_code);
                }
            }
            //alert (map_ids.join());
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    update_tables();
                }
            }
            var request = "catalog-map-post.php?operation=map&ids=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function map_hide() {
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var pricelist_id = collection[i].id.substr(3);
                    map_ids.push(pricelist_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    update_tables();
                }
            }
            var request = "catalog-map-post.php?operation=hide&ids=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function remove_map_products() {
            var collection = document.getElementsByClassName("invalid_map_checkbox");
            var table = document.getElementById("invalid_map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var map_id = get_value(table.rows[i + 1].cells[1].firstChild);
                    map_ids.push(map_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    update_tables();
                }
            }
            var request = "catalog-map-post.php?operation=remove_map&id_to_remove=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function update_tables() {
            // Needed mapping
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById("map_table");
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = "catalog-map-post.php?operation=get_unmapped";

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            // Unneeded mapping
//
//        xmlhttp1 = new XMLHttpRequest();
//        xmlhttp1.onreadystatechange = function()
//        {
//            // Wait to get query result
//            if (xmlhttp1.readyState==4 && xmlhttp1.status==200)  // Request finished
//            {
//                table = document.getElementById("invalid_map_table");
//                table.innerHTML = xmlhttp1.response;
//            }
//        }
//        var request1 = "catalog-map-post.php?operation=get_invalid_mapped";
//        xmlhttp1.open("GET", request1, true);
//        xmlhttp1.send();

        }
        function select_all_toggle() {
            var is_on = document.getElementById("select_all").checked;
            var collection = document.getElementsByClassName("product_checkbox");
            for (var i = 0; i < collection.length; i++) {
                collection[i].checked = is_on;
            }
        }

        function selected(sel) {
            var pricelist_id = sel.id;
            document.getElementById("chk" + pricelist_id).checked = true;

        }

    </script>
</header>
<body onload="update_tables()">
<input id="select_all" type="checkbox" onclick="select_all_toggle()">בחר הכל</button>

<button id="btn_hide" onclick="map_hide()">הסתר</button>
<button id="btn_map" onclick="map_products()">שמור מיפוי</button>
<button id="btn_create" onclick="create_products()">צור מוצרים</button>
<?php
print_category_select( "product_cat" );
?>

<table id="map_table">
</table>

<!--<button id="btn_remove_map" onclick="remove_map_products()">הסר מיפוי</button>-->

<table id="invalid_map_table">
</table>

</body>

</html>