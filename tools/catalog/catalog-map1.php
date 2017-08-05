<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( 'catalog.php' );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

?>

<html dir="rtl">
<header>

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
                    var product_name = encodeURI(get_value(table.rows[i + 1].cells[2].firstChild));
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
            var request = "catalog-map-post.php?operation=create_products&create_info=" + map_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function map_products() {
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("map_table");
            var map_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_name = get_value(table.rows[i + 1].cells[2].firstChild);
                    var supplier_product_code = get_value(table.rows[i + 1].cells[1].firstChild);
                    var supplier_code = get_value(table.rows[i + 1].cells[3].firstChild);
                    var sel = table.rows[i + 1].cells[4].firstChild;
                    var product_id = sel.options[sel.selectedIndex].value;
                    var pricelist_id = collection[i].id.substr(3);
                    map_ids.push(product_name);
                    map_ids.push(supplier_code);
                    map_ids.push(product_id);
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
            var request = "catalog-map-post.php?operation=map&map_triplets=" + map_ids.join();
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
            var request = "catalog-map-post.php?operation=get_unmapped&seq=" + Math.random();

            // Unneeded mapping
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    table = document.getElementById("invalid_map_table");
                    table.innerHTML = xmlhttp1.response;
                }
            }
            var request1 = "catalog-map-post.php?operation=get_invalid_mapped";
            xmlhttp1.open("GET", request1, true);
            xmlhttp1.send();

        }
    </script>
</header>
<body onload="update_tables()">

<button id="btn_map" onclick="map_products()">שמור מיפוי</button>
<button id="btn_create" onclick="create_products()">צור מוצרים</button>

<table id="map_table">
</table>

<button id="btn_remove_map" onclick="remove_map_products()">הסר מיפוי</button>

<table id="invalid_map_table">
</table>

</body>

</html>