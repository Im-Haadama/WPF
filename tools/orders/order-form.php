<?php

if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . "/im_tools.php" );
require_once( ROOT_DIR . "/agla/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/multi-site.php" );
require_once( TOOLS_DIR . "/options.php" );
require_once( TOOLS_DIR . "/pricing.php" );
// print header_text();

$text = isset( $_GET["text"] );

function show_category( $term_name, $sale = false, $text = false ) {
	$the_term = get_term_by( 'name', $term_name, 'product_cat' );
	if ( $the_term ) {
		show_category_by_id( $the_term->term_id, $sale, $text );
	}
}

function show_category_by_id( $term_id, $sale = false, $text = false ) {
	$img_size = 40;

	$the_term = get_term( $term_id );

	print gui_header( 2, $the_term->name );

	if ( $sale ) {
		$table = array( array( "", "מוצר", "מחיר מוזל", "מחיר רגיל", "כמות", "סה\"כ" ) );
	} else {
		$table = array( array( "", "מוצר", "מחיר", gui_link( "מחיר לכמות", "", "" ), "כמות", "סה\"כ" ) );
	}

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 1000,
// 'product_cat'    => $category,
		'orderby'        => 'name',
		'order'          => 'ASC'
	);
	$loop = new WP_Query( $args );
	while ( $loop->have_posts() ) {
		$loop->the_post();
		$line = array();
		global $product;
		if ( ! $product->get_regular_price() ) {
			continue;
		}
		$prod_id = $loop->post->ID;
		$terms   = get_the_terms( $prod_id, 'product_cat' );
// print "<br/>" . $prod_id . " "; print get_product_name($prod_id);
		$found = false;
		foreach ( $terms as $term ) {
			if ( $term->term_id == $term_id ) {
				$found = true;
			}
		}
		if ( ! $found ) {
			continue;
		}
		if ( has_post_thumbnail( $prod_id ) ) {
			array_push( $line, get_the_post_thumbnail( $loop->post->ID, array( $img_size, $img_size ) ) );
		} else {
			array_push( $line, '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="' . $img_size . 'px" height="'
			                   . $img_size . 'px" />' );
		}
		array_push( $line, the_title( '', '', false ) );
		if ( $sale ) {
			array_push( $line, gui_lable( "prc_" . $prod_id, $product->get_sale_price() ) );
			array_push( $line, gui_lable( "vpr_" . $prod_id, $product->get_regular_price() ) );
		} else {
			array_push( $line, gui_lable( "prc_" . $prod_id, $product->get_price() ) );
			$q_price = get_price_by_type( $prod_id, null, 8 );
//			if ( is_numeric( get_buy_price( $prod_id ) ) ) {
//				$q_price = min( round( get_buy_price( $prod_id ) * 1.25 ), $product->get_price() );
//			}
			array_push( $line, gui_lable( "vpr_" . $prod_id, $q_price, 1 ) );
		}
		array_push( $line, gui_input( "qua_" . $prod_id, "0", array( 'onchange="calc_line(this)"' ) ) );
		array_push( $line, gui_lable( "tot_" . $prod_id, '' ) );
		array_push( $table, $line );
	}

	if ( $text ) {
		unset( $table[0] );
		foreach ( $table as $row ) {
			print $row[1] . " - " . $row [2] . ' ש"ח, ' . "<br/>";
		}
		print "<br/>";
	} else {
		print gui_table( $table, "table_" . $term_id );
	}
//echo get_permalink( $loop->post->ID )
//echo esc_attr( $loop->post->post_title ? $loop->post->post_title : $loop->post->ID );
//woocommerce_show_product_sale_flash( $post, $product );
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
    <script type="text/javascript" src="/agla/client_tools.js"></script>

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
            var url = "<?php print MultiSite::LocalSiteTools();?>/orders/order-form-post.php?operation=create_order" +
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
				print 100;
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
print "גרסה נסיונית<br/>";

if ( $text ) {
	print "מוצרים זמינים השבוע</br>";
} else {
//print "כתובת מייל של המזמין:";
//print gui_input("email", "", array("onchange=update()"));
	print "<br/>";
	print gui_table( array( 'סה"כ הזמנה:', gui_lable( "total", "0" ) ) );

	print gui_button( "btn_add_order_1", "add_order()", "הוסף הזמנה" );
}
?>
<br/>
כתובת מייל:
<br/>
<?php
if ( ! $text )
	print gui_input( "email", "", "oninput=update()" );

print "<br/>";

//print "לפניכם מארזי כמות במחירים מוזלים. פירות ממשק נהרי, יש להזמין עד יום שישי, ההספקה בשבוע העוקב (איסוף או משלוח). בננות סוטו - להזמין עד יום ראשון בערב.";

$categs = info_get( "form_categs" );
foreach ( explode( ",", $categs ) as $categ ) {
	show_category_by_id( $categ );
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

print gui_button( "btn_add_order_2", "add_order()", "הוסף הזמנה" );

?>
<script>
    document.getElementById("btn_add_order_1").disabled = true;
    document.getElementById("btn_add_order_2").disabled = true;

</script>