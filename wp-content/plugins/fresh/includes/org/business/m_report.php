<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/08/17
 * Time: 22:51
 */
if ( ! defined( 'FRESH_INCLUDES' ) ) {
	define( 'FRESH_INCLUDES', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( FRESH_INCLUDES . "/wp-config.php" );
require_once( FRESH_INCLUDES . "/niver/gui/inputs.php" );
?>
<body onload="show_all()">
<?php
print header_text( true );

?>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>
<script>

    function create_invoice() {
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                var response = xmlhttp.responseText.trim();
                report.innerHTML = response;
            }
        }

        var input = document.getElementsByName("report_month")[0];
        var month = get_value(input);// document.getElementsByName("month")[0].value;
        // alert (month);
        var request = "business-post.php?operation=create_makolet&month=" + month;
//	            +
//                "&ids=" + del_ids.join() +
//                "&user_id=" + <?php //print $customer_id; ?>//;
        // report.innerHTML = request;
        // alert(request);

        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function show_all() {
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                var response = xmlhttp.responseText.trim();
                report.innerHTML = response;
            }
        }

        var input = document.getElementsByName("report_month")[0];
        var month = get_value(input);// document.getElementsByName("month")[0].value;
        // alert (month);
        var request = "business-post.php?operation=show_makolet&month=" + month;
//	            +
//                "&ids=" + del_ids.join() +
//                "&user_id=" + <?php //print $customer_id; ?>//;
        // report.innerHTML = request;
        // alert(request);

        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

</script>
<?php

$today = date( 'Y-m', strtotime( 'last month' ) );

print gui_input_month( "report_month", "month", $today, "onchange=show_all()" );

?>

<div id="report"></div>
</body>
</html>

