<?php
require_once "../tools.php";
require_once "../gui/sql_table.php";
require_once( "../header.php" );
require_once( "../gui/inputs.php" );
?>
<script>
	<?php
	$filename = __DIR__ . "/../client_tools.js";
	$handle = fopen( $filename, "r" );
	$contents = fread( $handle, filesize( $filename ) );
	print $contents;
	?>

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
        xmlhttp3.open("GET", "inv-post.php?operation=show", true);
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


print gui_hyperlink( "איפוס המלאי", "../weekly/start.php" );
// print gui_button("btn_reset_invetory", "reset_inv()", "אפס מלאי");


?>
<table id="inventory">
</table>
</body>
