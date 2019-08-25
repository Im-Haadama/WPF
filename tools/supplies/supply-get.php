<?php
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

<div id="items"></div>
<button id="btn_del" onclick="deleteItems()">מחק שורות</button>
<button id="btn_update" onclick="updateItems()">עדכן שורות</button>

<div id="add_items">
	<?php
	print gui_button( "btn_add_line", "add_item()", "הוסף" );
	print gui_select_product( "itm_" );
	?>
    <!--    <input id="itm_" list="prods">-->
</div>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>
<script type="text/javascript" src="supply.js"></script>

<script>
    function save_mission() {
        var mission = get_value(document.getElementById("mission_select"));
        var request = "supplies-post.php?operation=set_mission&mission_id=" + mission + "&supply_id=<?print $id; ?>";

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                location.reload();
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }


    function update_comment() {
        var text = get_value(document.getElementById("comment"));

        execute_url("supplies-post.php?operation=save_comment&text=" + encodeURI(text)
            + "&id=<?php print $id; ?>", "update_display()");
    }

    // function changed(field) {
    //     var subject = field.name;
    //     document.getElementById("chk_" + subject).checked = true;
    // }

    function update_display() {
        xmlhttp = new XMLHttpRequest();
        var filter = document.getElementById("chk_internal").checked;
        var request = "supplies-post.php?operation=get_supply";
        request = request + "&id=<?php print $id; ?>";
        if (filter) request = request + "&internal=1";

        var grouped_by_categ = get_value_by_name("chk_categ_group");
        if (grouped_by_categ) request = request + "&categ_group=1";

        xmlhttp.open("GET", request, true);
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                table = document.getElementById("items");
                table.innerHTML = xmlhttp.response;
                xmlhttp1 = new XMLHttpRequest();
                var request1 = "supplies-post.php?operation=get_comment"
                    + "&id=<?php print $id; ?>";

                xmlhttp1.open("GET", request1, true);
                xmlhttp1.onreadystatechange = function () {
                    // Wait to get query result
                    if (xmlhttp1.readyState === 4 && xmlhttp1.status === 200) {  // Request finished
                        var comment = document.getElementById("comment");
                        comment.innerHTML = xmlhttp1.response;
                        xmlhttp2 = new XMLHttpRequest();
                        var request2 = "supplies-post.php?operation=get_business"
                            + "&supply_id=<?php print $id; ?>";

                        xmlhttp2.open("GET", request2, true);
                        xmlhttp2.onreadystatechange = function () {
                            // Wait to get query result
                            if (xmlhttp2.readyState === 4 && xmlhttp2.status === 200) {  // Request finished
                                var arrival_info = xmlhttp2.response;
                                if (arrival_info.length > 5) {
                                    supply_document.innerHTML = arrival_info;
                                    supply_arrived.hidden = true;
                                    supply_document.hidden = false;
                                } else {
                                    supply_arrived.hidden = false;
                                    supply_document.hidden = true;
                                }
                            }
                        }
                        xmlhttp2.send();

                    }
                }
                xmlhttp1.send();
            }
        }
        xmlhttp.send();
    }

    function updateItems() {
        var collection = document.getElementsByClassName("supply_checkbox");
        var params = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                // var name = get_value(table.rows[i+1].cells[0].firstChild);
                var line_id = collection[i].id.substr(4);

                params.push(line_id);
                params.push(get_value(document.getElementById(line_id)));
            }
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                update_display();
            }
        }
        var request = "supplies-post.php?operation=update_lines&params=" + params;
//        alert(request);
        xmlhttp.open("GET", request, true);
        xmlhttp.send();

    }
    function del_line(supply_line_id) {
        var btn = document.getElementById("del_" + supply_line_id);
        btn.parentElement.parentElement.style.display = 'none';
        execute_url("supplies-post.php?operation=delete_lines&params=" + supply_line_id);
    }

    function deleteItems() {
        var table = document.getElementById('del_table');

        var collection = document.getElementsByClassName("supply_checkbox");
        var params = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                // var name = get_value(table.rows[i+1].cells[0].firstChild);
                var line_id = collection[i].id.substr(4);

                params.push(line_id);
            }
        }
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                update_display();
            }
        }
        var request = "supplies-post.php?operation=delete_lines&params=" + params;
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

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
</div>


</body>
</html>
