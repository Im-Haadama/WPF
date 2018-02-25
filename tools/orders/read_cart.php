<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 19/02/18
 * Time: 16:38
 */

require_once( "../im_tools.php" );
require_once( "../gui/inputs.php" );

print header_text( false );

?>
<script type="text/javascript" src="../client_tools.js"></script>

<script>
    function load_cart() {
//	    var text = get_value_by_name("cart");
        var url = "cart.php?operation=clear_cart";

//	    execute_url(url);

        var lines = get_value_by_name("cart").split("\n");
        for (var i = 0; i < lines.length; i++) {
            url = "cart.php?operation=add&line=" + encodeURI(lines[i]);

            execute_url(url);
        }
    }
</script>
<?php

print gui_textarea( "cart", "", "" );

print gui_button( "", "load_cart()", "טען עגלת קניות" );

?>
