<?php

require_once("../r-shop_manager.php");

print header_text(false, true, true, array('/vendor/sorttable.js', '/tools/admin/data.js', '/niver/gui/client_tools.js'));

$user_id = get_param("user_id", false, null);

if ($user_id) { show_user($user_id); return; }


$sql = "select id, client_displayname(id), client_last_order_date(id) as last_date, client_last_order(id) as last_order from wp_users
order by 3 desc
limit 100";

$args = array();

$args["class"] = "sortable";
$args["links"] = array("id" => "admin.php?user_id=%s",
	"last_order" => "../orders/get-order.php?order_id=%s");

print GuiTableContent("wp_users", $sql, $args);


function show_user($user_id)
{
	$args = array("transpose" => true,
	              "meta_fields" => array("preference", "auto_mail", "print_delivery_note"),
	              "meta_table" => "wp_usermeta",
		          "meta_key" => "user_id",
		   		  "edit" => true,
	              "add_checkbox" => true,
	              "id_field" => "ID");

	print gui_header(1, "משתמש מספר " . $user_id);
	print GuiRowContent("wp_users", $user_id, $args);
	print gui_button("btn_save",
		"save_entity('wp_users', $user_id)",
		"שמור");
}
