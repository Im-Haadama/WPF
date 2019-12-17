<?php

require_once( "../r-shop_manager.php" );

print header_text(false, true, true, array('/vendor/sorttable.js', '/fresh/admin/data.js', '/core/gui/client_tools.js'));

$user_id = get_param("user_id", false, null);

if ($user_id) { show_user($user_id); return; }

//$sql = "select id, display_name, client_last_order_date(id) as 'last order date', client_last_order(id) as 'last order' from wp_users
//order by 2 asc
//limit 20";

$page = get_param("page", false, null);
$rows_per_page = 20;
if ($page) {
	$offset = ($page - 1) * $rows_per_page;
	$limit = (($page > -1) ? " limit $rows_per_page offset $offset" : "");
} else
	$limit = $rows_per_page;

$last_users = sql_query_array_scalar("select distinct meta_value from wp_postmeta where meta_key = '_customer_user'  order by post_id desc limit $limit");

// Default view - customer of last 20 orders.
//$sql = "select distinct
$sql = "select id, display_name, client_last_order_date(id) as 'last order date', client_last_order(id) as 'last order' from wp_users where id in (" . comma_implode($last_users) . ")";

$args = array();

$args["class"] = "sortable";
$args["links"] = array("id" => "admin.php?user_id=%s",
	"last_order" => "../orders/get-order.php?order_id=%s");

print GuiTableContent("wp_users", $sql, $args);
if ($page > 1) print gui_hyperlink("Next", add_to_url("page", $page - 1));
print gui_hyperlink("Next", add_to_url("page", $page ? $page + 1 : 1));

function show_user($user_id)
{
	$args = array("transpose" => true,
	              "meta_fields" => array("preference", "auto_mail", "print_delivery_note",
		              'billing_first_name',
		              'billing_last_name',
		              'billing_phone',
		              'shipping_first_name',
		              'shipping_last_name',
		              'shipping_address_1',
		              'shipping_address_2',
		              'shipping_city',
		              'shipping_postcode'
	              ),
	              "meta_table" => "wp_usermeta",
		          "meta_key" => "user_id",
		   		  "edit" => true,
	              // "add_checkbox" => true,
	              "id_field" => "ID",
		"fields" => array("ID", "user_email", "display_name"),
		"header_fields" => array("Email", "Name", "Client preferences (E.g - small cucumbers)", "Send mail about products",
			"Delivery note - M for mail, P for Paper print",
			"Billing first name", "Billing last name", "Phone", "Shipping first name", "Shipping last name", "Shipping address - street and number",
			"Shipping address - entrance code, floor and flat number", "Shipping city", "Shipping post code"));

	print gui_header(1, "משתמש מספר " . $user_id);
	print GuiRowContent("wp_users", $user_id, $args);
	print gui_button("btn_save",
		"data_save_entity('wp_users', $user_id)",
		"שמור");
}
