<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 13:07
 */
define( 'WCDI', '' );
require_once( '../im_tools.php' );
require_once( ROOT_DIR . '/agla/gui/inputs.php' );
?>
<header>
    <script>
        function update() {
			<?php if ( get_current_user() == 'aglamaz' ) {
			print 'show_all();';
		} else {
			print 'show_user("' . get_current_user() . '")';
		}
			?>
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
            var month = document.getElementsByName("month")[0].value;
            // alert (month);
            var request = "people-post.php?operation=show_all&month=" + month;
//	            +
//                "&ids=" + del_ids.join() +
//                "&user_id=" + <?php //print $customer_id; ?>//;
            // report.innerHTML = request;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>
</header>
<?php

print header_text(); ?>
<body onload="update();">
<h1 align="center">נתוני שכר לחודש
	<?
	$today = date( 'Y-m', strtotime( 'last month' ) );

	print gui_input_month( "month", "month", $today, "onchange=update()" );

	?>
</h1>
<div id="report"></div>
</body>
</html>