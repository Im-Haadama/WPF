<?php

require_once "../r-shop_manager.php";
require_once( ROOT_DIR . "/niver/gui/sql_table.php" );
require_once( "../header.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( "../options.php" );
?>
<script type="text/javascript" src="/niver/client_tools.js"></script>
<script>

    function save_inv(term) {
        var collection = document.getElementsByName("term_" + term);
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
            var q = get_value_by_name("inv_" + prod_id);
            prod_ids.push(prod_id, q);
        }
        var request = "inv-post.php?operation=save_inv&data=" + prod_ids.join();
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
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
<?php
print header_text();
?>
<body onload="update_display()">
<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 16/01/17
 * Time: 12:37
 */

// print "מציג מאספקה מספר " . info_get( "inventory_in" ) . "<br/>";
// print " מציג ממשלוח מספר " . info_get( "inventory_out" ) . "<br/>";

// print gui_hyperlink( "איפוס המלאי", "../weekly/start.php" );
// print gui_button("btn_reset_invetory", "reset_inv()", "אפס מלאי");

//print gui_button( "btn_waste", "add_wasted()", "הוסף לפחת" );

?>
<div id="inventory">
</div>
</body>
