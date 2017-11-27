<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/07/15
 * Time: 15:25
 */

require_once( '../r-shop_manager.php' );
?>

<html dir="rtl">
<header>
    <script>
        var post_url = "bundles-post.php";
        var class_name = "bundle";
        var table_name = class_name + "_list";

        function get_value(element) {
            if (element.tagName == "INPUT") {
                return element.value;
            } else {
                return element.nodeValue;
            }
        }

        function save_items() {
            var table = document.getElementById(table_name);
            var sel = document.getElementById("supplier_id");
            var id = sel.options[sel.selectedIndex].value;

            var collection = document.getElementsByClassName(class_name + "_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var name = get_value(table.rows[i + 1].cells[1].firstChild);
                    var new_price = get_value(table.rows[i + 1].cells[3].firstChild);
                    var sel = document.getElementById("supplier_id");
                    var supplier_id = sel.options[sel.selectedIndex].value;

                    params.push(name, new_price, supplier_id);
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
            var request = post_url + "?operation=update_price&supplier_id=" + id + "&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function del_items() {
            var table = document.getElementById(table_name);

            var collection = document.getElementsByClassName(class_name + "_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id;

                    params.push(id);
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
            var request = post_url + "?operation=delete_item" + "&params=" + params;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function updateDisplay() {
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById(table_name);
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = post_url + "?operation=get_bundles";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function add_item() {
            var prod_id = get_value(document.getElementById("item_id"));
            var quantity = get_value(document.getElementById("quantity"));
            var margin = get_value(document.getElementById("margin"));
            var bundle_prod_id = get_value(document.getElementById("bundle_prod_id"));

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    updateDisplay();
                }
            }
            var request = post_url + "?operation=add_item&product_id=" + prod_id + '&quantity=' + quantity +
                '&margin=' + margin + '&bundle_prod_id=' + bundle_prod_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>
</header>
<body onload="updateDisplay()">
<button id="btn_save" onclick="save_items()">שמור עדכונים</button>
<button id="btn_delete" onclick="del_items()">מחק פריטים</button>
<table id="bundle_list" border="1">
    <table id="other_product_list">
        <div>הוסף מחיר למוצר חדש
            מזהה פריט
            <input id="item_id">
            מזהה מארז
            <input id="bundle_prod_id">
            כמות
            <input id="quantity">
            מרווח
            <input id="margin">
            <button id="btn_add_bundle" onclick="add_item()">הוסף מארז</button>
        </div>

</body>
</html>