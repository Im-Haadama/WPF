<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 24/09/18
 * Time: 09:26
 */
?>
<script>

    /**
     * Created by agla on 24/09/18.
     */

    function map_products() {
        var collection = document.getElementsByClassName("product_checkbox");
        var table = document.getElementById("<?php if ( isset( $map_table ) ) {
			print $map_table;
		} else {
			print "map_table";
		}?>");
        var map_ids = new Array();
//        var map_ids_remote = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                var pricelist_id = collection[i].id.substr(3);
//                var product_name = get_value(table.rows[i+1].cells[2].firstChild);
//                var supplier_product_code = get_value(table.rows[i+1].cells[1].firstChild);
//                var supplier_code = get_value(table.rows[i+1].cells[3].firstChild);
                var sel = document.getElementById("prd" + pricelist_id);// table.rows[i + 1].cells[4].firstChild;
                if (sel.selectedIndex == -1) {
                    alert("לא נבחר מוצר עבור " + table.rows[i + 1].cells[2].innerHTML);
                    continue;
                }
                var product_id = sel.options[sel.selectedIndex].value;
//                map_ids.push(product_name);
//                map_ids.push(supplier_code);
                var site = <?php print ImMultiSite::LocalSiteID(); ?>; // local
                // TODO: fix that
//                    if (table.rows[i + 1].cells.length > 6) {
//                        // Handle remote
//                        site = table.rows[i + 1].cells[6].innerHTML;
//
////                    if (map_ids_remote[remote_site] == null)
////                        map_ids_remote[remote_site] = new Array();
////                    map_ids_remote[remote_site].push(product_id);
////                    map_ids_remote[remote_site].push(pricelist_id);
//                    }
                // Handle local
                map_ids.push(site);
                map_ids.push(product_id);
                map_ids.push(pricelist_id);
//                map_ids.push(supplier_product_code);
            }
        }
        //alert (map_ids.join());
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                refresh();
            }
        }
        var request = "../catalog/catalog-map-post.php?operation=map&ids=" + map_ids.join();
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function map_hide() {
        var collection = document.getElementsByClassName("product_checkbox");
        var table = document.getElementById("map_table");
        var map_ids = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                var pricelist_id = collection[i].id.substr(3);
                map_ids.push(pricelist_id);
            }
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                refresh();
            }
        }
        var request = "catalog-map-post.php?operation=hide&ids=" + map_ids.join();
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }
</script>