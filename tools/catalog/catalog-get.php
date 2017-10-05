<?php
require_once( '../tools_wp_login.php' );
require_once( '../gui/wp_inputs.php' );
require_once( '../gui/inputs.php' );

print header_text( true );
?>

<script type="text/javascript" src="../client_tools.js"></script>
    <script>

        function select_category() {

        }
        function set_category() {
            var collection = document.getElementsByClassName("product_checkbox");
            var prod_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);
                    prod_ids.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    searchProducts();
                }
            }
            var category = get_value(document.getElementById("cat_1"));
            var request = "catalog-db-query.php?operation=set_category&category=" + encodeURI(category) + "&prod_ids=" + prod_ids;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function set_vat() {
            var collection = document.getElementsByClassName("product_checkbox");
            var prod_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);
                    prod_ids.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    searchProducts();
                }
            }
            var request = "catalog-db-query.php?operation=set_vat&update_ids=" + prod_ids;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function publish() {
            var collection = document.getElementsByClassName("product_checkbox");
            var prod_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);
                    prod_ids.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    searchProducts();
                }
            }
            var request = "catalog-update-post.php?operation=publish_items&update_ids=" + prod_ids;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function set_supplier() {
            var collection = document.getElementsByClassName("product_checkbox");
            var prod_ids = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    var id = collection[i].id.substr(3);
                    prod_ids.push(id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    searchProducts();
                }
            }
            var supplier_name = document.getElementById('supplier_name').value;
            var request = "catalog-db-query.php?operation=set_supplier&supplier_name=" + supplier_name + "&update_ids=" + prod_ids;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function select_all_toggle() {
            var is_on = document.getElementById("select_all").checked;
            var collection = document.getElementsByClassName("product_checkbox");
            for (var i = 0; i < collection.length; i++) {
                collection[i].checked = is_on;
            }
        }

        function searchProducts() {
            var query = document.getElementById('search_txt').value;
            table = document.getElementById("results_table");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    table = document.getElementById("results_table");
                    table.innerHTML = xmlhttp.response;
                }
            }
            var request = "catalog-db-query.php?operation=for_update&search_txt=" + query;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

</head>
<body onload="searchProducts()">
<center><h2>פריטים בחנות</h2></center>
<input type="text" id="search_txt">
<button id="search_btn" onclick="searchProducts()">חפש פריטים</button>
<input id="select_all" type="checkbox" onclick="select_all_toggle()">בחר הכל</button>
<!--<button id="set_vat" onclick="set_vat()">שנה מעמ</button>-->
<input type="text" id="supplier_name">
<!--<button id="set_vat" onclick="set_supplier()">שנה ספק</button>-->
<!--<button id="publish" onclick="publish()">פרסם</button>-->
<?php

print gui_button( "btn_set_terms", "set_category()", "שנה קטגוריות" );
print gui_select_category( 1, true );
?>

<table id="results_table">
</table>
</body>
</html>


