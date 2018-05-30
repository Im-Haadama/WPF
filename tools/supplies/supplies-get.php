<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 17:38
 */
require_once( '../r-shop_manager.php' );
// require_once( '../header_no_login.php' );
require_once( 'supplies-post.php' );
require_once( "../account/gui.php" );

if ( ! isset( $_GET["week"] ) ) {
	$week = sunday( date( "Y-m-d" ) )->format( "Y-m-d" );
}
if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
	print gui_hyperlink( "שבוע הבא", "get_all.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
}

print gui_hyperlink( "שבוע קודם", "supplies-get.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

print header_text( false, false );

print gui_button( "btn_new", "show_create_item()", "אספקה חדשה" );
?>
<div id="new_item" style="display: none; border:1px solid black; padding: 30px">
	<?php
	print gui_header( 1, "יצירת אספקה" );
	print gui_table( array(
		array(
			gui_header( 2, "בחר ספק" ),
			gui_header( 2, "בחר מועד" ),
			gui_header( 2, "בחר משימה" )
		),
		array(
			gui_select_supplier(),
			gui_input_date( "date", "" ),
			gui_select_mission( "new_mission", "", "" )
			// gui_select_mission( "mis_new")
		)
	) );

	print gui_header( 2, "בחר מוצרים" );
	print gui_datalist( "items", "im_products", "post_title", true );

	print gui_table( array( array( "פריט", "כמות", "קג או יח" ) ),
		"supply_items" );

	print gui_button( "add_line", "add_line()", "הוסף שורה" );
	print gui_button( "add_item", "add_item()", "הוסף הספקה" );

	?>
</div>
<?php

if ( isset( $_GET["week"] ) ) {
	print display_supplies( $_GET["week"] );
}
?>
<script type="text/javascript" src="/agla/client_tools.js"></script>

    <script>
        function mission_changed(supply_id) {
            var mis = document.getElementById("mis_" + supply_id);
            var mission_id = get_value(mis);
            execute_url("supplies-post.php?operation=set_mission&supply_id=" + supply_id + "&mission_id=" + mission_id);
        }
        function show_create_item() {
            var new_order = document.getElementById("new_item");
            new_order.style.display = 'inline-block';
            add_line();
            document.getElementById("supplier_select").focus();
        }

        function print_selected() {
            var table = document.getElementById('supplies_list');
            var sel = document.getElementById("supplier_id");

            var collection = document.getElementsByClassName("supply_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var supply_id = collection[i].id.substr(3);

                    params.push(supply_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = "supplies-post.php?operation=print&id=" + params;
            window.open(request);
        }
        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }

        function delSupplies() {
            var table = document.getElementById('supplies_list');

            var collection = document.getElementsByClassName("supply_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var supply_id = collection[i].id.substr(3);

                    params.push(supply_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {

                    updateDisplay();
                }
            }
            var request = "supplies-post.php?operation=delete_supplies&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function deleteItems() {
            var table = document.getElementById('supplies_list');

            var collection = document.getElementsByClassName("supply_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var supply_id = collection[i].id.substr(3);

                    params.push(supply_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = "supplies-post.php?operation=delete_supplies&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function sendItems() {
            var table = document.getElementById('supplies_list');

            var collection = document.getElementsByClassName("supply_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var supply_id = collection[i].id.substr(3);

                    params.push(supply_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var http_text = xmlhttp.responseText.trim();
                    document.getElementById("logging").innerHTML = http_text;
                    updateDisplay();
                }
            }
            var request = "supplies-post.php?operation=send&id=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function mergeItems() {
            var collection = document.getElementsByClassName("supply_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var supply_id = collection[i].id.substr(3);

                    params.push(supply_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    http_text = xmlhttp.responseText.trim();
                    if (http_text.length > 2)
                        document.getElementById("logging").innerHTML = http_text;
                    else
                        updateDisplay();
                }
            }
            var request = "supplies-post.php?operation=merge_supplies&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function updateDisplay() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    supplies_list.innerHTML = xmlhttp.response;
                    // document.getElementById("logging").innerHTML= "";
                }
            }
            var request = "supplies-post.php?operation=get_all";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function add_item() {
            document.getElementById('add_item').disabled = true;
            var supplier_name = get_value(document.getElementById("supplier_select"));
            var supplier_id = supplier_name.substr(0, supplier_name.indexOf(")"));
            if (!(supplier_id > 0)) {
                alert("יש לבחור ספק, כולל מספר מזהה מהרשימה");
                document.getElementById('add_item').disabled = false;
                return;
            }
            var ids = [];

            var item_table = document.getElementById("supply_items");
            var line_number = 0;

            for (var i = 1; i < item_table.rows.length; i++) {
                var prod = get_value(document.getElementById("itm_" + i));
                var prod_id = prod.substr(0, prod.indexOf(")"));
                var q = get_value(document.getElementById("qua_" + i));
                var u = get_value(document.getElementById("uni_" + i));
                if (!u > 0) u = 0;
//                $prod_id  = $ids[ $pos ];
//                $quantity = $ids[ $pos + 1 ];
//                $units    = $ids[ $pos + 2 ];
                if (q > 0) {
                    ids.push(prod_id);
                    ids.push(q);
                    ids.push(u);

                    line_number++;
                }
                // ids.push(get_value(item_table.rows[i].cells[0].innerHTML));
            }
            if (line_number === 0) {
                alert("יש לבחור מוצרים, כולל כמויות");
                return;
            }

            var date = get_value(document.getElementById("date"));

            var request = "supplies-post.php?operation=create_supply" +
                "&supplier_id=" + supplier_id +
                "&create_info=" + ids.join() +
                "&date=" + date;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    if (xmlhttp.responseText.includes("בהצלחה")) {
                        location.reload();
                    } else {
                        logging.innerHTML = xmlhttp.responseText;
                        document.getElementById('add_order').disabled = false;
                    }
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function select_unit(my_row) {
            if (event.which === 13) {
                var objs = document.getElementById("uni_" + (my_row));
                if (objs) objs.focus();
            }
        }

        function add_line() {
            var item_table = document.getElementById("supply_items");
            var line_idx = item_table.rows.length;
            var new_row = item_table.insertRow(-1);
            var product = new_row.insertCell(0);
            product.innerHTML = "<input id=\"itm_" + line_idx + "\" list=\"items\" \">";
            var quantity = new_row.insertCell(1);
            quantity.innerHTML = "<input id = \"qua_" + line_idx + "\" onkeypress=\"select_unit(" + line_idx + ")\">";
            var units = new_row.insertCell(2);
            units.innerHTML = "<input id=\"uni_" + line_idx + "\" list=\"units\", onkeypress=\"add_line(" + line_idx + ")\">";
            product.firstElementChild.focus();
        }

    </script>
</header>
<body onload="updateDisplay()">
<br/>
<br/>

<button id="btn_save" onclick="savePrices()">שמור עדכונים</button>
<button id="btn_delete" onclick="delSupplies()">מחק פריטים</button>
<!--<button id="btn_close" onclick="deleteItems()">בטל</button>-->
<button id="btn_merge" onclick="mergeItems()">מזג פריטים</button>
<button id="btn_send" onclick="sendItems()">שלח הזמנה</button>
<div id="logging" rows="6" cols="50"></div>

<div id="supplies_list" border="1">
    <!--    <table id="other_product_list">-->
    <!--        <div>-->

    <button id="btn_print" onclick="print_selected()">הדפס נבחרים</button>
    <!--        </div>-->
    <!--    </table>-->
</div>


</body>
</html>