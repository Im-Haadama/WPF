<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

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

$text  = isset( $_GET["text"] );
$fresh = isset( $_GET["fresh"] );

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
            var url = "<?php print ImMultiSite::LocalSiteTools();?>/orders/order-form-post.php?operation=create_order" +
                "&params=" + params;

            var phone = get_value_by_name("phone");
            var name = get_value_by_name("name");
            var email = get_value_by_name("email");

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
            window.location.href = url;
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
	print "כתובת מייל של המזמין:";
	print gui_input( "email", "", array( "onchange=update()" ) );
	print "<br/>";
	print gui_table( array( 'סה"כ הזמנה:', gui_label( "total", "0" ) ) );

	print gui_button( "btn_add_order_1", "add_order(0)", "הוסף הזמנה" );
}
?>
<?php

print "<br/>";

//print "לפניכם מארזי כמות במחירים מוזלים. פירות ממשק נהרי, יש להזמין עד יום שישי, ההספקה בשבוע העוקב (איסוף או משלוח). בננות סוטו - להזמין עד יום ראשון בערב.";

$categs = info_get( "form_categs" );
if ( $categs ) {
	foreach ( explode( ",", $categs ) as $categ ) {
		print show_category_by_id( $categ, false, $text );
	}
} else {
	print show_category_all( false, $text, $fresh, true );
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