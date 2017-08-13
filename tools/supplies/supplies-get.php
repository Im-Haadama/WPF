<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 17:38
 */
require_once( '../tools.php' );
// require_once( '../header_no_login.php' );
require_once( 'supplies.php' );

print header_text( true );
?>

<html dir="rtl">
<header>
    <script>
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
            var sel = document.getElementById("supplier_id");
            var id = sel.options[sel.selectedIndex].value;
            var name = get_value(document.getElementById("product_name"));
            var price = get_value(document.getElementById("price"));
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    change_supplier();
                }
            }
            var request = "pricelist-post.php?operation=add_price&product_name=" + name + '&price=' + price +
                '&supplier_id=' + id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
</header>
<body onload="updateDisplay()">
<button id="btn_save" onclick="savePrices()">שמור עדכונים</button>
<button id="btn_delete" onclick="delSupplies()">מחק פריטים</button>
<button id="btn_close" onclick="deleteItems()">בטל</button>
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