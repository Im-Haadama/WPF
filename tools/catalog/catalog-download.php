<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <script>
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
            var request = "catalog-db-query.php";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        searchProducts();
    </script>

</head>
<body onload="searchProducts()">
<table id="results_table">
</table>
</body>
</html>