<?php
if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . "/im_tools.php" );
require_once( ROOT_DIR . "/niver/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( TOOLS_DIR . "/options.php" );
require_once( TOOLS_DIR . "/pricing.php" );
require_once( TOOLS_DIR . "/orders/form.php" );
require_once( TOOLS_DIR . "/orders/orders-common.php" );
// print header_text();

$text    = isset( $_GET["text"] );
$fresh   = isset( $_GET["fresh"] );
$user_id = get_param( "user_id" );

// print "text = " . $text;

function show_category( $term_name, $sale = false, $text = false ) {
	$the_term = get_term_by( 'name', $term_name, 'product_cat' );
	if ( $the_term ) {
		print show_category_by_id( $the_term->term_id, $sale, $text );
	}
}

function get_form_tables() {
	$key         = "form_categs";
	$categs_info = info_get( $key );
	if ( ! $categs_info ) {
		info_update( $key, "" );
		$categs_info = "";
	}
	$categs = explode( ",", $categs_info );
	$result = "";
	foreach ( $categs as $categ ) {
		$result .= "\"table_" . $categ . "\", ";
	}

	return rtrim( $result, ", " );
}
?>
<script type="text/javascript" src="/niver/gui/client_tools.js"></script>

    <script>

        var tables = [<?php print get_form_tables(); ?>];

        function add_order() {
            var params = new Array();
            var prod_id;
            for (var i = 0; i < tables.length; i++) {
                var table = document.getElementById(tables[i]);
                for (var j = 1; j < table.rows.length; j++) {
                    var line = get_value_by_name(table.rows[j].cells[5].firstElementChild.id);
                    if (parseFloat(line) > 0) {
                        prod_id = table.rows[j].cells[4].firstElementChild.id.substr(4);
                        params.push(prod_id);
                        params.push(get_value_by_name(table.rows[j].cells[4].firstElementChild.id))
                    }
                }
            }
            var url = "/order-finish/?params=" + params;

//                "<?php //print ImMultiSite::LocalSiteTools();?>///orders/order-form-post.php?operation=create_order" +
//                "&params=" + params;

            var phone = get_value_by_name("phone");
            var name = get_value_by_name("name");
            var email = get_value_by_name("email");
            var method = get_value_by_name("select_method");

            if (phone.length > 2) url += "&phone=" + encodeURI(phone);
            if (name.length > 2) url += "&name=" + encodeURI(name);
		    <?php if ( isset( $group ) ) {
		    print "url += \"&group=\" + encodeURI('" . $group . "');";
	    } ?>
		    <?php if ( isset( $user ) ) {
		    print "url += \"&user=\" + encodeURI('" . $user . "');";
        }
		    ?>
            if (email.length > 4) url += "&email=" + encodeURI(email);
            url += "&method=" + method;
            window.location.href = url;
        }

        function update_shipping() {
            var m = document.getElementById("select_method");
        }

        function update_email() {
            var email = get_value_by_name("email");

            var request = "/tools/orders/orders-post.php?operation=check_email&email=" + email;

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200)  // Request finished
                {
                    if (xmlhttp.response === "u") {
                        alert("משתמש לא ידוע. בדוק את כתובת המייל שלך");
                    } else {
                        document.getElementById("user_info").innerHTML = xmlhttp.response;
                    }
                }
            }
            xmlhttp.open("GET", request, true);
            xmlhttp.send();

            request = "/tools/orders/orders-post.php?operation=check_delivery&email=" + email;

            xmlhttp1 = new XMLHttpRequest();
            xmlhttp1.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp1.readyState == 4 && xmlhttp1.status == 200)  // Request finished
                {
                    document.getElementById("delivery_info").innerHTML = xmlhttp1.response;
                }
            }
            xmlhttp1.open("GET", request, true);
            xmlhttp1.send();

        }
        function update() {
            var total = update_total();
            var email = get_value_by_name("email");
            var email_valid = email.length > 5;
            var min = <?php if ( isset( $min ) ) {
			    print $min;
		    } else {
			    print 80;
		    } ?>;
            var dis = !((total > min) && email_valid);
            document.getElementById("btn_add_order_1").disabled = dis;
            document.getElementById("btn_add_order_2").disabled = dis;

        }
        function update_total() {
            var total = 0;

            for (var i = 0; i < tables.length; i++)
                total += table_total(tables[i]);

            document.getElementById("total").innerHTML = Math.round(total * 100) / 100;

            return total;

        }
        function table_total(table_name) {
            // alert(table_name);
            var table = document.getElementById(table_name);
            var total = 0;

            for (var i = 1; i < table.rows.length; i++) {
                var line = get_value_by_name(table.rows[i].cells[5].firstElementChild.id);
                if (parseFloat(line) > 0)
                    total += parseFloat(line);
            }

            return total;
        }
        function calc_line(elm) {
            var id = elm.id;
            var prod_id = id.substr(4);
            var q = get_value_by_name("qua_" + prod_id);
            var p;
            if (q >= 8) {
                p = get_value_by_name("vpr_" + prod_id);
            } else {
                p = get_value_by_name("prc_" + prod_id);
            }
            document.getElementById("tot_" + prod_id).innerHTML = String(Math.round(q * p * 100) / 100);
            var next_row = elm.parentElement.parentElement.nextElementSibling;
            if (next_row) {
                var input = next_row.childNodes[4].children[0];
                if (input) {
                    input.focus();
                    input.select();
                }
            }
            update();
        }
    </script>


<?php
// print gui_header(1, "פרוטי אקספרס");

if ( $text ) {
	print header_text( true );
	print "מוצרים זמינים השבוע</br>";
} else {
	print gui_header(1, "פרטי המזמין:");
	print gui_table(array(
	        array("כתובת המייל של המזמין:", gui_input( "email", "", array( "onchange=update_email()" ))),
	    array("שם הלקוח:", gui_label( "user_info", "" )),
		array("מועד המשלוח", gui_div("delivery_info")),
		array('סה"כ הזמנה:', gui_label("total", "0" )) )
    );

	print gui_button( "btn_add_order_1", "add_order(0)", "הוסף הזמנה" );

//	print gui_label("disable_reason", "(יש להזמין את כתובת המייל. מינימום הזמנה " . (isset($min) ? $min : 80) . " ש\"ח, לא כולל דמי משלוח");
}
?>
<?php

print "<br/>";

//print "לפניכם מארזי כמות במחירים מוזלים. פירות ממשק נהרי, יש להזמין עד יום שישי, ההספקה בשבוע העוקב (איסוף או משלוח). בננות סוטו - להזמין עד יום ראשון בערב.";

$categs = info_get( "form_categs" );
if ( $categs ) {
	foreach ( explode( ",", $categs ) as $categ ) {
		print show_category_by_id( $categ, false, $text, customer_type( $user_id ) );
	}
} else {
	print show_category_all( false, $text, $fresh, true, customer_type( $user_id ) );
}
//show_category( "מארזי כמות מוזלים", true, $text );

print "<br/>";

//show_category( "פירות אורגניים", false, $text );
//show_category( "פירות לא אורגניים", false, $text );
//show_category( "ירקות אורגניים", false, $text );
//show_category( "עלים אורגניים", false, $text );
//show_category( "פטריות אורגניות", false, $text );
//show_category( "זרעי מאכל אורגניים", false, $text );
//show_category( "פירות יבשים אורגניים", false, $text );
//show_category( "צמחי מרפא אורגניים", false, $text );
//show_category( "נבטים אורגניים", false, $text);

if ( ! $text ) {
	print gui_button( "btn_add_order_2", "add_order(0)", "הוסף הזמנה" );
}

?>
<script>
    document.getElementById("btn_add_order_1").disabled = true;
    document.getElementById("btn_add_order_2").disabled = true;

</script>