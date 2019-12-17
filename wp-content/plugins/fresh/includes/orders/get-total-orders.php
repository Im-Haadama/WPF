<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/10/16
 * Time: 18:06
 */
if ( ! defined( "FRESH_INCLUDES" ) ) {
	define( 'FRESH_INCLUDES', dirname(dirname( dirname( __FILE__ ) ) ) );
}

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once(FRESH_INCLUDES . '/wp-config.php');
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );

require_once( FRESH_INCLUDES . '/core/gui/inputs.php' );

print header_text( false );

$supplier_id = get_param("supplier_id");

?>
<html dir="rtl" lang="he">
<header>
    <script type="text/javascript" src="/core/gui/client_tools.js"></script>
    <script>
        function moveNext(prod_id) {
            if (window.event.keyCode !== 13) return;
            let this_row = window.event.currentTarget.parentElement.parentElement;
            if (this_row.nextElementSibling) // have next row
                this_row.nextElementSibling.cells[4].focus();
            // var row = document.getElementById("inv_" + prod_id).parentElement.parentElement.rowIndex;
            // var next = document.getElementById("ordered_items").rows[row + 1].cells[4];
            // if (next) next.firstElementChild.focus();
        }
        function change_inv(prod_id) {

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    if (xmlhttp.response !== "done")
                        alert(xmlhttp.response);
                }
            }
            var prod_ids = new Array();
            var q = get_value_by_name("inv_" + prod_id);
            prod_ids.push(prod_id, q);
            var request = "../inventory/inv-post.php?operation=save_inv&data=" + prod_ids.join();
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function show_totals() {
            xmlhttp = new XMLHttpRequest();
            var filter = document.getElementById("filter_zero").checked;
            var stock = document.getElementById("filter_stock").checked;
            var request = "get-total-orders-post.php?operation=show_required";
            if (filter) request = request + "&filter_zero=1";
            if (stock) request = request + "&filter_stock=1";
            <?php if (isset($supplier_id)) print 'request += "&supplier_id=' . $supplier_id . '"'; ?>

            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    table = document.getElementById("ordered_items");
                    table.innerHTML = xmlhttp.response;

                    update_selector();
                    document.getElementById("btn_create_order").disabled = false;

                }
            }
            xmlhttp.send();
        }

        function update_selector() {
            // var table = document.getElementById("ordered_items");
            // var terms = [];
            // for (var i = 1; i < table.rows.length; i++) {
            //     var term = table.rows[i].cells[7].innerHTML;
            //     var found = false;
            //     for (var j = 0; j < terms.length; j++)
            //         if (terms[j] == term) found = true;
            //     if (!found) terms[j] = term;
            // }
            //
            // var selector = document.getElementById("select_term");
            //
            // //selector.options.length = 0;
            // for (i = 0; i < terms.length; i++) {
            //     var option = document.createElement("option");
            //     option.text = terms[i];
            //     selector.options.add(option);
            // }
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

        function select_single() {
            var table = document.getElementById("ordered_items");
            for (var i = 1; i < table.rows.length - 1; i++) {
                if (table.rows[i].cells[7].firstChild.options.length == 2)
                    table.rows[i].cells[7].firstChild.selectedIndex = 1;
            }
        }

        function create_supplies() {
            document.getElementById("btn_create_supplies").disabled = true;
            var table = document.getElementById('ordered_items');
            // var lines = table.rows.length;
            var collection = document.getElementsByClassName("product_checkbox");

            var sel = document.getElementById("supplier_id");
            var supplier_id = sel.options[sel.selectedIndex].value;

            // Request header
            var request = "../supplies/supplies-post.php?operation=create_supplies&params=";
            var params = new Array();

            // Add the data.
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) { // Add to suppplies
                    var prod_id = collection[i].id.substring(3);
                    params.push(prod_id);

                    var supplier = get_value_by_name("sup_" + prod_id);
                    params.push(supplier);

                    var quantity = get_value_by_name("qua_" + prod_id);
                    params.push(quantity);

                    var units = 0;
//                    var units_text = get_value(table.rows[i + 1].cells[3].innerText);
//                    if (units_text.length >= 1) units = eval(units_text);
                    params.push(units);
                }
            }
            // Call the server to save the supply

            request = request + params.join();

            xmlhttp = new XMLHttpRequest();
            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                document.getElementById("logging").innerHTML = xmlhttp.response;
                show_totals();
                // location.reload();
            }

            xmlhttp.send();
        }

        function create_delta() {
            document.getElementById("btn_create_delta").disabled = true;
            // Request header
            var request = "../supplies/supplies-post.php?operation=create_delta";

            xmlhttp = new XMLHttpRequest();
            xmlhttp.open("GET", request, true);
            xmlhttp.onreadystatechange = function () {
                document.getElementById("btn_create_delta").disabled = false;

                document.getElementById("logging").innerHTML = xmlhttp.response;
                show_totals();
                // location.reload();
            }

            xmlhttp.send();
        }

        function createSupply(supplier_id) {
            document.getElementById("btn_create_order").disabled = true;
            var table = document.getElementById('ordered_items' + supplier_id);
            // var lines = table.rows.length;
            var collection = document.getElementsByClassName("product_checkbox" + supplier_id);

            //          var sel = document.getElementById("supplier_id");
//            var supplier_id = sel.options[sel.selectedIndex].value;

            // Request header
            var request = "../supplies/supplies-post.php?operation=create_supply&supplier_id=" + supplier_id + "&create_info=";
            var prod_ids = new Array();

            // Add the data.
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) { // Add to suppply
                    var prod_id = collection[i].id.substring(3); // remove chk_
                    prod_id = prod_id.substr(0, prod_id.indexOf("_")); // remove supplier id
                    prod_ids.push(prod_id);

                    var quantity = get_value_by_name("qua_" + prod_id);
                    prod_ids.push(quantity);

                    var units = 0;
//                    var units_text = get_value(table.rows[i + 1].cells[3].innerText);
//                    if (units_text.length >= 1) units = eval(units_text);
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

        function line_selected(check_id) {
            var chk = document.getElementById("chk" + check_id);
            chk.checked = true;
        }
        function selectSupplier(s) {
            var pricelist_id = s.id.substr(4);
            document.getElementById("chk" + pricelist_id).checked = true;
        }

        function draft_products()
        {
            var collection = document.getElementsByClassName("product_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var line_id = collection[i].id.substr(3).slice(0, -1);  // remove _ separating from null supplier_id
                    line_id =

                    params.push(line_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location.reload();
                }
            }
            var request = "../catalog/catalog-update-post.php?operation=draft_items&update_ids=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }

    </script>
</header>
<body onload="show_totals()">

<center><h1>פריטים להזמנות</h1></center>
<input type="checkbox" id="filter_zero" onclick='show_totals();'>סנן מוזמנים<br>
<input type="checkbox" id="filter_stock" onclick='show_totals();'>סנן פרטים במלאי<br>
<div id="ordered_items"></div>

<?php
print gui_button( "btn_create_delta", "create_delta()", "השלם פערים" );
print gui_button( "btn_create_supplies", "create_supplies()", "צור הספקות" ); ?>

<br/>
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
<button id="btn_create_supply_single_supplier" onclick="select_single()">ספק יחיד</button>

<div id="logging"></div>

</body>
</html>
