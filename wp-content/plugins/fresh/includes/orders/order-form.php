<?php
if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( FRESH_INCLUDES . "/core/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/imMulti-site.php" );
require_once( FRESH_INCLUDES . "/core/options.php" );
require_once( TOOLS_DIR . "/pricing.php" );
require_once( TOOLS_DIR . "/orders/form.php" );
require_once( TOOLS_DIR . "/orders/orders-common.php" );

$text    = isset( $_GET["text"] );
$fresh   = isset( $_GET["fresh"] );
$user_id = GetParam( "user_id", false, null );

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
		InfoUpdate( $key, "" );
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
<script type="text/javascript" src="/core/gui/client_tools.js"></script>

    <script>

        var tables = [<?php print get_form_tables(); ?>];

        function add_order() {
            let params = new Array();
            let prod_id;
            for (let i = 0; i < tables.length; i++) {
                let table = document.getElementById(tables[i]);
                for (let j = 1; j < table.rows.length; j++) {
                    let line = get_value_by_name(table.rows[j].cells[5].firstElementChild.id);
                    if (parseFloat(line) > 0) {
                        prod_id = table.rows[j].cells[4].firstElementChild.id.substr(4);
                        params.push(prod_id);
                        params.push(get_value_by_name(table.rows[j].cells[4].firstElementChild.id))
                    }
                }
            }
            let url = "/order-finish/?params=" + params;
            let phone = get_value_by_name("phone");
            let name = get_value_by_name("name");
            let email = get_value_by_name("email");
            let method = get_value_by_name("select_method");

            if (phone) url += "&phone=" + encodeURI(phone);
            if (name) url += "&name=" + encodeURI(name);
		    <?php if ( isset( $group ) ) {
		    print "url += \"&group=\" + encodeURI('" . $group . "');";
	    } ?>
		    <?php if ( isset( $user ) ) {
		    print "url += \"&user=\" + encodeURI('" . $user . "');";
        }
		    ?>
            if (email) url += "&email=" + encodeURI(email);
            else {
                alert("email must be supplied");
                return;
            }
            url += "&method=" + method;
            window.location.href = url;
        }

        function update_shipping() {
            var m = document.getElementById("select_method");
        }

        function update_email() {
            var email = get_value_by_name("email");

            var request = "/fresh/orders/orders-post.php?operation=check_email&email=" + email;

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

            request = "/fresh/orders/orders-post.php?operation=check_delivery&email=" + email;

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
                if (table.rows[i].cells[5]) {
                    var line = get_value_by_name(table.rows[i].cells[5].firstElementChild.id);
                    if (parseFloat(line) > 0)
                        total += parseFloat(line);
                }
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

$just_pricelist = false;
if ( $text ) {
	print header_text( true );
	print "מוצרים זמינים השבוע</br>";
	if ($user_id)
	{
		print Core_Html::gui_header(1, "מחירון ללקוח " . get_user_name($user_id));
		$just_pricelist = true;
	}

} else {
    if (! $just_pricelist){
        print Core_Html::gui_header(1, "פרטי המזמין:");
	    try {
		    print gui_table_args( array(
				    array( "כתובת המייל של המזמין:", gui_input( "email", "", array( "onchange=update_email()" ) ) ),
				    array( "שם הלקוח:", gui_label( "user_info", "" ) ),
				    array( "מועד המשלוח", gui_div( "delivery_info" ) ),
				    array( 'סה"כ הזמנה:', gui_label( "total", "0" ) )
			    )
		    );
	    } catch ( Exception $e ) {
	        MyLog( __FILE__ . ":" . __LINE__ . $e->getMessage());
	    }

	    print Core_Html::GuiButton( "btn_add_order_1", "add_order(0)", "הוסף הזמנה" );
    }
}
?>
<?php

print "<br/>";

$categs = info_get( "form_categs" );
$args = array();

if ($just_pricelist)
{
    $args["just_pricelist"] = true;
}

if ( $categs ) {
	foreach ( explode( ",", $categs ) as $categ ) {
		print show_category_by_id( $categ, false, $text, customer_type( $user_id ), false, null, $args );
	}
} else {
	print show_category_all( false, $text, $fresh, true, customer_type( $user_id ), null, $args );
}
//show_category( "מארזי כמות מוזלים", true, $text );

print "<br/>";

if ( ! $text and ! $just_pricelist) {
	print Core_Html::GuiButton( "btn_add_order_2", "add_order(0)", "הוסף הזמנה" );
}

?>
<script>
    document.getElementById("btn_add_order_1").disabled = true;
    document.getElementById("btn_add_order_2").disabled = true;

</script>