<?php
return;
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

require_once( "../suppliers/gui.php" );
require_once(FRESH_INCLUDES . '/im-config.php');
require_once( FRESH_INCLUDES . "/init.php" );
require_once(FRESH_INCLUDES . '/focus/gui.php');

require_once(FRESH_INCLUDES . '/fresh/catalog/gui.php');

print load_scripts(array("/core/gui/client_tools.js", "/core/data/data.js"));
// print gui_select_supplier("supplier", 100051, array("edit"=>true));

$args = [];
$args["worker"] = get_user_id();
print gui_select_product("product", null, $args);

print Core_Html::GuiButton("btn_test", "get_value()", "get");
//
//print "<br/>";
//print gui_select_project("project", null, $args);
//
//print "<br/>";
//print gui_select_worker("worker", null, $args);
//
//print "<br/>";
//print gui_select_task("task", null, $args);

//print "<br/>";
print gui_select_client("client", "", $args);
?>
<script>
	function get_value()
	{
	    let val = document.getElementById("product").value;
        let list = document.getElementById("product").list.firstElementChild.options;
        let idx = -1;
        for (let i = 0; i < list.length; i++)
            if (val == list[i].value) {
                idx = i;
                break;
            }
        if (idx >= 0)
			alert (document.getElementById("product").list.firstElementChild.options[idx].dataset.id);
        else
            alert("not found");
	}
</script>
