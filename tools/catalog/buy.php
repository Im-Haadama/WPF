<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 25/11/15
 * Time: 19:46
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
            var request = "catalog-db-query.php?operation=fresh_buy";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

</head>
<body onload="searchProducts()">
<center><h2>עלות אספקה טרייה</h2></center>
<table id="results_table">
</table>
</body>
</html>


