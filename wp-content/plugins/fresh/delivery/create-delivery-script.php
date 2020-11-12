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

    function getPrice(my_row, user_id) {
        // var product_info = get_value(document.getElementById("nam_" + my_row));
        // if (!product_info.indexOf(")")) return;
        var product_id = get_value_by_name("nam_" + my_row); // product_info.substr(0, product_info.indexOf(")"));
        var request = "delivery-post.php?operation=get_price_vat&id=" + product_id; //encodeURI(product_name);

        if (typeof(user_id) != "undefined") request += "&user_id=" +  user_id;

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

    function leaveQuantityFocus()
    {
        let current = event.object;
        current.value = Math.round(current.value * 10) / 10;
        calcDelivery();
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
