<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 11/11/18
 * Time: 07:37
 */
require_once( "../im_tools.php" );
include_once( "../r-shop_manager.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );

print header_text( true );
?>
<body onload="show_all()">
<script>
    function show_all() {
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
            {
                var response = xmlhttp.responseText.trim();
                report.innerHTML = response;
            }
        }
        var month = document.getElementsByName("month")[0].value;
        // alert (month);
        var request = "business-post.php?operation=show_control&month=" + month;
        //	            +
        //                "&ids=" + del_ids.join() +
        //                "&user_id=" + <?php //print $customer_id; ?>//;
        // report.innerHTML = request;
        // alert(request);

        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }
</script>
<?
$today = date( 'Y-m', strtotime( 'last month' ) );

print gui_input_month( "month", "month", $today, "onchange=show_all()" );

?>

<div id="report"></div>
</body>
</html>
