<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/10/16
 * Time: 18:06
 */
require_once( '../tools_wp_login.php' );
require_once( '../header.php' );

?>
<html dir="rtl" lang="he">
<header>
    <script type="text/javascript" src="../client_tools.js"></script>
    <script>
        function show_totals() {
            xmlhttp = new XMLHttpRequest();
            var filter = document.getElementById("filter_zero").checked;
            var request = "get-total-orders-post.php?operation=show_required";
            if (filter) request = request + "&filter_zero=1";

            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    table = document.getElementById("ordered_items");
                    table.innerHTML = xmlhttp.response;

                    update_selector();
                }
            }
            xmlhttp.send();
        }

        function update_selector() {
            var table = document.getElementById("ordered_items");
            var terms = [];
            for (var i = 1; i < table.rows.length; i++) {
                var term = table.rows[i].cells[7].innerHTML;
                var found = false;
                for (var j = 0; j < terms.length; j++)
                    if (terms[j] == term) found = true;
                if (!found) terms[j] = term;
            }

            var selector = document.getElementById("select_term");

            selector.options.length = 0;
            for (i = 0; i < terms.length; i++) {
                var option = document.createElement("option");
                option.text = terms[i];
                selector.options.add(option);
            }
        }

        function create_single() {
            xmlhttp = new XMLHttpRequest();
            var request = "get-total-orders-post.php?operation=create_single";

            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    if (xmlhttp.response.length > 2) {
                        document.getElementById("logging").innerText = xmlhttp.response;
                    }
                }
            }
            xmlhttp.send();
        }

        function createSupply() {
            var table = document.getElementById('ordered_items');
            // var lines = table.rows.length;
            var collection = document.getElementsByClassName("product_checkbox");

            var sel = document.getElementById("supplier_id");
            var supplier_id = sel.options[sel.selectedIndex].value;

            // Request header
            var request = "../supplies/supplies-post.php?operation=create_supply&supplier_id=" + supplier_id + "&create_info=";
            var prod_ids = new Array();

            // Add the data.
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) { // Add to suppply
                    var prod_id = collection[i].id.substring(3);
                    prod_ids.push(prod_id);

                    var quantity = eval(get_value(table.rows[i + 1].cells[6].innerText));
                    prod_ids.push(quantity);

                    var units = 0;
                    var units_text = get_value(table.rows[i + 1].cells[3].innerText);
                    if (units_text.length >= 1) units = eval(units_text);
                    prod_ids.push(units);
                }
            }
            // Call the server to save the supply

            request = request + prod_ids.join();

            xmlhttp = new XMLHttpRequest();
            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                document.getElementById("logging").innerHTML = xmlhttp.response;
                show_totals();
                // location.reload();
            }

            xmlhttp.send();
        }

        function selectProds() {
            var table = document.getElementById('ordered_items');
            // var lines = table.rows.length;
            var collection = document.getElementsByClassName("product_checkbox");

            var sel = document.getElementById("select_term");
            var supplier_name = sel.options[sel.selectedIndex].innerHTML.trim();

            // select the prods
            for (var i = 0; i < collection.length; i++) {
                var prod_supplier_name = table.rows[i + 1].cells[7].innerHTML;

                if (supplier_name == prod_supplier_name)
                    collection[i].checked = true;
            }
        }

    </script>
</header>
<body onload="show_totals()">

<center><h1>סך הפריטים שהוזמנו</h1></center>
<input type="checkbox" id="filter_zero" onclick='show_totals();'>סנן מוזמנים<br>
<table id="ordered_items"></table>


צור הזמנה לספק
<select id="supplier_id">
	<?php

	$sql1 = 'SELECT id, supplier_name FROM im_suppliers';

// Get line options
	$result = sql_query( $sql1 );
	while ( $row1 = mysqli_fetch_row( $result ) ) {
		print "<option value = \"" . $row1[0] . "\" > " . $row1[1] . "</option>";
	}
	?>
</select>

<button id="btn_create_order" onclick="createSupply()">צור!</button>
<br/>
בחר פריטים של קטגוריה
<select id="select_term"></select>
<button id="btn_select_prods" onclick="selectProds()">בחר</button>

<br/>
<button id="btn_create_supply_single_supplier" onclick="create_single()">ספק יחיד</button>

<div id="logging"></div>

</body>
</html>
