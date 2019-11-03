<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 13:07
 */

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

if ( ! defined( 'ROOT_DIR' ) ) {
	define( 'ROOT_DIR', dirname(dirname( dirname( __FILE__ ) ) ));
}

require_once( ROOT_DIR . '/niver/gui/inputs.php' );
$edit = get_param("edit");

print header_text(true, true, true);

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
            let request = "people-post.php?operation=show_all&month=" + month;

            <?php
                 if ($edit) print "request = request + '&edit=$edit';\n";
                ?>
//	            +
//                "&ids=" + del_ids.join() +
//                "&user_id=" + <?php //print $customer_id; ?>//;
            // report.innerHTML = request;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }

    </script>
</header>

<body onload="update();">
<h1 align="center">נתוני שכר לחודש
	<?
	$today = date( 'Y-m', strtotime( 'last month' ) );

	print gui_input_month( "month", "month", $today, "onchange=update()" );

	?>
</h1>
<div id="report"></div>
<?php print footer_text(); ?>
</body>
</html>