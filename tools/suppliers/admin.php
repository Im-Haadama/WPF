<?php

require_once("../r-shop_manager.php");

print header_text(false, true, true, "/niver/gui/client_tools.js");
?>
<script>
    function save_entity(table_name, id)
    {
        var operation = "suppliers-post.php?operation=update&id=" + id;
        var table = document.getElementById(table_name);
        var size = table.rows.length;
        for (var i = 0; i < size; i++){
            var name = table.rows[i].cells[1].innerText;
            if (get_value_by_name("chk_" + name)) {
                operation += "&" + name + "=" + get_value_by_name(name);
            }
        }
        // alert(operation);
	    execute_url(operation, action_back);
    }
    function action_back(xmlhttp)
    {
        if (xmlhttp.response === "done")
            window.history.back();
        else
            alert (xmlhttp.response);
    }

</script>


<?php
$id = get_param("id");

if ($id)
{
	$args = array();
	$args["edit"] = true;
	$args["add_checkbox"] = true;
	print RowContent("im_suppliers", $id, $args, true);
	print gui_button("btn_save", 'save_entity(\'im_suppliers\', ' . $id .')', "שמור");
	return;
}

$suppliers = sql_query_array("select * from im_suppliers where active = 1", true);

$links = array("admin.php?id=%s");
$sum = array();

print gui_table($suppliers, "tbl_suppliers", true, true, $sum, null,
	null, null, $links);

?>
