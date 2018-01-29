<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../r-shop_manager.php' );
require_once( 'catalog.php' );
require_once( '../gui.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

?>
<html dir="rtl" lang="he">
<header>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script type="text/javascript" src="../client_tools.js"></script>
    <script>
        function create_term() {
            var t = document.getElementById("create_term");
            var category_name = t.options[t.selectedIndex].text;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    update_tables();
                }
            }
            var request = "catalog-map-post.php?operation=create_term&category_name=" + encodeURI(category_name);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function create_products() {
            var sel = document.getElementById("product_cat").selectedIndex;
            var category_name = document.getElementById("product_cat").options[sel].innerHTML;
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_name = encodeURIComponent(get_value(table.rows[i + 1].cells[2].firstChild.data));
                    var supplier_code = get_value(table.rows[i + 1].cells[3].firstChild);
                    var pricelist_id = collection[i].id.substr(3);
                    var supplier_product_code = get_value(table.rows[i + 1].cells[1].firstChild);
                    map_ids.push(product_name);
                    map_ids.push(supplier_code.data);
                    map_ids.push(pricelist_id);
                    map_ids.push(supplier_product_code.data);
                    // Send every 10 products
                    if (map_ids.length > 40) {
                        xmlhttp = new XMLHttpRequest();
                        var request = "catalog-map-post.php?operation=create_products&category_name=" + encodeURI(category_name) +
                            "&create_info=" + map_ids.join();
                        xmlhttp.open("GET", request, true);
                        xmlhttp.send();
                        map_ids.length = 0;
                    }
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    update_tables();
                }
            }
            request = "catalog-map-post.php?operation=create_products&category_name=" + encodeURI(category_name) +
                "&create_info=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function select_term_products() {
            var t = document.getElementById("create_term");
            var term = t.options[t.selectedIndex].text;
            var table = document.getElementById("map_table");
            var collection = document.getElementsByClassName("product_checkbox");
            var map_ids = new Array();
            for (var i = 0; i < table.rows.length - 1; i++) {
                var terms = get_value(table.rows[i + 1].cells[6].firstChild).data;
                if (terms && terms.indexOf(term) >= 0)
                    collection[i].checked = true;
            }
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
                    // TODO: fix that
//                    if (table.rows[i + 1].cells.length > 6) {
//                        // Handle remote
//                        site = table.rows[i + 1].cells[6].innerHTML;
//
////                    if (map_ids_remote[remote_site] == null)
////                        map_ids_remote[remote_site] = new Array();
////                    map_ids_remote[remote_site].push(product_id);
////                    map_ids_remote[remote_site].push(pricelist_id);
//                    }
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

            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    var terms = document.getElementById("create_term");
                    terms.innerHTML = xmlhttp1.response;
                }
            }
            var request1 = "catalog-map-post.php?operation=get_unmapped_terms";

            xmlhttp1.open("GET", request1, true);
            xmlhttp1.send();

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

        function select_detailed() {
            var is_on = document.getElementById("select_details").checked;
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
<input id="select_details" type="checkbox" onclick="select_detailed()">בחר מפורטים</button>

<button id="btn_hide" onclick="map_hide()">הסתר</button>
<button id="btn_map" onclick="map_products()">שמור מיפוי</button>
<button id="btn_create" onclick="create_products()">צור מוצרים</button>
<datalist id="unmapped_terms"></datalist>

<?php
print_category_select( "product_cat" );

print gui_header( 1, "יצירת מוצרים" );

print gui_select( "create_term", "", array(), "", "" );
// print gui_select_datalist("term", "t", array(), "");

print gui_button( "btn_select_term_items", "select_term_products()", "בחר" );

print gui_button( "btn_create_term", "create_term()", "צור קטגוריה" );

print gui_header( 1, "פריטים לא ממופים" );
?>


<table id="map_table">
</table>

<!--<button id="btn_remove_map" onclick="remove_map_products()">הסר מיפוי</button>-->

<table id="invalid_map_table">
</table>

</body>

</html>