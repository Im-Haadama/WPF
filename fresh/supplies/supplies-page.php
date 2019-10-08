<?php
// Created 14/8/2019
// By: agla

define ('ROOT_DIR', dirname(dirname(dirname(__FILE__))));

require_once( '../r-shop_manager.php' );
require_once (ROOT_DIR . '/niver/web.php');
require_once(ROOT_DIR . '/fresh/supplies/Supply.php');
require_once(ROOT_DIR . '/fresh/catalog/gui.php');

$entity_name_plural = "הספקות";
$table_name = "im_supplies";
$general_selectors = array("supplier" => "gui_select_supplier");
// function update_table_field(post_file, table_name, id, field_name, finish_action) {

$update_event = 'onchange="update_table_field(\'/niver/data/data.php\', \'' . $table_name . '\', \'%d\', \'%s\', check_update)"';

print header_text( false, true, true, array( "/niver/gui/client_tools.js", "/niver/data/data.js", "/fresh/supplies/supply.js" ) );

global $user_ID; // by wordpress.

$id = get_param("id", false);
require_once(ROOT_DIR . '/init.php');

if ($id)
{
	$supply = new Supply($id);
	print "<center> " . gui_header(1, "Supply number ", true, true). gui_label("supply_number", $id) . "</center>";
	print $supply->Html(true, false);
	print gui_button( "btn_add_line", "add_item()", "add" );
	print gui_select_product( "itm_" );
	print gui_button("btn_del", "deleteItems()", "delete lines");
	print gui_button("btn_update", "updateItems()", "update items");

	return;
}

$operation = get_param("operation", false, "get_all");
if ($operation) {
    handle_supplies_operation($operation);
    return;
}

exit;

$row_id = get_param( "row_id", false );
// if ($row_id) { show_supply($row_id); return; }

$edit = get_param("edit", false, false);
show_last_supplies($edit, get_param("supplier", false));

function show_last_supplies($edit = false, $supplier_id = null)
{
	global $general_selectors;
	global $this_url;
	global $entity_name_plural;
	global $table_name;
	global $update_event;

	$args = array("events" => $update_event,
	              "edit" => $edit,
		"links" => array("id" => get_url(1) . "?id=%s", "supplier" => get_url(1) . "?supplier=%s"),
		"selectors" => array("status" => "gui_select_supply_status", "supplier" => "gui_select_supplier"));

	print gui_header( 1, "ניהול " . $entity_name_plural );

	$sql = "select id, status, date(date), supplier, text, business_id, paid_date from $table_name where status != " . SupplyStatus::Deleted;

	if ($supplier_id)
		$sql .= " and supplier = " . $supplier_id;
	$sql .= " order by id desc limit 30";
	$args["header_fields"] = array("Id", "Status", "Date", "Supplier", "Comments", "Transaction", "Pay date");

	// print $sql;
	print GuiTableContent( $table_name, $sql, $args );

}

/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 18:09
 */

require '../r-shop_manager.php';
require_once( "Supply.php" );
require_once( ROOT_DIR . '/niver/gui/inputs.php' );
require_once( "../account/gui.php" );
require_once("../catalog/gui.php");

print header_text( false, true, true,
    array("/vendor/sorttable.js",	"/niver/gui/client_tools.js")
);

$print = false;
if ( isset( $_GET["print"] ) ) {
	$print = true;
}

if ( isset( $_GET["business_id"] ) ) {
	$bid = $_GET["business_id"];
	$sql = "SELECT id FROM im_supplies WHERE business_id = " . $bid;
//    print $sql;
	$id = sql_query_single_scalar( $sql );
	if ( ! ( $id > 0 ) ) {
		print "לא נמצאה הספקה";

		return;
	}
} else {
	$id = $_GET["id"];
}

$s = new Supply( $id );

?>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>
<script type="text/javascript" src="supply.js"></script>

<script>
    function sendSupply() {
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                var http_text = xmlhttp.responseText.trim();
                add_message(http_text);
                updateDisplay();
            }
        }
        var request = "supplies-post.php?operation=send&id=" + <? print $id; ?>;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function supply_pay() {
        var date = get_value_by_name("pay_date");

        var request_url = "supplies-post.php?operation=supply_pay&date=" + date +
            "&id=" + <?php print $id; ?>;

        var request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (request.readyState === 4 && request.status === 200) {
                // window.location = window.location;
                update_display();
            }
        }

        request.open("GET", request_url, true);
        request.send();
    }
    function add_item() {
        var request_url = "supplies-post.php?operation=add_item&supply_id=<?php print $id; ?>";
        var prod_id = get_value_by_name("itm_");
        request_url = request_url + "&prod_id=" + prod_id;
        var _q = 1; // encodeURI(get_value(document . getElementById("qua_")));
        request_url = request_url + "&quantity=" + _q;
        var request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (request.readyState === 4 && request.status === 200) {
                // window.location = window.location;
                update_display();
            }
        }

        request.open("GET", request_url, true);
        request.send();
    }

</script>
<body onload="update_display()">

<div id="log"></div>
<?php

$send = isset( $_GET["send"] );

print "<center><h1>הספקה מספר ";
print $id . " - " . supply_get_supplier( $id );
print  "</h1> </center>";

?>
<div id="head_edit">

    <input type="checkbox" id="chk_internal" onclick='update_display();'>מסמך פנימי<br>
    <input type="checkbox" id="chk_categ_group" onclick='update_display();'>קבץ לקטגוריות<br>

    <!--<button id="update_comment" onclick='update_comment();'>עדכן הערות-->
    <!--</button>-->
<br/>
<?php
//if ( user_can( get_user_id(), "pay_supply" ) ) {
//
//	print gui_table( array( array( "תאריך תשלום", gui_input_date( "pay_date", "", "", "onchange=supply_pay()" ) ) ) );
//}
//?>
</div>
<div id="head_print">
</div>
<div id="supply"></div>

<?php
print gui_datalist( "products", "im_products", "post_title", true );

if ( ! $send ) {
	print '<div id="buttons">';
	print '<button id="btn_print" onclick="printDeliveryNotes()">הדפס תעודה</button>';
	if ( $s->getStatus() == 1 ) {
		print gui_button( "btn_send", "sendSupply()", "שלח לספק" );
	}
	print '</div>';
}

?>
<br/>

משימה
<?php
$mission_id = supply_get_mission_id( $id );
print gui_select_mission( "mission_select", $mission_id, "onchange=\"save_mission()\"" );

?>
<script>

    function printDeliveryNotes() {
        document.getElementById('head_print').innerHTML = get_value(document.getElementById("comment"));
        var elements = ["buttons", "supply_arrived", "add_items", "head_edit"];
        elements.forEach(function (element) {
            document.getElementById(element).style.display = "none";
        });
        document.getElementById("head_print").style.display = "block";

        window.print();

        document.getElementById("head_print").style.display = "none";
        elements.forEach(function (element) {
            document.getElementById(element).style.display = "block";
        });
    }

    function got_supply() {
        disable_btn("btn_got_supply");
        var supply_number = get_value(document.getElementById("supply_number"));
        var supply_total = get_value(document.getElementById("supply_total"));
        var net_amount = get_value(document.getElementById("net_amount"));
        var is_invoice = get_value(document.getElementById("is_invoice"));
        var date = get_value_by_name("document_date");

        if (!supply_number) {
            alert("יש לרשום את מספר תעודת המשלוח");
            enable_btn("btn_got_supply");
            return;
        }

        if (!supply_total) {
            alert("יש לרשום סכום תעודת המשלוח");
            enable_btn("btn_got_supply");
            return;
        }

        if (!net_amount) {
            alert("יש לרשום סכום תעודת המשלוח ללא מע\"מ");
            enable_btn("btn_got_supply");
            return;
        }

        var request_url = "supplies-post.php?operation=got_supply&supply_id=<?php print $id; ?>" +
            "&supply_total=" + supply_total + "&supply_number=" + supply_number +
            "&net_amount=" + net_amount +
            "&is_invoice=" + is_invoice;

        if (date)
            request_url = request_url + "&document_date=" + date;

        var request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (request.readyState === 4 && request.status === 200) {
                if (request.response.indexOf("fail") !== -1) {
                    add_message("הפעולה נכשלה" + request.response);
                    enable_btn("btn_got_supply");
                    return;
                }
                // window.location = window.location;
                update_display();
            }
        }

        request.open("GET", request_url, true);
        request.send();
        // alert (request_url);
    }
</script>
<br/>
<br/>
<style>
	.tooltip {
		position: relative;
		display: inline-block;
		border-bottom: 1px dotted black;
	}

	.tooltip .tooltiptext {
		visibility: hidden;
		width: 120px;
		background-color: black;
		color: #fff;
		text-align: center;
		border-radius: 6px;
		padding: 5px 0;
		position: absolute;
		z-index: 1;
		top: -5px;
		right: 110%;
	}

	.tooltip .tooltiptext::after {
		content: "";
		position: absolute;
		top: 50%;
		left: 100%;
		margin-top: -5px;
		border-width: 5px;
		border-style: solid;
		border-color: transparent transparent transparent black;
	}

	.tooltip:hover .tooltiptext {
		visibility: visible;
	}
</style>

<div id="supply_arrived">
	<?php $invoice_text = '
    <div class="tooltip">' . gui_checkbox( "is_invoice", "" ) .
	                      '<span class="tooltiptext">יש לסמן עבור חשבונית ולהשאיר לא מסומן עבור תעודת משלוח</span>
    </div>';

		print gui_table_args( array(
			array( "חשבונית", $invoice_text ),
			array( "מספר מסמך", gui_input( "supply_number", "" ) ),
			array( "סכום כולל מעמ", gui_input( "supply_total", "" ) ),
			array( "סכום ללא מעמ", gui_input( "net_amount", "" ) ),
			array( "תאריך", gui_input_date( "document_date", "" ) )
		) );
	// print gui_label("help", 'תיבת הסימון ליד "חשבונית" תשאר לא מסומנת במקרה של תעודת משלוח');
	print "<br/>";

		print gui_button( "btn_got_supply", "got_supply()", "סחורה התקבלה" );

		?>
</div>

<div id="supply_document">
