<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/07/17
 * Time: 04:27
 */
require_once( "../gui/inputs.php" );
require_once( "../im_tools.php" );

print header_text( true );

if ( ! isset( $_GET["client_id"] ) ) {
	print "קישור לא תקין<br/>";
	die ( 1 );
}
?>

<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <script>

        function place_order() {
            var client_id = <?print $client_id; ?>;

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
            var request = "catalog-db-query.php?operation=fresh_siton_order";
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

</head>
<body onload="searchProducts()">
<center><h2>מחירון לחנות טבע</h2></center>
<?php
print gui_button( "btn_place_order", "place_order()", "צור הזמנה" );
?>
<table id="results_table">
    <tr>
        <td>המחירון נטען</td>
    </tr>
</table>
</body>
</html>
