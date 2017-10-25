<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 04/03/17
 * Time: 21:04
 */
require_once( "inputs.php" );
require_once( "../account/gui.php" );

?>
<html>
<header>
    <script>
		<?php
		$filename = __DIR__ . "/../client_tools.js";
		$handle = fopen( $filename, "r" );
		$contents = fread( $handle, filesize( $filename ) );
		print $contents;
		?>

        function update_prices() {
            var sale = get_value(document.getElementById("with_sale"));
            alert(sale);
        }
    </script>
</header>
<body>
<?php
// print gui_checkbox( "with_sale", "", "", "onclick=\"update_prices()\"" );
print gui_select_mission();

?>
</body>
</html>
