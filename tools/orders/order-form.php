<?php
if ( ! defined( "TOOLS_DIR" ) ) {
	define( 'TOOLS_DIR', dirname( dirname( __FILE__ ) ) );
}
require_once( TOOLS_DIR . "/im_tools.php" );
require_once( TOOLS_DIR . "/gui/inputs.php" );
require_once( TOOLS_DIR . "/multi-site/multi-site.php" );

// print header_text();

function show_category( $term_name ) {
	$the_term = get_term_by( 'name', $term_name, 'product_cat' );
	if ( $the_term ) {
		show_category_by_id( $the_term->term_id );
	}
}

function show_category_by_id( $term_id ) {
	$img_size = 40;

	$the_term = get_term( $term_id );

	print gui_header( 2, $the_term->name );

	$table = array( array( "", "מוצר", "מחיר", "מחיר לכמות", "כמות", "סה\"כ" ) );

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
		array_push( $line, gui_lable( "prc_" . $prod_id, $product->get_price() ) );
		array_push( $line, gui_lable( "vpr_" . $prod_id, min( round( get_buy_price( $prod_id ) * 1.4, 1 ), $product->get_price() ) ) );
		array_push( $line, gui_input( "qua_" . $prod_id, "0", array( 'onchange="calc_line(this)"' ) ) );
		array_push( $line, gui_lable( "tot_" . $prod_id, '' ) );
		array_push( $table, $line );
	}

	print gui_table( $table, "table_" . $term_id );
//echo get_permalink( $loop->post->ID )
//echo esc_attr( $loop->post->post_title ? $loop->post->post_title : $loop->post->ID );
//woocommerce_show_product_sale_flash( $post, $product );
}

?>
<html dir="rtl">
<header>
    <script type="text/javascript" src="http://fruity.co.il/tools/client_tools.js"></script>

    <script>
        var tables = ["table_18", "table_19", "table_62", "table_96", "table_125", "table_66", "table_106"];

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

            if (phone.length > 2) url += "&phone=" + encodeURI(phone);
            if (name.length > 2) url += "&name=" + encodeURI(name);
			<? if ( isset( $group ) ) {
			print "url += \"&group=\" + encodeURI('" . $group . "');";
		} ?>
			<? if ( isset( $user ) ) {
			print "url += \"&user=\" + encodeURI('" . $user . "');";
		} ?>
            window.location.href = url;
        }
        function update() {
            var total = update_total();
            var email = get_value_by_name("email");
            var min = <?php if ( isset( $min ) ) {
				print $min;
			} else {
				print 100;
			} ?>;
            document.getElementById("btn_add_order_1").disabled = !(total > min);
            document.getElementById("btn_add_order_2").disabled = !(total > min);
        }
        function update_total() {
            var total = 0;

            for (var i = 0; i < tables.length; i++)
                total += table_total(tables[i]);

            document.getElementById("total").innerHTML = Math.round(total * 100) / 100;

            return total;

        }
        function table_total(table_name) {
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
</header>

<body onload="update()">

<?php
// print gui_header(1, "פרוטי אקספרס");
print "גרסה נסיונית<br/>";

//print "כתובת מייל של המזמין:";
//print gui_input("email", "", array("onchange=update()"));
print "<br/>";
print gui_table( array( 'סה"כ הזמנה:', gui_lable( "total", "0" ) ) );

print gui_button( "btn_add_order_1", "add_order()", "הוסף הזמנה" );

show_category( "פירות אורגניים" );
show_category( "ירקות אורגניים" );
show_category( "עלים אורגניים" );
show_category( "פטריות אורגניות" );
show_category( "זרעי מאכל אורגניים" );
show_category( "פירות יבשים אורגניים" );
show_category( "צמחי מרפא אורגניים" );
show_category( "נבטים אורגניים" );

print gui_button( "btn_add_order_2", "add_order()", "הוסף הזמנה" );

?>
</body>
</html>
