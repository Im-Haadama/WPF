<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/07/17
 * Time: 13:45
 */
?>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <script>

        function changeOrder() {
            order_id = document.getElementById("order_id");
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    // table = document.getElementById("results_table");
                    // table.innerHTML = xmlhttp.response;
                }
            }
            var request = "catalog-db-query.php?operation=siton";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }
        function searchProducts() {
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
            var request = "catalog-db-query.php?operation=customer_prices";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

</head>
<body onload="searchProducts()">
<center><h2>מחירון פירות וירקות אורגניים לצרכן</h2></center>
<table id="results_table">
</table>
</body>
</html>
