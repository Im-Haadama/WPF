<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 03/07/15
 * Time: 11:53
 */
require_once( '../tools_wp_login.php' );
require_once( "../gui/inputs.php" );

// To map item from price list to our database the shop manager select item from the price list
// and product_id. The triplet: product_id, supplier_id and product_code are sent as saved
// in im_supplier_products

print header_text();
?>
<header>
    <script>
		<?php
		$filename = __DIR__ . "/../client_tools.js";
		$handle = fopen( $filename, "r" );
		$contents = fread( $handle, filesize( $filename ) );
		print $contents;
		?>
        function select_pr() {
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("change_price_products_table");

            for (var i = 0; i < collection.length; i++) {
                var sel = table.rows[i + 1].cells[4].firstChild;
                if (sel.length == 1) {
                    sel.selectedIndex = 0;
                    collection[i].checked = true;
                }
            }
        }

        function select_suppliers() {
            var collection = document.getElementsByClassName("product_checkbox");
            var table = document.getElementById("change_price_products_table");
            var update_price_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_code = table.rows[i + 1].cells[1].firstChild.nodeValue;
                    // var new_price = table.rows[i+1].cells[4].firstChild.value;
                    var sel = table.rows[i + 1].cells[4].firstChild;
                    var pricelist_id = sel.options[sel.selectedIndex].getAttribute("data-pricelist-id");
                    // var supplier_price = sel.options[sel.selectedIndex].getAttribute("data-supplier-price");
                    update_price_ids.push(product_code);
                    // update_price_ids.push(new_price);
                    update_price_ids.push(pricelist_id);
                    // update_price_ids.push(supplier_price);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    show_prices();
                }
            }
            var request = "catalog-update-post.php?operation=select_suppliers&update_ids=" + update_price_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function draft_items() {
            var collection = document.getElementsByClassName("remove_product_checkbox");
            var table = document.getElementById("off_products_table");
            var draft_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_code = table.rows[i + 1].cells[1].firstChild.nodeValue;
                    draft_ids.push(product_code);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    show_prices();
                }
            }
            var request = "catalog-update-post.php?operation=draft_items&update_ids=" + draft_ids.join();
            // alert(request);
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function publish_items() {
            var collection = document.getElementsByClassName("publish_product_checkbox");
            var table = document.getElementById("new_products_table");
            var ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var product_code = table.rows[i + 1].cells[1].firstChild.nodeValue;
                    ids.push(product_code);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    show_prices();
                }
            }
            var request = "catalog-update-post.php?operation=publish_items&update_ids=" + ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function show_prices() {
            var sale = get_value(document.getElementById("with_sale"));

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById("change_price_products_table");
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = "catalog-update-post.php?operation=get_prices_change&sale=" + sale;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    table = document.getElementById("off_products_table");
                    table.innerHTML = xmlhttp1.response;
                }
            }
            request = "catalog-update-post.php?operation=get_items_to_remove";
            xmlhttp1.open("GET", request, true);
            xmlhttp1.send();

            xmlhttp2 = new XMLHttpRequest();
            xmlhttp2.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp2.readyState == 4 && xmlhttp2.status == 200)  // Request finished
                {
                    table = document.getElementById("new_products_table");
                    table.innerHTML = xmlhttp2.response;
                }
            }
            request = "catalog-update-post.php?operation=get_items_to_publish";
            xmlhttp2.open("GET", request, true);
            xmlhttp2.send();

        }
    </script>
</header>
<body onload="show_prices()">

<button id="btn_map" onclick="select_suppliers()">עדכן מחירים</button>
<button id="btn_select" onclick="select_pr()">סמן פריטים עם מחיר בודד</button>

<?php print "<br/>" . gui_checkbox( "with_sale", "", "", "onclick=\"select_suppliers()\"" ); ?> כלול מוצרים במבצע

<div style="text-align: center;"><h1>מוצרים לעדכון</h1></div>
<table id="change_price_products_table">
</table>

<h1>פריטים לפרסום</h1>
<table id="new_products_table">
</table>
<button id="btn_publish" onclick="publish_items()">פרסם פריט )טיוטא)</button>


<h1>פריטים להסרה</h1>
<table id="off_products_table">
</table>
0
<button id="btn_draft" onclick="draft_items()">הסר מהחנות (הפוך לטיוטא)</button>
</body>

</html>