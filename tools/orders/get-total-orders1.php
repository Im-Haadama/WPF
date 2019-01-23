<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/10/16
 * Time: 18:06
 */

require_once( '../r-shop_manager.php' );

?>
<html dir="rtl" lang="he">
<header>
    <script>
        function show_totals() {
            xmlhttp = new XMLHttpRequest();
            var filter = document.getElementById("filter_zero").checked;
            var request = "get-total-orders-post.php";
            if (filter) request = request + "?filter_zero=1";

            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    table = document.getElementById("ordered_items");
                    table.innerHTML = xmlhttp.response;
                }
            }
            xmlhttp.send();
        }

        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }

        function createSupply() {
            var table = document.getElementById('ordered_items');
            // var lines = table.rows.length;
            var collection = document.getElementsByClassName("product_checkbox");

            var sel = document.getElementById("supplier_id");
            var supplier_id = sel.options[sel.selectedIndex].value;

            // Request header
            var request = "../supplies/supplies-post.php?operation=create_supply&supplier_id=" + supplier_id + "&create_info=";
            var map_ids = new Array();

            // Add the data.
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) { // Add to suppply
                    var prod_id = collection[i].id.substring(3);
                    map_ids.push(prod_id);

                    var quantity = eval(get_value(table.rows[i + 1].cells[5].firstChild));
                    map_ids.push(quantity);
                }
            }
            // Call the server to save the supply

            request = request + map_ids.join();

            xmlhttp = new XMLHttpRequest();
            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                show_totals();
                // location.reload();
            }

            xmlhttp.send();
        }

        function selectProds() {
            var table = document.getElementById('ordered_items');
            // var lines = table.rows.length;
            var collection = document.getElementsByClassName("product_checkbox");

            var sel = document.getElementById("supplier_id");
            var supplier_name = sel.options[sel.selectedIndex].innerHTML;

            // select the prods
            for (var i = 0; i < collection.length; i++) {
                var prod_supplier_name = table.rows[i + 1].cells[6].innerHTML;

                if (supplier_id == prod_supplier_id)
                    collection[i].checked = true;
            }
        }

    </script>
</header>
<body onload="show_totals()">

<center><h1>סך הפריטים שהוזמנו</h1></center>
<input type="checkbox" id="filter_zero" onclick='show_totals();'>סנן מוזמנים<br>
<table id="ordered_items"></table>
<button id="btn_create_order" onclick="createSupply()">צור!</button>
צור הזמנה לספק
<select id="supplier_id"" >
<button id="btn_select_prods" onclick="selectProds()">בחר</button>
<?php

$sql1 = 'SELECT id, supplier_name FROM im_suppliers';

// Get line options
$export1 = mysql_query( $sql1 );
while ( $row1 = mysql_fetch_row( $export1 ) ) {
	print "<option value = \"" . $row1[0] . "\" > " . $row1[1] . "</option>";
}

?>
</select>

</body>
</html>
