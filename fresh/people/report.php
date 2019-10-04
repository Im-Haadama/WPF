<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 26/05/17
 * Time: 13:07
 */
define( 'WCDI', '' );
require_once( '../im_tools.php' );
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

        function delete_line()
        {
            var collection = document.getElementsByClassName("hours_checkbox");
            var params = new Array();
            for (var i = 0; i < collection.length; i++) {
                if (collection[i].checked) {
                    // var name = get_value(table.rows[i+1].cells[0].firstChild);
                    var line_id = collection[i].id.substr(3);

                    params.push(line_id);
                }
            }
            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    window.location.reload();
                }
            }
            var request = "people-post.php?operation=delete&params=" + params;

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