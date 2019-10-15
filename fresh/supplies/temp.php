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
require_once( ROOT_DIR . "/tools/suppliers/gui.php" );

$week = get_param( "week" );

if ( ! isset( $_GET["week"] ) ) {
	$week = sunday( date( "Y-m-d" ) )->format( "Y-m-d" );
}
if ( date( 'Y-m-d' ) > date( 'Y-m-d', strtotime( $week . "+1 week" ) ) ) {
	print gui_hyperlink( "שבוע הבא", "get_all.php?week=" . date( 'Y-m-d', strtotime( $week . " +1 week" ) ) ) . " ";
}

print gui_hyperlink( "שבוע קודם", "supplies-get.php?week=" . date( 'Y-m-d', strtotime( $week . " -1 week" ) ) );

print header_text( false, false );

print gui_button( "btn_new", "show_create_item()", "הספקה חדשה" );
?>

	</div>

<?php

if ( isset( $_GET["week"] ) ) {
	print SuppliesTable( $_GET["week"] );
}
?>
	<script type="text/javascript" src="/niver/gui/client_tools.js"></script>
	<script type="text/javascript" src="supply.js"></script>

	<script>
        function change_supplier() {
            var supplier_id = get_value_by_name("supplier_select");
            var upcsv = document.getElementById("upcsv");
            let date = get_value_by_name("date");
            upcsv.action = "/tools/supplies/supplies-post.php?operation=create_from_file&supplier_id=" + supplier_id + "&date=" + date;
        }
        function show_create_item() {
            var new_order = document.getElementById("new_item");
            new_order.style.display = 'inline-block';

            document.getElementById("supplies_list").style.display = 'none';
            document.getElementById("actions").style.display = 'none';
            document.getElementById("btn_new").style.visibility = 'hidden';

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
                        add_message(http_text);
                    else
                        updateDisplay();
                }
            }
            var request = "supplies-post.php?operation=merge_supplies&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function updateDisplay() {
            change_supplier(); // Set the upload button
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
            var supplier_id = get_value_by_name("supplier_select");
//            var supplier_id = supplier_name.substr(0, supplier_name.indexOf(")"));
            if (!(supplier_id > 0)) {
                alert("יש לבחור ספק, כולל מספר מזהה מהרשימה");
                document.getElementById('add_item').disabled = false;

                return;
            }
            var ids = [];

            var item_table = document.getElementById("supply_items");
            var line_number = 0;

            for (var i = 1; i < item_table.rows.length; i++) {
                var prod_id = get_value_by_name("itm_" + i);
                var q = get_value_by_name("qua_" + i);
                var u = get_value_by_name("uni_" + i);
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
                enable_btn('add_item');

                return;
            }

            var date = get_value(document.getElementById("date"));

            var request = "supplies-post.php?operation=create_supply" +
                "&supplier_id=" + supplier_id +
                "&create_info=" + ids.join() +
                "&date=" + date;

            reset_message();
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                    add_message(xmlhttp.responseText);
                    var id = xmlhttp.responseText.match(/\d+/)[0];
                    document.getElementById('add_item').disabled = false;
                    if (id > 0) {
                        window.location.href = "supply-get.php?id=" + id;
                        // location.reload();
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

	<div id="actions">
		<button id="btn_save" onclick="savePrices()">שמור עדכונים</button>
		<button id="btn_delete" onclick="delSupplies()">מחק פריטים</button>
		<!--<button id="btn_close" onclick="deleteItems()">בטל</button>-->
		<button id="btn_merge" onclick="mergeItems()">מזג פריטים</button>
		<button id="btn_send" onclick="sendItems()">שלח הזמנה</button>
	</div>
	<?php print gui_textarea( "log", "", "" ); ?>

	<div id="supplies_list" border="1">
		<!--    <table id="other_product_list">-->
		<!--        <div>-->

		<button id="btn_print" onclick="print_selected()">הדפס נבחרים</button>
		<!--        </div>-->
		<!--    </table>-->
	</div>


	</body>
	</html><?php
