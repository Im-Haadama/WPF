<?php

require_once "../r-shop_manager.php";
require_once( FRESH_INCLUDES . "/core/gui/sql_table.php" );
require_once( "../header.php" );
require_once( FRESH_INCLUDES . "/core/gui/inputs.php" );
require_once( FRESH_INCLUDES . '/core/options.php' );

print header_text( false, true, is_rtl(), array("/fresh/orders/orders.js", "/core/gui/client_tools.js") );


?>
<script type="text/javascript" src="/core/gui/client_tools.js"></script>
<script>

    function add_wasted() {
        var collection = document.getElementsByClassName("select");
        var prod_ids = new Array();

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                window.location = window.location;
            }
        }

        for (var i = 0; i < collection.length; i++) {
            var prod_id = collection[i].id.substr(4);
            if (document.getElementById("chk_" + prod_id).checked)
                prod_ids.push(prod_id);
        }
        var request = "inv-post.php?operation=add_waste&prod_ids=" + prod_ids.join();
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
    function update_display() {
        var t = document.getElementById("inventory");

        xmlhttp3 = new XMLHttpRequest();
        xmlhttp3.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp3.readyState == 4 && xmlhttp3.status == 200)  // Request finished
            {
                t.innerHTML = xmlhttp3.response;
            }
        }
        var url = "inv-post.php?operation=show";
	    <?php
            $not_available = GetParam("not_available", false, false);
            if ($not_available) print "url += '&not_available=1';\n";
	    if ( isset( $_GET["supplier_id"] ) ) {
		    print 'url += "&supplier_id=' . $_GET["supplier_id"] . '";';
	    }
	    ?>
        xmlhttp3.open("GET", url, true);
        xmlhttp3.send();

    }
    //    function reset_inv()
    //	{
    //	    execute_url("../weekly/start.php", update_display);
    //	}
</script>
<body onload="update_display()">
<div id="inventory">
</div>
</body>


