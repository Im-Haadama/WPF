<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//print gui_select_product("datalist", '', array("datalist" => "im_products"));
?>

<script>
    const line_select_id = <?php print eDeliveryFields::line_select; ?>;
    const product_name_id = <?php print eDeliveryFields::product_name; ?>;
    const q_quantity_ordered_id = <?php print eDeliveryFields::order_q; ?>;
    const q_units = <?php print eDeliveryFields::order_q_units; ?>;
    const q_supply_id = <?php print eDeliveryFields::delivery_q; ?>;
    const has_vat_id = <?php print eDeliveryFields::has_vat; ?>;
    const line_vat_id = <?php print eDeliveryFields::line_vat; ?>;
    const price_id = <?php print eDeliveryFields::price; ?>;
    const line_total_id = <?php print eDeliveryFields::delivery_line; ?>;
    const term_id = <?php print eDeliveryFields::term; ?>;
    const q_refund_id = <?php print eDeliveryFields::refund_q ?>;
    const refund_total_id = <?php print eDeliveryFields::refund_line; ?>;
    const line_type_id = <?php print eDeliveryFields::line_type; ?>;

    function getPrice(my_row) {
        // var product_info = get_value(document.getElementById("nam_" + my_row));
        // if (!product_info.indexOf(")")) return;
        var product_id = get_value_by_name("nam_" + my_row); // product_info.substr(0, product_info.indexOf(")"));
        var request = "delivery-post.php?operation=get_price_vat&id=" + product_id; //encodeURI(product_name);

        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            // Wait to get delivery id.
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
            {
                var response = xmlhttp.response.split(",");
                var price = response[0];
                var vat = response[1] > 0;

                if (price > 0) {
                    document.getElementById("prc_" + my_row).value = price;
                    document.getElementById("deq_" + my_row).focus();
                }

                document.getElementById("hvt_" + my_row).checked = vat;
            }
        }
        xmlhttp.open("GET", request, true);
        xmlhttp.send();
    }

    function leaveQuantityFocus(my_row)
    {
        let current = document.getElementsByName("quantity" + (my_row));
        current[0].value = Math.round(current[0].value * 10) / 10;
        calcDelivery();
    }

    function moveNextRow(my_row) {
        if (event.which === 13) {
            var i;
            for (i = my_row + 1; i < document.getElementById("del_table").rows.length; i++) {
                var next = document.getElementById("quantity" + i);
                if (next) {
                    next.focus();
                    return;
                }
            }

            var del = document.getElementById("delivery");
            if (del) del.focus();
        }
    }

    function addLine(draft) {
        var table = document.getElementById('del_table');
//        var hidden = [];
//        for (var i = 0; i < table.rows.length; i++) {
//            for (var j = 0; j < table.rows[0].cells.length; j++)
//                table.rows[i].cells[j].style.display = 'visible';
////             hidden[i] = table.rows[0].cells[i].style.display;
//
//        }
//        return;

        var lines = table.rows.length;
        var line_id = lines - 4;
        var row = table.insertRow(lines - 4);
//        row.insertCell(0).style.visibility = false;              // 0 - select
        var list = "items";
        if (draft) list = "draft_items";
        let input = "<?php $args = array("events" => "onchange=\"getPrice(XX)\"", "datalist" => "YYY");
            print escape_string(Fresh_Product::gui_select_product( "nam_XX", '', $args )); ?>";
        input = input.replace(/XX/g, line_id);
        input = input.replace(/YYY/g, list);

        row.insertCell(-1).innerHTML = input;// "<input id=\"nam_" + line_id + "\" type=\"text\" list=\"" + list + "\" onchange=\"getPrice(" + line_id + ")\">";   // 1 - product name
        row.insertCell(-1).innerHTML = "0";                       // 2 - quantity ordered
        row.insertCell(-1).innerHTML = "";                        // 3 - unit ordered
        // row.insertCell(-1).innerHTML = ""; // order total
        row.insertCell(-1).innerHTML = "<input id=\"deq_" + line_id + "\" type=\"text\" onchange='calcDelivery()'>";   // 4 - supplied
        row.insertCell(-1).innerHTML = "<input id=\"prc_" + line_id + "\" type=\"text\">";   // 5 - price
        row.insertCell(-1).innerHTML = "<label id=\"lpr_" + line_id + "\" type=\"text\">";   // line price
        row.insertCell(-1).innerHTML = "<input id=\"hvt_" + line_id + "\"  type = \"checkbox\" checked>"; // 6 - has vat
        row.insertCell(-1).id = "lvt_" + line_id;                       // 7 - line vat
        row.insertCell(-1).id = "del_" + line_id;   // 8 - total_line
        row.insertCell(-1).id = "pac_" + line_id; // 9 - packing info
        row.insertCell(-1).innerHTML = "<input id=\"typ_" + line_id + "\" type=\"text\" value=\"prd\">";   // 10 - line type


        calcDelivery();
//        row.insertCell(9).style.visibility = false;              // 9 - categ
//        row.insertCell(10).style.visibility = false;              // 10 - refund q
//        row.insertCell(11).style.visibility = false;              // 11 - refund total
    }

    function addDelivery(draft) {
        calcDelivery();
        if (draft) {
//            // Get the modal
//            var modal = document.getElementById('myModal');
//            modal.style.display = "block";
//
//            // Get the <span> element that closes the modal
//            var span = document.getElementsByClassName("close")[0];
//
////            var btn = document.getElementById("save_draft_modal");
////            btn.onclick = function () {
                execute_url("delivery-post.php?operation=check_delivery&order_id=" + <?php print $order_id; ?>, doAddDraft);
//            }
////            // When the user clicks on <span> (x), close the modal
////            span.onclick = function () {
////                modal.style.display = "none";
////            }
////
////            // When the user clicks anywhere outside of the modal, close it
////            window.onclick = function (event) {
////                if (event.target == modal) {
////                    modal.style.display = "none";
////                }
////            }            //
        }
        else
            execute_url("delivery-post.php?operation=check_delivery&order_id=" + <?php print $order_id; ?>, doAdd);
    }

    function doAddDraft(xmlhttp) {
	    <?php
	    if ( isset( $id ) ) { // Was new when open
		    print 'if (xmlhttp.response != ' . $id . ') { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
	    } else { // Was before open
		    print 'if (xmlhttp.response != "none") { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
	    }
	    ?>

        do_add(1);
    }

    function doAdd(xmlhttp) {
	    <?php
	    if ( isset( $id ) ) { // Was new when open
		    print 'if (xmlhttp.response != ' . $id . ') { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
	    } else { // Was before open
		    print 'if (xmlhttp.response != "none") { alert ("תעודה נשמרה במקום אחר. יש לסגור ולפתוח מחדש"); return; }';
	    }
	    ?>
        do_add(0);
    }

    function do_add(draft) {
        document.getElementById('btn_add').disabled = true;
	    <?php if ( isset( $order_id ) ) print "var order_id = " . $order_id . ";" ?>

        var table = document.getElementById('del_table');
        var lines = table.rows.length;
        var total = table.rows[table.rows.length - 1].cells[line_total_id].firstChild.nodeValue;
        var total_vat = table.rows[table.rows.length - 2].cells[line_total_id].firstChild.nodeValue;
        var logging = document.getElementById('logging');
        var line_number = 0;
        var is_edit = false;

	    <?php if ( $edit ) {  print "is_edit = true;"; } ?>

        // Enter delivery note to db.
        var request = "delivery-post.php?operation=add_header&order_id=" + order_id
            + "&total=" + total
            + "&vat=" + total_vat;

	    <?php if ( $edit ) {
	    print "request = request + \"&edit&delivery_id=" . $id . "\"";
    } ?>

        var delivery_id = 0;
        var saved_lines = 0;
        var fee = 0;
        var i;

        // Check number of lines in the delivery
        fee = get_value(document.getElementById("del_del"));
        for (i = 1; i < lines - 3; i++) {
            var prfx = table.rows[i].cells[0].id.substr(4);
            if (prfx === "")
                prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);

            var quantity = get_value(document.getElementById("deq_" + prfx));
            if (quantity > 0 || quantity < 0) saved_lines++;
            var prod_name = get_value(document.getElementById("nam_" + prfx));
            if (prod_name === "דמי משלוח"
                || prod_name === "משלוח") fee = get_value(document.getElementById("del_" + prfx))

            // var product = get_value(table.rows[i].cells[product_name_id].firstChild);
            // if (product == "דמי משלוח") fee = get_value(table.rows[i].cells[line_total_id].firstChild);
        }
        request = request + "&lines=" + saved_lines;
        request = request + "&fee=" + fee;
        if (draft) {
            request += "&draft";
            var reason = ""; // get_select_text("draft_reason");
            // alert(reason);

            request += "&reason=" + encodeURI(reason);
        }

        // Call the server to save the delivery
        server_header = new XMLHttpRequest();
        server_header.onreadystatechange = function () {
            // Wait to get delivery id.
            // 2) Save the lines.
            if (server_header.readyState == 4 && server_header.status == 200)  // Request finished
            {
                delivery_id = server_header.responseText.trim();
                logging.value += "תעודת משלוח מס " + delivery_id + "נשמרת " + "..";

                server_lines = new XMLHttpRequest();

                var line_request = "delivery-post.php?operation=add_lines&delivery_id=" + delivery_id;
                if (is_edit) line_request = line_request + "&edit";
                var line_args = new Array();

                // logging.value += response_text;
                // Enter delivery lines to db.
                for (i = 1; i < lines - 3; i++) {
                    var prfx = table.rows[i].cells[0].id.substr(4);
                    if (prfx === "")
                        prfx = table.rows[i].cells[0].firstElementChild.id.substr(4);
                    var prod_id;
                    var prod_name;
                    let line_type = get_line_type(i);
                    let part_of_basket = 0;

                    //if (parseInt(prfx) > 0) { // Regular line
                    prod_id = get_value(document.getElementById("pid_" + prfx));
                    prod_name = get_value(document.getElementById("nam_" + prfx));
//                    } else {
//                        // Special line:
//                        if (prfx === "dis") {
//                            prod_id = 0;
//                            prod_name = "הנחת כמות";
//                        }
//                    }
                    if (!prod_id && (prod_name.indexOf(")") > 0)) {
                        prod_id = prod_name.substr(0, prod_name.indexOf(")"));
                        prod_name = prod_name.substr(prod_name.indexOf(")"));
                    }
                    if ((prod_id != -1) && !(prod_id > 0)) prod_id = 0; // New or unknown

                    if (prod_name.substr(0, 6) === "===&gt")
                        part_of_basket = 1;
                    else
                        part_of_basket = 0;

                    if (prod_name.length > 1) prod_name = prod_name.replace(/['"()%,]/g, "").substr(0, 40);

                    var quantity = get_value(document.getElementById("deq_" + prfx));
                    if (quantity === "") quantity = 0;

                    var quantity_ordered = get_value(document.getElementById("orq_" + prfx));
                    if (quantity_ordered === "") quantity_ordered = 0;

                    var unit_ordered = get_value(document.getElementById("oru_" + prfx));
                    if (unit_ordered.length < 1) unit_ordered = 0;

                    var price = get_value(document.getElementById("prc_" + prfx));
                    var vat = get_value(document.getElementById("lvt_" + prfx));
                    // var prod_name = get_value(table.rows[i].cells[product_name_id].firstChild);
                    var line_total = get_value(document.getElementById("del_" + prfx));
//                    if (table.rows[i].cells[0].children.length === 1) { // delivery lines or new line
//                        prod_id = 0;
//                        prod_name = get_value(table.rows[i].cells[0]);
//                        quantity = get_value(table.rows[i].cells[3]);
//                        quantity_ordered = 0;
//                        price = get_value(table.rows[i].cells[4]);
//                        vat = get_value(table.rows[i].cells[6]);
//                        line_total = get_value(table.rows[i].cells[7]);
//                    } else {
//                        line_total = get_value(table.rows[i].cells[line_total_id].firstChild);
//                    }

                    if (prod_id === -1 || prod_id > 0 || line_total > 0 || line_total < 0 || line_type === "bsk" || line_type === "dis") { // Line to be saved.
                        if (prod_id > 0 || prod_id == -1) // -1 is basket discount.
                            push(line_args, prod_id);
                        else
                            push(line_args, encodeURIComponent(prod_name));
                        push(line_args, quantity);
                        push(line_args, quantity_ordered);
                        push(line_args, unit_ordered);
                        push(line_args, vat);
                        push(line_args, price);
                        push(line_args, line_total);
                        push(line_args, part_of_basket);
                    }
                }
                server_lines = new XMLHttpRequest();
                server_lines.onreadystatechange = function () {
                    if (server_lines.readyState === 4 && server_lines.status === 200) {  // Request finished
                        logging.value += "הסתיים.\n";
//                    3) Send the delivery notes to the client
                        // Now call the server, to send the delivery. It waits few seconds for the save lines to finish
                        if (!draft) {
                            var xmlhttp_send = new XMLHttpRequest();
                            var request = "send-delivery.php?del_id=" + delivery_id;
			                <?php if ( $edit ) {
			                print 'request = request + "&edit";     ';
		                } ?>
                            logging.value += "תעודה נשלחת ללקוח";
                            xmlhttp_send.open("GET", request);
                            xmlhttp_send.send();
	                        <?php
	                        $d = new Fresh_Delivery( $id );
//	                        if ( strstr( $d->getPrintDeliveryOption(), "P" ) ) {
//		                        //   print 'logging.style.display="false";';
//		                        print 'location.replace("get-delivery.php?id=" + delivery_id + "&print"); return;';
//		                        // print 'logging.style.display="true";';
//	                        }

	                        ?>
                        }
                        location.replace(document.referrer);
                    }

                }

                line_request = line_request + "&lines=" + line_args.join();
                server_lines.open("GET", line_request, true);
                server_lines.send();
            }
        }

//	1) Send the header.
        server_header.open("GET", request, true);
        server_header.send();
    }

    function push(array, item) {
        if (item === null) {
            alert(null);
            item = '';
        }

        array.push(item);
    }

    function printDeliveryNotes() {
        document.getElementById('btn_calc').style.visibility = "hidden";
        document.getElementById('btn_print').style.visibility = "hidden";
        // Get the html
        var txt = document.documentElement.innerHTML;

        // Download the html
        var a = document.getElementById("a");
        var file = new Blob(txt, 'text/html');
        a.href = URL.createObjectURL(file);
        a.download = 're.html';

//	download(txt, 'myfilename.html', 'text/html')
//	window.open('data:text/html;charset=utf-8,<html dir="rtl" lang="he">' + txt + '</html>');

        document.getElementById('btn_calc').style.visibility = "visible";
        document.getElementById('btn_print').style.visibility = "visible";

    }

    function get_line_type(i)
    {
        let v = get_value_by_name("typ_" + i);
        if (v) return v;
        return "prd";
    }
</script>
<style>
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0, 0, 0); /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
    }

    /* Modal Content/Box */
    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
    }

    /* The Close Button */
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>
<!-- The Modal -->
<div id="myModal" class="modal">

    <!-- Modal content -->
    <div class="modal-content">
        <span class="close">&times;</span>
        <p>יש לבחור את הסיבה לשמירת טיוטא.</p>
	    <?php
	    $select_options   = array();
	    $option           = array();
	    $option["id"]     = 1;
	    $option["reason"] = "אריזה חלקית";
	    array_push( $select_options, $option );

	    $option["id"] ++;
	    $option["reason"] = "התווספו פריטים ולא ידוע המחיר";
	    array_push( $select_options, $option );

	    $option["id"] ++;
	    $option["reason"] = "לא ידוע מחיר המשלוח";
	    array_push( $select_options, $option );

	    //	    print gui_select( "draft_reason", "reason",
	    //		    $select_options, "", "" );

	    //	    print gui_button( "save_draft_modal", "", "בצע" );
	    ?>

    </div>
</div>
<script>
    var modal = document.getElementById('myModal');

    // Get the button that opens the modal
    var btn = document.getElementById("btn_save_draft");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks on the button, open the modal
    if (btn) // Exists only on draft
        btn.onclick = function () {
            modal.style.display = "block";
        }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function () {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>