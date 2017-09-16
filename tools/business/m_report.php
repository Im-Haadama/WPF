<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/08/17
 * Time: 22:51
 */

if ( ! defined( STORE_DIR ) ) {
	define( 'STORE_DIR', dirname( dirname( dirname( __FILE__ ) ) ) );
}

require_once( STORE_DIR . "/wp-config.php" );
require_once( "../im_tools.php" );
require_once( "../gui/inputs.php" );
?>
<body onload="show_all()">
<?php
print header_text( true );

?>
<script>
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
        var month = document.getElementsByName("month")[0].value;
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
<?
$today = date( 'Y-m', strtotime( 'last month' ) );

print gui_input_month( "month", "month", $today, "onchange=update()" );

?>

<div id="report"></div>
</body>
</html>
