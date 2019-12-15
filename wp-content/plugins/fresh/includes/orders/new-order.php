<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 31/05/18
 * Time: 17:29
 */
require_once( FRESH_INCLUDES . "/niver/gui/inputs.php" );
require_once( "../account/gui.php" );

global $pos;

?>

    <script>
        function do_create_delivery_note(order_id) {
            var request = "pos-post.php?operation=create_delivery&order_id=" + order_id;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var response = xmlhttp.response;
                    var user_name = get_value_by_name("client_select");
                    var user_id = user_name.substr(0, user_name.indexOf(")"));

                    add_message(response);
                    del_id = response.substr(19, response.indexOf("נוצרה") - 20);

                    if (!get_value_by_name("chk_delivery"))
                        do_close_order(order_id);

                    if (get_value_by_name("chk_payment")) {
					<?php
					if ( $pos ) {
						print 'add_message("מופקת קבלה ");';
						print 'do_pay_delivery( del_id, user_id, order_id );';
					}
					?>
                    }
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function do_close_order(order_id) {
            var request = "pos-post.php?operation=close_order";
            request += "&order_id=" + order_id;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    var response = xmlhttp.response;
                    add_message(response);
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function show_create_order() {
            var new_order = document.getElementById("new_order");
            new_order.style.display = 'block';
            add_line();
            document.getElementById("client_select").focus();
        }

        function calcOrder() {
            var item_table = document.getElementById("order_items");
            var total = 0;
            for (var i = 1; i < item_table.rows.length; i++) {
                var p = get_value_by_name("prc_" + i);
                var q = get_value_by_name("qua_" + i);
                var line_total = Math.round(p * q * 100) / 100;
                document.getElementById("lto_" + i).innerHTML = line_total;
                total += line_total;
            }
            document.getElementById("total").innerHTML = total;
        }

        function add_line() {
            var item_table = document.getElementById("order_items");
            var line_idx = item_table.rows.length;
            if (line_idx > 1 && item_table.rows[line_idx - 1].cells[0].firstElementChild.value.length === 0) return; // Empty line
            var new_row = item_table.insertRow(-1);
            var product = new_row.insertCell(0);

            //product.innerHTML = "<input id=\"nam_" + line_idx + "\" list=\"items\" onchange=\"select_product(" + line_idx + ")\">";
            //var select = "<?php //print gui_select_product( "XX", "products", "onfocusout=\\\"select_product(YYY)\\\"" ); ?>//";
            // product.innerHTML = select.replace("XX", "nam_" + line_idx).replace("YYY", line_idx);
            product.innerHTML = '<input id="nam_' + line_idx + '" list="product_list"  onkeyup="update_list(\'products\', this)">';
            var quantity = new_row.insertCell(1);
			<?php
			//        if ($pos) $event = 'onkeyup=\"quantity_entered(" + line_idx + ")\"'; else
			$event = 'onkeypress=\"quantity_entered(" + line_idx + ")\" onchange=\"getPrice(" + line_idx + ")\"';
			?>
            quantity.innerHTML = "<input id = \"qua_" + line_idx + "\" <?php print $event; ?>>";
            if (! <?php print $pos ? 1 : 0; ?>) {
                var units = new_row.insertCell(2);
                units.innerHTML = "<input id=\"uni_" + line_idx + "\" list=\"units\", onkeypress=\"add_line(" + line_idx + ")\">";
            }
            var unit_price = new_row.insertCell(-1);
            unit_price.innerHTML = "<input id=\"prc_" + line_idx + "\" \">";
            var line_total = new_row.insertCell(-1);
            line_total.innerHTML = "<label id=\"lto_" + line_idx + "\" \">";
            product.focus();
        }
        function client_changed() {
            // Owner and other client types should create order in other place.
            let user_id = get_value_by_name("client_select");

            if (! (user_id > 0)){
                document.getElementById("rate").innerHTML = "";
                document.getElementById("client_address").innerHTML = "";
            }

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var rate = xmlhttp.response.split('\n')[0];
                    var address = xmlhttp.response.split('\n')[1];

                    document.getElementById("rate").innerHTML = <?php if ( $pos ) {
						print '"pos"';
					} else {
						print "rate";
					} print ";" ?> // POS - all clients get POS rate.


                        document.getElementById("client_address").innerHTML = address;
                }
            }
            var request = "orders-post.php?operation=get_client_info&id=" + user_id;
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

        function select_unit(my_row) {
            if (event.which === 13) {
                var objs = document.getElementById("uni_" + (my_row + 1));
                if (objs) objs.focus();
            }
        }

        function quantity_entered(my_row) {
            getPrice(my_row);
            if (event.which === 13) {
                var table = document.getElementById("order_items");
                if (table.rows.length == (my_row + 1)) {
                    add_line();
                }
                var next = 'nam_' + (my_row + 1);
                var objs = document.getElementById(next);
                if (objs) objs.focus();
            }
        }
        function clear_message() {
            var log = document.getElementById("log");

            log.innerHTML = "";

        }

        // pay = 0; Just create the order.
        // pay = 1; Move control back to order-pos - create delivery and invoice
        // pay = 2; Create order and delivery note.
        function add_order() {
            clear_message();

            // Check info
            document.getElementById('add_order').disabled = true;
            var user_id = get_value(document.getElementById("client_select"));
            if (!(user_id > 0)) {
                add_message("יש לבחור לקוח, כולל מספר מזהה מהרשימה");
                document.getElementById('add_order').disabled = false;
                return false;
            }

            if (get_value_by_name("chk_payment")) {
                var cash = get_value_by_name("cash");
                var credit = get_value_by_name("credit");
                var bank = get_value_by_name("bank");
                var check = get_value_by_name("check");

                if (!(cash + credit + bank + check) > 0) {
                    add_message("יש לרשום סכום תשלום");
                    document.getElementById('add_order').disabled = false;
                    return;
                }

                if (!get_value_by_name("chk_packed")) {
                    add_message("ניתן לשלם על משלוח ארוז בלבד");
                    document.getElementById('add_order').disabled = false;
                    return;
                }
            }

            var prods = [];
            var quantities = [];
            var comment = get_value_by_name("order_excerpt");
            var units = [];

            var item_table = document.getElementById("order_items");
            var line_number = 0;

            for (var i = 1; i < item_table.rows.length; i++) {
                var prod_id = get_value(document.getElementById("nam_" + i));
                // var prod_id = prod.substr(0, prod.indexOf(")"));
                var q = get_value(document.getElementById("qua_" + i));
                if (q > 0 && !(prod_id > 0)) {
                    alert("אנא בחר מוצר מתוך הרשימה " + prod + " למחיקה, אפס את הכמות");
                    document.getElementById('add_order').disabled = false;
                    return false;
                }
                var u = get_value(document.getElementById("uni_" + i));

                if (q > 0 || u > 0) {
                    prods.push(prod_id);
                    quantities.push(q);
                    units.push(u);
                    line_number++;
                }

                // ids.push(get_value(item_table.rows[i].cells[0].innerHTML));
            }
            if (line_number === 0) {
                add_message("יש לבחור מוצרים, כולל כמויות");
                document.getElementById('add_order').disabled = false;

                return false;
            }

            var mission_id = <?php if ( $pos ) {
			print 'get_value(document.getElementById("mis_new"));';
		} else {
			print 0;
		} ?>

            var request = "../orders/orders-post.php?operation=create_order" +
                "&user_id=" + user_id +
                "&prods=" + prods.join() +
                "&quantities=" + quantities.join() +
                "&comments=" + encodeURI(comment) +
                "&units=" + units.join() +
                "&mission_id=" + mission_id;

            request += '&type=' + document.getElementById("rate").innerHTML;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {  // Request finished
                    var message = "";
                    var order_id = 0;
                    if (xmlhttp.responseText.includes("בהצלחה")) {
                        // We have new order. Now handle it.
                        message = xmlhttp.response;
                        add_message(xmlhttp.response + "<br/>");
                        order_id = message.substr(6, message.indexOf("נקלטה") - 7);

                        if (get_value_by_name("chk_packed")) {
                            add_message("נוצרת תעודת משלוח. ");
                            del_id = do_create_delivery_note(order_id);
                        }
                    } else {
                        add_message(xmlhttp.responseText);
                        document.getElementById('add_order').disabled = false;
                        return false;
                    }
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            return true;
        }
        function select_product(my_row) {
//            if (event.which === 13) {
                var objs = document.getElementById("qua_" + (my_row));
                if (objs) objs.focus();
//            }
            getPrice(my_row);
        }

        function getPrice(my_row) {
            var product_id = get_value(document.getElementById("nam_" + my_row))
            // if (prod.length = 0) return;

            // product_id = prod.substr(0, prod.indexOf(")"));
            if (!(product_id > 0)) {
                alert("לא נבחר מוצר");
                return;
            }
            var q = get_value(document.getElementById("qua_" + my_row));

            var request = "../delivery/delivery-post.php?operation=get_price&id=" + product_id;
			<?php // if ($pos) print "request = request + '&type=pos';";
			// else
			print "request = request + '&type=' + document.getElementById(\"rate\").innerHTML;"; ?>
////            if ($type = get_client_type())
//	    	        print 'request = request + "&type=" + \'' . $type . '\';';
//	    ?>
            if (q > 0) request = request + '&quantity=' + q;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get delivery id.
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    var price = xmlhttp.response;

                    if (price > 0) {
                        document.getElementById("prc_" + my_row).value = price;
                        // document.getElementById("qua_" + my_row).focus();
                    }
                    calcOrder();
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();
        }

    </script>

    <datalist id="product_list"></datalist>

    <label id="log"></label>

    <div id="new_order" style="display: none">
		<?php
		print gui_header( 1, "New Order" );

		$header = array(
			array( gui_header( 2, "Select client" ) ),
			array( gui_select_client( "client_select", "", array("events" => "onchange=\"client_changed()\"") ) )
		);

		if ( ! $pos ) {
			array_push( $header[0], gui_header( 2, "Select delivery" ) );
			array_push( $header[1], gui_select_mission( "mis_new" ) );
		}
		array_push( $header[0], gui_header( 2, "הערה/שם לקוח" ) );
		array_push( $header[1], GuiInput( "order_excerpt", "" ) );
		print gui_table_args( $header );

		print gui_label( "rate", $pos ? "pos" : "" );

		print gui_header( 2, "בחר מוצרים" );

		$show_fields = array( true, true, ! $pos, true, true );
		$sum         = array();
		print gui_table_args( array( array( "פריט", "כמות", "קג או יח", "מחיר ליחיד או לק\"ג", "סה\"כ" ) ),
			"order_items", true, true, $sum, null, null, $show_fields );

		print "<br/>";
		print gui_button( "add_line", "add_line()", "הוסף שורה" );
		print "<br/>";

		print gui_label( "", 'סה"כ הזמנה ' );
		print gui_label( "total", "0" );
		print "<br/><br/>";

		print gui_checkbox( "chk_delivery", "", ! $pos );
		print "משלוח ";
		print gui_label( "client_address", "" );
		printbr();

		if ( $pos ) {
			print gui_checkbox( "chk_payment", "", 0 );
			print "תשלום";
			printbr();
		}
		print gui_checkbox( "chk_packed", "", $pos );
		print "ארוז";
		printbr();

		//	if (! $pos)
		//	    print gui_button( "add_order", "add_order(0)", "הוסף הזמנה" );

		print gui_button( "add_order", "add_order()", "הוסף עסקה" );
		?>

    </div>

<?php

?>