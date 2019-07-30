<?php

require_once("../r-shop_manager.php");

print header_text(false, true, true, array("/niver/gui/client_tools.js", "/tools/admin/data.js"));
?>
<script>

</script>


<?php
$id = get_param("id");

if ($id)
{
	$args = array();
	$args["edit"] = true;
	$args["add_checkbox"] = true;
	$args["transpose"] = true;

	print GuiRowContent("im_suppliers", $id, $args);
	print gui_button("btn_save", 'save_entity(\'im_suppliers\', ' . $id .')', "שמור");
	return;
}

$suppliers = sql_query_array("select * from im_suppliers where active = 1", true);

$links = array("admin.php?id=%s");
$sum = array();

print gui_table($suppliers, "tbl_suppliers", true, true, $sum, null,
	null, null, $links);

?>
