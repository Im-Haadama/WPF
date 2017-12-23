<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 05/05/16
 * Time: 18:09
 */

require '../r-shop_manager.php';
require_once( "supplies.php" );
require_once( "../gui/inputs.php" );
$id = $_GET["id"];

print header_text( false );
?>

<script>
    function add_item() {
        var request_url = "supplies-post.php?operation=add_item&supply_id=<?php print $id; ?>";
        var _name = encodeURI(get_value(document.getElementById("itm_")));
        request_url = request_url + "&name=" + _name;
        var _q = 1; // encodeURI(get_value(document . getElementById("qua_")));
        request_url = request_url + "&quantity=" + _q;
        var request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                // window.location = window.location;
                update_display();
                //     document.getElementById("logging").innerHTML += http_text;
            }
        }

        // document.getElementById("logging").innerHTML = request_url;
        request.open("GET", request_url, true);
        request.send();
    }

</script>
<body onload="update_display()">

<div id="logging"></div>
<?php

$send = $_GET["send"];

print "<center><h1>הספקה מספר ";
print $id . " - " . supply_get_supplier( $id );
print  "</h1> </center>";

?>

<input type="checkbox" id="chk_internal" onclick='update_display();'>מסמך פנימי<br>

<h2>הערות</h2>
<textarea id="comment" rows="4" cols="50">
</textarea>
<button id="update_comment" onclick='update_comment();'>עדכן הערות
</button>
<br/>
<?php
print gui_datalist( "prods", "im_products", "post_title" );

if ( ! $send ) {
	print '<button id="btn_print" onclick="printDeliveryNotes()">הדפס תעודה</button>';
	print '<button id="btn_del" onclick="deleteItems()">מחק שורות</button>';
	print '<button id="btn_update" onclick="updateItems()">עדכן שורות</button>';
}

?>
<h2>פריטים</h2>

<table id="items"></table>

<div>
	<?php
	print gui_button( "btn_add_line", "add_item()", "הוסף" );

	?>
    <input id="itm_" list="prods">
</div>
<script type="text/javascript" src="../client_tools.js"></script>
<script>

    function update_comment() {
        var text = get_value(document.getElementById("comment"));

        execute_url("supplies-post.php?operation=save_comment&text=" + encodeURI(text)
            + "&id=<?php print $id; ?>", "update_display()");
    }

    function changed(field) {
        var subject = field.name;
        document.getElementById("chk" + subject).checked = true;
    }

    function update_display() {
        xmlhttp = new XMLHttpRequest();
        var filter = document.getElementById("chk_internal").checked;
        var request = "supplies-post.php?operation=get_supply";
        request = request + "&id=<?php print $id; ?>";
        if (filter) request = request + "&internal=1";

        xmlhttp.open("GET", request, true);
        xmlhttp.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {  // Request finished
                table = document.getElementById("items");
                table.innerHTML = xmlhttp.response;
            }
        }
        xmlhttp.send();

        xmlhttp1 = new XMLHttpRequest();
        var request1 = "supplies-post.php?operation=get_comment"
            + "&id=<?php print $id; ?>";

        xmlhttp1.open("GET", request1, true);
        xmlhttp1.onreadystatechange = function () {
            // Wait to get query result
            if (xmlhttp1.readyState === 4 && xmlhttp1.status === 200) {  // Request finished
                var comment = document.getElementById("comment");
                comment.innerHTML = xmlhttp1.response;
            }
        }
        xmlhttp1.send();

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

    function updateItems() {
        var table = document.getElementById('del_table');

        var collection = document.getElementsByClassName("supply_checkbox");
        var params = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                // var name = get_value(table.rows[i+1].cells[0].firstChild);
                var line_id = collection[i].id.substr(3);

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
    function deleteItems() {
        var table = document.getElementById('del_table');

        var collection = document.getElementsByClassName("supply_checkbox");
        var params = new Array();
        for (var i = 0; i < collection.length; i++) {
            if (collection[i].checked) {
                // var name = get_value(table.rows[i+1].cells[0].firstChild);
                var line_id = collection[i].id.substr(3);

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
        // Get the html
        document.getElementById('btn_print').style.visibility = "hidden";
        window.open("//pdfcrowd.com/url_to_pdf/");
        document.getElementById('btn_print').style.visibility = "visible";
//	var txt = document.documentElement.innerHTML;

        // Download the html
// 	var a = document.getElementById("a");
//	var file = new Blob(txt, 'text/html');
// 	a.href = URL.createObjectURL(file);
        // a.download = 're.html';

//	download(txt, 'myfilename.html', 'text/html')
//	window.open('data:text/html;charset=utf-8,<html dir="rtl" lang="he">' + txt + '</html>');

//

        // To Do: upload the file

        document.getElementById('btn_calc').style.visibility = "visible";
        document.getElementById('btn_print').style.visibility = "visible";
    }

    function got_supply() {
        var supply_number = get_value(document.getElementById("supply_number"));
        var supply_total = get_value(document.getElementById("supply_total"));

        var request_url = "supplies-post.php?operation=got_supply&supply_id=<?php print $id; ?>" +
            "&supply_total=" + supply_total + "&supply_number=" + supply_number;

        var request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (request.readyState === 4 && request.status === 200) {
                // window.location = window.location;
                update_display();
                //     document.getElementById("logging").innerHTML += http_text;
            }
        }

        // document.getElementById("logging").innerHTML = request_url;
        request.open("GET", request_url, true);
        request.send();
        // alert (request_url);
    }
</script>
<br/>
<br/>
<div id="supply_arrived">
    <div id="arrival_entry">
		<?php
		print gui_table( array(
			array( "מספר תעודת משלוח", gui_input( "supply_number", "" ) ),
			array( "סכום", gui_input( "supply_total", "" ) )
		) );

		print gui_button( "btn_got_supply", "got_supply()", "סחורה התקבלה" );

		?>
    </div>
</div>
<div id="supply_document">
</div>

</body>
</html>
