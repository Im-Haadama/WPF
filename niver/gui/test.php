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
    <script type="text/javascript" src="..//client_tools.js"></script>
    <script>

        function update_prices() {
            var sale = get_value(document.getElementById("with_sale"));
            alert(sale);
        }
    </script>
</header>
<body>
<?php
// print gui_checkbox( "with_sale", "", "", "onclick=\"update_prices()\"" );
print gui_select_mission( "mission_select" );

?>
</body>
</html>